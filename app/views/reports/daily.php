<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Receptionist', 'Doctor'])) {
    header('Location: ../auth/login.php');
    exit();
}

$page_title = 'Comprehensive Daily Reports';
$page_header = '📊 System Reports Dashboard';
$show_nav = true;

$selected_date = $_GET['date'] ?? date('Y-m-d');
$report_type = $_GET['type'] ?? 'daily';
$date_range = $_GET['range'] ?? '7'; // days

// Calculate date ranges
$start_date = $selected_date;
$end_date = $selected_date;

if ($report_type == 'weekly') {
    $start_date = date('Y-m-d', strtotime($selected_date . ' -6 days'));
} elseif ($report_type == 'monthly') {
    $start_date = date('Y-m-01', strtotime($selected_date));
    $end_date = date('Y-m-t', strtotime($selected_date));
}

// COMPREHENSIVE STATISTICS
$stats = [];

// 1. PATIENT STATISTICS
$patient_stats = $mysqli->query("
    SELECT 
        COUNT(*) as total_patients,
        SUM(CASE WHEN DATE(created_at) = '$selected_date' THEN 1 ELSE 0 END) as new_today,
        SUM(CASE WHEN DATE(created_at) >= '$start_date' AND DATE(created_at) <= '$end_date' THEN 1 ELSE 0 END) as new_period,
        SUM(CASE WHEN gender = 'Female' THEN 1 ELSE 0 END) as female_patients,
        AVG(YEAR(CURDATE()) - YEAR(date_of_birth)) as avg_age
    FROM patients WHERE is_active = 1
")->fetch_assoc();

// 2. APPOINTMENT STATISTICS
$appointment_stats = $mysqli->query("
    SELECT 
        COUNT(*) as total_appointments,
        SUM(CASE WHEN DATE(appointment_date) = '$selected_date' THEN 1 ELSE 0 END) as today_appointments,
        SUM(CASE WHEN DATE(appointment_date) >= '$start_date' AND DATE(appointment_date) <= '$end_date' THEN 1 ELSE 0 END) as period_appointments,
        SUM(CASE WHEN status = 'Completed' AND DATE(appointment_date) >= '$start_date' AND DATE(appointment_date) <= '$end_date' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'Cancelled' AND DATE(appointment_date) >= '$start_date' AND DATE(appointment_date) <= '$end_date' THEN 1 ELSE 0 END) as cancelled,
        SUM(CASE WHEN status = 'No Show' AND DATE(appointment_date) >= '$start_date' AND DATE(appointment_date) <= '$end_date' THEN 1 ELSE 0 END) as no_shows
    FROM appointments
")->fetch_assoc();

// 3. PREGNANCY STATISTICS
$pregnancy_stats = $mysqli->query("
    SELECT 
        COUNT(*) as total_pregnancies,
        SUM(CASE WHEN current_status = 'Active' THEN 1 ELSE 0 END) as active_pregnancies,
        SUM(CASE WHEN risk_level = 'High' AND current_status = 'Active' THEN 1 ELSE 0 END) as high_risk,
        SUM(CASE WHEN risk_level = 'Critical' AND current_status = 'Active' THEN 1 ELSE 0 END) as critical_risk,
        SUM(CASE WHEN DATE(created_at) >= '$start_date' AND DATE(created_at) <= '$end_date' THEN 1 ELSE 0 END) as new_pregnancies
    FROM pregnancies
")->fetch_assoc();

// 4. CLINICAL STATISTICS
$clinical_stats = $mysqli->query("
    SELECT 
        COUNT(*) as total_visits,
        SUM(CASE WHEN DATE(visit_date) = '$selected_date' THEN 1 ELSE 0 END) as today_visits,
        SUM(CASE WHEN DATE(visit_date) >= '$start_date' AND DATE(visit_date) <= '$end_date' THEN 1 ELSE 0 END) as period_visits,
        AVG(CASE WHEN blood_pressure_systolic > 0 THEN blood_pressure_systolic END) as avg_systolic,
        AVG(CASE WHEN weight_kg > 0 THEN weight_kg END) as avg_weight,
        SUM(CASE WHEN blood_pressure_systolic > 140 OR blood_pressure_diastolic > 90 THEN 1 ELSE 0 END) as high_bp_cases
    FROM anc_visits WHERE DATE(visit_date) >= '$start_date' AND DATE(visit_date) <= '$end_date'
")->fetch_assoc();

// 5. DELIVERY STATISTICS
$delivery_stats = $mysqli->query("
    SELECT 
        COUNT(*) as total_deliveries,
        SUM(CASE WHEN DATE(delivery_date) = '$selected_date' THEN 1 ELSE 0 END) as today_deliveries,
        SUM(CASE WHEN DATE(delivery_date) >= '$start_date' AND DATE(delivery_date) <= '$end_date' THEN 1 ELSE 0 END) as period_deliveries,
        SUM(CASE WHEN mode_delivery = 'Normal' THEN 1 ELSE 0 END) as normal_deliveries,
        SUM(CASE WHEN mode_delivery = 'Cesarean' THEN 1 ELSE 0 END) as cesarean_deliveries,
        AVG(CASE WHEN birth_weight_kg > 0 THEN birth_weight_kg END) as avg_birth_weight,
        SUM(CASE WHEN delivery_outcome = 'Live Birth' THEN 1 ELSE 0 END) as live_births
    FROM deliveries WHERE DATE(delivery_date) >= '$start_date' AND DATE(delivery_date) <= '$end_date'
")->fetch_assoc();

// 6. FINANCIAL STATISTICS
$financial_stats = $mysqli->query("
    SELECT 
        COUNT(*) as total_transactions,
        SUM(CASE WHEN DATE(processed_at) = '$selected_date' AND status = 'Completed' THEN amount ELSE 0 END) as today_revenue,
        SUM(CASE WHEN DATE(processed_at) >= '$start_date' AND DATE(processed_at) <= '$end_date' AND status = 'Completed' THEN amount ELSE 0 END) as period_revenue,
        SUM(CASE WHEN status = 'Pending' THEN amount ELSE 0 END) as pending_payments,
        AVG(CASE WHEN status = 'Completed' THEN amount END) as avg_transaction,
        COUNT(DISTINCT patient_id) as paying_patients
    FROM payment_transactions WHERE DATE(created_at) >= '$start_date' AND DATE(created_at) <= '$end_date'
")->fetch_assoc();

// 7. SYSTEM ACTIVITY STATISTICS
$system_stats = $mysqli->query("
    SELECT 
        COUNT(*) as total_notifications,
        SUM(CASE WHEN DATE(created_at) = '$selected_date' THEN 1 ELSE 0 END) as today_notifications,
        SUM(CASE WHEN notification_type = 'Clinical Alert' AND DATE(created_at) >= '$start_date' AND DATE(created_at) <= '$end_date' THEN 1 ELSE 0 END) as clinical_alerts,
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as unread_notifications
    FROM notifications WHERE DATE(created_at) >= '$start_date' AND DATE(created_at) <= '$end_date'
")->fetch_assoc();

// 8. USER ACTIVITY STATISTICS
$user_stats = $mysqli->query("
    SELECT 
        COUNT(DISTINCT user_id) as active_users,
        SUM(CASE WHEN role = 'Doctor' THEN 1 ELSE 0 END) as doctors,
        SUM(CASE WHEN role = 'Nurse' THEN 1 ELSE 0 END) as nurses,
        SUM(CASE WHEN role = 'Patient' THEN 1 ELSE 0 END) as patient_accounts,
        SUM(CASE WHEN DATE(last_login) = '$selected_date' THEN 1 ELSE 0 END) as today_logins
    FROM users WHERE is_active = 1
")->fetch_assoc();

// DETAILED DATA QUERIES
$detailed_data = [];

// Today's appointments with full details
$detailed_data['appointments'] = $mysqli->query("
    SELECT a.*, p.first_name, p.last_name, p.contact_number,
           u.first_name as doctor_first, u.last_name as doctor_last,
           TIMESTAMPDIFF(YEAR, p.date_of_birth, CURDATE()) as age
    FROM appointments a 
    JOIN patients p ON a.patient_id = p.patient_id 
    LEFT JOIN users u ON a.doctor_id = u.user_id 
    WHERE DATE(a.appointment_date) = '$selected_date' 
    ORDER BY a.appointment_time
");

// New patients with comprehensive info
$detailed_data['new_patients'] = $mysqli->query("
    SELECT p.*, 
           TIMESTAMPDIFF(YEAR, p.date_of_birth, CURDATE()) as age,
           u.first_name as registered_by_first, u.last_name as registered_by_last
    FROM patients p
    LEFT JOIN users u ON p.registered_by = u.user_id
    WHERE DATE(p.created_at) = '$selected_date' 
    ORDER BY p.created_at DESC
");

// ANC visits with pregnancy details
$detailed_data['anc_visits'] = $mysqli->query("
    SELECT av.*, p.first_name, p.last_name, 
           pr.pregnancy_id, pr.risk_level,
           FLOOR(DATEDIFF('$selected_date', pr.lmp_date) / 7) as gestational_weeks,
           u.first_name as recorded_by_first, u.last_name as recorded_by_last
    FROM anc_visits av 
    JOIN pregnancies pr ON av.pregnancy_id = pr.pregnancy_id
    JOIN patients p ON pr.patient_id = p.patient_id 
    LEFT JOIN users u ON av.recorded_by = u.user_id
    WHERE DATE(av.visit_date) = '$selected_date' 
    ORDER BY av.visit_date DESC
");

// Payments with service details
$detailed_data['payments'] = $mysqli->query("
    SELECT pt.*, p.first_name, p.last_name,
           u.first_name as staff_first, u.last_name as staff_last
    FROM payment_transactions pt
    JOIN patients p ON pt.patient_id = p.patient_id
    LEFT JOIN users u ON pt.processed_by = u.user_id
    WHERE DATE(pt.processed_at) = '$selected_date' AND pt.status = 'Completed'
    ORDER BY pt.processed_at DESC
");

// Deliveries with full details
$detailed_data['deliveries'] = $mysqli->query("
    SELECT d.*, p.first_name, p.last_name,
           pr.risk_level, pr.lmp_date,
           u.first_name as staff_first, u.last_name as staff_last
    FROM deliveries d
    JOIN pregnancies pr ON d.pregnancy_id = pr.pregnancy_id
    JOIN patients p ON pr.patient_id = p.patient_id
    LEFT JOIN users u ON d.recorded_by = u.user_id
    WHERE DATE(d.delivery_date) = '$selected_date'
    ORDER BY d.delivery_date DESC
");

// High-risk pregnancies requiring attention
$detailed_data['high_risk'] = $mysqli->query("
    SELECT p.first_name, p.last_name, p.contact_number,
           pr.risk_level, pr.lmp_date,
           FLOOR(DATEDIFF(CURDATE(), pr.lmp_date) / 7) as current_week,
           pr.expected_delivery_date,
           DATEDIFF(pr.expected_delivery_date, CURDATE()) as days_to_edd
    FROM pregnancies pr
    JOIN patients p ON pr.patient_id = p.patient_id
    WHERE pr.current_status = 'Active' AND pr.risk_level IN ('High', 'Critical')
    ORDER BY pr.risk_level DESC, pr.expected_delivery_date ASC
    LIMIT 20
");

// Recent clinical alerts
$detailed_data['alerts'] = $mysqli->query("
    SELECT n.*, p.first_name, p.last_name
    FROM notifications n
    LEFT JOIN patients p ON n.patient_id = p.patient_id
    WHERE n.notification_type = 'Clinical Alert' 
    AND DATE(n.created_at) >= '$start_date' AND DATE(n.created_at) <= '$end_date'
    ORDER BY n.created_at DESC
    LIMIT 10
");

// Performance metrics by staff
$detailed_data['staff_performance'] = $mysqli->query("
    SELECT u.first_name, u.last_name, u.role,
           COUNT(DISTINCT a.appointment_id) as appointments_handled,
           COUNT(DISTINCT av.visit_id) as visits_recorded,
           COUNT(DISTINCT pt.transaction_id) as payments_processed
    FROM users u
    LEFT JOIN appointments a ON u.user_id = a.doctor_id AND DATE(a.appointment_date) >= '$start_date' AND DATE(a.appointment_date) <= '$end_date'
    LEFT JOIN anc_visits av ON u.user_id = av.recorded_by AND DATE(av.visit_date) >= '$start_date' AND DATE(av.visit_date) <= '$end_date'
    LEFT JOIN payment_transactions pt ON u.user_id = pt.processed_by AND DATE(pt.processed_at) >= '$start_date' AND DATE(pt.processed_at) <= '$end_date'
    WHERE u.is_active = 1 AND u.role IN ('Doctor', 'Nurse', 'Receptionist')
    GROUP BY u.user_id, u.first_name, u.last_name, u.role
    HAVING COUNT(DISTINCT a.appointment_id) > 0 OR COUNT(DISTINCT av.visit_id) > 0 OR COUNT(DISTINCT pt.transaction_id) > 0
    ORDER BY u.role, (COUNT(DISTINCT a.appointment_id) + COUNT(DISTINCT av.visit_id) + COUNT(DISTINCT pt.transaction_id)) DESC
");

include '../layouts/header.php';

// Calculate key performance indicators
$kpis = [
    'patient_satisfaction' => $appointment_stats['completed'] > 0 ? round(($appointment_stats['completed'] / ($appointment_stats['completed'] + $appointment_stats['cancelled'] + $appointment_stats['no_shows'])) * 100, 1) : 0,
    'delivery_success_rate' => $delivery_stats['total_deliveries'] > 0 ? round(($delivery_stats['live_births'] / $delivery_stats['total_deliveries']) * 100, 1) : 0,
    'cesarean_rate' => $delivery_stats['total_deliveries'] > 0 ? round(($delivery_stats['cesarean_deliveries'] / $delivery_stats['total_deliveries']) * 100, 1) : 0,
    'revenue_per_patient' => $financial_stats['paying_patients'] > 0 ? round($financial_stats['period_revenue'] / $financial_stats['paying_patients'], 0) : 0
];
?>

<div class="container">
    <!-- Report Header -->
    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 2rem; border-radius: 10px; margin-bottom: 2rem; text-align: center;">
        <h1 style="margin: 0 0 0.5rem 0;">📊 Comprehensive System Report</h1>
        <p style="margin: 0; opacity: 0.9;"><?php echo ucfirst($report_type); ?> Report for <?php echo date('F j, Y', strtotime($selected_date)); ?></p>
    </div>
    
    <!-- Report Controls -->
    <div class="section no-print">
        <form method="GET" style="display: flex; gap: 1rem; align-items: end; flex-wrap: wrap;">
            <div class="form-group">
                <label>Report Type:</label>
                <select name="type" onchange="this.form.submit()">
                    <option value="daily" <?php echo $report_type == 'daily' ? 'selected' : ''; ?>>Daily Report</option>
                    <option value="weekly" <?php echo $report_type == 'weekly' ? 'selected' : ''; ?>>Weekly Report</option>
                    <option value="monthly" <?php echo $report_type == 'monthly' ? 'selected' : ''; ?>>Monthly Report</option>
                </select>
            </div>
            <div class="form-group">
                <label>Date:</label>
                <input type="date" name="date" value="<?php echo $selected_date; ?>" onchange="this.form.submit()">
            </div>
            <div class="form-group">
                <button type="button" onclick="exportReport()" class="btn" style="background: #28a745;">📊 Export</button>
                <button type="button" onclick="window.print()" class="btn">🖨️ Print</button>
                <button type="button" onclick="emailReport()" class="btn" style="background: #17a2b8;">📧 Email</button>
            </div>
        </form>
    </div>

    <!-- Key Performance Indicators -->
    <div class="section">
        <h3>🎯 Key Performance Indicators</h3>
        <div class="stats">
            <div class="stat-card" style="border-left: 4px solid #28a745;">
                <div class="stat-number"><?php echo $kpis['patient_satisfaction']; ?>%</div>
                <div class="stat-label">Appointment Success Rate</div>
            </div>
            <div class="stat-card" style="border-left: 4px solid #007cba;">
                <div class="stat-number"><?php echo $kpis['delivery_success_rate']; ?>%</div>
                <div class="stat-label">Delivery Success Rate</div>
            </div>
            <div class="stat-card" style="border-left: 4px solid #ffc107;">
                <div class="stat-number"><?php echo $kpis['cesarean_rate']; ?>%</div>
                <div class="stat-label">Cesarean Rate</div>
            </div>
            <div class="stat-card" style="border-left: 4px solid #28a745;">
                <div class="stat-number">RWF <?php echo number_format($kpis['revenue_per_patient']); ?></div>
                <div class="stat-label">Revenue per Patient</div>
            </div>
        </div>
    </div>

    <!-- Main Statistics Dashboard -->
    <div class="section">
        <h3>📈 System Overview</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
            
            <!-- Patient Statistics -->
            <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 8px; border-left: 4px solid #007cba;">
                <h4 style="color: #007cba; margin: 0 0 1rem 0;">👥 Patient Statistics</h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div><strong>Total Patients:</strong> <?php echo number_format($patient_stats['total_patients']); ?></div>
                    <div><strong>New Today:</strong> <?php echo $patient_stats['new_today']; ?></div>
                    <div><strong>New This Period:</strong> <?php echo $patient_stats['new_period']; ?></div>
                    <div><strong>Average Age:</strong> <?php echo round($patient_stats['avg_age'], 1); ?> years</div>
                </div>
            </div>
            
            <!-- Appointment Statistics -->
            <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 8px; border-left: 4px solid #28a745;">
                <h4 style="color: #28a745; margin: 0 0 1rem 0;">📅 Appointments</h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div><strong>Today:</strong> <?php echo $appointment_stats['today_appointments']; ?></div>
                    <div><strong>This Period:</strong> <?php echo $appointment_stats['period_appointments']; ?></div>
                    <div><strong>Completed:</strong> <?php echo $appointment_stats['completed']; ?></div>
                    <div><strong>No Shows:</strong> <?php echo $appointment_stats['no_shows']; ?></div>
                </div>
            </div>
            
            <!-- Pregnancy Statistics -->
            <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 8px; border-left: 4px solid #e91e63;">
                <h4 style="color: #e91e63; margin: 0 0 1rem 0;">🤰 Pregnancies</h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div><strong>Active:</strong> <?php echo $pregnancy_stats['active_pregnancies']; ?></div>
                    <div><strong>New This Period:</strong> <?php echo $pregnancy_stats['new_pregnancies']; ?></div>
                    <div><strong>High Risk:</strong> <span style="color: #dc3545;"><?php echo $pregnancy_stats['high_risk']; ?></span></div>
                    <div><strong>Critical:</strong> <span style="color: #dc3545; font-weight: bold;"><?php echo $pregnancy_stats['critical_risk']; ?></span></div>
                </div>
            </div>
            
            <!-- Clinical Statistics -->
            <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 8px; border-left: 4px solid #17a2b8;">
                <h4 style="color: #17a2b8; margin: 0 0 1rem 0;">🏥 Clinical Care</h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div><strong>ANC Visits Today:</strong> <?php echo $clinical_stats['today_visits']; ?></div>
                    <div><strong>Period Visits:</strong> <?php echo $clinical_stats['period_visits']; ?></div>
                    <div><strong>Avg BP:</strong> <?php echo round($clinical_stats['avg_systolic'], 0); ?> mmHg</div>
                    <div><strong>High BP Cases:</strong> <span style="color: #dc3545;"><?php echo $clinical_stats['high_bp_cases']; ?></span></div>
                </div>
            </div>
            
            <!-- Delivery Statistics -->
            <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 8px; border-left: 4px solid #ffc107;">
                <h4 style="color: #ffc107; margin: 0 0 1rem 0;">👶 Deliveries</h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div><strong>Today:</strong> <?php echo $delivery_stats['today_deliveries']; ?></div>
                    <div><strong>This Period:</strong> <?php echo $delivery_stats['period_deliveries']; ?></div>
                    <div><strong>Normal:</strong> <?php echo $delivery_stats['normal_deliveries']; ?></div>
                    <div><strong>Cesarean:</strong> <?php echo $delivery_stats['cesarean_deliveries']; ?></div>
                </div>
            </div>
            
            <!-- Financial Statistics -->
            <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 8px; border-left: 4px solid #28a745;">
                <h4 style="color: #28a745; margin: 0 0 1rem 0;">💰 Financial</h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div><strong>Today Revenue:</strong> RWF <?php echo number_format($financial_stats['today_revenue']); ?></div>
                    <div><strong>Period Revenue:</strong> RWF <?php echo number_format($financial_stats['period_revenue']); ?></div>
                    <div><strong>Pending:</strong> RWF <?php echo number_format($financial_stats['pending_payments']); ?></div>
                    <div><strong>Avg Transaction:</strong> RWF <?php echo number_format($financial_stats['avg_transaction']); ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- High Risk Pregnancies Alert -->
    <?php if ($detailed_data['high_risk']->num_rows > 0): ?>
    <div class="section" style="border-left: 4px solid #dc3545;">
        <h3 style="color: #dc3545;">🚨 High Risk Pregnancies Requiring Attention</h3>
        <div style="max-height: 300px; overflow-y: auto;">
            <table>
                <thead>
                    <tr>
                        <th>Patient</th>
                        <th>Contact</th>
                        <th>Risk Level</th>
                        <th>Current Week</th>
                        <th>EDD</th>
                        <th>Days to EDD</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $detailed_data['high_risk']->fetch_assoc()): ?>
                    <tr style="background: <?php echo $row['risk_level'] == 'Critical' ? '#ffebee' : '#fff3e0'; ?>;">
                        <td><strong><?php echo $row['first_name'] . ' ' . $row['last_name']; ?></strong></td>
                        <td><?php echo $row['contact_number']; ?></td>
                        <td><span style="background: <?php echo $row['risk_level'] == 'Critical' ? '#dc3545' : '#ff9800'; ?>; color: white; padding: 0.2rem 0.5rem; border-radius: 10px; font-size: 0.8rem;"><?php echo $row['risk_level']; ?></span></td>
                        <td><?php echo $row['current_week']; ?> weeks</td>
                        <td><?php echo date('M d, Y', strtotime($row['expected_delivery_date'])); ?></td>
                        <td><?php echo $row['days_to_edd']; ?> days</td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Recent Clinical Alerts -->
    <?php if ($detailed_data['alerts']->num_rows > 0): ?>
    <div class="section">
        <h3 style="color: #dc3545;">⚠️ Recent Clinical Alerts</h3>
        <div style="max-height: 250px; overflow-y: auto;">
            <?php while ($alert = $detailed_data['alerts']->fetch_assoc()): ?>
            <div style="border: 1px solid #dc3545; padding: 1rem; margin-bottom: 0.5rem; border-radius: 6px; background: #fff5f5;">
                <strong><?php echo $alert['subject']; ?></strong>
                <?php if ($alert['first_name']): ?>
                    <span style="color: #666;"> - <?php echo $alert['first_name'] . ' ' . $alert['last_name']; ?></span>
                <?php endif; ?>
                <div style="margin-top: 0.5rem; font-size: 0.9rem;"><?php echo $alert['message']; ?></div>
                <small style="color: #666;"><?php echo date('M d, H:i', strtotime($alert['created_at'])); ?></small>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Staff Performance -->
    <?php if ($detailed_data['staff_performance']->num_rows > 0): ?>
    <div class="section">
        <h3>👨‍⚕️ Staff Performance Summary</h3>
        <table>
            <thead>
                <tr>
                    <th>Staff Member</th>
                    <th>Role</th>
                    <th>Appointments</th>
                    <th>Visits Recorded</th>
                    <th>Payments Processed</th>
                    <th>Total Activity</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($staff = $detailed_data['staff_performance']->fetch_assoc()): ?>
                <tr>
                    <td><strong><?php echo $staff['first_name'] . ' ' . $staff['last_name']; ?></strong></td>
                    <td><?php echo $staff['role']; ?></td>
                    <td><?php echo $staff['appointments_handled']; ?></td>
                    <td><?php echo $staff['visits_recorded']; ?></td>
                    <td><?php echo $staff['payments_processed']; ?></td>
                    <td><strong><?php echo $staff['appointments_handled'] + $staff['visits_recorded'] + $staff['payments_processed']; ?></strong></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- Today's Appointments -->
    <?php if ($detailed_data['appointments']->num_rows > 0): ?>
    <div class="section">
        <h3>Appointments</h3>
        <table>
            <thead>
                <tr>
                    <th>Time</th>
                    <th>Patient</th>
                    <th>Age</th>
                    <th>Contact</th>
                    <th>Doctor</th>
                    <th>Type</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $detailed_data['appointments']->fetch_assoc()): ?>
                <tr style="background: <?php echo $row['status'] == 'Completed' ? '#f0f8f0' : ($row['status'] == 'Cancelled' ? '#fff0f0' : '#ffffff'); ?>;">
                    <td><strong><?php echo date('g:i A', strtotime($row['appointment_time'])); ?></strong></td>
                    <td><?php echo $row['first_name'] . ' ' . $row['last_name']; ?></td>
                    <td><?php echo $row['age']; ?></td>
                    <td><?php echo $row['contact_number']; ?></td>
                    <td><?php echo ($row['doctor_first'] && $row['doctor_last']) ? 'Dr. ' . $row['doctor_first'] . ' ' . $row['doctor_last'] : 'Not assigned'; ?></td>
                    <td><?php echo $row['appointment_type']; ?></td>
                    <td><span style="background: <?php echo $row['status'] == 'Completed' ? '#28a745' : ($row['status'] == 'Cancelled' ? '#dc3545' : '#ffc107'); ?>; color: white; padding: 0.2rem 0.5rem; border-radius: 10px; font-size: 0.8rem;"><?php echo $row['status']; ?></span></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <?php if ($detailed_data['new_patients']->num_rows > 0): ?>
    <div class="section">
        <h3>New Patient Registrations</h3>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Age</th>
                    <th>Registration Time</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $detailed_data['new_patients']->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['first_name'] . ' ' . $row['last_name']; ?></td>
                    <td><?php echo $row['contact_number']; ?></td>
                    <td><?php echo date_diff(date_create($row['date_of_birth']), date_create('today'))->y; ?></td>
                    <td><?php echo date('g:i A', strtotime($row['created_at'])); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <?php if ($detailed_data['anc_visits']->num_rows > 0): ?>
    <div class="section">
        <h3>ANC Visits</h3>
        <table>
            <thead>
                <tr>
                    <th>Patient</th>
                    <th>Visit Number</th>
                    <th>Gestational Age</th>
                    <th>Risk Level</th>
                    <th>Weight</th>
                    <th>Blood Pressure</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $detailed_data['anc_visits']->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['first_name'] . ' ' . $row['last_name']; ?></td>
                    <td><?php echo $row['visit_number']; ?></td>
                    <td><?php echo $row['gestational_weeks']; ?> weeks</td>
                    <td><span style="background: <?php echo $row['risk_level'] == 'High' ? '#dc3545' : ($row['risk_level'] == 'Medium' ? '#ffc107' : '#28a745'); ?>; color: white; padding: 0.2rem 0.5rem; border-radius: 10px; font-size: 0.8rem;"><?php echo $row['risk_level']; ?></span></td>
                    <td><?php echo $row['weight_kg']; ?> kg</td>
                    <td><?php echo $row['blood_pressure_systolic'] . '/' . $row['blood_pressure_diastolic']; ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <?php if ($detailed_data['payments']->num_rows > 0): ?>
    <div class="section">
        <h3>Payments Received</h3>
        <table>
            <thead>
                <tr>
                    <th>Time</th>
                    <th>Patient</th>
                    <th>Service</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Processed By</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $detailed_data['payments']->fetch_assoc()): ?>
                <tr>
                    <td><?php echo date('g:i A', strtotime($row['processed_at'])); ?></td>
                    <td><?php echo $row['first_name'] . ' ' . $row['last_name']; ?></td>
                    <td><?php echo $row['description']; ?></td>
                    <td>RWF <?php echo number_format($row['amount']); ?></td>
                    <td><?php echo $row['payment_method']; ?></td>
                    <td><?php echo ($row['staff_first'] && $row['staff_last']) ? $row['staff_first'] . ' ' . $row['staff_last'] : 'System'; ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <?php if ($detailed_data['deliveries']->num_rows > 0): ?>
    <div class="section">
        <h3>Deliveries</h3>
        <table>
            <thead>
                <tr>
                    <th>Time</th>
                    <th>Patient</th>
                    <th>Mode</th>
                    <th>Outcome</th>
                    <th>Birth Weight</th>
                    <th>Recorded By</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $detailed_data['deliveries']->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['delivery_time'] ? date('g:i A', strtotime($row['delivery_time'])) : 'N/A'; ?></td>
                    <td><?php echo $row['first_name'] . ' ' . $row['last_name']; ?></td>
                    <td><?php echo $row['mode_delivery']; ?></td>
                    <td><?php echo $row['delivery_outcome']; ?></td>
                    <td><?php echo $row['birth_weight_kg'] ? $row['birth_weight_kg'] . ' kg' : 'N/A'; ?></td>
                    <td><?php echo ($row['staff_first'] && $row['staff_last']) ? $row['staff_first'] . ' ' . $row['staff_last'] : 'N/A'; ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- Report Actions -->
    <div class="section no-print">
        <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
            <button onclick="window.print()" class="btn" style="background: #6c757d;">🖨️ Print Report</button>
            <button onclick="exportReport()" class="btn" style="background: #28a745;">📊 Export Excel</button>
            <button onclick="exportPDF()" class="btn" style="background: #dc3545;">📄 Export PDF</button>
            <button onclick="emailReport()" class="btn" style="background: #17a2b8;">📧 Email Report</button>
            <a href="../dashboard/index.php" class="btn">🏠 Back to Dashboard</a>
        </div>
    </div>

<script>
function exportReport() {
    const data = {
        date: '<?php echo $selected_date; ?>',
        type: '<?php echo $report_type; ?>',
        format: 'excel'
    };
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'export_report.php';
    
    Object.keys(data).forEach(key => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = key;
        input.value = data[key];
        form.appendChild(input);
    });
    
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}

function exportPDF() {
    window.open('export_report.php?date=<?php echo $selected_date; ?>&type=<?php echo $report_type; ?>&format=pdf', '_blank');
}

function emailReport() {
    const email = prompt('Enter email address to send report:');
    if (email) {
        fetch('email_report.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `email=${email}&date=<?php echo $selected_date; ?>&type=<?php echo $report_type; ?>`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ Report sent successfully!');
            } else {
                alert('❌ Failed to send report: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(() => alert('❌ Error sending report'));
    }
}
</script>

<style>
.stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin: 1rem 0;
}

.stat-card {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    text-align: center;
}

.stat-number {
    font-size: 2rem;
    font-weight: bold;
    color: #007cba;
    margin-bottom: 0.5rem;
}

.stat-label {
    color: #6c757d;
    font-size: 0.9rem;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 1rem;
}

th, td {
    padding: 0.75rem;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

th {
    background: #f8f9fa;
    font-weight: bold;
    color: #495057;
}

tr:hover {
    background: #f8f9fa;
}

@media print {
    .header, .nav, .breadcrumb, .no-print { display: none !important; }
    body { background: white !important; font-size: 11px; }
    .container { padding: 0 !important; }
    .section { box-shadow: none !important; border: 1px solid #000; margin-bottom: 1rem; page-break-inside: avoid; }
    .stats { display: grid !important; grid-template-columns: repeat(4, 1fr) !important; }
    .stat-card { border: 1px solid #000; margin: 0; }
    table { border-collapse: collapse; width: 100%; }
    th, td { border: 1px solid #000; padding: 4px; font-size: 10px; }
    h1, h2, h3 { color: #000 !important; }
    .stat-number { font-size: 1.2rem !important; }
}

@media (max-width: 768px) {
    .stats { grid-template-columns: repeat(2, 1fr); }
    table { font-size: 0.8rem; }
    th, td { padding: 0.5rem; }
}
</style>
</div>

<?php include '../layouts/footer.php'; ?>