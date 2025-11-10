<?php
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Maternal Care System'; ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background-color: #f8f9fa; min-height: 100vh; display: flex; flex-direction: column; }
        .header { background: #0077B6; color: white; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; }
        .nav { background: #023E8A; padding: 1rem 2rem; }
        .nav a { color: white; text-decoration: none; margin-right: 2rem; padding: 0.5rem 1rem; border-radius: 4px; }
        .nav a:hover { background: rgba(255,255,255,0.1); }
        .breadcrumb { background: #e9ecef; padding: 0.5rem 2rem; font-size: 14px; }
        .breadcrumb a { color: #0077B6; text-decoration: none; }
        .breadcrumb a:hover { text-decoration: underline; }
        .container { padding: 2rem; flex: 1; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
        .stat-card { background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .stat-number { font-size: 2rem; font-weight: bold; color: #0077B6; }
        .stat-label { color: #6c757d; margin-top: 0.5rem; }
        .section { background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 1rem; }
        .btn { background: #0077B6; color: white; padding: 0.5rem 1rem; border: none; border-radius: 4px; text-decoration: none; display: inline-block; margin-right: 0.5rem; }
        .btn:hover { background: #023E8A; }
        .btn-secondary { background: #6c757d; }
        .btn-secondary:hover { background: #5a6268; }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f8f9fa; }
        .alert { padding: 1rem; margin-bottom: 1rem; border-radius: 4px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .footer { background: #343a40; color: white; padding: 2rem; margin-top: auto; }
        .footer-content { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem; }
        .footer-section h4 { margin-bottom: 1rem; color: #0077B6; }
        .footer-section p, .footer-section a { color: #adb5bd; text-decoration: none; }
        .footer-section a:hover { color: white; }
        .footer-bottom { text-align: center; margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #495057; color: #6c757d; }
        .form-container { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .form-row { display: flex; gap: 1rem; margin-bottom: 1rem; }
        .form-group { flex: 1; margin-bottom: 1rem; }
        label { display: block; margin-bottom: 0.5rem; font-weight: 500; }
        input, select, textarea { width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; }
        input:focus, select:focus, textarea:focus { outline: none; border-color: #0077B6; }
    </style>
</head>
<body>
    <div class="header">
        <h1>
            <?php 
            $icons = ['Admin' => '🏥', 'Doctor' => '👨⚕️', 'Nurse' => '👩⚕️', 'Receptionist' => '🏢', 'Patient' => '🤱'];
            echo $icons[$_SESSION['role']] . ' ' . ($page_header ?? $_SESSION['role'] . ' Dashboard');
            ?>
        </h1>
        <div style="display: flex; align-items: center; gap: 1rem;">
            <a href="../notifications/index.php" style="color: white; text-decoration: none; position: relative;">
                🔔
                <?php
                $unread_count = 0;
                if (isset($_SESSION['user_id'])) {
                    $unread_query = $mysqli->query("SELECT COUNT(*) as count FROM notifications WHERE user_id = {$_SESSION['user_id']} AND status = 'Pending'");
                    if ($unread_query) {
                        $unread_count = $unread_query->fetch_assoc()['count'];
                    }
                }
                if ($unread_count > 0): ?>
                    <span style="position: absolute; top: -8px; right: -8px; background: #dc3545; color: white; border-radius: 50%; width: 18px; height: 18px; font-size: 10px; display: flex; align-items: center; justify-content: center;"><?php echo $unread_count; ?></span>
                <?php endif; ?>
            </a>
            <span style="color: white;">Welcome, <?php echo $_SESSION['full_name']; ?></span>
            <a href="../auth/login.php?logout=1" style="color: white;">Logout</a>
        </div>
    </div>

    <?php if (isset($show_nav) && $show_nav): ?>
    <div class="nav">
        <a href="../dashboard/index.php">Dashboard</a>
        <?php if ($_SESSION['role'] == 'Admin'): ?>
            <a href="../admin/users.php">User Management</a>
            <a href="../patients/list.php">Patient Management</a>
            <a href="../laboratory/index.php">Laboratory</a>
            <a href="../pharmacy/index.php">Pharmacy</a>
            <a href="../ai/risk_dashboard.php">AI Risk</a>
            <a href="../reports/daily.php">Reports</a>
            <a href="../messages/index.php">Messages</a>
            <a href="../notifications/index.php">Notifications</a>
        <?php elseif ($_SESSION['role'] == 'Doctor'): ?>
            <a href="../appointments/view.php">Appointments</a>
            <a href="../patients/list.php">My Patients</a>
            <a href="../visits/anc_visit.php">ANC Visits</a>
            <a href="../laboratory/index.php">Laboratory</a>
            <a href="../pharmacy/index.php">Pharmacy</a>
            <a href="../ai/risk_dashboard.php">AI Risk</a>
            <a href="../pregnancies/register.php">Register Pregnancy</a>
            <a href="../deliveries/manage.php">Deliveries</a>
            <a href="../messages/index.php">Messages</a>
            <a href="../notifications/index.php">Notifications</a>
        <?php elseif ($_SESSION['role'] == 'Nurse'): ?>
            <a href="../patients/list.php">Patient Care</a>
            <a href="../visits/anc_visit.php">Vital Signs</a>
            <a href="../laboratory/index.php">Laboratory</a>
            <a href="../pharmacy/index.php">Pharmacy</a>
            <a href="../messages/index.php">Messages</a>
            <a href="../notifications/index.php">Notifications</a>
        <?php elseif ($_SESSION['role'] == 'Receptionist'): ?>
            <a href="../patients/register.php">Patient Registration</a>
            <a href="../appointments/schedule.php">Appointments</a>
            <a href="../payments/process.php">Payments</a>
            <a href="../reports/daily.php">Reports</a>
            <a href="../messages/index.php">Messages</a>
            <a href="../notifications/index.php">Notifications</a>
        <?php elseif ($_SESSION['role'] == 'Patient'): ?>
            <a href="../appointments/my_appointments.php">My Appointments</a>
            <a href="../pregnancies/my_records.php">Pregnancy Records</a>
            <a href="../visits/my_visits.php">Visit History</a>
            <a href="../payments/my_bills.php">Billing</a>
            <a href="../messages/index.php">Messages</a>
            <a href="../notifications/index.php">Notifications</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if (isset($breadcrumb)): ?>
    <div class="breadcrumb">
        <a href="../dashboard/index.php">Dashboard</a>
        <?php foreach ($breadcrumb as $item): ?>
            <?php if (isset($item['url'])): ?>
                → <a href="<?php echo $item['url']; ?>"><?php echo $item['title']; ?></a>
            <?php else: ?>
                → <?php echo $item['title']; ?>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>