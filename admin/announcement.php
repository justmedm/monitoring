<?php
include '../db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['announcement'])) {
    $message = trim($_POST['announcement']);

    if (!empty($message)) {
        $stmt = $conn->prepare("INSERT INTO announcements (message, created_at) VALUES (?, NOW())");
        $stmt->bind_param("s", $message);
        $stmt->execute();
        $stmt->close();
        header("Location: admin_dashboard.php"); // Redirect after posting
        exit();
    }
}

// Handle AJAX request
if ($_SERVER["REQUEST_METHOD"] == "GET") {
    $result = $conn->query("SELECT message, created_at FROM announcements ORDER BY created_at DESC");
    $announcements = [];
    while ($row = $result->fetch_assoc()) {
        $announcements[] = ["message" => $row['message'], "date" => $row['created_at']];
    }
    echo json_encode($announcements);
    exit();
}
?>
