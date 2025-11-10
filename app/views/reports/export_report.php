<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Receptionist', 'Doctor'])) {
    header('Location: ../auth/login.php');
    exit();
}

$date = $_REQUEST['date'] ?? date('Y-m-d');
$type = $_REQUEST['type'] ?? 'daily';
$format = $_REQUEST['format'] ?? 'excel';

// Calculate date ranges
$start_date = $date;
$end_date = $date;

if ($type == 'weekly') {
    $start_date = date('Y-m-d', strtotime($date . ' -6 days'));
} elseif ($type == 'monthly') {
    $start_date = date('Y-m-01', strtotime($date));
    $end_date = date('Y-m-t', strtotime($date));
}

// Get comprehensive data
$export_data = [];

// Summary statistics
$summary = $mysqli->query("
    SELECT 
        (SELECT COUNT(*) FROM patients WHERE DATE(created_at) >= '$start_date' AND DATE(created_at) <= '$end_date') as new_patients,
        (SELECT COUNT(*) FROM appointments WHERE DATE(appointment_date) >= '$start_date' AND DATE(appointment_date) <= '$end_date') as appointments,
        (SELECT COUNT(*) FROM anc_visits WHERE DATE(visit_date) >= '$start_date' AND DATE(visit_date) <= '$end_date') as anc_visits,
        (SELECT COUNT(*) FROM deliveries WHERE DATE(delivery_date) >= '$start_date' AND DATE(delivery_date) <= '$end_date') as deliveries,
        (SELECT COALESCE(SUM(amount), 0) FROM payment_transactions WHERE DATE(processed_at) >= '$start_date' AND DATE(processed_at) <= '$end_date' AND status = 'Completed') as revenue
")->fetch_assoc();

// Detailed appointments
$appointments = $mysqli->query("
    SELECT a.appointment_date, a.appointment_time, a.appointment_type, a.status,
           p.first_name, p.last_name, p.contact_number,
           u.first_name as doctor_first, u.last_name as doctor_last
    FROM appointments a
    JOIN patients p ON a.patient_id = p.patient_id
    LEFT JOIN users u ON a.doctor_id = u.user_id
    WHERE DATE(a.appointment_date) >= '$start_date' AND DATE(a.appointment_date) <= '$end_date'
    ORDER BY a.appointment_date, a.appointment_time
");

// ANC visits
$anc_visits = $mysqli->query("
    SELECT av.visit_date, av.visit_number, av.gestational_age_weeks, av.weight_kg,
           av.blood_pressure_systolic, av.blood_pressure_diastolic,
           p.first_name, p.last_name, pr.risk_level
    FROM anc_visits av
    JOIN pregnancies pr ON av.pregnancy_id = pr.pregnancy_id
    JOIN patients p ON pr.patient_id = p.patient_id
    WHERE DATE(av.visit_date) >= '$start_date' AND DATE(av.visit_date) <= '$end_date'
    ORDER BY av.visit_date DESC
");

// Payments
$payments = $mysqli->query("
    SELECT pt.processed_at, pt.description, pt.amount, pt.payment_method,
           p.first_name, p.last_name
    FROM payment_transactions pt
    JOIN patients p ON pt.patient_id = p.patient_id
    WHERE DATE(pt.processed_at) >= '$start_date' AND DATE(pt.processed_at) <= '$end_date' 
    AND pt.status = 'Completed'
    ORDER BY pt.processed_at DESC
");

if ($format == 'excel') {
    // Excel export
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="maternal_care_report_' . $date . '.xls"');
    
    echo "<table border='1'>";
    echo "<tr><th colspan='6' style='background: #007cba; color: white; font-size: 16px;'>Maternal Care System - " . ucfirst($type) . " Report</th></tr>";
    echo "<tr><th colspan='6'>Period: " . date('M d, Y', strtotime($start_date)) . " to " . date('M d, Y', strtotime($end_date)) . "</th></tr>";
    echo "<tr><td></td></tr>";
    
    // Summary
    echo "<tr><th colspan='6' style='background: #f0f0f0;'>SUMMARY STATISTICS</th></tr>";
    echo "<tr><td>New Patients</td><td>" . $summary['new_patients'] . "</td><td>Appointments</td><td>" . $summary['appointments'] . "</td><td>Revenue</td><td>RWF " . number_format($summary['revenue']) . "</td></tr>";
    echo "<tr><td>ANC Visits</td><td>" . $summary['anc_visits'] . "</td><td>Deliveries</td><td>" . $summary['deliveries'] . "</td><td></td><td></td></tr>";
    echo "<tr><td></td></tr>";
    
    // Appointments
    if ($appointments->num_rows > 0) {
        echo "<tr><th colspan='6' style='background: #f0f0f0;'>APPOINTMENTS</th></tr>";
        echo "<tr><th>Date</th><th>Time</th><th>Patient</th><th>Doctor</th><th>Type</th><th>Status</th></tr>";
        while ($row = $appointments->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . date('M d, Y', strtotime($row['appointment_date'])) . "</td>";
            echo "<td>" . date('g:i A', strtotime($row['appointment_time'])) . "</td>";
            echo "<td>" . $row['first_name'] . ' ' . $row['last_name'] . "</td>";
            echo "<td>" . ($row['doctor_first'] ? 'Dr. ' . $row['doctor_first'] . ' ' . $row['doctor_last'] : 'Not assigned') . "</td>";
            echo "<td>" . $row['appointment_type'] . "</td>";
            echo "<td>" . $row['status'] . "</td>";
            echo "</tr>";
        }
        echo "<tr><td></td></tr>";
    }
    
    // ANC Visits
    if ($anc_visits->num_rows > 0) {
        echo "<tr><th colspan='6' style='background: #f0f0f0;'>ANC VISITS</th></tr>";
        echo "<tr><th>Date</th><th>Patient</th><th>Visit #</th><th>Gestational Age</th><th>Weight</th><th>Blood Pressure</th></tr>";
        while ($row = $anc_visits->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . date('M d, Y', strtotime($row['visit_date'])) . "</td>";
            echo "<td>" . $row['first_name'] . ' ' . $row['last_name'] . "</td>";
            echo "<td>" . $row['visit_number'] . "</td>";
            echo "<td>" . $row['gestational_age_weeks'] . " weeks</td>";
            echo "<td>" . $row['weight_kg'] . " kg</td>";
            echo "<td>" . $row['blood_pressure_systolic'] . '/' . $row['blood_pressure_diastolic'] . "</td>";
            echo "</tr>";
        }
        echo "<tr><td></td></tr>";
    }
    
    // Payments
    if ($payments->num_rows > 0) {
        echo "<tr><th colspan='6' style='background: #f0f0f0;'>PAYMENTS</th></tr>";
        echo "<tr><th>Date</th><th>Patient</th><th>Service</th><th>Amount</th><th>Method</th><th></th></tr>";
        while ($row = $payments->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . date('M d, Y H:i', strtotime($row['processed_at'])) . "</td>";
            echo "<td>" . $row['first_name'] . ' ' . $row['last_name'] . "</td>";
            echo "<td>" . $row['description'] . "</td>";
            echo "<td>RWF " . number_format($row['amount']) . "</td>";
            echo "<td>" . $row['payment_method'] . "</td>";
            echo "<td></td>";
            echo "</tr>";
        }
    }
    
    echo "</table>";
    
} elseif ($format == 'pdf') {
    // Simple HTML to PDF
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="maternal_care_report_' . $date . '.pdf"');
    
    // For now, output HTML that can be saved as PDF
    echo "<!DOCTYPE html><html><head><title>Maternal Care Report</title>";
    echo "<style>body{font-family:Arial,sans-serif;font-size:12px;} table{width:100%;border-collapse:collapse;} th,td{border:1px solid #000;padding:4px;} th{background:#f0f0f0;}</style>";
    echo "</head><body>";
    echo "<h1>Maternal Care System Report</h1>";
    echo "<h2>" . ucfirst($type) . " Report for " . date('F j, Y', strtotime($date)) . "</h2>";
    
    echo "<h3>Summary</h3>";
    echo "<p>New Patients: " . $summary['new_patients'] . " | Appointments: " . $summary['appointments'] . " | Revenue: RWF " . number_format($summary['revenue']) . "</p>";
    
    // Add detailed tables here similar to Excel format
    echo "</body></html>";
    
} else {
    // CSV export
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="maternal_care_report_' . $date . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Summary
    fputcsv($output, ['Maternal Care System - ' . ucfirst($type) . ' Report']);
    fputcsv($output, ['Period', date('M d, Y', strtotime($start_date)) . ' to ' . date('M d, Y', strtotime($end_date))]);
    fputcsv($output, []);
    fputcsv($output, ['SUMMARY']);
    fputcsv($output, ['New Patients', $summary['new_patients']]);
    fputcsv($output, ['Appointments', $summary['appointments']]);
    fputcsv($output, ['ANC Visits', $summary['anc_visits']]);
    fputcsv($output, ['Deliveries', $summary['deliveries']]);
    fputcsv($output, ['Revenue (RWF)', number_format($summary['revenue'])]);
    fputcsv($output, []);
    
    // Appointments
    if ($appointments->num_rows > 0) {
        fputcsv($output, ['APPOINTMENTS']);
        fputcsv($output, ['Date', 'Time', 'Patient', 'Doctor', 'Type', 'Status']);
        while ($row = $appointments->fetch_assoc()) {
            fputcsv($output, [
                date('M d, Y', strtotime($row['appointment_date'])),
                date('g:i A', strtotime($row['appointment_time'])),
                $row['first_name'] . ' ' . $row['last_name'],
                $row['doctor_first'] ? 'Dr. ' . $row['doctor_first'] . ' ' . $row['doctor_last'] : 'Not assigned',
                $row['appointment_type'],
                $row['status']
            ]);
        }
        fputcsv($output, []);
    }
    
    fclose($output);
}
?>