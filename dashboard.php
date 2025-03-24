<?php
session_start();
include 'db.php';

// Redirect to login if not logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];

// Fetch user details, including idno (student ID)
$sql = "SELECT id, idno, lastname, firstname, middlename, course, yearlevel, email, address, profile_image FROM users WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// If user is not found, exit
if (!$user) {
    die("Error: User not found.");
}

// Check if the user has been logged out by the admin
$student_id = $user['idno'] ?? null;
if ($student_id) {
    $logout_check_sql = "SELECT logged_out FROM sit_in_records WHERE student_id = ? ORDER BY stt_id DESC LIMIT 1";
    $logout_check_stmt = $conn->prepare($logout_check_sql);
    $logout_check_stmt->bind_param("s", $student_id);
    $logout_check_stmt->execute();
    $logout_check_result = $logout_check_stmt->get_result();
    $logout_data = $logout_check_result->fetch_assoc();

    if ($logout_data && $logout_data['logged_out'] == 1) {
        // Log the user out
        echo "<script>
            alert('You have been logged out by an admin.');
            window.location.href = 'login.php';
        </script>";

        // Optionally reset the logged_out flag in the database
        $reset_logout_sql = "UPDATE sit_in_records SET logged_out = 0 WHERE student_id = ?";
        $reset_logout_stmt = $conn->prepare($reset_logout_sql);
        $reset_logout_stmt->bind_param("s", $student_id);
        $reset_logout_stmt->execute();

        exit();
    }
}

// Prepare user data
$full_name = trim(($user['firstname'] ?? '') . " " . (($user['middlename'] ?? '') ? $user['middlename'] . " " : '') . ($user['lastname'] ?? ''));
$course = $user['course'] ?? 'Not Available';
$year = $user['yearlevel'] ?? 'Not Available';
$email = $user['email'] ?? 'Not Available';
$address = $user['address'] ?? 'Not Available';
$profile_image = !empty($user['profile_image']) && $user['profile_image'] !== 'cat.jpg'
    ? 'image/' . htmlspecialchars($user['profile_image'])
    : './cat.jpg';

// Debugging: Check if student ID (idno) is retrieved correctly
$student_id = $user['idno'] ?? null;
if (!$student_id) {
    die("Error: Student ID (idno) not found.");
}

// Fetch the latest sit-in session for the user
$reserved_session = 'No session reserved';

$sit_in_sql = "SELECT session, status FROM sit_in_records WHERE student_id = ? ORDER BY stt_id DESC LIMIT 1";
$sit_in_stmt = $conn->prepare($sit_in_sql);
$sit_in_stmt->bind_param("s", $student_id);
$sit_in_stmt->execute();
$sit_in_result = $sit_in_stmt->get_result();
$sit_in_data = $sit_in_result->fetch_assoc();

if ($sit_in_data) {
    $reserved_session = $sit_in_data['session'];

    // Update users table with the latest session
    $update_sql = "UPDATE users SET session = ? WHERE idno = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ss", $sit_in_data['session'], $student_id);
    $update_stmt->execute();
} else {
    $reserved_session = "No session reserved";
}

