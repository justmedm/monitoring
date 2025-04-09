<?php
// reset_student_session.php
// This script resets all session counts by deleting all records in `sit_in_records`.

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
header('Content-Type: application/json'); // Set response type to JSON

// Include the database connection
include('database.php');

// Initialize the response array
$response = ['success' => false, 'message' => 'An unknown error occurred.'];

// Check 1: Admin Authentication
if (!isset($_SESSION["admin"])) {
    $response['message'] = 'Authentication required. Please log in as admin.';
    echo json_encode($response);
    exit();
}

// Check 2: Database Connection
if (!$conn) {
    $response['message'] = 'Database connection failed: ' . mysqli_connect_error();
    echo json_encode($response);
    exit();
}

// Action: Reset all session counts by deleting all records in `sit_in_records`
$reset_query = "DELETE FROM sit_in_records";

if (mysqli_query($conn, $reset_query)) {
    $response['success'] = true;
    $response['message'] = 'All session counts have been reset successfully.';
} else {
    $response['message'] = 'Failed to reset session counts: ' . mysqli_error($conn);
    error_log('Reset all sessions error: ' . mysqli_error($conn)); // Log detailed error
}

// Close the database connection
if (isset($conn)) {
    mysqli_close($conn);
}

// Send the JSON response back to the JavaScript
echo json_encode($response);
exit();
?>