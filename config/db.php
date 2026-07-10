<?php
// Simple database connector for mysqli with minimal error handling.
$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'kuesioner_db';

$conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

// Force utf8mb4 for emoji-safe input.
$conn->set_charset('utf8mb4');

