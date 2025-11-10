<?php
require_once 'database.php';

echo "<h2>Adding Password Reset Functionality</h2>";

// Add reset_token and reset_expires columns to users table
$add_columns = "
ALTER TABLE users 
ADD COLUMN reset_token VARCHAR(64) NULL,
ADD COLUMN reset_expires TIMESTAMP NULL,
ADD INDEX idx_reset_token (reset_token)";

if ($mysqli->query($add_columns) === TRUE) {
    echo "✓ Password reset columns added successfully<br>";
} else {
    if (strpos($mysqli->error, 'Duplicate column name') !== false) {
        echo "✓ Password reset columns already exist<br>";
    } else {
        echo "✗ Error adding password reset columns: " . $mysqli->error . "<br>";
    }
}

echo "<h3>Password Reset Setup Complete!</h3>";
echo "<p>You can now use the forgot password functionality.</p>";

$mysqli->close();
?>