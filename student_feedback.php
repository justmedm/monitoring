<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Ensure user is logged in
if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit();
}

include('database.php'); // Database connection

// Get user data
$user_id = $_SESSION["user"];
$query = "SELECT * FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

// Process feedback submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $laboratory = mysqli_real_escape_string($conn, $_POST['laboratory']);
    $message = mysqli_real_escape_string($conn, $_POST['message']);
    $student_id = $user['idno'];
    
    $insert_query = "INSERT INTO feedback (student_id, laboratory, message, created_at) 
                     VALUES (?, ?, ?, NOW())";
    $stmt = mysqli_prepare($conn, $insert_query);
    
    if (!$stmt) {
        $error = "Database error: " . mysqli_error($conn);
    } else {
        mysqli_stmt_bind_param($stmt, "sss", $student_id, $laboratory, $message);
        $result = mysqli_stmt_execute($stmt);
        
        if ($result) {
            $success = "Thank you for your feedback!";
        } else {
            $error = "Error submitting feedback: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Feedback</title>
    <link rel="icon" type="image/x-icon" href="ccs.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .navbar {
            background-color: #808080 !important;
        }
        .navbar-brand, .nav-link {
            color: white !important;
        }
        
        /* Form styling */
        .feedback-form {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        .form-label {
            font-weight: 500;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">College of Computer Studies</a>
            <div class="d-flex">
                <a class="nav-link me-3" href="index.php">Home</a>
                <a class="nav-link me-3" href="student_feedback.php">Feedback</a>
                <a class="btn btn-warning" href="logout.php">Log out</a>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <h2 class="text-center mb-4">Submit Feedback</h2>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $success ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <div class="feedback-form">
            <form method="POST" action="student_feedback.php">
                <div class="mb-3">
                    <label for="student_id" class="form-label">Student ID:</label>
                    <input type="text" class="form-control" id="student_id" value="<?= htmlspecialchars($user['idno']) ?>" disabled>
                </div>
                
                <div class="mb-3">
                    <label for="laboratory" class="form-label">Laboratory:</label>
                    <select class="form-select" id="laboratory" name="laboratory" required>
                        <option value="" selected disabled>Select a laboratory</option>
                        <option value="524">524</option>
                        <option value="526">526</option>
                        <option value="528">528</option>
                        <option value="530">530</option>
                        <option value="542">542</option>
                        <option value="Mac">Mac Lab</option>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label for="message" class="form-label">Your Feedback:</label>
                    <textarea class="form-control" id="message" name="message" rows="5" required placeholder="Please share your experience, suggestions, or concerns..."></textarea>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">Submit Feedback</button>
                    <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 