<?php
session_start();
include 'db.php'; // Ensure the file path is correct

// Check if the database connection is valid
if (!isset($conn)) {
    error_log("⚠️ WARNING: Database connection failed.");
    die("Error: Database connection failed.");
}

// Debugging: Check if session data is set
if (!isset($_SESSION['student_id'])) {
    error_log("⚠️ WARNING: No student_id found in session.");
    die("Error: No student_id found in session.");
}

$user_id = $_SESSION['student_id'];

// ✅ Step 1: Retrieve the latest session record
$getSessionQuery = "SELECT session FROM sit_in_records WHERE student_id = ? ORDER BY stt_id DESC LIMIT 1";
$getSessionStmt = $conn->prepare($getSessionQuery);
$getSessionStmt->bind_param("s", $user_id);
$getSessionStmt->execute();
$getSessionStmt->store_result();
$getSessionStmt->bind_result($sessionValue);
$getSessionStmt->fetch();
$getSessionStmt->close();

// Debugging: Log the fetched session value
error_log("DEBUG: Fetched session value for student_id $user_id: " . ($sessionValue !== null ? $sessionValue : 'NULL'));

// Convert session to integer (if not empty)
if (!empty($sessionValue)) {
    $newSessionValue = max(0, (int)$sessionValue - 1); // Ensure it doesn't go negative
} else {
    $newSessionValue = 0; // Default to 0 if empty
}

// ✅ Step 2: Update the session count
$updateSessionQuery = "UPDATE sit_in_records SET session = ? WHERE student_id = ? ORDER BY stt_id DESC LIMIT 1";
$updateSessionStmt = $conn->prepare($updateSessionQuery);
$updateSessionStmt->bind_param("is", $newSessionValue, $user_id);
$updateSessionStmt->execute();

if ($updateSessionStmt->affected_rows > 0) {
    error_log("✅ SUCCESS: Session count updated for student_id: $user_id to $newSessionValue");
} else {
    error_log("⚠️ WARNING: Session count NOT updated for student_id: $user_id");
    error_log("DEBUG: Query Error: " . $conn->error);
}

$updateSessionStmt->close();

// ✅ Step 3: Update the logout time
$logout_time = date('Y-m-d H:i:s'); // Current timestamp
$logoutQuery = "UPDATE sit_in_records 
                SET logged_out = 1, logout_time = ? 
                WHERE student_id = ? AND logged_out = 0 
                ORDER BY stt_id DESC 
                LIMIT 1";
$logoutStmt = $conn->prepare($logoutQuery);
$logoutStmt->bind_param("ss", $logout_time, $user_id);
$logoutStmt->execute();

if ($logoutStmt->affected_rows > 0) {
    error_log("✅ SUCCESS: Logout time recorded for student_id: $user_id");
} else {
    error_log("⚠️ WARNING: Logout time NOT recorded for student_id: $user_id");
    error_log("DEBUG: Query Error: " . $conn->error);
}

$logoutStmt->close();

// ✅ Step 4: Destroy the session and redirect to login page
session_destroy();
header("Location: login.php");
exit();
?>
