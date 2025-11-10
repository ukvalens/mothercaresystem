<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

// Delete all messages where user is sender or recipient
$stmt = $mysqli->prepare("DELETE FROM messages WHERE from_user_id = ? OR to_user_id = ?");
$stmt->bind_param("ii", $_SESSION['user_id'], $_SESSION['user_id']);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'deleted' => $stmt->affected_rows]);
} else {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
?>