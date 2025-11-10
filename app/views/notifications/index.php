<?php
session_start();
require_once '../../config/database.php';
require_once '../../config/notification_system.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

// Handle actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['mark_read'])) {
        $notification_id = $_POST['notification_id'];
        $stmt = $mysqli->prepare("UPDATE notifications SET status = 'Read' WHERE notification_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $notification_id, $_SESSION['user_id']);
        $stmt->execute();
        echo json_encode(['success' => true]);
        exit();
    }
    
    if (isset($_POST['mark_all_read'])) {
        $stmt = $mysqli->prepare("UPDATE notifications SET status = 'Read' WHERE user_id = ? AND status = 'Pending'");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        echo json_encode(['success' => true]);
        exit();
    }
}

// Mark notification as read if requested via GET
if (isset($_GET['mark_read']) && $_GET['mark_read']) {
    $notification_id = $_GET['mark_read'];
    $stmt = $mysqli->prepare("UPDATE notifications SET status = 'Read' WHERE notification_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $notification_id, $_SESSION['user_id']);
    $stmt->execute();
}

$page_title = 'Notifications';
$page_header = '🔔 System Notifications';
$show_nav = true;

include '../layouts/header.php';

// Get filter
$filter = $_GET['filter'] ?? 'all';
$filter_sql = '';
if ($filter != 'all') {
    if ($filter == 'unread') {
        $filter_sql = "AND n.status = 'Pending'";
    } else {
        $filter_sql = "AND n.notification_type = '" . $mysqli->real_escape_string($filter) . "'";
    }
}

// Get notifications with enhanced details
$notifications = $mysqli->query("
    SELECT n.*, 
           p.first_name, p.last_name,
           CASE 
               WHEN n.reference_type = 'appointment' AND n.reference_id IS NOT NULL THEN (
                   SELECT CONCAT('Dr. ', u.first_name, ' ', u.last_name) 
                   FROM appointments a 
                   JOIN users u ON a.doctor_id = u.user_id 
                   WHERE a.appointment_id = n.reference_id
               )
               WHEN n.reference_type = 'pregnancy' AND n.reference_id IS NOT NULL THEN (
                   SELECT CONCAT('Week ', FLOOR(DATEDIFF(CURDATE(), pr.lmp_date) / 7))
                   FROM pregnancies pr 
                   WHERE pr.pregnancy_id = n.reference_id
               )
               ELSE NULL
           END as reference_info
    FROM notifications n
    LEFT JOIN patients p ON n.patient_id = p.patient_id
    WHERE n.user_id = {$_SESSION['user_id']} $filter_sql
    ORDER BY n.status ASC, n.created_at DESC
    LIMIT 100
");

// Get notification counts by type
$counts = $mysqli->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as unread,
        SUM(CASE WHEN notification_type LIKE '%Appointment%' THEN 1 ELSE 0 END) as appointments,
        SUM(CASE WHEN notification_type = 'Clinical Alert' THEN 1 ELSE 0 END) as clinical,
        SUM(CASE WHEN notification_type LIKE '%Payment%' OR notification_type LIKE '%Financial%' THEN 1 ELSE 0 END) as financial,
        SUM(CASE WHEN notification_type LIKE '%System%' THEN 1 ELSE 0 END) as system
    FROM notifications 
    WHERE user_id = {$_SESSION['user_id']}
")->fetch_assoc();
?>

<div class="container">
    <!-- Statistics -->
    <div class="stats" style="margin-bottom: 2rem;">
        <div class="stat-card">
            <div class="stat-number"><?php echo $counts['total']; ?></div>
            <div class="stat-label">Total</div>
        </div>
        <div class="stat-card">
            <div class="stat-number" style="color: #dc3545;"><?php echo $counts['unread']; ?></div>
            <div class="stat-label">Unread</div>
        </div>
        <div class="stat-card">
            <div class="stat-number" style="color: #0077B6;"><?php echo $counts['appointments']; ?></div>
            <div class="stat-label">Appointments</div>
        </div>
        <div class="stat-card">
            <div class="stat-number" style="color: #dc3545;"><?php echo $counts['clinical']; ?></div>
            <div class="stat-label">Clinical Alerts</div>
        </div>
        <div class="stat-card">
            <div class="stat-number" style="color: #28a745;"><?php echo $counts['financial']; ?></div>
            <div class="stat-label">Financial</div>
        </div>
    </div>
    
    <!-- Filters and Actions -->
    <div style="margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            <a href="../dashboard/index.php" class="btn btn-secondary">← Back</a>
            <select onchange="filterNotifications(this.value)" style="padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                <option value="all" <?php echo $filter == 'all' ? 'selected' : ''; ?>>All Notifications</option>
                <option value="unread" <?php echo $filter == 'unread' ? 'selected' : ''; ?>>Unread Only</option>
                <option value="Appointment" <?php echo $filter == 'Appointment' ? 'selected' : ''; ?>>Appointments</option>
                <option value="Clinical Alert" <?php echo $filter == 'Clinical Alert' ? 'selected' : ''; ?>>Clinical Alerts</option>
                <option value="Payment" <?php echo $filter == 'Payment' ? 'selected' : ''; ?>>Payments</option>
                <option value="System Activity" <?php echo $filter == 'System Activity' ? 'selected' : ''; ?>>System Activity</option>
            </select>
        </div>
        <div>
            <button onclick="markAllRead()" class="btn">Mark All Read</button>
            <button onclick="emailNotifications()" class="btn" style="background: #28a745;">📧 Email All</button>
        </div>
    </div>

    <div class="section">
        <h3 style="color: #0077B6; margin-bottom: 1rem;">Your Notifications</h3>
        
        <?php if ($notifications && $notifications->num_rows > 0): ?>
            <div style="max-height: 600px; overflow-y: auto;">
                <?php while ($notification = $notifications->fetch_assoc()): ?>
                    <div style="border: 1px solid #ddd; border-radius: 8px; padding: 1rem; margin-bottom: 1rem; background: <?php echo $notification['status'] == 'Pending' ? '#fff3cd' : '#ffffff'; ?>; border-left: 4px solid <?php 
                        $colors = [
                            'Clinical Alert' => '#dc3545',
                            'Appointment' => '#0077B6', 
                            'Payment' => '#28a745',
                            'Financial' => '#28a745',
                            'Pregnancy' => '#e91e63',
                            'System Activity' => '#6c757d'
                        ];
                        echo $colors[$notification['notification_type']] ?? '#0077B6';
                    ?>;">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.75rem;">
                            <div style="flex: 1;">
                                <h5 style="color: #0077B6; margin: 0 0 0.25rem 0; display: flex; align-items: center; gap: 0.5rem;">
                                    <?php if ($notification['status'] == 'Pending'): ?>
                                        <span style="color: #dc3545; font-size: 1.2rem;">●</span>
                                    <?php endif; ?>
                                    <?php 
                                    $icons = [
                                        'Clinical Alert' => '🚨',
                                        'Appointment' => '📅',
                                        'Payment' => '💳',
                                        'Financial' => '💰',
                                        'Pregnancy' => '🤱',
                                        'System Activity' => '⚙️',
                                        'Medical Record' => '📋',
                                        'Delivery' => '👶'
                                    ];
                                    echo $icons[$notification['notification_type']] ?? '📢';
                                    ?>
                                    <?php echo htmlspecialchars($notification['subject']); ?>
                                </h5>
                                <div style="display: flex; gap: 1rem; align-items: center; margin-bottom: 0.5rem;">
                                    <span style="background: <?php echo $colors[$notification['notification_type']] ?? '#0077B6'; ?>; color: white; padding: 0.2rem 0.6rem; border-radius: 12px; font-size: 0.75rem; font-weight: bold;">
                                        <?php echo $notification['notification_type']; ?>
                                    </span>
                                    <small style="color: #6c757d;"><?php echo date('M d, Y H:i', strtotime($notification['created_at'])); ?></small>
                                    <?php if ($notification['reference_info']): ?>
                                        <small style="color: #6c757d; background: #f8f9fa; padding: 0.2rem 0.5rem; border-radius: 10px;">
                                            <?php echo $notification['reference_info']; ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <p style="margin-bottom: 1rem; line-height: 1.5;"><?php echo nl2br(htmlspecialchars($notification['message'])); ?></p>
                        
                        <?php if ($notification['first_name']): ?>
                            <div style="background: #f8f9fa; padding: 0.5rem; border-radius: 6px; margin-bottom: 1rem;">
                                <strong>Patient:</strong> <?php echo $notification['first_name'] . ' ' . $notification['last_name']; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <?php if ($notification['status'] == 'Pending'): ?>
                                    <span style="background: #ffc107; color: #000; padding: 0.3rem 0.8rem; border-radius: 15px; font-size: 0.8rem; font-weight: bold;">
                                        🔔 New
                                    </span>
                                <?php else: ?>
                                    <span style="background: #28a745; color: white; padding: 0.3rem 0.8rem; border-radius: 15px; font-size: 0.8rem;">
                                        ✓ Read
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <div style="display: flex; gap: 0.5rem;">
                                <?php if ($notification['status'] == 'Pending'): ?>
                                    <button onclick="markRead(<?php echo $notification['notification_id']; ?>)" class="btn" style="font-size: 12px; padding: 0.4rem 0.8rem; background: #17a2b8;">
                                        📖 Mark Read
                                    </button>
                                <?php endif; ?>
                                <button onclick="emailSingle(<?php echo $notification['notification_id']; ?>)" class="btn" style="background: #28a745; font-size: 12px; padding: 0.4rem 0.8rem;">
                                    📧 Email
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 3rem;">
                <h4 style="color: #6c757d; margin-bottom: 1rem;">No Notifications</h4>
                <p style="color: #6c757d;">You don't have any notifications yet.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function filterNotifications(filter) {
    window.location.href = '?filter=' + filter;
}

function markRead(notificationId) {
    fetch('index.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'mark_read=1&notification_id=' + notificationId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    })
    .catch(() => location.reload());
}

function markAllRead() {
    if (confirm('Mark all notifications as read?')) {
        fetch('index.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'mark_all_read=1'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        })
        .catch(() => location.reload());
    }
}

function emailNotifications() {
    if (confirm('Send all notifications to your email?')) {
        fetch('email_notifications.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=email_all'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ Notifications sent to your email!');
            } else {
                alert('❌ Error sending emails: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            alert('❌ Error sending emails');
        });
    }
}

function emailSingle(notificationId) {
    fetch('email_notifications.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=email_single&notification_id=' + notificationId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ Notification sent to your email!');
        } else {
            alert('❌ Error sending email: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        alert('❌ Error sending email');
    });
}

// Auto-refresh every 30 seconds for new notifications
setInterval(() => {
    if (document.visibilityState === 'visible') {
        fetch('check_new_notifications.php')
        .then(response => response.json())
        .then(data => {
            if (data.new_count > 0) {
                const currentUnread = parseInt(document.querySelector('.stat-card .stat-number[style*="color: #dc3545"]')?.textContent || '0');
                if (data.total_unread > currentUnread) {
                    location.reload();
                }
            }
        })
        .catch(() => {});
    }
}, 30000);
</script>

<?php include '../layouts/footer.php'; ?>