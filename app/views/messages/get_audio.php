<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

// Debug info
$debug = [
    'session_user_id' => isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NOT SET',
    'message_id_param' => isset($_GET['message_id']) ? $_GET['message_id'] : 'NOT SET',
    'all_get_params' => $_GET
];

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'No user session', 'debug' => $debug]);
    exit();
}

if (!isset($_GET['message_id'])) {
    echo json_encode(['error' => 'No message_id parameter', 'debug' => $debug]);
    exit();
}

$message_id = intval($_GET['message_id']);

try {
    $stmt = $mysqli->prepare("SELECT audio_data, from_user_id, to_user_id FROM messages WHERE message_id = ?");
    $stmt->bind_param("i", $message_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Check if user has access to this message
        if ($row['from_user_id'] == $_SESSION['user_id'] || $row['to_user_id'] == $_SESSION['user_id']) {
            echo json_encode([
                'audio_data' => $row['audio_data'],
                'has_audio' => !empty($row['audio_data']),
                'audio_length' => strlen($row['audio_data'] ?? '')
            ]);
        } else {
            echo json_encode(['error' => 'Access denied']);
        }
    } else {
        echo json_encode(['error' => 'Message not found', 'message_id' => $message_id]);
    }
} catch (Exception $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>