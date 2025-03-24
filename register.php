<?php
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $idno = $_POST['idno'];
    $lastname = $_POST['lastname'];
    $firstname = $_POST['firstname'];
    $middlename = $_POST['middlename'];
    $course = $_POST['course'];
    $yearlevel = $_POST['year_level'];
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (idno, lastname, firstname, middlename, course, yearlevel, username, password) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssss", $idno, $lastname, $firstname, $middlename, $course, $yearlevel, $username, $password);
    
    if ($stmt->execute()) {
        echo "<script>alert('Registration successful!'); window.location='login.php';</script>";
    } else {
        echo "<script>alert('Error: " . $conn->error . "'); window.location='register.php';</script>";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register</title>
<style>
    body {
        font-family: Arial, sans-serif;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        background-color: #2a3d4f; 
    }

    form {
        width: 400px;
        margin: 0 auto;
        padding: 20px;
        border: 1px solid #d2b48c; 
        border-radius: 10px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        background-color: #fffff0; 
    }

    .container {
        padding: 20px;
        border-radius: 10px;
        text-align: center;
    }

    input, select {
        width: 100%;
        padding: 10px;
        margin: 10px 0;
        border: 1px solid #d2b48c;
        border-radius: 5px;
        box-sizing: border-box;
    }

    button {
        width: 100%;
        padding: 10px;
        background: #d2b48c; 
        color: black;
        border: none;
        border-radius: 5px;
        cursor: pointer;
    }

    button:hover {
        background: #b59f7a; 
    }

    a {
        display: block;
        margin-top: 10px;
        text-decoration: none;
        color: white;
    }

    a:hover {
        color: #b59f7a; 
    }

    h2 {
        color: white; 
    }
</style>

</head>
<body>
<div class="container">
    <h2>Register</h2>
    <form action="register.php" method="POST">
        <label for="idno">ID No:</label>
        <input type="text" name="idno" required>
        
        <label for="lastname">Lastname:</label>
        <input type="text" name="lastname" required>

        <label for="firstname">Firstname:</label>
        <input type="text" name="firstname" required>

        <label for="middlename">Middlename:</label>
        <input type="text" name="middlename" required>

        <label for="course">Course:</label>
        <select name="course" required>
            <option value="" disabled selected>Select Course</option>
            <option value="BSIT">BS in Information Technology</option>
            <option value="BSCS">BS in Computer Science</option>
            <option value="BSCE">BS in Civil Engineering</option>
            <option value="BSA">BS in Architecture</option>
            <option value="BSEE">BS in Electrical Engineering</option>
        </select>

        <label for="yearlevel">Year Level:</label>
        <select name="year_level" required>
            <option value="" disabled selected>Select Year Level</option>
            <option value="1st Year">1st Year</option>
            <option value="2nd Year">2nd Year</option>
            <option value="3rd Year">3rd Year</option>
            <option value="4th Year">4th Year</option>
        </select>

        <label for="username">Username:</label>
        <input type="text" name="username" required>

        <label for="password">Password:</label>
        <input type="password" name="password" required>

        <button type="submit">Register</button>
    </form>
    <a href="login.php">Already have an account? Login</a>
</div>
</body>
</html>