// Display success message if available
if (isset($_SESSION['success_message'])) {
    echo "<script>alert('" . $_SESSION['success_message'] . "');</script>";
    unset($_SESSION['success_message']);
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-gray-100">
    <!-- Header -->
    <div class="bg-[#F8F1E7] text-gray-900 flex justify-between items-center p-4 border-b-2 border-yellow-500">
        <h1 class="text-2xl font-bold">CCS Sit-in Monitoring</h1>
        <div class="space-x-2">
            <button class="px-4 py-2 bg-yellow-500 rounded hover:bg-yellow-600" onclick="location.href='edit.php'">Edit</button>
            <button class="px-4 py-2 bg-yellow-500 rounded hover:bg-yellow-600" onclick="location.href='sitinrules.php'">Sit-in Rules</button>
            <button class="px-4 py-2 bg-yellow-500 rounded hover:bg-yellow-600" onclick="location.href='history.php'">History</button>
            <button class="px-4 py-2 bg-yellow-500 rounded hover:bg-yellow-600" onclick="location.href='reservation.php'">Reservation</button>
            <button class="px-4 py-2 bg-red-500 rounded hover:bg-red-600" onclick="openLogoutModal()">Logout</button>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mx-auto mt-6 grid grid-cols-1 md:grid-cols-3 gap-6 p-4">
        <!-- Profile Information -->
        <div class="bg-white text-gray-900 p-6 rounded-lg shadow-md text-center">
            <h2 class="text-xl font-bold mb-4">Profile Information</h2>
            <img src="image/<?php echo !empty($user['profile_image']) ? htmlspecialchars($user['profile_image']) : 'cat.jpg'; ?>"
                 class="w-32 h-32 rounded-full mx-auto border-4 border-gray-800"
                 alt="Profile Picture">
            <div class="mt-4 text-left">
                <p><strong>Name:</strong> <?php echo htmlspecialchars($full_name); ?></p>
                <p><strong>Course:</strong> <?php echo htmlspecialchars($course); ?></p>
                <p><strong>Year:</strong> <?php echo htmlspecialchars($year); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($email); ?></p>
                <p><strong>Address:</strong> <?php echo htmlspecialchars($address); ?></p>
                <p><strong>Reserved Session:</strong> <span id="sessionInfo"><?php echo htmlspecialchars($reserved_session); ?></span></p>
            </div>
        </div>

        <!-- Announcements -->
        <div class="bg-white text-gray-900 p-6 rounded-lg shadow-md overflow-auto h-96">
            <h2 class="text-xl font-bold mb-4">📢 Announcements</h2>
            <?php
            $announcement_sql = "SELECT title, message, posted_by, DATE_FORMAT(created_at, '%Y-%b-%d') AS formatted_date FROM announcements ORDER BY created_at DESC";
            $announcement_result = $conn->query($announcement_sql);

            if ($announcement_result->num_rows > 0):
                while ($announcement = $announcement_result->fetch_assoc()):
            ?>
                <div class="border-b py-2">
                    <strong><?php echo htmlspecialchars($announcement['posted_by']) . " | " . htmlspecialchars($announcement['formatted_date']); ?></strong>
                    <p><strong><?php echo htmlspecialchars($announcement['title']); ?></strong></p>
                    <p><?php echo htmlspecialchars($announcement['message']); ?></p>
                </div>
            <?php
                endwhile;
            else:
            ?>
                <p>No announcements available.</p>
            <?php endif; ?>
        </div>

        <!-- Rules & Regulations -->
        <div class="bg-white text-gray-900 p-6 rounded-lg shadow-md overflow-auto h-96">
            <h2 class="text-xl font-bold mb-4">📜 Rules and Regulations</h2>
            <ul class="list-disc list-inside">
                <li>Maintain silence and discipline inside the laboratory.</li>
                <li>Games are not allowed inside the lab.</li>
                <li>Internet surfing is only allowed with permission.</li>
                <li>Do not access inappropriate websites.</li>
                <li>Observe computer usage time limits.</li>
            </ul>
        </div>
    </div>

    <!-- Logout Modal -->
    <div id="logoutModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
        <div class="bg-white text-gray-900 p-6 rounded-lg text-center shadow-md">
            <p class="mb-4">Are you sure you want to log out?</p>
            <button id="proceedLogout" class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600">Yes</button>
            <button id="cancelLogout" class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">No</button>
        </div>
    </div>

    <script>
        function openLogoutModal() {
            document.getElementById('logoutModal').classList.remove('hidden');
        }
        function closeLogoutModal() {
            document.getElementById('logoutModal').classList.add('hidden');
        }
        document.getElementById('proceedLogout').addEventListener('click', function() {
            window.location.href = 'logout.php';
        });
        document.getElementById('cancelLogout').addEventListener('click', closeLogoutModal);

        // Function to fetch the latest session data
function fetchCurrentSitIn() {
    fetch('currentSitin.php?action=fetch_session')
        .then(response => {
            if (!response.ok) {
                throw new Error('Session data not found');
            }
            return response.json();
        })
        .then(data => {
            if (data) {
                document.getElementById("sitInInfo").innerHTML = `
                    <p><strong>Session:</strong> ${data.session}</p>
                    <p><strong>Status:</strong> ${data.status}</p>
                `;
            } else {
                document.getElementById("sitInInfo").innerHTML = `<p>No ongoing sit-in session.</p>`;
            }
        })
        .catch(error => {
            console.error("Error fetching session data:", error);
            document.getElementById("sitInInfo").innerHTML = `<p>Failed to load sit-in data.</p>`;
        });
}

// Fetch data when page loads
document.addEventListener("DOMContentLoaded", fetchCurrentSitIn);

    </script>
</body>
</html>
