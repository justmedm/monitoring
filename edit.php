<?php
session_start();
include 'db.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT); // Enable MySQL error reporting

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];
$sql = "SELECT * FROM users WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    echo "User not found.";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $idno = $_POST['idno'] ?? '';
    $lastname = $_POST['lastname'] ?? '';
    $firstname = $_POST['firstname'] ?? '';
    $middlename = $_POST['middlename'] ?? '';
    $yearlevel = $_POST['yearlevel'] ?? '';
    $email = $_POST['email'] ?? '';
    $course = $_POST['course'] ?? '';
    $address = $_POST['address'] ?? '';

    // Handle file upload
    $profile_image = $user['profile_image']; // Default to current image
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == UPLOAD_ERR_OK) {
        $uploadDir = 'image/';
        $uploadFile = $uploadDir . basename($_FILES['profile_image']['name']);

        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $uploadFile)) {
            $profile_image = basename($_FILES['profile_image']['name']);
        } else {
            echo "Error uploading file.";
            exit();
        }
    }

    $sql = "UPDATE users SET idno=?, lastname=?, firstname=?, middlename=?, yearlevel=?, email=?, course=?, address=?, profile_image=? WHERE username=?";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("SQL Error: " . $conn->error); // Debugging SQL error
    }

    $stmt->bind_param("ssssssssss", $idno, $lastname, $firstname, $middlename, $yearlevel, $email, $course, $address, $profile_image, $username);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Profile updated successfully!";
        header("Location: dashboard.php");
        exit();
    } else {
        echo "Error updating profile.";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            flex-direction: column;
            margin: 0;
            align-items: center;
            justify-content: center;
            height: 100vh;
            background: #2a3d4f;
        }
        .header {
            background: #F8F1E7;
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 24px;
            font-weight: bold;
            width: 100%;
            position: fixed;
            top: 0;
            left: 0;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header button {
            background: #d2b48c;
            color: black;
            border: none;
            padding: 10px 15px;
            font-size: 16px;
            cursor: pointer;
            border-radius: 5px;
        }
        .header button:hover {
            background: #666;
        }
        .container {
            background: white;
            padding: 50px;
            border-radius: 10px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.3);
            width: 400px;
            max-width: 600px;
            text-align: left;
            margin-top: 30%;
        }
        .container h2 {
            text-align: center;
            margin-bottom: 0;
            padding-bottom: 10%;
        }
        .form-group {
            margin-bottom: 10px;
        }
        .form-group label {
            font-weight: bold;
            display: block;
            margin-bottom: 5px;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .save-button {
            background: blue;
            color: white;
            padding: 10px;
            width: 100%;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .save-button:hover {
            background: darkblue;
        }
        .button-container {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            margin-top: 20px;
        }
        /* Save Confirmation Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            width: 350px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.2);
        }
        .modal-content p {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 20px;
        }
        button {
            padding: 10px 20px;
            margin: 10px;
            background: #444;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background: #666;
        }
    </style>
</head>
<body>

<div class="header">
    <button onclick="window.location.href='dashboard.php'">← Back</button>
    <div></div>
</div>

<div class="container">
    <h2>Edit Profile</h2>
    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label>ID Number</label>
            <input type="text" name="idno" value="<?php echo htmlspecialchars($user['idno'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label>Last Name</label>
            <input type="text" name="lastname" value="<?php echo htmlspecialchars($user['lastname'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label>First Name</label>
            <input type="text" name="firstname" value="<?php echo htmlspecialchars($user['firstname'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label>Middle Name</label>
            <input type="text" name="middlename" value="<?php echo htmlspecialchars($user['middlename'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label>Course</label>
            <select name="course" required>
                <option value="" disabled>Select Course</option>
                <option value="BSIT" <?php echo ($user['course'] == 'BSIT') ? 'selected' : ''; ?>>BS in Information Technology</option>
                <option value="BSCS" <?php echo ($user['course'] == 'BSCS') ? 'selected' : ''; ?>>BS in Computer Science</option>
                <option value="BSCE" <?php echo ($user['course'] == 'BSCE') ? 'selected' : ''; ?>>BS in Civil Engineering</option>
                <option value="BSA" <?php echo ($user['course'] == 'BSA') ? 'selected' : ''; ?>>BS in Architecture</option>
                <option value="BSEE" <?php echo ($user['course'] == 'BSEE') ? 'selected' : ''; ?>>BS in Electrical Engineering</option>
            </select>
        </div>
        <div class="form-group">
            <label>Year Level</label>
            <input type="text" name="yearlevel" value="<?php echo htmlspecialchars($user['yearlevel'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label>Address</label>
            <input type="text" name="address" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label>Profile Image</label>
            <input type="file" name="profile_image" accept="image/*">
        </div>
        <div class="button-container">
            <button type="button" onclick="openSaveModal()">Save</button>
        </div>
    </form>
</div>

<!-- Save Confirmation Modal -->
<div id="saveModal" class="modal">
    <div class="modal-content">
        <p>Are you sure you want to save?</p>
        <button id="proceedSave">Proceed</button>
        <button id="cancelSave">Cancel</button>
    </div>
</div>

<script>
    // Open the save confirmation modal
    function openSaveModal() {
        document.getElementById('saveModal').style.display = 'flex';
    }

    // Close the save confirmation modal
    function closeSaveModal() {
        document.getElementById('saveModal').style.display = 'none';
    }

    // Proceed with saving action
    document.getElementById('proceedSave').addEventListener('click', function() {
        document.querySelector('form').submit();
    });

    // Cancel the save action
    document.getElementById('cancelSave').addEventListener('click', closeSaveModal);

    // Show success message on page load
    document.addEventListener("DOMContentLoaded", function() {
        <?php if (isset($_SESSION['success_message'])): ?>
            alert("<?php echo $_SESSION['success_message']; ?>");
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
    });
</script>

</body>
</html>
