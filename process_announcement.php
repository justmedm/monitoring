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
    $announcement = $_POST['announcement'] ?? '';

    // Validate the data
    if (empty($announcement)) {
        // Set error message and redirect back
        $_SESSION['announcement_error'] = "Announcement text is required";
        header("Location: admindashboard.php");
        exit();
    }

    // Sanitize the input
    $announcement = mysqli_real_escape_string($conn, $announcement);

    // Insert the announcement - using the correct 'message' column, not 'title'
    $insert_query = "INSERT INTO announcements (message, created_at, active) 
                     VALUES (?, NOW(), 1)";
    
    $stmt = mysqli_prepare($conn, $insert_query);
    if (!$stmt) {
        $_SESSION['announcement_error'] = "Database query preparation failed: " . mysqli_error($conn);
        header("Location: admindashboard.php");
        exit();
    }
    
    mysqli_stmt_bind_param($stmt, "s", $announcement);
    $result = mysqli_stmt_execute($stmt);
    
    if ($result) {
        // Success message
        $_SESSION['announcement_success'] = "Announcement successfully posted";
    } else {
        // Error message
        $_SESSION['announcement_error'] = "Error posting announcement: " . mysqli_error($conn);
    }
    
    // Redirect back to admin dashboard
    header("Location: admindashboard.php");
    exit();
} else {
    // If not a POST request, redirect to admin dashboard
    header("Location: admindashboard.php");
    exit();
}
?> 