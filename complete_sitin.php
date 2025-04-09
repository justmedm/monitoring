<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Ensure only admin can access
if (!isset($_SESSION["admin"])) {
    // If AJAX request, return JSON response
    if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit();
    }
    // Otherwise redirect
    header("Location: login.php");
    exit();
}

include('database.php'); // Database connection

// Check if an ID was provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // If AJAX request, return JSON response
    if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'No sit-in ID provided']);
        exit();
    }
    // Otherwise redirect with error
    $_SESSION['sitin_error'] = "No sit-in ID provided";
    header("Location: sit_in.php");
    exit();
}

$sit_in_id = $_GET['id'];

// Update the sit-in record to mark it as completed (set time_out to current time)
$update_query = "UPDATE sit_in_records SET time_out = NOW() WHERE id = ? AND time_out IS NULL";
$stmt = mysqli_prepare($conn, $update_query);

if (!$stmt) {
    // If AJAX request, return JSON response
    if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
        exit();
    }
    // Otherwise redirect with error
    $_SESSION['sitin_error'] = "Database error: " . mysqli_error($conn);
    header("Location: sit_in.php");
    exit();
}

mysqli_stmt_bind_param($stmt, "i", $sit_in_id);
$result = mysqli_stmt_execute($stmt);

if ($result) {
    // If AJAX request, return JSON response
    if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Sit-in session completed successfully']);
        exit();
    }
    // Otherwise redirect with success message
    $_SESSION['sitin_success'] = "Sit-in session completed successfully";
} else {
    // If AJAX request, return JSON response
    if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error completing sit-in session: ' . mysqli_error($conn)]);
        exit();
    }
    // Otherwise redirect with error
    $_SESSION['sitin_error'] = "Error completing sit-in session: " . mysqli_error($conn);
}

// If not an AJAX request, redirect back to the sit-in page
header("Location: sit_in.php");
exit();
?> 