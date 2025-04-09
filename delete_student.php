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
    // Get student ID
    $student_id = $_POST['student_id'] ?? '';

    if (empty($student_id)) {
        $_SESSION['student_error'] = "Student ID is required";
        header("Location: students.php");
        exit();
    }

    // Delete student record
    $delete_query = "DELETE FROM users WHERE id = ?";
    
    $stmt = mysqli_prepare($conn, $delete_query);
    if (!$stmt) {
        $_SESSION['student_error'] = "Database query preparation failed: " . mysqli_error($conn);
        header("Location: students.php");
        exit();
    }
    
    mysqli_stmt_bind_param($stmt, "i", $student_id);
    $result = mysqli_stmt_execute($stmt);
    
    if ($result) {
        $_SESSION['student_success'] = "Student deleted successfully";
    } else {
        $_SESSION['student_error'] = "Error deleting student: " . mysqli_error($conn);
    }
    
    header("Location: students.php");
    exit();
} else {
    // If not a POST request, redirect back
    header("Location: students.php");
    exit();
}
?> 