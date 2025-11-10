<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header('Location: ../auth/login.php');
    exit();
}
require_once '../../config/database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Management - Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background-color: #f8f9fa; }
        .header { background: #0077B6; color: white; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; }
        .nav { background: #023E8A; padding: 1rem 2rem; }
        .nav a { color: white; text-decoration: none; margin-right: 2rem; padding: 0.5rem 1rem; border-radius: 4px; }
        .nav a:hover { background: rgba(255,255,255,0.1); }
        .container { padding: 2rem; }
        .section { background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f8f9fa; }
        .btn { background: #0077B6; color: white; padding: 0.25rem 0.5rem; border: none; border-radius: 4px; text-decoration: none; font-size: 12px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🤱 Patient Management</h1>
        <div>
            <a href="dashboard.php" style="color: white;">← Back to Dashboard</a>
        </div>
    </div>

    <div class="nav">
        <a href="dashboard.php">Dashboard</a>
        <a href="users.php">User Management</a>
        <a href="patients.php">Patient Management</a>
        <a href="reports.php">Reports</a>
        <a href="settings.php">System Settings</a>
    </div>

    <div class="container">
        <div class="section">
            <h3>All Patients</h3>
            <table>
                <thead>
                    <tr>
                        <th>Patient ID</th>
                        <th>Name</th>
                        <th>Contact</th>
                        <th>Age</th>
                        <th>Status</th>
                        <th>Registered</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $patients = $mysqli->query("SELECT *, YEAR(CURDATE()) - YEAR(date_of_birth) AS age FROM patients ORDER BY created_at DESC LIMIT 50");
                    if ($patients && $patients->num_rows > 0) {
                        while ($patient = $patients->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>P{$patient['patient_id']}</td>";
                            echo "<td>{$patient['first_name']} {$patient['last_name']}</td>";
                            echo "<td>{$patient['contact_number']}</td>";
                            echo "<td>{$patient['age']} years</td>";
                            echo "<td>" . ($patient['is_active'] ? 'Active' : 'Inactive') . "</td>";
                            echo "<td>" . date('M d, Y', strtotime($patient['created_at'])) . "</td>";
                            echo "<td><a href='view_patient.php?id={$patient['patient_id']}' class='btn'>View</a></td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='7'>No patients found</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>