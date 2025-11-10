<?php
require_once 'app/config/database.php';
require_once 'app/config/notification_system.php';

echo "<h2>Creating Sample Notifications</h2>";

// Get a user ID to create notifications for (assuming admin user exists)
$user_result = $mysqli->query("SELECT user_id FROM users WHERE role = 'Admin' LIMIT 1");
if ($user_result && $user_result->num_rows > 0) {
    $admin_user = $user_result->fetch_assoc();
    $user_id = $admin_user['user_id'];
    
    // Create sample notifications
    $sample_notifications = [
        [
            'subject' => 'New Patient Registration',
            'message' => 'A new patient has been registered in the system and requires initial assessment.',
            'type' => 'System Activity'
        ],
        [
            'subject' => 'High Blood Pressure Alert',
            'message' => 'Patient shows elevated blood pressure readings (150/95) during recent visit. Immediate attention required.',
            'type' => 'Clinical Alert'
        ],
        [
            'subject' => 'Appointment Reminder',
            'message' => 'You have 3 appointments scheduled for tomorrow. Please review your schedule.',
            'type' => 'Appointment Reminder'
        ],
        [
            'subject' => 'Payment Overdue',
            'message' => 'Patient payment of RWF 25,000 is now 5 days overdue. Follow-up required.',
            'type' => 'Payment Alert'
        ],
        [
            'subject' => 'Lab Results Available',
            'message' => 'New laboratory test results are available for review in the system.',
            'type' => 'Medical Record'
        ],
        [
            'subject' => 'System Maintenance Notice',
            'message' => 'Scheduled system maintenance will occur tonight from 2:00 AM to 4:00 AM.',
            'type' => 'System Alert'
        ]
    ];
    
    foreach ($sample_notifications as $notif) {
        $stmt = $mysqli->prepare("INSERT INTO notifications (user_id, subject, message, notification_type, status) VALUES (?, ?, ?, ?, 'Pending')");
        $stmt->bind_param("isss", $user_id, $notif['subject'], $notif['message'], $notif['type']);
        
        if ($stmt->execute()) {
            echo "✅ Created: " . $notif['subject'] . "<br>";
        } else {
            echo "❌ Failed to create: " . $notif['subject'] . "<br>";
        }
    }
    
    echo "<h3>✅ Sample notifications created successfully!</h3>";
    echo "<p>User ID: $user_id</p>";
    
} else {
    echo "❌ No admin user found. Please create a user first.";
}

echo "<br><a href='app/views/notifications/index.php' style='background: #007cba; color: white; padding: 1rem; text-decoration: none; border-radius: 4px;'>View Notifications</a>";
?>