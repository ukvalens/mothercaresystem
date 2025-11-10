<?php
session_start();
require_once '../../config/database.php';
require_once '../../config/email_config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Receptionist', 'Doctor'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$email = $_POST['email'] ?? '';
$date = $_POST['date'] ?? date('Y-m-d');
$type = $_POST['type'] ?? 'daily';

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'error' => 'Invalid email address']);
    exit();
}

// Get current user info
$user_query = $mysqli->prepare("SELECT first_name, last_name FROM users WHERE user_id = ?");
$user_query->bind_param("i", $_SESSION['user_id']);
$user_query->execute();
$user_data = $user_query->get_result()->fetch_assoc();
$user_name = ($user_data && $user_data['first_name']) ? $user_data['first_name'] . ' ' . $user_data['last_name'] : 'System User';

// Calculate date ranges
$start_date = $date;
$end_date = $date;

if ($type == 'weekly') {
    $start_date = date('Y-m-d', strtotime($date . ' -6 days'));
} elseif ($type == 'monthly') {
    $start_date = date('Y-m-01', strtotime($date));
    $end_date = date('Y-m-t', strtotime($date));
}

// Get summary data
$summary = $mysqli->query("
    SELECT 
        (SELECT COUNT(*) FROM patients WHERE DATE(created_at) >= '$start_date' AND DATE(created_at) <= '$end_date') as new_patients,
        (SELECT COUNT(*) FROM appointments WHERE DATE(appointment_date) >= '$start_date' AND DATE(appointment_date) <= '$end_date') as appointments,
        (SELECT COUNT(*) FROM anc_visits WHERE DATE(visit_date) >= '$start_date' AND DATE(visit_date) <= '$end_date') as anc_visits,
        (SELECT COUNT(*) FROM deliveries WHERE DATE(delivery_date) >= '$start_date' AND DATE(delivery_date) <= '$end_date') as deliveries,
        (SELECT COALESCE(SUM(amount), 0) FROM payment_transactions WHERE DATE(processed_at) >= '$start_date' AND DATE(processed_at) <= '$end_date' AND status = 'Completed') as revenue,
        (SELECT COUNT(*) FROM pregnancies WHERE current_status = 'Active' AND risk_level IN ('High', 'Critical')) as high_risk_pregnancies
")->fetch_assoc();

// Create email content
$title = ucfirst($type) . ' Report - ' . date('F j, Y', strtotime($date));

$content = '
<h2>📊 System Overview</h2>
<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin: 20px 0;">
    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid #007cba;">
        <h3 style="margin: 0; color: #007cba;">👥 Patients</h3>
        <p style="font-size: 24px; font-weight: bold; margin: 10px 0;">' . $summary['new_patients'] . '</p>
        <p style="margin: 0; color: #666;">New registrations</p>
    </div>
    
    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid #28a745;">
        <h3 style="margin: 0; color: #28a745;">📅 Appointments</h3>
        <p style="font-size: 24px; font-weight: bold; margin: 10px 0;">' . $summary['appointments'] . '</p>
        <p style="margin: 0; color: #666;">Total scheduled</p>
    </div>
    
    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid #17a2b8;">
        <h3 style="margin: 0; color: #17a2b8;">🏥 ANC Visits</h3>
        <p style="font-size: 24px; font-weight: bold; margin: 10px 0;">' . $summary['anc_visits'] . '</p>
        <p style="margin: 0; color: #666;">Visits completed</p>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin: 20px 0;">
    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid #ffc107;">
        <h3 style="margin: 0; color: #ffc107;">👶 Deliveries</h3>
        <p style="font-size: 24px; font-weight: bold; margin: 10px 0;">' . $summary['deliveries'] . '</p>
        <p style="margin: 0; color: #666;">Successful deliveries</p>
    </div>
    
    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid #28a745;">
        <h3 style="margin: 0; color: #28a745;">💰 Revenue</h3>
        <p style="font-size: 24px; font-weight: bold; margin: 10px 0;">RWF ' . number_format($summary['revenue']) . '</p>
        <p style="margin: 0; color: #666;">Total collected</p>
    </div>
    
    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid #dc3545;">
        <h3 style="margin: 0; color: #dc3545;">⚠️ High Risk</h3>
        <p style="font-size: 24px; font-weight: bold; margin: 10px 0;">' . $summary['high_risk_pregnancies'] . '</p>
        <p style="margin: 0; color: #666;">Pregnancies requiring attention</p>
    </div>
</div>

<div style="background: #e3f2fd; padding: 20px; border-radius: 8px; margin: 20px 0;">
    <h3 style="margin: 0 0 10px 0; color: #1976d2;">📈 Key Insights</h3>
    <ul style="margin: 0; padding-left: 20px;">
        <li>Patient registration is ' . ($summary['new_patients'] > 5 ? 'strong' : 'steady') . ' with ' . $summary['new_patients'] . ' new patients</li>
        <li>Clinical activity shows ' . $summary['anc_visits'] . ' ANC visits completed</li>
        <li>Financial performance: RWF ' . number_format($summary['revenue']) . ' in revenue</li>
        ' . ($summary['high_risk_pregnancies'] > 0 ? '<li style="color: #d32f2f;">⚠️ ' . $summary['high_risk_pregnancies'] . ' high-risk pregnancies require immediate attention</li>' : '') . '
    </ul>
</div>

<p style="margin-top: 30px;">
    <a href="http://localhost/mothercaresystem/app/views/reports/daily.php?date=' . $date . '&type=' . $type . '" 
       style="background: #007cba; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; display: inline-block;">
        📊 View Full Report
    </a>
</p>

<p style="color: #666; font-size: 14px; margin-top: 20px;">
    Report generated on ' . date('F j, Y \a\t g:i A') . ' by ' . $user_name . '<br>
    Maternal Care System - Comprehensive Healthcare Management
</p>
';

$html = getEmailTemplate($title, $content);

try {
    $result = sendEmail(
        $email,
        'Report Recipient',
        $title . ' - Maternal Care System',
        $html,
        true
    );
    
    echo json_encode(['success' => $result]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Failed to send email: ' . $e->getMessage()]);
}
?>