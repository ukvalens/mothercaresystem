<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header('Location: ../auth/login.php');
    exit();
}
require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $user_id = $_POST['user_id'];
        if ($_POST['action'] == 'activate') {
            $mysqli->query("UPDATE users SET is_active = 1 WHERE user_id = $user_id");
        } elseif ($_POST['action'] == 'deactivate') {
            $mysqli->query("UPDATE users SET is_active = 0 WHERE user_id = $user_id");
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Admin</title>
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
        .btn-danger { background: #dc3545; }
        .btn-success { background: #28a745; }
    </style>
</head>
<body>
    <div class="header">
        <h1>👥 User Management</h1>
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
            <h3>System Users</h3>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Last Login</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $users = $mysqli->query("SELECT * FROM users ORDER BY created_at DESC");
                    while ($user = $users->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>{$user['first_name']} {$user['last_name']}</td>";
                        echo "<td>{$user['username']}</td>";
                        echo "<td>{$user['email']}</td>";
                        echo "<td>{$user['role']}</td>";
                        echo "<td>" . ($user['is_active'] ? 'Active' : 'Inactive') . "</td>";
                        echo "<td>" . ($user['last_login'] ?: 'Never') . "</td>";
                        echo "<td>";
                        if ($user['is_active']) {
                            echo "<form method='POST' style='display:inline;'>";
                            echo "<input type='hidden' name='user_id' value='{$user['user_id']}'>";
                            echo "<input type='hidden' name='action' value='deactivate'>";
                            echo "<button type='submit' class='btn btn-danger'>Deactivate</button>";
                            echo "</form>";
                        } else {
                            echo "<form method='POST' style='display:inline;'>";
                            echo "<input type='hidden' name='user_id' value='{$user['user_id']}'>";
                            echo "<input type='hidden' name='action' value='activate'>";
                            echo "<button type='submit' class='btn btn-success'>Activate</button>";
                            echo "</form>";
                        }
                        echo "</td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>