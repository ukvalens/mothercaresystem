<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['message_id'])) {
    exit();
}

$message_id = $_POST['message_id'];

// Only allow deletion if user is sender or recipient
$stmt = $mysqli->prepare("DELETE FROM messages WHERE message_id = ? AND (from_user_id = ? OR to_user_id = ?)");
$stmt->bind_param("iii", $message_id, $_SESSION['user_id'], $_SESSION['user_id']);
$stmt->execute();

echo json_encode(['success' => true]);
?>