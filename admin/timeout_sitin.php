<?php
session_start();
include '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stt_id = $_POST['id'];
    $student_id = $_POST['student_id'];

    // ✅ Step 1: Update the session status to "Inactive" and set the logout_time
    $update_sql = "UPDATE sit_in_records 
                   SET status = 'Inactive', logout_time = NOW(), logged_out = 1 
                   WHERE stt_id = ?";
    $stmt = $conn->prepare($update_sql);
    if (!$stmt) {
        error_log("⚠️ ERROR: Failed to prepare status update query: " . $conn->error);
        die("Error: Failed to prepare status update query.");
    }
    $stmt->bind_param("i", $stt_id);
    if (!$stmt->execute()) {
        error_log("⚠️ ERROR: Failed to update status and logout_time: " . $stmt->error);
        die("Error updating sit_in_records: " . $stmt->error);
    }
    $stmt->close();

    // ✅ Step 2: Decrement the session count for the student, ensuring it doesn't go below 0
    $decrement_sql = "UPDATE sit_in_records 
                      SET session = GREATEST(CAST(session AS SIGNED) - 1, 0) 
                      WHERE student_id = ?";
    $stmt = $conn->prepare($decrement_sql);
    if (!$stmt) {
        error_log("⚠️ ERROR: Failed to prepare session decrement query: " . $conn->error);
        die("Error: Failed to prepare session decrement query.");
    }
    $stmt->bind_param("s", $student_id); // Use "s" because student_id is VARCHAR
    if (!$stmt->execute()) {
        error_log("⚠️ ERROR: Failed to decrement session count: " . $stmt->error);
        die("Error updating sit_in_records: " . $stmt->error);
    }
    $stmt->close();

    // ✅ Step 3: Redirect back to the current Sit-in page
    header("Location: currentSitin.php");
    exit();
}
?>
