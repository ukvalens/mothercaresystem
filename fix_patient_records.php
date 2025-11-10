<?php
require_once 'app/config/database.php';

echo "<h2>Creating Missing Patient Records</h2>";

// Find users with Patient role who don't have patient records
$missing_patients = $mysqli->query("
    SELECT u.user_id, u.nid, u.first_name, u.last_name, u.email 
    FROM users u 
    LEFT JOIN patients p ON u.nid = p.nid 
    WHERE u.role = 'Patient' AND p.patient_id IS NULL
");

if ($missing_patients && $missing_patients->num_rows > 0) {
    while ($user = $missing_patients->fetch_assoc()) {
        $stmt = $mysqli->prepare("INSERT INTO patients (nid, first_name, last_name, email, registered_by) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssi", $user['nid'], $user['first_name'], $user['last_name'], $user['email'], $user['user_id']);
        
        if ($stmt->execute()) {
            echo "✓ Created patient record for {$user['first_name']} {$user['last_name']}<br>";
        } else {
            echo "✗ Error creating patient record for {$user['first_name']} {$user['last_name']}<br>";
        }
    }
} else {
    echo "✓ All patient users already have patient records<br>";
}

echo "<h3>Patient Records Fixed!</h3>";
echo "<p><a href='app/views/auth/login.php'>Go to Login</a></p>";

$mysqli->close();
?>