<?php
require_once 'database.php';
require_once 'email_config.php';

function createNotification($user_id, $subject, $message, $type = 'System', $patient_id = null, $reference_id = null, $reference_type = null) {
    global $mysqli;
    
    $stmt = $mysqli->prepare("INSERT INTO notifications (user_id, patient_id, subject, message, notification_type, reference_id, reference_type, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')");
    $stmt->bind_param("iisssss", $user_id, $patient_id, $subject, $message, $type, $reference_id, $reference_type);
    return $stmt->execute();
}

function notifyAllUsers($subject, $message, $type = 'System', $exclude_roles = []) {
    global $mysqli;
    
    $exclude_sql = '';
    if (!empty($exclude_roles)) {
        $placeholders = str_repeat('?,', count($exclude_roles) - 1) . '?';
        $exclude_sql = "WHERE role NOT IN ($placeholders)";
    }
    
    $stmt = $mysqli->prepare("SELECT user_id FROM users WHERE is_active = 1 $exclude_sql");
    if (!empty($exclude_roles)) {
        $stmt->bind_param(str_repeat('s', count($exclude_roles)), ...$exclude_roles);
    }
    $stmt->execute();
    $users = $stmt->get_result();
    
    while ($user = $users->fetch_assoc()) {
        createNotification($user['user_id'], $subject, $message, $type);
    }
}

function notifyByRole($roles, $subject, $message, $type = 'System', $patient_id = null) {
    global $mysqli;
    
    if (!is_array($roles)) $roles = [$roles];
    
    $placeholders = str_repeat('?,', count($roles) - 1) . '?';
    $stmt = $mysqli->prepare("SELECT user_id FROM users WHERE role IN ($placeholders) AND is_active = 1");
    $stmt->bind_param(str_repeat('s', count($roles)), ...$roles);
    $stmt->execute();
    $users = $stmt->get_result();
    
    while ($user = $users->fetch_assoc()) {
        createNotification($user['user_id'], $subject, $message, $type, $patient_id);
    }
}

// Activity tracking functions
function trackPatientRegistration($patient_id, $registered_by) {
    global $mysqli;
    
    $patient = $mysqli->query("SELECT first_name, last_name FROM patients WHERE patient_id = $patient_id")->fetch_assoc();
    $name = $patient['first_name'] . ' ' . $patient['last_name'];
    
    // Notify medical staff
    notifyByRole(['Doctor', 'Nurse'], 
        "New Patient Registered", 
        "New patient $name has been registered in the system.", 
        'Patient Registration', 
        $patient_id
    );
    
    // Notify admin
    notifyByRole('Admin', 
        "Patient Registration", 
        "Patient $name was registered by staff member.", 
        'System Activity'
    );
}

