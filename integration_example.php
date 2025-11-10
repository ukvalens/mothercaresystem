<?php
/*
INTEGRATION EXAMPLE - How to add notification hooks to existing pages

Add these lines to your existing PHP files to automatically trigger notifications:
*/

// 1. PATIENT REGISTRATION (add to patient registration page)
/*
require_once '../../config/activity_hooks.php';

// After successful patient registration
if ($stmt->execute()) {
    $patient_id = $mysqli->insert_id;
    
    // Trigger notification
    hook_patient_registered($patient_id, $_SESSION['user_id']);
    
    $success = "Patient registered successfully!";
}
*/

// 2. APPOINTMENT SCHEDULING (add to appointment booking page)
/*
require_once '../../config/activity_hooks.php';

// After appointment is scheduled
if ($stmt->execute()) {
    $appointment_id = $mysqli->insert_id;
    
    // Trigger notification
    hook_appointment_activity($appointment_id, 'scheduled', $_SESSION['user_id']);
    
    $success = "Appointment scheduled successfully!";
}
*/

// 3. APPOINTMENT STATUS CHANGES (add to appointment management page)
/*
require_once '../../config/activity_hooks.php';

// After appointment status change
if ($stmt->execute()) {
    // Trigger notification based on action
    hook_appointment_activity($appointment_id, $action, $_SESSION['user_id']); // $action = 'confirmed', 'rejected', 'rescheduled'
    
    $success = "Appointment $action successfully!";
}
*/

// 4. PREGNANCY REGISTRATION (add to pregnancy registration page)
/*
require_once '../../config/activity_hooks.php';

// After pregnancy registration
if ($stmt->execute()) {
    $pregnancy_id = $mysqli->insert_id;
    
    // Trigger notification
    hook_pregnancy_registered($pregnancy_id, $_SESSION['user_id']);
    
    $success = "Pregnancy registered successfully!";
}
*/

// 5. ANC VISIT RECORDING (add to ANC visit page)
/*
require_once '../../config/activity_hooks.php';

// After ANC visit is recorded
if ($stmt->execute()) {
    $visit_id = $mysqli->insert_id;
    
    // Trigger notification
    hook_anc_visit_recorded($visit_id, $_SESSION['user_id']);
    
    $success = "ANC visit recorded successfully!";
}
*/

// 6. PAYMENT PROCESSING (add to payment processing page)
/*
require_once '../../config/activity_hooks.php';

// After payment is processed
if ($stmt->execute()) {
    // Trigger notification
    hook_payment_activity($transaction_id, 'completed', $_SESSION['user_id']);
    
    $success = "Payment processed successfully!";
}
*/

// 7. MESSAGE SENDING (add to messaging system)
/*
require_once '../../config/activity_hooks.php';

// After message is sent
if ($stmt->execute()) {
    // Trigger notification
    hook_message_sent($_SESSION['user_id'], $recipient_id, $subject, $message);
    
    $success = "Message sent successfully!";
}
*/

// 8. DELIVERY RECORDING (add to delivery recording page)
/*
require_once '../../config/activity_hooks.php';

// After delivery is recorded
if ($stmt->execute()) {
    $delivery_id = $mysqli->insert_id;
    
    // Trigger notification
    hook_delivery_recorded($delivery_id, $_SESSION['user_id']);
    
    $success = "Delivery recorded successfully!";
}
*/

// 9. CLINICAL ALERTS (add anywhere clinical alerts are needed)
/*
require_once '../../config/activity_hooks.php';

// For clinical alerts (e.g., abnormal vital signs)
if ($blood_pressure_systolic > 140) {
    hook_clinical_alert(
        $patient_id,
        "High Blood Pressure Alert",
        "Patient has elevated BP: $blood_pressure_systolic/$blood_pressure_diastolic"
    );
}
*/

// 10. SYSTEM ALERTS (add for system-wide notifications)
/*
require_once '../../config/activity_hooks.php';

// For system alerts
hook_system_alert(
    "System Maintenance",
    "System will be under maintenance from 2 AM to 4 AM tomorrow"
);
*/

echo "
<h2>📋 Integration Instructions</h2>

<h3>🔧 How to Integrate Notifications</h3>
<ol>
    <li><strong>Include the hooks file:</strong><br>
        <code>require_once '../../config/activity_hooks.php';</code>
    </li>
    
    <li><strong>Add hook calls after successful operations:</strong><br>
        Use the appropriate hook function based on the activity
    </li>
    
    <li><strong>Test the integration:</strong><br>
        Perform the activity and check the notifications page
    </li>
</ol>

<h3>📱 Available Hook Functions</h3>
<ul>
    <li><code>hook_patient_registered(\$patient_id, \$user_id)</code></li>
    <li><code>hook_appointment_activity(\$appointment_id, \$action, \$user_id)</code></li>
    <li><code>hook_pregnancy_registered(\$pregnancy_id, \$user_id)</code></li>
    <li><code>hook_anc_visit_recorded(\$visit_id, \$user_id)</code></li>
    <li><code>hook_payment_activity(\$transaction_id, \$action, \$user_id)</code></li>
    <li><code>hook_delivery_recorded(\$delivery_id, \$user_id)</code></li>
    <li><code>hook_message_sent(\$from_id, \$to_id, \$subject, \$message)</code></li>
    <li><code>hook_clinical_alert(\$patient_id, \$title, \$message)</code></li>
    <li><code>hook_system_alert(\$title, \$message, \$roles)</code></li>
</ul>

<h3>🎯 Notification Types Generated</h3>
<ul>
    <li><strong>Appointment:</strong> Scheduling, confirmations, rejections, reminders</li>
    <li><strong>Clinical Alert:</strong> High BP, high-risk pregnancies, overdue visits</li>
    <li><strong>Payment:</strong> Completed payments, overdue alerts</li>
    <li><strong>Pregnancy:</strong> New registrations, risk level changes</li>
    <li><strong>Medical Record:</strong> ANC visits, delivery records</li>
    <li><strong>System Activity:</strong> User management, system alerts</li>
    <li><strong>Message:</strong> New messages between users</li>
</ul>

<div style='background: #d4edda; padding: 1rem; border-radius: 8px; margin: 2rem 0;'>
    <h4 style='color: #155724; margin: 0 0 0.5rem 0;'>✅ Benefits</h4>
    <ul style='margin: 0; color: #155724;'>
        <li>Real-time notifications for all system activities</li>
        <li>Role-based notification targeting</li>
        <li>Email integration for important alerts</li>
        <li>Comprehensive activity tracking</li>
        <li>Automated clinical and financial alerts</li>
    </ul>
</div>

<a href='app/views/notifications/index.php' style='background: #007cba; color: white; padding: 1rem 2rem; text-decoration: none; border-radius: 6px; display: inline-block; margin-top: 1rem;'>
    🔔 View Notifications System
</a>
";
?>