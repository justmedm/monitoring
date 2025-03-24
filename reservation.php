<?php
session_start();
include 'db.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];

$sql = "SELECT idno, lastname, firstname, middlename FROM users WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    die("Error: User not found.");
}

// Get ID Number
$id_number = $user['idno'] ?? null;
$full_name = trim(($user['firstname'] ?? '') . " " . (($user['middlename'] ?? '') ? $user['middlename'] . " " : '') . ($user['lastname'] ?? ''));

// Debugging: Check if id_number is set
if (!$id_number) {
    die("Error: Missing ID Number from Database!");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_number = $_POST['id_number'] ?? $id_number;
    $purpose = $_POST['purpose'];
    $lab = $_POST['lab'];
    $time_in = $_POST['time_in'];
    $date = $_POST['date'];
    $session = $_POST['session'];

    if (!$id_number) {
        die("Error: ID Number is missing in the form submission!");
    }

    $sql = "INSERT INTO reservations (id_number, full_name, purpose, lab, time_in, date, remaining_session) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Error preparing SQL: " . $conn->error);
    }

    $stmt->bind_param("sssssss", $id_number, $full_name, $purpose, $lab, $time_in, $date, $session);

    if ($stmt->execute()) {
        $_SESSION['reserved_session'] = $session;
        $_SESSION['success_message'] = "Reservation successful!";
        header("Location: dashboard.php");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            flex-direction: column;
            margin: 0;
            padding: 0;
            height: 100vh;
            background: #2a3d4f;
        }
        .header {
            background: #F8F1E7;
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px 20px;
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
        .form-container {
            max-width: 600px;
            margin: auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.3);
            position: relative;
            top: 100px;
            margin-top: 50px;
        }
        .form-container h2 {
            text-align: center;
            margin-bottom: 20px;
        }
        .form-container input, .form-container select {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box;
        }
        .form-container button {
            width: 100%;
            padding: 10px;
            background: #444;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .form-container button:hover {
            background: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <button onclick="window.location.href='dashboard.php'">← Back</button>
        <div></div>
    </div>
    <div class="form-container">
        <h2>Reservation Form</h2>
        <form method="POST" action="">
            <input type="text" name="id_number" placeholder="ID Number" value="<?php echo htmlspecialchars($id_number); ?>" readonly>
            <input type="text" name="full_name" placeholder="Student Name" value="<?php echo htmlspecialchars($full_name); ?>" readonly>
            <select name="purpose" required>
                <option value="">Select Purpose</option>
                <option value="Study">C Programming</option>
                <option value="Project">Java Programming</option>
                <option value="Research">C# Programming</option>
                <option value="Other">Php Programming</option>
                <option value="Other">ASP.Net Programming</option>
            </select>
            <select name="lab" required>
                <option value="">Select Lab</option>
                <option value="Lab 1">Lab 524</option>
                <option value="Lab 2">Lab 526</option>
                <option value="Lab 3">Lab 528</option>
                <option value="Lab 2">Lab 530</option>
                <option value="Lab 3">Lab 542</option>
                <option value="Lab 3">MAC laboratory</option>
               
            </select>
            <input type="time" name="time_in" placeholder="Time In" required>
            <input type="date" name="date" placeholder="Date" required>
           <!--prevent editing-->
            <input type="number" value="30" readonly>
            <input type="hidden" name="session" value="30">
            <button type="submit">Submit</button>
        </form>
    </div>
</body>
</html>