function trackAppointmentActivity($appointment_id, $action, $user_id) {
    global $mysqli;
    
    $apt = $mysqli->query("
        SELECT a.*, p.first_name, p.last_name, d.first_name as doc_first, d.last_name as doc_last,
               u.user_id as patient_user_id
        FROM appointments a 
        JOIN patients p ON a.patient_id = p.patient_id 
        JOIN users d ON a.doctor_id = d.user_id
        LEFT JOIN users u ON p.nid = u.nid
        WHERE a.appointment_id = $appointment_id
    ")->fetch_assoc();
    
    if (!$apt) return;
    
    $patient_name = $apt['first_name'] . ' ' . $apt['last_name'];
    $doctor_name = "Dr. " . $apt['doc_first'] . ' ' . $apt['doc_last'];
    $date = date('M d, Y', strtotime($apt['appointment_date']));
    
    switch ($action) {
        case 'scheduled':
            // Notify doctor
            createNotification($apt['doctor_id'], 
                "New Appointment Scheduled", 
                "Appointment with $patient_name scheduled for $date", 
                'Appointment', $apt['patient_id'], $appointment_id, 'appointment'
            );
            
            // Notify patient if has account
            if ($apt['patient_user_id']) {
                createNotification($apt['patient_user_id'], 
                    "Appointment Scheduled", 
                    "Your appointment with $doctor_name is scheduled for $date", 
                    'Appointment', $apt['patient_id'], $appointment_id, 'appointment'
                );
            }
            break;
            
        case 'confirmed':
        case 'rejected':
        case 'rescheduled':
            // Notify patient
            if ($apt['patient_user_id']) {
                createNotification($apt['patient_user_id'], 
                    "Appointment " . ucfirst($action), 
                    "Your appointment with $doctor_name has been $action", 
                    'Appointment', $apt['patient_id'], $appointment_id, 'appointment'
                );
            }
            
            // Notify admin about rejections
            if ($action == 'rejected') {
                notifyByRole('Admin', 
                    "Appointment Rejected", 
                    "$doctor_name rejected appointment with $patient_name on $date", 
                    'System Activity'
                );
            }
            break;
    }
}

function trackPregnancyActivity($pregnancy_id, $action, $user_id) {
    global $mysqli;
    
    $preg = $mysqli->query("
        SELECT p.*, pt.first_name, pt.last_name, u.user_id as patient_user_id
        FROM pregnancies p 
        JOIN patients pt ON p.patient_id = pt.patient_id
        LEFT JOIN users u ON pt.nid = u.nid
        WHERE p.pregnancy_id = $pregnancy_id
    ")->fetch_assoc();
    
    if (!$preg) return;
    
    $patient_name = $preg['first_name'] . ' ' . $preg['last_name'];
    
    switch ($action) {
        case 'registered':
            // Notify medical staff
            notifyByRole(['Doctor', 'Nurse'], 
                "New Pregnancy Registered", 
                "Pregnancy registered for $patient_name (EDD: " . date('M d, Y', strtotime($preg['edd_date'])) . ")", 
                'Pregnancy', $preg['patient_id'], $pregnancy_id, 'pregnancy'
            );
            
            // Notify patient
            if ($preg['patient_user_id']) {
                createNotification($preg['patient_user_id'], 
                    "Pregnancy Registered", 
                    "Your pregnancy has been registered. EDD: " . date('M d, Y', strtotime($preg['edd_date'])), 
                    'Pregnancy', $preg['patient_id'], $pregnancy_id, 'pregnancy'
                );
            }
            break;
            
        case 'risk_updated':
            if ($preg['risk_level'] == 'High') {
                notifyByRole('Doctor', 
                    "High Risk Pregnancy Alert", 
                    "$patient_name pregnancy marked as HIGH RISK - requires immediate attention", 
                    'Clinical Alert', $preg['patient_id'], $pregnancy_id, 'pregnancy'
                );
            }
            break;
    }
}

function trackPaymentActivity($transaction_id, $action, $user_id) {
    global $mysqli;
    
    $payment = $mysqli->query("
        SELECT pt.*, p.first_name, p.last_name, u.user_id as patient_user_id
        FROM payment_transactions pt 
        JOIN patients p ON pt.patient_id = p.patient_id
        LEFT JOIN users u ON p.nid = u.nid
        WHERE pt.transaction_id = $transaction_id
    ")->fetch_assoc();
    
    if (!$payment) return;
    
    $patient_name = $payment['first_name'] . ' ' . $payment['last_name'];
    $amount = number_format($payment['amount'], 0);
    
    switch ($action) {
        case 'completed':
            // Notify patient
            if ($payment['patient_user_id']) {
                createNotification($payment['patient_user_id'], 
                    "Payment Received", 
                    "Payment of RWF $amount has been processed successfully", 
                    'Payment', $payment['patient_id'], $transaction_id, 'payment'
                );
            }
            
            // Notify admin
            notifyByRole('Admin', 
                "Payment Processed", 
                "Payment of RWF $amount received from $patient_name", 
                'Financial'
            );
            break;
            
        case 'overdue':
            // Notify patient
            if ($payment['patient_user_id']) {
                createNotification($payment['patient_user_id'], 
                    "Payment Overdue", 
                    "Your payment of RWF $amount is overdue. Please settle as soon as possible.", 
                    'Payment Alert', $payment['patient_id'], $transaction_id, 'payment'
                );
            }
            
            // Notify admin and receptionist
            notifyByRole(['Admin', 'Receptionist'], 
                "Overdue Payment Alert", 
                "$patient_name has overdue payment of RWF $amount", 
                'Financial Alert'
            );
            break;
    }
}

function trackANCVisit($visit_id, $user_id) {
    global $mysqli;
    
    $visit = $mysqli->query("
        SELECT av.*, p.first_name, p.last_name, pr.current_week, u.user_id as patient_user_id
        FROM anc_visits av 
        JOIN pregnancies pr ON av.pregnancy_id = pr.pregnancy_id
        JOIN patients p ON pr.patient_id = p.patient_id
        LEFT JOIN users u ON p.nid = u.nid
        WHERE av.visit_id = $visit_id
    ")->fetch_assoc();
    
    if (!$visit) return;
    
    $patient_name = $visit['first_name'] . ' ' . $visit['last_name'];
    
    // Notify patient
    if ($visit['patient_user_id']) {
        createNotification($visit['patient_user_id'], 
            "ANC Visit Recorded", 
            "Your ANC visit for week {$visit['current_week']} has been recorded", 
            'Medical Record', $visit['patient_id'], $visit_id, 'anc_visit'
        );
    }
    
    // Check for alerts
    if ($visit['blood_pressure_systolic'] > 140 || $visit['blood_pressure_diastolic'] > 90) {
        notifyByRole('Doctor', 
            "High Blood Pressure Alert", 
            "$patient_name has elevated BP: {$visit['blood_pressure_systolic']}/{$visit['blood_pressure_diastolic']}", 
            'Clinical Alert', $visit['patient_id'], $visit_id, 'anc_visit'
        );
    }
}

function trackDelivery($delivery_id, $user_id) {
    global $mysqli;
    
    $delivery = $mysqli->query("
        SELECT d.*, p.first_name, p.last_name, u.user_id as patient_user_id
        FROM deliveries d 
        JOIN patients p ON d.patient_id = p.patient_id
        LEFT JOIN users u ON p.nid = u.nid
        WHERE d.delivery_id = $delivery_id
    ")->fetch_assoc();
    
    if (!$delivery) return;
    
    $patient_name = $delivery['first_name'] . ' ' . $delivery['last_name'];
    
    // Notify all medical staff
    notifyByRole(['Doctor', 'Nurse'], 
        "Delivery Recorded", 
        "$patient_name delivery completed - {$delivery['delivery_type']}", 
        'Delivery', $delivery['patient_id'], $delivery_id, 'delivery'
    );
    
    // Notify patient
    if ($delivery['patient_user_id']) {
        createNotification($delivery['patient_user_id'], 
            "Delivery Record", 
            "Your delivery has been recorded. Congratulations!", 
            'Medical Record', $delivery['patient_id'], $delivery_id, 'delivery'
        );
    }
    
    // Notify admin
    notifyByRole('Admin', 
        "New Birth", 
        "Successful delivery recorded for $patient_name", 
        'System Activity'
    );
}

function trackUserActivity($user_id, $action, $target_user_id = null) {
    global $mysqli;
    
    $user = $mysqli->query("SELECT first_name, last_name, role FROM users WHERE user_id = $user_id")->fetch_assoc();
    $user_name = $user['first_name'] . ' ' . $user['last_name'];
    
    switch ($action) {
        case 'login':
            // Only notify admin for suspicious logins (multiple rapid logins, etc.)
            break;
            
        case 'created':
            if ($target_user_id) {
                $target = $mysqli->query("SELECT first_name, last_name, role FROM users WHERE user_id = $target_user_id")->fetch_assoc();
                notifyByRole('Admin', 
                    "New User Created", 
                    "New {$target['role']} account created: {$target['first_name']} {$target['last_name']}", 
                    'User Management'
                );
            }
            break;
    }
}
?>