<?php
session_start();
require_once '../../config/database.php';

// Get messages with audio data
$result = $mysqli->query("SELECT message_id, subject, audio_data FROM messages WHERE audio_data IS NOT NULL AND LENGTH(audio_data) > 100 ORDER BY message_id DESC LIMIT 5");

echo "<h3>Direct Audio Test</h3>";

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px;'>";
        echo "<h4>Message ID: " . $row['message_id'] . "</h4>";
        echo "<p>Subject: " . htmlspecialchars($row['subject']) . "</p>";
        echo "<p>Audio Data Length: " . strlen($row['audio_data']) . " characters</p>";
        
        if ($row['audio_data']) {
            echo "<audio controls style='width: 100%;'>";
            echo "<source src='" . $row['audio_data'] . "' type='audio/wav'>";
            echo "Your browser does not support the audio element.";
            echo "</audio>";
            
            echo "<br><button onclick='testPlay(" . $row['message_id'] . ")'>🎵 Test Play</button>";
            echo "<script>";
            echo "function testPlay(id) {";
            echo "  const audio = new Audio('" . addslashes($row['audio_data']) . "');";
            echo "  audio.play().then(() => console.log('Playing audio for message', id)).catch(e => console.error('Play failed:', e));";
            echo "}";
            echo "</script>";
        }
        
        echo "</div>";
    }
} else {
    echo "<p>No messages with audio data found.</p>";
}
?>