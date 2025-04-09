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
    $idno = $_POST['idno'] ?? '';
    $firstname = $_POST['firstname'] ?? '';
    $lastname = $_POST['lastname'] ?? '';
    $emailadd = $_POST['emailadd'] ?? '';
    $course = $_POST['course'] ?? '';

    // Validate the data
    if (empty($student_id) || empty($idno) || empty($firstname) || empty($lastname) || empty($emailadd) || empty($course)) {
        $_SESSION['student_error'] = "All fields are required";
        header("Location: students.php");
        exit();
    }

    // Update student information
    $update_query = "UPDATE users SET idno = ?, firstname = ?, lastname = ?, emailadd = ?, course = ? WHERE id = ?";
    
    $stmt = mysqli_prepare($conn, $update_query);
    if (!$stmt) {
        $_SESSION['student_error'] = "Database query preparation failed: " . mysqli_error($conn);
        header("Location: students.php");
        exit();
    }
    
    mysqli_stmt_bind_param($stmt, "sssssi", $idno, $firstname, $lastname, $emailadd, $course, $student_id);
    $result = mysqli_stmt_execute($stmt);
    
    if ($result) {
        $_SESSION['student_success'] = "Student information updated successfully";
    } else {
        $_SESSION['student_error'] = "Error updating student information: " . mysqli_error($conn);
    }
    
    header("Location: students.php");
    exit();
} else {
    // If not a POST request, redirect back
    header("Location: students.php");
    exit();
}
?> 