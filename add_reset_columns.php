<?php
require_once 'app/config/database.php';

// Add reset token columns to users table
$add_columns = "
ALTER TABLE users 
ADD COLUMN reset_token VARCHAR(64) NULL,
ADD COLUMN reset_expires TIMESTAMP NULL,
ADD INDEX idx_reset_token (reset_token)
";

if ($mysqli->query($add_columns)) {
    echo "✅ Reset token columns added successfully!";
} else {
    echo "❌ Error: " . $mysqli->error;
}

$mysqli->close();
?>