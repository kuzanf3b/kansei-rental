<?php
// Database Configuration
$host = "localhost";
$user = "root";
$pass = "";
$db   = "rental_jdm";

// Create connection using mysqli object-oriented
$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}

// Set charset
$conn->set_charset("utf8mb4");


