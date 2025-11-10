<?php
session_start();
require_once '../../config/database.php';

$page_title = $_SESSION['role'] . ' Dashboard - Maternal Care System';
$show_nav = true;
$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

include '../layouts/header.php';
?>

    <div class="container">
        <!-- Welcome Section -->
        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 2rem; border-radius: 10px; margin-bottom: 2rem; text-align: center;">
            <h2 style="margin: 0 0 0.5rem 0;">Welcome back, <?php echo $_SESSION['username']; ?>! 👋</h2>
            <p style="margin: 0; opacity: 0.9;">Today is <?php echo date('l, F j, Y'); ?></p>
        </div>
        
        <!-- Quick Notifications -->
        <?php
        $unread_notifications = 0;
        $unread_messages = 0;
        
        if ($role == 'Patient') {
            $notif_query = $mysqli->query("SELECT COUNT(*) as count FROM notifications WHERE user_id = {$user_id} AND status = 'Pending'");
            $unread_notifications = $notif_query ? $notif_query->fetch_assoc()['count'] : 0;
            
            $msg_query = $mysqli->query("SELECT COUNT(*) as count FROM messages WHERE to_user_id = {$user_id} AND is_read = 0");
            $unread_messages = $msg_query ? $msg_query->fetch_assoc()['count'] : 0;
        } else {
            $msg_query = $mysqli->query("SELECT COUNT(*) as count FROM messages WHERE to_user_id = {$user_id} AND is_read = 0");
            $unread_messages = $msg_query ? $msg_query->fetch_assoc()['count'] : 0;
        }
        
        if ($unread_notifications > 0 || $unread_messages > 0):
        ?>
        <div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 1rem; border-radius: 8px; margin-bottom: 2rem;">
            <h4 style="color: #856404; margin: 0 0 0.5rem 0;">📢 You have updates!</h4>
            <?php if ($unread_notifications > 0): ?>
                <p style="margin: 0.25rem 0;">🔔 <a href="../notifications/index.php" style="color: #856404;"><?php echo $unread_notifications; ?> new notification(s)</a></p>
            <?php endif; ?>
            <?php if ($unread_messages > 0): ?>
                <p style="margin: 0.25rem 0;">💬 <a href="../messages/index.php" style="color: #856404;"><?php echo $unread_messages; ?> unread message(s)</a></p>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <div class="stats">
            <?php if ($role == 'Admin'): ?>
                <?php
                $stats = [
                    ['Total Users', 'SELECT COUNT(*) FROM users WHERE is_active = 1'],
                    ['Total Patients', 'SELECT COUNT(*) FROM patients WHERE is_active = 1'],
                    ['Active Pregnancies', 'SELECT COUNT(*) FROM pregnancies WHERE current_status = "Active"'],
                    ['Today Appointments', 'SELECT COUNT(*) FROM appointments WHERE appointment_date = CURDATE()']
                ];
                foreach ($stats as $stat) {
                    $result = $mysqli->query($stat[1]);
                    $count = $result ? $result->fetch_row()[0] : 0;
                    echo "<div class='stat-card'><div class='stat-number'>{$count}</div><div class='stat-label'>{$stat[0]}</div></div>";
                }
                ?>
            <?php elseif ($role == 'Doctor'): ?>
                <?php
                $doctor_stats = [
                    ['Today Appointments', "SELECT COUNT(*) FROM appointments WHERE doctor_id = {$user_id} AND appointment_date = CURDATE()"],
                    ['My Patients', "SELECT COUNT(DISTINCT patient_id) FROM appointments WHERE doctor_id = {$user_id}"],
                    ['Active Pregnancies', "SELECT COUNT(*) FROM pregnancies p JOIN appointments a ON p.patient_id = a.patient_id WHERE a.doctor_id = {$user_id} AND p.current_status = 'Active'"],
                    ['This Week Visits', "SELECT COUNT(*) FROM anc_visits WHERE recorded_by = {$user_id} AND WEEK(visit_date) = WEEK(CURDATE())"]
                ];
                foreach ($doctor_stats as $stat) {
                    $result = $mysqli->query($stat[1]);
                    $count = $result ? $result->fetch_row()[0] : 0;
                    echo "<div class='stat-card'><div class='stat-number'>{$count}</div><div class='stat-label'>{$stat[0]}</div></div>";
                }
                ?>
            <?php elseif ($role == 'Nurse'): ?>
                <?php
                $nurse_stats = [
                    ['Today Patients', "SELECT COUNT(DISTINCT a.patient_id) FROM appointments a WHERE a.appointment_date = CURDATE()"],
                    ['Pending Vitals', "SELECT COUNT(*) FROM appointments WHERE appointment_date = CURDATE() AND status = 'Scheduled'"],
                    ['This Week Visits', "SELECT COUNT(*) FROM anc_visits WHERE WEEK(visit_date) = WEEK(CURDATE())"],
                    ['Active Pregnancies', "SELECT COUNT(*) FROM pregnancies WHERE current_status = 'Active'"]
                ];
                foreach ($nurse_stats as $stat) {
                    $result = $mysqli->query($stat[1]);
                    $count = $result ? $result->fetch_row()[0] : 0;
                    echo "<div class='stat-card'><div class='stat-number'>{$count}</div><div class='stat-label'>{$stat[0]}</div></div>";
                }
                ?>
            <?php elseif ($role == 'Receptionist'): ?>
                <?php
                $receptionist_stats = [
                    ['Today Appointments', "SELECT COUNT(*) FROM appointments WHERE appointment_date = CURDATE()"],
                    ['New Patients Today', "SELECT COUNT(*) FROM patients WHERE DATE(created_at) = CURDATE()"],
                    ['Pending Payments', "SELECT COUNT(*) FROM payment_transactions WHERE status = 'Pending'"],
                    ['Total Patients', "SELECT COUNT(*) FROM patients WHERE is_active = 1"]
                ];
                foreach ($receptionist_stats as $stat) {
                    $result = $mysqli->query($stat[1]);
                    $count = $result ? $result->fetch_row()[0] : 0;
                    echo "<div class='stat-card'><div class='stat-number'>{$count}</div><div class='stat-label'>{$stat[0]}</div></div>";
                }
                ?>
            <?php elseif ($role == 'Patient'): ?>
                <?php
                $patient_query = $mysqli->prepare("SELECT patient_id FROM patients WHERE nid = (SELECT nid FROM users WHERE user_id = ?)");
                $patient_query->bind_param("i", $user_id);
                $patient_query->execute();
                $patient_result = $patient_query->get_result();
                $patient = $patient_result->fetch_assoc();
                
                if (!$patient) {
                    $user_query = $mysqli->prepare("SELECT nid, first_name, last_name, email FROM users WHERE user_id = ?");
                    $user_query->bind_param("i", $user_id);
                    $user_query->execute();
                    $user_data = $user_query->get_result()->fetch_assoc();
                    
                    $create_patient = $mysqli->prepare("INSERT INTO patients (nid, first_name, last_name, email, registered_by) VALUES (?, ?, ?, ?, ?)");
                    $create_patient->bind_param("ssssi", $user_data['nid'], $user_data['first_name'], $user_data['last_name'], $user_data['email'], $user_id);
                    $create_patient->execute();
                    $patient_id = $mysqli->insert_id;
                } else {
                    $patient_id = $patient['patient_id'];
                }
                
                $patient_stats = [
                    ['Upcoming Appointments', "SELECT COUNT(*) FROM appointments WHERE patient_id = {$patient_id} AND appointment_date >= CURDATE()"],
                    ['Current Week', "SELECT FLOOR(DATEDIFF(CURDATE(), lmp_date) / 7) FROM pregnancies WHERE patient_id = {$patient_id} AND current_status = 'Active' LIMIT 1"],
                    ['Total Visits', "SELECT COUNT(*) FROM anc_visits av JOIN pregnancies p ON av.pregnancy_id = p.pregnancy_id WHERE p.patient_id = {$patient_id}"],
                    ['Pending Bills', "SELECT COUNT(*) FROM payment_transactions WHERE patient_id = {$patient_id} AND status = 'Pending'"]
                ];
                foreach ($patient_stats as $stat) {
                    $result = $mysqli->query($stat[1]);
                    $count = $result ? $result->fetch_row()[0] : 0;
                    echo "<div class='stat-card'><div class='stat-number'>{$count}</div><div class='stat-label'>{$stat[0]}</div></div>";
                }
                ?>
            <?php endif; ?>
        </div>

        <?php if ($role == 'Admin'): ?>
            <div class="section">
                <h3>🛠️ Quick Actions</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-top: 1rem;">
                    <a href="../admin/users.php" class="btn" style="padding: 1rem; text-align: center; text-decoration: none;">
                        👥 Manage Users
                    </a>
                    <a href="../patients/list.php" class="btn" style="padding: 1rem; text-align: center; text-decoration: none;">
                        👩‍⚕️ View Patients
                    </a>
                    <a href="../reports/daily.php" class="btn" style="padding: 1rem; text-align: center; text-decoration: none;">
                        📈 Generate Reports
                    </a>
                    <a href="../messages/index.php" class="btn" style="padding: 1rem; text-align: center; text-decoration: none; background: #28a745;">
                        💬 Messages <?php if ($unread_messages > 0) echo "({$unread_messages})"; ?>
                    </a>
                </div>
            </div>
        <?php elseif ($role == 'Doctor'): ?>
            <div class="section">
                <h3>👨‍⚕️ Today's Schedule</h3>
                <?php
                $today_appointments = $mysqli->query("
                    SELECT a.*, p.first_name, p.last_name 
                    FROM appointments a 
                    JOIN patients p ON a.patient_id = p.patient_id 
                    WHERE a.doctor_id = {$user_id} AND a.appointment_date = CURDATE() 
                    ORDER BY a.appointment_time
                ");
                
                if ($today_appointments && $today_appointments->num_rows > 0):
                ?>
                    <div style="max-height: 300px; overflow-y: auto; margin-bottom: 1rem;">
                        <?php while ($apt = $today_appointments->fetch_assoc()): ?>
                            <div style="border: 1px solid #ddd; padding: 0.75rem; margin-bottom: 0.5rem; border-radius: 6px; background: #f8f9fa;">
                                <strong><?php echo date('g:i A', strtotime($apt['appointment_time'])); ?></strong> - 
                                <?php echo $apt['first_name'] . ' ' . $apt['last_name']; ?> 
                                <span style="color: #6c757d;">(<?php echo $apt['appointment_type']; ?>)</span>
                                <span style="float: right; background: <?php echo $apt['status'] == 'Confirmed' ? '#28a745' : '#ffc107'; ?>; color: white; padding: 0.2rem 0.5rem; border-radius: 10px; font-size: 0.75rem;">
                                    <?php echo $apt['status']; ?>
                                </span>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <p style="color: #6c757d; text-align: center; padding: 2rem;">No appointments scheduled for today</p>
                <?php endif; ?>
                <a href="../appointments/view.php" class="btn">View All Appointments</a>
            </div>
            <div class="section">
                <h3>🛠️ Quick Actions</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-top: 1rem;">
                    <a href="../patients/list.php" class="btn" style="padding: 1rem; text-align: center; text-decoration: none;">
                        📁 Patient Records
                    </a>
                    <a href="../visits/anc_visit.php" class="btn" style="padding: 1rem; text-align: center; text-decoration: none;">
                        📝 Record ANC Visit
                    </a>
                    <a href="../pregnancies/register.php" class="btn" style="padding: 1rem; text-align: center; text-decoration: none;">
                        🤰 Register Pregnancy
                    </a>
                    <a href="../messages/index.php" class="btn" style="padding: 1rem; text-align: center; text-decoration: none; background: #28a745;">
                        💬 Messages <?php if ($unread_messages > 0) echo "({$unread_messages})"; ?>
                    </a>
                </div>
            </div>
        <?php elseif ($role == 'Nurse'): ?>
            <div class="section">
                <h3>👩‍⚕️ Patient Care Tasks</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-top: 1rem;">
                    <a href="../patients/list.php" class="btn" style="padding: 1rem; text-align: center; text-decoration: none;">
                        📄 Patient List
                    </a>
                    <a href="../visits/anc_visit.php" class="btn" style="padding: 1rem; text-align: center; text-decoration: none;">
                        🌡️ Record Vitals
                    </a>
                    <a href="../pregnancies/register.php" class="btn" style="padding: 1rem; text-align: center; text-decoration: none;">
                        🤰 Pregnancy Care
                    </a>
                    <a href="../messages/index.php" class="btn" style="padding: 1rem; text-align: center; text-decoration: none; background: #28a745;">
                        💬 Messages <?php if ($unread_messages > 0) echo "({$unread_messages})"; ?>
                    </a>
                </div>
            </div>
        <?php elseif ($role == 'Receptionist'): ?>
            <div class="section">
                <h3>💼 Front Desk Operations</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-top: 1rem;">
                    <a href="../patients/register.php" class="btn" style="padding: 1rem; text-align: center; text-decoration: none;">
                        👤 Register Patient
                    </a>
                    <a href="../appointments/schedule.php" class="btn" style="padding: 1rem; text-align: center; text-decoration: none;">
                        📅 Schedule Appointment
                    </a>
                    <a href="../payments/process.php" class="btn" style="padding: 1rem; text-align: center; text-decoration: none;">
                        💳 Process Payment
                    </a>
                    <a href="../reports/daily.php" class="btn" style="padding: 1rem; text-align: center; text-decoration: none;">
                        📈 Daily Reports
                    </a>
                    <a href="../appointments/view.php" class="btn" style="padding: 1rem; text-align: center; text-decoration: none;">
                        🗓️ View Schedule
                    </a>
                    <a href="../messages/index.php" class="btn" style="padding: 1rem; text-align: center; text-decoration: none; background: #28a745;">
                        💬 Messages <?php if ($unread_messages > 0) echo "({$unread_messages})"; ?>
                    </a>
                </div>
            </div>
        <?php elseif ($role == 'Patient'): ?>
            <?php
            // Get current pregnancy info
            $pregnancy_query = $mysqli->query("
                SELECT p.*, FLOOR(DATEDIFF(CURDATE(), p.lmp_date) / 7) as current_week
                FROM pregnancies p 
                WHERE p.patient_id = {$patient_id} AND p.current_status = 'Active' 
                ORDER BY p.created_at DESC LIMIT 1
            ");
            $current_pregnancy = $pregnancy_query ? $pregnancy_query->fetch_assoc() : null;
            
            if ($current_pregnancy):
            ?>
            <div class="section">
                <h3>🤰 My Pregnancy Journey</h3>
                <div style="background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%); padding: 1.5rem; border-radius: 10px; color: #333; margin-bottom: 1rem;">
                    <h4 style="margin: 0 0 0.5rem 0;">🎆 Week <?php echo $current_pregnancy['current_week']; ?> of Pregnancy</h4>
                    <p style="margin: 0;">EDD: <?php echo date('M d, Y', strtotime($current_pregnancy['expected_delivery_date'])); ?></p>
                    <p style="margin: 0.25rem 0 0 0; font-size: 0.9rem; opacity: 0.8;">Risk Level: <?php echo $current_pregnancy['risk_level']; ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="section">
                <h3>🛠️ Quick Actions</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 1rem; margin-top: 1rem;">
                    <a href="../appointments/book.php" class="btn" style="padding: 1rem; text-align: center; text-decoration: none; background: #007cba;">
                        📅 Book Appointment
                    </a>
                    <a href="../appointments/my_appointments.php" class="btn" style="padding: 1rem; text-align: center; text-decoration: none;">
                        🗓️ My Appointments
                    </a>
                    <a href="../pregnancies/my_records.php" class="btn" style="padding: 1rem; text-align: center; text-decoration: none;">
                        📁 Pregnancy Records
                    </a>
                    <a href="../visits/my_visits.php" class="btn" style="padding: 1rem; text-align: center; text-decoration: none;">
                        🏥 Visit History
                    </a>
                    <a href="../payments/my_bills.php" class="btn" style="padding: 1rem; text-align: center; text-decoration: none;">
                        💳 View Bills
                    </a>
                    <a href="../messages/index.php" class="btn" style="padding: 1rem; text-align: center; text-decoration: none; background: #28a745;">
                        💬 Messages <?php if ($unread_messages > 0) echo "({$unread_messages})"; ?>
                    </a>
                    <a href="../patients/profile.php" class="btn" style="padding: 1rem; text-align: center; text-decoration: none;">
                        👤 My Profile
                    </a>
                    <?php if ($unread_notifications > 0): ?>
                    <a href="../notifications/index.php" class="btn" style="padding: 1rem; text-align: center; text-decoration: none; background: #dc3545;">
                        🔔 Notifications (<?php echo $unread_notifications; ?>)
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

<?php include '../layouts/footer.php'; ?>