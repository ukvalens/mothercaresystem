<?php
echo "PHP is working!<br>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Server: " . $_SERVER['SERVER_SOFTWARE'] . "<br>";

// Test database connection
try {
    require_once 'app/config/database.php';
    echo "Database connection: SUCCESS<br>";
} catch (Exception $e) {
    echo "Database connection: FAILED - " . $e->getMessage() . "<br>";
}

echo "<br><a href='public/index.html'>Go to Homepage</a>";
echo "<br><a href='app/views/auth/login.php'>Go to Login</a>";
?>