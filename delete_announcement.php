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

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['announcement_error'] = "No announcement ID provided";
    header("Location: admindashboard.php");
    exit();
}

$announcement_id = $_GET['id'];

// Delete the announcement
$delete_query = "DELETE FROM announcements WHERE id = ?";
$stmt = mysqli_prepare($conn, $delete_query);

if (!$stmt) {
    $_SESSION['announcement_error'] = "Database query preparation failed: " . mysqli_error($conn);
    header("Location: admindashboard.php");
    exit();
}

mysqli_stmt_bind_param($stmt, "i", $announcement_id);
$result = mysqli_stmt_execute($stmt);

if ($result) {
    $_SESSION['announcement_success'] = "Announcement successfully deleted";
} else {
    $_SESSION['announcement_error'] = "Error deleting announcement: " . mysqli_error($conn);
}

// Redirect back to admin dashboard
header("Location: admindashboard.php");
exit();
?> 