<?php
// Database configuration template
// Copy this file to database.php and update with your actual database credentials

$host = 'localhost';
$username = 'your_username';
$password = 'your_password';
$database = 'your_database_name';

// Create connection
$mysqli = new mysqli($host, $username, $password, $database);

// Check connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Set charset
$mysqli->set_charset("utf8");
?>