<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Doctor') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$appointment_id = $_POST['appointment_id'] ?? 0;
$message = $_POST['message'] ?? '';
$send_email = $_POST['send_email'] ?? false;

if (!$appointment_id || !$message) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit();
}

// Get appointment and patient details
$query = $mysqli->query("
    SELECT a.*, p.first_name, p.last_name, p.email, u.email as patient_email,
           d.first_name as doctor_first, d.last_name as doctor_last
    FROM appointments a
    JOIN patients p ON a.patient_id = p.patient_id
    LEFT JOIN users u ON p.nid = u.nid
    JOIN users d ON a.doctor_id = d.user_id
    WHERE a.appointment_id = $appointment_id AND a.doctor_id = {$_SESSION['user_id']}
");

if ($query->num_rows == 0) {
    echo json_encode(['success' => false, 'error' => 'Appointment not found']);
    exit();
}

$apt = $query->fetch_assoc();
$patient_email = $apt['patient_email'] ?: $apt['email'];

// Insert notification into database
$subject = "Message from Dr. {$apt['doctor_first']} {$apt['doctor_last']}";
$stmt = $mysqli->prepare("INSERT INTO notifications (patient_id, subject, message, notification_type, status) VALUES (?, ?, ?, 'Doctor Message', 'Pending')");
$stmt->bind_param("iss", $apt['patient_id'], $subject, $message);
$stmt->execute();

$success = true;
$email_sent = false;

// Send email if requested and patient has email
if ($send_email && $patient_email) {
    require_once '../../config/email_config.php';
    
    $email_subject = "Message from Dr. {$apt['doctor_first']} {$apt['doctor_last']} - Maternal Care System";
    $email_body = "Dear {$apt['first_name']} {$apt['last_name']},\n\n";
    $email_body .= "You have received a message from Dr. {$apt['doctor_first']} {$apt['doctor_last']}:\n\n";
    $email_body .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    $email_body .= $message . "\n";
    $email_body .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    $email_body .= "Regarding your appointment:\n";
    $email_body .= "Date: " . date('M d, Y', strtotime($apt['appointment_date'])) . "\n";
    $email_body .= "Time: " . date('g:i A', strtotime($apt['appointment_time'])) . "\n";
    $email_body .= "Type: {$apt['appointment_type']}\n\n";
    $email_body .= "If you have any questions, please contact our clinic.\n\n";
    $email_body .= "Best regards,\nMaternal Care System";
    
    $email_sent = sendEmail($patient_email, $apt['first_name'] . ' ' . $apt['last_name'], $email_subject, $email_body);
}

echo json_encode([
    'success' => $success,
    'email_sent' => $email_sent,
    'patient_email' => $patient_email ? 'Available' : 'Not available'
]);
?>