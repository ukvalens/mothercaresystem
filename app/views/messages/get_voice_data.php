<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_POST['message_id'])) {
    echo json_encode(['error' => 'Invalid request']);
    exit();
}

$message_id = intval($_POST['message_id']);

$stmt = $mysqli->prepare("SELECT audio_data FROM messages WHERE message_id = ? AND (from_user_id = ? OR to_user_id = ?)");
$stmt->bind_param("iii", $message_id, $_SESSION['user_id'], $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode(['audio_data' => $row['audio_data']]);
} else {
    echo json_encode(['error' => 'Audio not found']);
}
?>