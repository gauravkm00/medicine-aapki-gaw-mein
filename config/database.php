<?php

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "medicine_aapki_gaw_mein";
$port = 3307;

// Create connection
$conn = new mysqli(
    $servername,
    $username,
    $password,
    $dbname,
    $port
);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set character encoding
$conn->set_charset("utf8mb4");

?>
