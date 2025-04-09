<?php
session_start();
if (isset($_SESSION["user"])) {
    header("Location: index.php");
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Form</title>
    <link rel="icon" type="image/x-icon" href="ccs.png">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<script src="https://cdn.tailwindcss.com"></script>
<body class="bg-[#2a3d4f] flex justify-center items-center h-screen">

    <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md">
        <div class="flex justify-between mb-6">
            <img src="ccs.png" alt="CCS Logo" class="h-12">
            <img src="uc.png" alt="UC Logo" class="h-12">
        </div>

        <?php
        $errors = array();

        if (isset($_POST["submit"])) {
            $idno = isset($_POST["idno"]) ? $_POST["idno"] : '';
            $lastname = isset($_POST["lastname"]) ? $_POST["lastname"] : '';
            $firstname = isset($_POST["firstname"]) ? $_POST["firstname"] : '';
            $midname = isset($_POST["midname"]) ? $_POST["midname"] : '';
            $course = isset($_POST["course"]) ? $_POST["course"] : '';
            $yearlvl = isset($_POST["yearlvl"]) ? $_POST["yearlvl"] : '';
            $emailadd = isset($_POST["emailadd"]) ? $_POST["emailadd"] : '';
            $username = isset($_POST["username"]) ? $_POST["username"] : '';
            $password = isset($_POST["password"]) ? $_POST["password"] : '';

            // Validation for empty fields
            if (empty($idno) || empty($lastname) || empty($firstname) || empty($midname) || empty($course) || empty($yearlvl) || empty($emailadd) || empty($username) || empty($password)) {
                array_push($errors, "Please fill in all fields");
            }

            // Validate email
            if (!filter_var($emailadd, FILTER_VALIDATE_EMAIL)) {
                array_push($errors, "Email is not valid");
            }

            // Validate password length
            if (strlen($password) < 8) {
                array_push($errors, "Password must be at least 8 characters");
            }

            // Check if the username exists
            require_once "database.php";
            $sql = "SELECT * FROM users WHERE username = '$username'";
            $result = mysqli_query($conn, $sql);
            $rowCount = mysqli_num_rows($result);
            if ($rowCount > 0) {
                array_push($errors, "Username already exists!");
            }

            // Check if the email exists
            $sql_email = "SELECT * FROM users WHERE emailadd = '$emailadd'";
            $result_email = mysqli_query($conn, $sql_email);
            $rowCount_email = mysqli_num_rows($result_email);
            if ($rowCount_email > 0) {
                array_push($errors, "Email already exists!");
            }

            // If there are errors, display them
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    echo "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4' role='alert'>
                            <strong class='font-bold'>Error!</strong>
                            <span class='block sm:inline'>$error</span>
                          </div>";
                }
            } else {
                // If no errors, proceed with registration
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);

                $sql = "INSERT INTO users (idno, lastname, firstname, midname, course, yearlvl, emailadd, username, password ) VALUES (?,?,?,?,?,?,?,?,?)";
                $stmt = mysqli_stmt_init($conn);
                $prepareStmt = mysqli_stmt_prepare($stmt, $sql);
                if ($prepareStmt) {
                    mysqli_stmt_bind_param($stmt, "sssssssss", $idno, $lastname, $firstname, $midname, $course, $yearlvl, $emailadd, $username, $passwordHash);
                    mysqli_stmt_execute($stmt);
                    echo "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4' role='alert'>
                            <strong class='font-bold'>Success!</strong>
                            <span class='block sm:inline'>You are registered successfully. Redirecting to login...</span>
                          </div>";
                    header("Location: login.php");
                    exit();
                } else {
                    die("Something went wrong");
                }
            }
        }
        ?>

        <h2 class="text-2xl font-semibold mb-6 text-center">Registration</h2>

        <form action="registration.php" method="post">
            <div class="mb-4">
                <input type="text" class="w-full px-4 py-2 border rounded focus:outline-none focus:ring focus:border-blue-300" name="idno" id="idno" placeholder="ID NO:" value="<?php echo isset($idno) ? $idno : ''; ?>" oninput="mirrorusername()">
            </div>
            <div class="mb-4">
                <input type="text" class="w-full px-4 py-2 border rounded focus:outline-none focus:ring focus:border-blue-300" name="lastname" placeholder="Last Name:" value="<?php echo isset($lastname) ? $lastname : ''; ?>">
            </div>
            <div class="mb-4">
                <input type="text" class="w-full px-4 py-2 border rounded focus:outline-none focus:ring focus:border-blue-300" name="firstname" placeholder="First Name:" value="<?php echo isset($firstname) ? $firstname : ''; ?>">
            </div>
            <div class="mb-4">
                <input type="text" class="w-full px-4 py-2 border rounded focus:outline-none focus:ring focus:border-blue-300" name="midname" placeholder="Middle Name:" value="<?php echo isset($midname) ? $midname : ''; ?>">
            </div>
            <div class="mb-4">
                <label for="course" class="block text-sm font-medium text-gray-700">Course:</label>
                <select class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-200 focus:border-blue-300" name="course" id="course">
                    <option value="Select" <?php echo (isset($course) && $course == 'Select') ? 'selected' : ''; ?>>Select Course</option>
                    <option value="BSIT" <?php echo (isset($course) && $course == 'BSIT') ? 'selected' : ''; ?>>BSIT</option>
                    <option value="BSCS" <?php echo (isset($course) && $course == 'BSCS') ? 'selected' : ''; ?>>BSCS</option>
                    <option value="ACT" <?php echo (isset($course) && $course == 'ACT') ? 'selected' : ''; ?>>ACT</option>
                    <option value="BSCE" <?php echo (isset($course) && $course == 'ACT') ? 'selected' : ''; ?>>BSCE</option>
                    <option value="BSA" <?php echo (isset($course) && $course == 'ACT') ? 'selected' : ''; ?>>BSA</option>
                    <option value="BSEE" <?php echo (isset($course) && $course == 'ACT') ? 'selected' : ''; ?>>BSEE</option>
                </select>
            </div>
            <div class="mb-4">
                <label for="yearlvl" class="block text-sm font-medium text-gray-700">Year Level:</label>
                <select class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-200 focus:border-blue-300" name="yearlvl" id="yearlvl">
                    <option value="Select" <?php echo (isset($yearlvl) && $yearlvl == 'Select') ? 'selected' : ''; ?>>Select Year Level</option>
                    <option value="1" <?php echo (isset($yearlvl) && $yearlvl == '1') ? 'selected' : ''; ?>>1</option>
                    <option value="2" <?php echo (isset($yearlvl) && $yearlvl == '2') ? 'selected' : ''; ?>>2</option>
                    <option value="3" <?php echo (isset($yearlvl) && $yearlvl == '3') ? 'selected' : ''; ?>>3</option>
                    <option value="4" <?php echo (isset($yearlvl) && $yearlvl == '4') ? 'selected' : ''; ?>>4</option>
                </select>
            </div>
            <div class="mb-4">
                <input type="text" class="w-full px-4 py-2 border rounded focus:outline-none focus:ring focus:border-blue-300" name="emailadd" placeholder="Email Address:" value="<?php echo isset($emailadd) ? $emailadd : ''; ?>">
            </div>
            <div class="mb-4">
                <input type="text" class="w-full px-4 py-2 border rounded focus:outline-none focus:ring focus:border-blue-300" name="username" id="username" placeholder="Username:" value="<?php echo isset($username) ? $username : ''; ?>" readonly>
            </div>
            <div class="mb-6">
                <input type="password" class="w-full px-4 py-2 border rounded focus:outline-none focus:ring focus:border-blue-300" name="password" placeholder="Password:" value="<?php echo isset($password) ? $password : ''; ?>">
            </div>
            <div class="text-center">
                <input type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded" value="Register" name="submit">
            </div>
        </form>
        <div class="mt-4 text-center">
            <p>Already Registered? <a href="login.php" class="text-blue-500 hover:underline">Login</a></p>
        </div>
    </div>

    <script>
        function mirrorusername() {
            document.getElementById('username').value = document.getElementById('idno').value;
        }
    </script>

</body>
</html>