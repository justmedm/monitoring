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
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="flex items-center justify-center min-h-screen bg-gradient-to-br from-gray-800 to-gray-900 text-white">
    <div class="bg-white text-gray-800 w-full max-w-lg h-auto p-6 rounded-lg shadow-lg">
        <h2 class="text-2xl font-bold text-center mb-6">Register</h2>
        <form action="register.php" method="POST" class="space-y-4">
            <div>
                <label for="idno" class="block text-sm font-medium">ID No:</label>
                <input type="text" name="idno" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter your ID number" required>
            </div>
            <div>
                <label for="lastname" class="block text-sm font-medium">Lastname:</label>
                <input type="text" name="lastname" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter your last name" required>
            </div>
            <div>
                <label for="firstname" class="block text-sm font-medium">Firstname:</label>
                <input type="text" name="firstname" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter your first name" required>
            </div>
            <div>
                <label for="middlename" class="block text-sm font-medium">Middlename:</label>
                <input type="text" name="middlename" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter your middle name" required>
            </div>
            <div>
                <label for="course" class="block text-sm font-medium">Course:</label>
                <select name="course" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    <option value="" disabled selected>Select Course</option>
                    <option value="BSIT">BS in Information Technology</option>
                    <option value="BSCS">BS in Computer Science</option>
                    <option value="BSCE">BS in Civil Engineering</option>
                    <option value="BSA">BS in Architecture</option>
                    <option value="BSEE">BS in Electrical Engineering</option>
                </select>
            </div>
            <div>
                <label for="yearlevel" class="block text-sm font-medium">Year Level:</label>
                <select name="year_level" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    <option value="" disabled selected>Select Year Level</option>
                    <option value="1st Year">1st Year</option>
                    <option value="2nd Year">2nd Year</option>
                    <option value="3rd Year">3rd Year</option>
                    <option value="4th Year">4th Year</option>
                </select>
            </div>
            <div>
                <label for="username" class="block text-sm font-medium">Username:</label>
                <input type="text" name="username" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Choose a username" required>
            </div>
            <div>
                <label for="password" class="block text-sm font-medium">Password:</label>
                <input type="password" name="password" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter a secure password" required>
            </div>
            <button type="submit" class="w-full bg-blue-500 text-white py-2 rounded-lg hover:bg-blue-600 transition duration-300">Register</button>
        </form>
        <a href="login.php" class="block text-center text-sm text-blue-500 hover:underline mt-4">Already have an account? Login</a>
    </div>
</body>
</html>