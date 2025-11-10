<?php
require_once 'database.php';

$add_audio_column = "ALTER TABLE messages ADD COLUMN audio_data LONGTEXT NULL COMMENT 'Base64 encoded audio data'";

if ($mysqli->query($add_audio_column) === TRUE) {
    echo "✓ Audio column added to messages table successfully";
} else {
    echo "✗ Error adding audio column: " . $mysqli->error;
}

$mysqli->close();
?>