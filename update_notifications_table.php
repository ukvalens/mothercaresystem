<?php
require_once 'app/config/database.php';

echo "<h2>Updating Notifications Table</h2>";

// Add missing columns to notifications table
$updates = [
    "ALTER TABLE notifications ADD COLUMN reference_id INT NULL COMMENT 'ID of related record (appointment_id, pregnancy_id, etc.)'",
    "ALTER TABLE notifications ADD COLUMN reference_type VARCHAR(50) NULL COMMENT 'Type of reference (appointment, pregnancy, payment, etc.)'",
    "ALTER TABLE notifications MODIFY COLUMN notification_type ENUM('Appointment','Clinical Alert','Payment','Financial','Pregnancy','System Activity','Medical Record','Delivery','Message','System Alert','Appointment Reminder','Payment Alert','Financial Alert','User Management') NOT NULL"
];

foreach ($updates as $sql) {
    if ($mysqli->query($sql)) {
        echo "✅ Successfully executed: " . substr($sql, 0, 50) . "...<br>";
    } else {
        echo "❌ Error: " . $mysqli->error . "<br>";
    }
}

// Add indexes for better performance
$indexes = [
    "CREATE INDEX idx_reference ON notifications(reference_type, reference_id)",
    "CREATE INDEX idx_notification_type ON notifications(notification_type)"
];

foreach ($indexes as $sql) {
    if ($mysqli->query($sql)) {
        echo "✅ Index created successfully<br>";
    } else {
        echo "ℹ️ Index may already exist: " . $mysqli->error . "<br>";
    }
}

echo "<h3>✅ Notifications table updated successfully!</h3>";
echo "<a href='app/views/notifications/index.php' style='background: #007cba; color: white; padding: 1rem; text-decoration: none; border-radius: 4px;'>Test Notifications Page</a>";
?>