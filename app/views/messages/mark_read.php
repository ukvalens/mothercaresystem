<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    exit();
}

// Mark all messages to current user as read
$stmt = $mysqli->prepare("UPDATE messages SET is_read = 1 WHERE to_user_id = ? AND is_read = 0");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();

echo json_encode(['success' => true]);
?>