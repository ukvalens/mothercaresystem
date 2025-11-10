<?php
// Activity hooks to be included in relevant pages
require_once 'notification_system.php';

// Hook for patient registration
function hook_patient_registered($patient_id, $registered_by) {
    trackPatientRegistration($patient_id, $registered_by);
}

// Hook for appointment activities
function hook_appointment_activity($appointment_id, $action, $user_id) {
    trackAppointmentActivity($appointment_id, $action, $user_id);
}

// Hook for pregnancy registration
function hook_pregnancy_registered($pregnancy_id, $user_id) {
    trackPregnancyActivity($pregnancy_id, 'registered', $user_id);
}

// Hook for pregnancy risk update
function hook_pregnancy_risk_updated($pregnancy_id, $user_id) {
    trackPregnancyActivity($pregnancy_id, 'risk_updated', $user_id);
}

// Hook for payment activities
function hook_payment_activity($transaction_id, $action, $user_id) {
    trackPaymentActivity($transaction_id, $action, $user_id);
}

// Hook for ANC visit recorded
function hook_anc_visit_recorded($visit_id, $user_id) {
    trackANCVisit($visit_id, $user_id);
}

// Hook for delivery recorded
function hook_delivery_recorded($delivery_id, $user_id) {
    trackDelivery($delivery_id, $user_id);
}

// Hook for user activities
function hook_user_activity($user_id, $action, $target_user_id = null) {
    trackUserActivity($user_id, $action, $target_user_id);
}

// Hook for message sent
function hook_message_sent($from_user_id, $to_user_id, $subject, $message) {
    global $mysqli;
    
    $sender = $mysqli->query("SELECT CONCAT(first_name, ' ', last_name) as name FROM users WHERE user_id = $from_user_id")->fetch_assoc();
    
    createNotification(
        $to_user_id,
        "New Message: " . $subject,
        "You have received a message from " . $sender['name'],
        'Message',
        null,
        null,
        'message'
    );
}

// Hook for system alerts
function hook_system_alert($title, $message, $target_roles = ['Admin']) {
    notifyByRole($target_roles, $title, $message, 'System Alert');
}

// Hook for clinical alerts
function hook_clinical_alert($patient_id, $title, $message, $target_roles = ['Doctor', 'Nurse']) {
    notifyByRole($target_roles, $title, $message, 'Clinical Alert', $patient_id);
}

// Daily automated checks (to be run via cron or scheduled task)
function run_daily_checks() {
    global $mysqli;
    
    // Check for overdue payments
    $overdue_payments = $mysqli->query("
        SELECT pt.*, p.first_name, p.last_name
        FROM payment_transactions pt
        JOIN patients p ON pt.patient_id = p.patient_id
        WHERE pt.status = 'Pending' 
        AND pt.due_date < CURDATE() 
        AND pt.due_date > DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    ");
    
    while ($payment = $overdue_payments->fetch_assoc()) {
        trackPaymentActivity($payment['transaction_id'], 'overdue', 1);
    }
    
    // Check for upcoming appointments (next day)
    $upcoming_appointments = $mysqli->query("
        SELECT a.*, p.first_name, p.last_name, u.user_id as patient_user_id
        FROM appointments a
        JOIN patients p ON a.patient_id = p.patient_id
        LEFT JOIN users u ON p.nid = u.nid
        WHERE a.appointment_date = DATE_ADD(CURDATE(), INTERVAL 1 DAY)
        AND a.status = 'Confirmed'
    ");
    
    while ($apt = $upcoming_appointments->fetch_assoc()) {
        if ($apt['patient_user_id']) {
            createNotification(
                $apt['patient_user_id'],
                "Appointment Reminder",
                "You have an appointment tomorrow at " . date('g:i A', strtotime($apt['appointment_time'])),
                'Appointment Reminder',
                $apt['patient_id'],
                $apt['appointment_id'],
                'appointment'
            );
        }
    }
    
    // Check for high-risk pregnancies needing attention
    $high_risk_pregnancies = $mysqli->query("
        SELECT p.*, pt.first_name, pt.last_name,
               FLOOR(DATEDIFF(CURDATE(), p.lmp_date) / 7) as current_week
        FROM pregnancies p
        JOIN patients pt ON p.patient_id = pt.patient_id
        WHERE p.current_status = 'Active' 
        AND p.risk_level = 'High'
        AND NOT EXISTS (
            SELECT 1 FROM anc_visits av 
            WHERE av.pregnancy_id = p.pregnancy_id 
            AND av.visit_date > DATE_SUB(CURDATE(), INTERVAL 14 DAY)
        )
    ");
    
    while ($pregnancy = $high_risk_pregnancies->fetch_assoc()) {
        notifyByRole(
            'Doctor',
            "High Risk Pregnancy Alert",
            "High-risk patient {$pregnancy['first_name']} {$pregnancy['last_name']} (Week {$pregnancy['current_week']}) hasn't had a visit in 2+ weeks",
            'Clinical Alert',
            $pregnancy['patient_id']
        );
    }
}
?>