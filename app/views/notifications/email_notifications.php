<?php
session_start();
require_once '../../config/database.php';
require_once '../../config/email_config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

$action = $_POST['action'] ?? '';

if ($action == 'email_all') {
    // Get user email
    $user_query = $mysqli->prepare("SELECT email, CONCAT(first_name, ' ', last_name) as full_name FROM users WHERE user_id = ?");
    $user_query->bind_param("i", $_SESSION['user_id']);
    $user_query->execute();
    $user_data = $user_query->get_result()->fetch_assoc();
    
    if (!$user_data || !$user_data['email']) {
        echo json_encode(['success' => false, 'error' => 'No email address found']);
        exit();
    }
    
    // Get all notifications
    $notifications = $mysqli->query("
        SELECT n.*, p.first_name, p.last_name
        FROM notifications n
        LEFT JOIN patients p ON n.patient_id = p.patient_id
        WHERE n.user_id = {$_SESSION['user_id']}
        ORDER BY n.created_at DESC
        LIMIT 20
    ");
    
    if ($notifications->num_rows == 0) {
        echo json_encode(['success' => false, 'error' => 'No notifications to send']);
        exit();
    }
    
    $content = '<h2>Your Recent Notifications</h2>';
    while ($notif = $notifications->fetch_assoc()) {
        $content .= '
        <div class="highlight" style="margin-bottom: 20px;">
            <h3>' . htmlspecialchars($notif['subject']) . '</h3>
            <p><strong>Type:</strong> ' . htmlspecialchars($notif['notification_type']) . '</p>
            <p>' . nl2br(htmlspecialchars($notif['message'])) . '</p>
            <p><small>Date: ' . date('M d, Y H:i', strtotime($notif['created_at'])) . '</small></p>
        </div>';
    }
    
    $html = getEmailTemplate('Your Notifications Summary', $content);
    $result = sendEmail($user_data['email'], $user_data['full_name'], 'Notifications Summary - Maternal Care System', $html, true);
    
    echo json_encode(['success' => $result]);
    
} elseif ($action == 'email_single') {
    $notification_id = $_POST['notification_id'] ?? 0;
    
    // Get user email
    $user_query = $mysqli->prepare("SELECT email, CONCAT(first_name, ' ', last_name) as full_name FROM users WHERE user_id = ?");
    $user_query->bind_param("i", $_SESSION['user_id']);
    $user_query->execute();
    $user_data = $user_query->get_result()->fetch_assoc();
    
    if (!$user_data || !$user_data['email']) {
        echo json_encode(['success' => false, 'error' => 'No email address found']);
        exit();
    }
    
    // Get notification
    $notif_query = $mysqli->prepare("
        SELECT n.*, p.first_name, p.last_name
        FROM notifications n
        LEFT JOIN patients p ON n.patient_id = p.patient_id
        WHERE n.notification_id = ? AND n.user_id = ?
    ");
    $notif_query->bind_param("ii", $notification_id, $_SESSION['user_id']);
    $notif_query->execute();
    $notif = $notif_query->get_result()->fetch_assoc();
    
    if (!$notif) {
        echo json_encode(['success' => false, 'error' => 'Notification not found']);
        exit();
    }
    
    $result = sendNotificationEmail(
        $user_data['email'],
        $user_data['full_name'],
        $notif['subject'],
        $notif['message']
    );
    
    echo json_encode(['success' => $result]);
    
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
}
?>