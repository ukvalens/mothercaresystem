<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maternal Care System - Database Setup</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #E6F2F1; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        h2 { color: #0077B6; }
        h3 { color: #023E8A; }
        .success { color: #2A9D8F; }
        .error { color: #E63946; }
        .info { background: #E6F2F1; padding: 15px; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🏥 Maternal Care System Database Setup</h1>
        
        <div class="info">
            <strong>⚠️ Important:</strong> Make sure your MySQL server is running and you have the correct database credentials in <code>app/config/database.php</code>
        </div>

        <?php
        // Check if setup should run
        if (isset($_GET['run']) && $_GET['run'] === 'setup') {
            echo "<hr>";
            include 'app/config/create_tables.php';
        } else {
            ?>
            <h2>Ready to Setup Database</h2>
            <p>This will create all necessary tables for the Maternal Care System including:</p>
            <ul>
                <li>✅ User management and security tables</li>
                <li>✅ Patient registration and management</li>
                <li>✅ Pregnancy tracking and ANC visits</li>
                <li>✅ Clinical data and laboratory tests</li>
                <li>✅ Delivery and postnatal care</li>
                <li>✅ Appointment scheduling</li>
                <li>✅ Financial management and billing</li>
                <li>✅ AI risk prediction system</li>
                <li>✅ Notification and communication</li>
                <li>✅ System configuration</li>
            </ul>
            
            <p><strong>Default Admin Account:</strong></p>
            <ul>
                <li>Username: <code>admin</code></li>
                <li>Password: <code>password</code></li>
                <li>Email: <code>admin@maternalcare.rw</code></li>
            </ul>
            
            <a href="?run=setup" style="background: #0077B6; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 20px 0;">
                🚀 Run Database Setup
            </a>
            <?php
        }
        ?>
    </div>
</body>
</html>