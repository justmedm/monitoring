<?php
$hostName = "localhost";
$dbUser = "root"; // Change if using a different user
$dbPassword = "";
$dbName = "login_register";

// Database connection
$conn = mysqli_connect($hostName . ":3306", $dbUser, $dbPassword, $dbName);

if (!$conn) {
    die("Database Connection Failed: " . mysqli_connect_error());
}

// Ensure correct character encoding
mysqli_set_charset($conn, "utf8mb4");
?>
