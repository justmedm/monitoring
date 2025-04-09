<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Ensure only admin can access
if (!isset($_SESSION["admin"])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Access denied'
    ]);
    exit();
}

include('database.php'); // Database connection

// Set content type to JSON
header('Content-Type: application/json');

// Check if an ID was provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'No student ID provided'
    ]);
    exit();
}

$student_id = $_GET['id'];

// Fetch student information from the database
$query = "SELECT * FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);

if (!$stmt) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . mysqli_error($conn)
    ]);
    exit();
}

mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'No student found with the provided ID'
    ]);
    exit();
}

$student = mysqli_fetch_assoc($result);

// Return the student information
echo json_encode([
    'success' => true,
    'student' => $student
]);
?> 