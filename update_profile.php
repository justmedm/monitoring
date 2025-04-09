<?php
session_start();
if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit();
}

include('database.php');

$user_id = $_SESSION["user"];
$firstname = $_POST['firstname'];
$lastname = $_POST['lastname'];
$emailadd = $_POST['emailadd'];
$midname = $_POST['midname']; // Corrected to match the form field name
$course = $_POST['course'];
$yearlvl = $_POST['yearlvl'];
$password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : null;

// Handle profile image upload
$profile_pic = null;
if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = "uploads/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true); // Create directory if it doesn't exist
    }

    $fileTmp = $_FILES['profile_image']['tmp_name'];
    $originalName = basename($_FILES['profile_image']['name']);
    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
    $newFileName = uniqid('profile_', true) . '.' . $extension;
    $uploadPath = $uploadDir . $newFileName;

    // Move uploaded file
    if (move_uploaded_file($fileTmp, $uploadPath)) {
        $profile_pic = $newFileName;
    } else {
        $_SESSION['error'] = "Failed to upload profile image.";
        header("Location: index.php");
        exit();
    }
}

// Build SQL query dynamically
$query_parts = ["firstname = ?", "lastname = ?", "emailadd = ?", "midname = ?", "course = ?", "yearlvl = ?"];
$types = "sssssi"; // Base types

$params = [$firstname, $lastname, $emailadd, $midname, $course, $yearlvl];

if ($password) {
    $query_parts[] = "password = ?";
    $types .= "s"; // Add 's' for password
    $params[] = $password;
}

if ($profile_pic) {
    $query_parts[] = "profile_pic = ?";
    $types .= "s"; // Add 's' for profile_pic
    $params[] = $profile_pic;
}

$query = "UPDATE users SET " . implode(", ", $query_parts) . " WHERE id = ?";
$types .= "i"; // Add 'i' for user_id
$params[] = $user_id;

$stmt = mysqli_prepare($conn, $query);

// Bind parameters dynamically
mysqli_stmt_bind_param($stmt, $types, ...$params);

// Execute the query
if (mysqli_stmt_execute($stmt)) {
    $_SESSION['message'] = "Profile updated successfully!";
    header("Location: index.php");
    exit();
} else {
    error_log("Error updating profile: " . mysqli_error($conn));
    $_SESSION['error'] = "Error updating profile. Please try again.";
    header("Location: index.php");
    exit();
}
?>