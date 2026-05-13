<?php
// includes/db.php - Database connection (XAMPP LOCAL)

$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'brms_db';

$conn = new mysqli($host, $user, $pass, $dbname);

// check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// set charset
$conn->set_charset("utf8mb4");