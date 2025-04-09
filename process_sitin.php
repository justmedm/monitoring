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
    $id_number = $_POST['id_number'] ?? '';
    $student_name = $_POST['student_name'] ?? '';
    $purpose = $_POST['purpose'] ?? '';
    $lab = $_POST['lab'] ?? '';
    $remaining_session = $_POST['remaining_session'] ?? '';

    // Validate the data
    if (empty($id_number) || empty($student_name) || empty($purpose) || empty($lab)) {
        // Set error message and redirect back
        $_SESSION['sitin_error'] = "All fields are required";
        header("Location: admindashboard.php");
        exit();
    }

    // Get user details from the database to ensure we have a valid user
    $check_user_query = "SELECT * FROM users WHERE idno = ?";
    $check_stmt = mysqli_prepare($conn, $check_user_query);
    if (!$check_stmt) {
        die("Database query preparation failed: " . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($check_stmt, "s", $id_number);
    mysqli_stmt_execute($check_stmt);
    $user_result = mysqli_stmt_get_result($check_stmt);
    
    if (mysqli_num_rows($user_result) === 0) {
        // User not found
        $_SESSION['sitin_error'] = "Student ID not found in the database";
        header("Location: admindashboard.php");
        exit();
    }
    
    // Insert the sit-in record - using only columns that exist in the database
    $insert_query = "INSERT INTO sit_in_records (student_id, purpose, sitlab, time_in, course) 
                     VALUES (?, ?, ?, NOW(), 'Default Course')";
    
    $stmt = mysqli_prepare($conn, $insert_query);
    if (!$stmt) {
        die("Database query preparation failed: " . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, "sss", $id_number, $purpose, $lab);
    $result = mysqli_stmt_execute($stmt);
    
    if ($result) {
        // Success message
        $_SESSION['sitin_success'] = "Student successfully checked in";
    } else {
        // Error message
        $_SESSION['sitin_error'] = "Error checking in student: " . mysqli_error($conn);
    }
    
    // Redirect back to sit_in page
    header("Location: sit_in.php");
    exit();
} else {
    // If not a POST request, redirect to sit_in page
    header("Location: sit_in.php");
    exit();
}
?> 