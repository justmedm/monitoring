<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Ensure only admin can access
if (!isset($_SESSION["admin"])) {
    header("Location: login.php");
    exit();
}

include('database.php'); // Database connection

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $student_id = $_POST['student_id'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validate the data
    if (empty($student_id) || empty($new_password) || empty($confirm_password)) {
        $_SESSION['student_error'] = "All fields are required";
        header("Location: students.php");
        exit();
    }

    // Check if passwords match
    if ($new_password !== $confirm_password) {
        $_SESSION['student_error'] = "Passwords do not match";
        header("Location: students.php");
        exit();
    }

    // Hash the password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    // Update the password
    $update_query = "UPDATE users SET password = ? WHERE id = ?";
    
    $stmt = mysqli_prepare($conn, $update_query);
    if (!$stmt) {
        $_SESSION['student_error'] = "Database query preparation failed: " . mysqli_error($conn);
        header("Location: students.php");
        exit();
    }
    
    mysqli_stmt_bind_param($stmt, "si", $hashed_password, $student_id);
    $result = mysqli_stmt_execute($stmt);
    
    if ($result) {
        $_SESSION['student_success'] = "Password changed successfully";
    } else {
        $_SESSION['student_error'] = "Error changing password: " . mysqli_error($conn);
    }
    
    header("Location: students.php");
    exit();
} else {
    // If not a POST request, redirect back
    header("Location: students.php");
    exit();
}
?> 