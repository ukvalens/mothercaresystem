<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Receptionist'])) {
    header('Location: ../auth/login.php');
    exit();
}

$selected_date = $_GET['date'] ?? date('Y-m-d');
$filename = "daily_report_" . $selected_date . ".csv";

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

// Write header
fputcsv($output, ['Daily Report for ' . date('F j, Y', strtotime($selected_date))]);
fputcsv($output, []);

// Statistics
fputcsv($output, ['SUMMARY']);
$result = $mysqli->query("SELECT COUNT(*) as count FROM appointments WHERE DATE(appointment_date) = '$selected_date'");
fputcsv($output, ['Appointments', $result->fetch_assoc()['count']]);

$result = $mysqli->query("SELECT COUNT(*) as count FROM patients WHERE DATE(created_at) = '$selected_date'");
fputcsv($output, ['New Patients', $result->fetch_assoc()['count']]);

$result = $mysqli->query("SELECT COUNT(*) as count FROM anc_visits WHERE DATE(visit_date) = '$selected_date'");
fputcsv($output, ['ANC Visits', $result->fetch_assoc()['count']]);

$result = $mysqli->query("SELECT COALESCE(SUM(amount), 0) as total FROM payment_transactions WHERE DATE(processed_at) = '$selected_date' AND status = 'Completed'");
fputcsv($output, ['Revenue', '$' . number_format($result->fetch_assoc()['total'], 2)]);

fputcsv($output, []);

// Appointments
fputcsv($output, ['APPOINTMENTS']);
fputcsv($output, ['Time', 'Patient', 'Doctor', 'Type', 'Status']);

$appointments = $mysqli->query("
    SELECT a.*, p.first_name, p.last_name, u.first_name as doctor_first, u.last_name as doctor_last 
    FROM appointments a 
    JOIN patients p ON a.patient_id = p.patient_id 
    LEFT JOIN users u ON a.doctor_id = u.user_id 
    WHERE DATE(a.appointment_date) = '$selected_date' 
    ORDER BY a.appointment_date, a.appointment_time
");

while ($row = $appointments->fetch_assoc()) {
    fputcsv($output, [
        date('g:i A', strtotime($row['appointment_date'])),
        $row['first_name'] . ' ' . $row['last_name'],
        ($row['doctor_first'] && $row['doctor_last']) ? $row['doctor_first'] . ' ' . $row['doctor_last'] : 'Not assigned',
        $row['appointment_type'],
        $row['status']
    ]);
}

fputcsv($output, []);

// Payments
fputcsv($output, ['PAYMENTS RECEIVED']);
fputcsv($output, ['Time', 'Patient', 'Service', 'Amount', 'Method']);

$payments = $mysqli->query("
    SELECT pt.*, p.first_name, p.last_name
    FROM payment_transactions pt
    JOIN patients p ON pt.patient_id = p.patient_id
    WHERE DATE(pt.processed_at) = '$selected_date' AND pt.status = 'Completed'
    ORDER BY pt.processed_at DESC
");

while ($row = $payments->fetch_assoc()) {
    fputcsv($output, [
        date('g:i A', strtotime($row['processed_at'])),
        $row['first_name'] . ' ' . $row['last_name'],
        $row['description'],
        '$' . number_format($row['amount'], 2),
        $row['payment_method']
    ]);
}

fclose($output);
?>