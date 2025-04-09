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

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $announcement_text = $_POST['announcement'] ?? '';
    
    if (empty($announcement_text)) {
        $_SESSION['edit_error'] = "Announcement text cannot be empty";
    } else {
        // Update the announcement
        $update_query = "UPDATE announcements SET message = ?, updated_at = NOW() WHERE id = ?";
        $stmt = mysqli_prepare($conn, $update_query);
        
        if (!$stmt) {
            $_SESSION['edit_error'] = "Database query preparation failed: " . mysqli_error($conn);
        } else {
            mysqli_stmt_bind_param($stmt, "si", $announcement_text, $announcement_id);
            $result = mysqli_stmt_execute($stmt);
            
            if ($result) {
                $_SESSION['announcement_success'] = "Announcement successfully updated";
                header("Location: admindashboard.php");
                exit();
            } else {
                $_SESSION['edit_error'] = "Error updating announcement: " . mysqli_error($conn);
            }
        }
    }
}

// Fetch the announcement data
$query = "SELECT * FROM announcements WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);

if (!$stmt) {
    $_SESSION['announcement_error'] = "Database query preparation failed: " . mysqli_error($conn);
    header("Location: admindashboard.php");
    exit();
}

mysqli_stmt_bind_param($stmt, "i", $announcement_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    $_SESSION['announcement_error'] = "Announcement not found";
    header("Location: admindashboard.php");
    exit();
}

$announcement = mysqli_fetch_assoc($result);

// Include the header/navbar
include('admin_header.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Announcement</title>
    <link rel="icon" type="image/x-icon" href="ccs.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        /* Other styles */
        body {
            padding-top: 20px;
        }
    </style>
</head>
<body>
    <!-- Navbar (will be included from admin_header.php) -->
    
    <div class="container mt-5">
        <h2 class="text-center mb-4">Edit Announcement</h2>
        
        <!-- Error Messages -->
        <?php if (isset($_SESSION['edit_error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $_SESSION['edit_error'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['edit_error']); ?>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-body">
                <form method="POST" action="edit_announcement.php?id=<?= $announcement_id ?>">
                    <div class="mb-3">
                        <label for="announcement" class="form-label">Announcement Text</label>
                        <textarea class="form-control" id="announcement" name="announcement" rows="5" required><?= htmlspecialchars($announcement['message']) ?></textarea>
                    </div>
                    <div class="mb-3">
                        <p><strong>Date posted:</strong> <?= date('Y-m-d H:i:s', strtotime($announcement['created_at'])) ?></p>
                        <?php if (isset($announcement['updated_at']) && $announcement['updated_at']): ?>
                            <p><strong>Last updated:</strong> <?= date('Y-m-d H:i:s', strtotime($announcement['updated_at'])) ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex justify-content-between">
                        <a href="admindashboard.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Update Announcement</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 