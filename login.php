<?php
session_start(); // Start session

include('database.php'); // Connect to database

if (isset($_SESSION["user"])) {
    header("Location: index.php");
    exit();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Validate credentials - using idno instead of username column
    $query = "SELECT id, password, role FROM users WHERE idno = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION["user"] = $user['id'];

        // Check if the user is an admin
        if ($user['role'] === 'admin') {
            $_SESSION["admin"] = true; // Set admin session
            header("Location: admindashboard.php"); // Redirect admin
        } else {
            header("Location: index.php"); // Redirect normal user
        }
        exit();
    } else {
        $error_message = "Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Form</title>
    <link rel="icon" type="image/x-icon" href="ccs.png">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<script src="https://cdn.tailwindcss.com"></script>
<body class="bg-[#2a3d4f] flex justify-center items-center h-screen">

<div class="w-full max-w-md p-8 bg-white rounded-xl shadow-lg"> <div class="flex justify-between mb-8"> <img src="ccs.png" alt="Logo Left" class="logo-left" style="width: 60px;"> <img src="uc.png" alt="Logo Right" class="logo-right" style="width: 60px;">
    </div>

    <form action="login.php" method="post">
        <div class="text-center mb-8"> <h2 class="text-3xl font-semibold text-gray-800 mb-2">Welcome Back</h2> <p class="text-lg text-gray-600">Enter your credentials to log in</p> </div>

        <?php if (isset($error_message)): ?>
            <div class="text-red-600 mb-6 text-sm"><?php echo $error_message; ?></div> <?php endif; ?>

        <div class="mb-6"> <input type="text" placeholder="Email address" name="username" class="w-full px-5 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-400" required> </div>

        <div class="mb-8"> <input type="password" placeholder="Password" name="password" class="w-full px-5 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-400" required> </div>

        <div class="mb-8"> <input type="submit" value="Login" name="login" class="w-full bg-blue-500 text-white py-3 rounded-xl hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-400"> </div>
    </form>

    <div class="text-center">
        <p class="text-lg text-gray-600">Don't have an account? <a href="registration.php" class="text-blue-500 hover:underline">Register</a></p> </div>
</div>

</body>
</html>