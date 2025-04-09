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
    $idno = $_POST['idno'] ?? '';
    $firstname = $_POST['firstname'] ?? '';
    $lastname = $_POST['lastname'] ?? '';
    $emailadd = $_POST['emailadd'] ?? '';
    $course = $_POST['course'] ?? '';
    $password = $_POST['password'] ?? '';

    // Validate the data
    if (empty($idno) || empty($firstname) || empty($lastname) || empty($emailadd) || empty($course) || empty($password)) {
        $_SESSION['student_error'] = "All fields are required";
        header("Location: students.php");
        exit();
    }

    // Check if ID number already exists
    $check_query = "SELECT * FROM users WHERE idno = ?";
    $check_stmt = mysqli_prepare($conn, $check_query);
    
    if (!$check_stmt) {
        $_SESSION['student_error'] = "Database query preparation failed: " . mysqli_error($conn);
        header("Location: students.php");
        exit();
    }
    
    mysqli_stmt_bind_param($check_stmt, "s", $idno);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    
    if (mysqli_num_rows($check_result) > 0) {
        $_SESSION['student_error'] = "A student with this ID number already exists";
        header("Location: students.php");
        exit();
    }

    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert new student
    $insert_query = "INSERT INTO users (idno, firstname, lastname, emailadd, course, password, role) 
                    VALUES (?, ?, ?, ?, ?, ?, 'student')";
    
    $stmt = mysqli_prepare($conn, $insert_query);
    if (!$stmt) {
        $_SESSION['student_error'] = "Database query preparation failed: " . mysqli_error($conn);
        header("Location: students.php");
        exit();
    }
    
    mysqli_stmt_bind_param($stmt, "ssssss", $idno, $firstname, $lastname, $emailadd, $course, $hashed_password);
    $result = mysqli_stmt_execute($stmt);
    
    if ($result) {
        $_SESSION['student_success'] = "Student added successfully";
    } else {
        $_SESSION['student_error'] = "Error adding student: " . mysqli_error($conn);
    }
    
    header("Location: students.php");
    exit();
} else {
    // If not a POST request, redirect back
    header("Location: students.php");
    exit();
}
?> 