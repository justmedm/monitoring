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

// Debug: Check database connection
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Fetch statistics dynamically from the database
$stats_query = "SELECT COUNT(*) AS total_users FROM users"; // Updated to 'users' table
$stats_result = mysqli_query($conn, $stats_query);
if (!$stats_result) {
    die("Error fetching user count: " . mysqli_error($conn));
}
$stats = mysqli_fetch_assoc($stats_result);

// Fetch total number of sit-in records
$sessions_query = "SELECT COUNT(*) AS total_sessions FROM sit_in_records"; // Corrected to sit_in_records table
$sessions_result = mysqli_query($conn, $sessions_query);
if (!$sessions_result) {
    die("Error fetching total sessions: " . mysqli_error($conn));
}
$sessions = mysqli_fetch_assoc($sessions_result);

// Ensure the necessary variables are defined
$total_active_today = 0;
$total_sessions = 0;
$total_students = 0;

// Fetch active students for today (if needed)
$active_today_query = "SELECT COUNT(*) AS active_today FROM sit_in_records WHERE time_out IS NULL";
$active_today_result = mysqli_query($conn, $active_today_query);
if ($active_today_result) {
    $active_today = mysqli_fetch_assoc($active_today_result);
    $total_active_today = $active_today['active_today'];
} else {
    die("Error fetching active students for today: " . mysqli_error($conn));
}

// Fetch total number of sit-in records
$total_sessions = $sessions['total_sessions'];

// Fetch total number of registered students
$total_students = $stats['total_users']; // Use the correct field name

// Fetch the distribution of sit-ins per subject
$subject_query = "SELECT purpose, COUNT(*) AS subject_count FROM sit_in_records GROUP BY purpose";
$subject_result = mysqli_query($conn, $subject_query);
if (!$subject_result) {
    die("Error fetching subject distribution: " . mysqli_error($conn));
}

// Prepare data for the chart
$subjects = [];
$counts = [];
while ($row = mysqli_fetch_assoc($subject_result)) {
    $subjects[] = $row['purpose'];
    $counts[] = $row['subject_count'];
}

// Handle announcement submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['announcement'])) {
    $announcement = mysqli_real_escape_string($conn, $_POST['announcement']);
    $admin = $_SESSION["admin"]; // Get admin username
    $insert_query = "INSERT INTO announcements (title, created_at, admin) VALUES ('$announcement', NOW(), '$admin')";

    if (!mysqli_query($conn, $insert_query)) {
        die("Error inserting announcement: " . mysqli_error($conn));
    }
    header("Location: admin_dashboard.php");
    exit();
}

// Fetch announcements
$announcement_query = "SELECT * FROM announcements ORDER BY created_at DESC";
$announcement_result = mysqli_query($conn, $announcement_query);
if (!$announcement_result) {
    die("Error in announcements query: " . mysqli_error($conn));
}

// Count current active sit-ins
$active_sit_in_query = "SELECT COUNT(*) AS active_count FROM sit_in_records WHERE time_out IS NULL";
$active_sit_in_result = mysqli_query($conn, $active_sit_in_query);
$active_sit_in_count = 0;
if ($active_sit_in_result) {
    $active_data = mysqli_fetch_assoc($active_sit_in_result);
    $active_sit_in_count = $active_data['active_count'];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="icon" type="image/x-icon" href="ccs.png">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<style>
        /* Custom colors using Tailwind's @apply directive */
        .bg-dark-blue { background-color: #2A3735; }
        .text-light-pink { color: #ABAAAA; }
        .bg-light-pink { background-color: #ABAAAA; }
        .text-dark-green { color: #3A3A3A !important; }
        .bg-light-gray { background-color: #C3BCC2 !important; }
        .border-gray { border-color: #494D49; }
    </style>
</head>
<body class="bg-light-gray text-dark-green">
    <!-- Navigation Bar -->
    <div class="bg-dark-blue text-white p-4 flex justify-between items-center shadow-md">
        <h1 class="text-2xl font-bold">CCS Admin</h1>
        <div class="flex items-center space-x-4">
        <a href="admindashboard.php" class="hover:underline text-light-pink">Home</a>
            <a href="students.php" class="hover:underline text-light-pink">Students</a>
            <a href="sit_in.php" class="hover:underline text-light-pink">Sit-in</a>
            <a href="view_records.php" class="hover:underline text-light-pink flex items-center">
                View Sit-in Records
                <?php if ($active_sit_in_count > 0): ?>
                    <span class="bg-red-500 text-white text-xs rounded-full px-2 py-1 ml-2"><?php echo $active_sit_in_count; ?></span>
                <?php endif; ?>
            </a>
            <a href="sit_in_reports.php" class="hover:underline text-light-pink">Sit-in Reports</a>
            <a href="feedback_reports.php" class="hover:underline text-light-pink">Feedback Reports</a>
            <a href="reservation.php" class="hover:underline text-light-pink">Reservation</a>
            <a href="logout.php" class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600">Log out</a>
        </div>
    </div>


    <div class="container mx-auto mt-4">
        <!-- Alert messages for success/error -->
        <?php if (isset($_SESSION['sitin_success'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                <?= $_SESSION['sitin_success'] ?>
                <button type="button" class="absolute top-0 bottom-0 right-0 px-4 py-3" data-bs-dismiss="alert" aria-label="Close">
                    <svg class="fill-current h-6 w-6 text-green-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><title>Close</title><path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697L8.383 10.5 5.652 7.769a1.2 1.2 0 0 1 1.697-1.697L10 8.781l2.651-3.03a1.2 1.2 0 0 1 1.697 1.697L11.617 10.5l2.651 3.03z"></path></svg>
                </button>
            </div>
            <?php unset($_SESSION['sitin_success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['sitin_error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <?= $_SESSION['sitin_error'] ?>
                <button type="button" class="absolute top-0 bottom-0 right-0 px-4 py-3" data-bs-dismiss="alert" aria-label="Close">
                    <svg class="fill-current h-6 w-6 text-red-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><title>Close</title><path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697L8.383 10.5 5.652 7.769a1.2 1.2 0 0 1 1.697-1.697L10 8.781l2.651-3.03a1.2 1.2 0 0 1 1.697 1.697L11.617 10.5l2.651 3.03z"></path></svg>
                </button>
            </div>
            <?php unset($_SESSION['sitin_error']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['announcement_success'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                <?= $_SESSION['announcement_success'] ?>
                <button type="button" class="absolute top-0 bottom-0 right-0 px-4 py-3" data-bs-dismiss="alert" aria-label="Close">
                    <svg class="fill-current h-6 w-6 text-green-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><title>Close</title><path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697L8.383 10.5 5.652 7.769a1.2 1.2 0 0 1 1.697-1.697L10 8.781l2.651-3.03a1.2 1.2 0 0 1 1.697 1.697L11.617 10.5l2.651 3.03z"></path></svg>
                </button>
            </div>
            <?php unset($_SESSION['announcement_success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['announcement_error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <?= $_SESSION['announcement_error'] ?>
                <button type="button" class="absolute top-0 bottom-0 right-0 px-4 py-3" data-bs-dismiss="alert" aria-label="Close">
                    <svg class="fill-current h-6 w-6 text-red-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><title>Close</title><path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697L8.383 10.5 5.652 7.769a1.2 1.2 0 0 1 1.697-1.697L10 8.781l2.651-3.03a1.2 1.2 0 0 1 1.697 1.697L11.617 10.5l2.651 3.03z"></path></svg>
                </button>
            </div>
            <?php unset($_SESSION['announcement_error']); ?>
        <?php endif; ?>

        <!-- Chart Section -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-white shadow-md rounded-lg overflow-hidden">
                <div class="bg-blue-500 text-white px-4 py-2 font-semibold flex items-center">
                    <i class="fas fa-chart-pie mr-2"></i> Statistics
                </div>
                <div class="p-4">
                    <p><strong>Students Registered:</strong> <?= $total_students ?></p>
                    <p><strong>Currently Sit-in:</strong> <?= $active_sit_in_count ?></p>
                    <p><strong>Total Sit-in:</strong> <?= $total_sessions ?></p>
                    <div class="mt-4">
                        <canvas id="subjectPieChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="bg-white shadow-md rounded-lg overflow-hidden">
                <div class="bg-blue-500 text-white px-4 py-2 font-semibold flex items-center">
                    <i class="fas fa-bullhorn mr-2"></i> Announcement
                </div>
                <div class="p-4">
                    <form method="POST" action="process_announcement.php">
                        <div class="mb-3">
                            <label for="announcement" class="block text-gray-700">New Announcement</label>
                            <textarea class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500" id="announcement" name="announcement" rows="3" required></textarea>
                        </div>
                        <div class="flex">
                            <button type="submit" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded w-full">Submit</button>
                        </div>
                    </form>

                    <hr class="my-4">

                    <h5 class="text-lg font-semibold">Posted Announcement</h5>
                    <div class="announcement-list mt-2 space-y-2">
                        <?php
                        // Fetch recent announcements
                        $announcements_query = "SELECT * FROM announcements ORDER BY created_at DESC LIMIT 5";
                        $announcements_result = mysqli_query($conn, $announcements_query);

                        if (mysqli_num_rows($announcements_result) > 0) {
                            while ($announcement = mysqli_fetch_assoc($announcements_result)) {
                                $date = date('Y-M-d', strtotime($announcement['created_at']));
                                echo "<div class='border-b pb-2 mb-2'>
                                        <div class='flex justify-between items-center'>
                                            <p class='text-gray-700 font-semibold'><strong>CCS Admin | {$date}</strong></p>
                                            <div>
                                                <a href='edit_announcement.php?id={$announcement['id']}' class='text-blue-500 hover:underline mr-2'>Edit</a>
                                                <a href='delete_announcement.php?id={$announcement['id']}' class='text-red-500 hover:underline' onclick='return confirm(\"Are you sure you want to delete this announcement?\")'>Delete</a>
                                            </div>
                                        </div>
                                        <p class='mt-1 text-gray-600'>{$announcement['message']}</p>
                                      </div>";
                            }
                        } else {
                            echo "<p class='text-gray-500'>No announcements available.</p>";
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pie Chart for Sit-in Distribution -->
        <div class="stats-container hidden">
            <h2 class="text-center text-2xl font-semibold mb-4">Overall Statistics</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-white shadow-md rounded-lg p-4">
                    <canvas id="subjectPieChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Modal for Search Student -->
        <div id="searchModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden">
            <div class="bg-white rounded-lg w-full max-w-md mx-auto">
                <div class="border-b px-4 py-2 flex justify-between items-center">
                    <h5 class="text-lg font-semibold">Search Student</h5>
                    <button class="text-gray-500 hover:text-gray-700" data-bs-dismiss="modal" aria-label="Close">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div class="p-4">
                    <form id="searchStudentForm">
                        <div class="mb-3">
                            <label for="student_id_search" class="block text-gray-700">Enter Student ID</label>
                            <input type="text" class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500" id="student_id_search" name="search" placeholder="Search..." required>
                        </div>
                        <button type="button" id="searchStudentBtn" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded w-full">Search</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal for Students (with actions for edit/delete/change password) -->
        <div id="studentsModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden">
            <div class="bg-white rounded-lg w-full max-w-lg mx-auto">
                <div class="border-b px-4 py-2 flex justify-between items-center">
                    <h5 class="text-lg font-semibold">All Registered Students</h5>
                    <button class="text-gray-500 hover:text-gray-700" data-bs-dismiss="modal" aria-label="Close">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div class="p-4">
                    <table class="min-w-full bg-white">
                        <thead>
                            <tr>
                                <th class="py-2 px-4 border-b">ID Number</th>
                                <th class="py-2 px-4 border-b">Name</th>
                                <th class="py-2 px-4 border-b">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $students_query = "SELECT * FROM users";
                            $students_result = mysqli_query($conn, $students_query);
                            while ($student = mysqli_fetch_assoc($students_result)) {
                                echo "<tr>
                                        <td class='py-2 px-4 border-b'>{$student['idno']}</td>
                                        <td class='py-2 px-4 border-b'>{$student['lastname']}, {$student['firstname']}</td>
                                        <td class='py-2 px-4 border-b'>
                                            <button class='bg-blue-500 hover:bg-blue-600 text-white px-2 py-1 rounded' data-bs-toggle='modal' data-bs-target='#editStudentModal'>Edit</button>
                                            <button class='bg-red-500 hover:bg-red-600 text-white px-2 py-1 rounded' data-bs-toggle='modal' data-bs-target='#deleteStudentModal'>Delete</button>
                                            <button class='bg-yellow-500 hover:bg-yellow-600 text-white px-2 py-1 rounded' data-bs-toggle='modal' data-bs-target='#changePasswordModal'>Change Password</button>
                                        </td>
                                    </tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Add your modals for Edit, Delete, and Change Password -->

        <!-- Modal for Sit-in -->
        <div id="currentSitInModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden">
            <div class="bg-white rounded-lg w-full max-w-md mx-auto">
                <div class="border-b px-4 py-2 flex justify-between items-center">
                    <h5 class="text-lg font-semibold">Sit In Form</h5>
                    <button class="text-gray-500 hover:text-gray-700" data-bs-dismiss="modal" aria-label="Close">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div class="p-4">
                    <form id="sitInForm" method="POST" action="process_sitin.php">
                        <div class="mb-3">
                            <label for="id_number" class="block text-gray-700">ID Number:</label>
                            <input type="text" class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500" id="id_number" name="id_number" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="student_name" class="block text-gray-700">Student Name:</label>
                            <input type="text" class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500" id="student_name" name="student_name" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="purpose" class="block text-gray-700">Purpose:</label>
                            <select class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500" id="purpose" name="purpose" required>
                                <option value="" selected>Select Purpose</option>
                                <option value="C Programming">C Programming</option>
                                <option value="Java Programming">Java Programming</option>
                                <option value="PHP Programming">PHP Programming</option>
                                <option value="Programming">Programming</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="lab" class="block text-gray-700">Lab:</label>
                            <select class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500" id="lab" name="lab" required>
                                <option value="" selected>Select Laboratory</option>
                                <option value="524">524</option>
                                <option value="526">526</option>
                                <option value="528">528</option>
                                <option value="530">530</option>
                                <option value="542">542</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="remaining_session" class="block text-gray-700">Remaining Session:</label>
                            <input type="text" class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500" id="remaining_session" name="remaining_session" readonly>
                        </div>
                        <div class="flex justify-end space-x-2">
                            <button type="button" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded" id="sitInButton">Sit In</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal for View Current Sit-in Students -->
        <div id="viewSitInModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden">
            <div class="bg-white rounded-lg w-full max-w-4xl mx-auto">
                <div class="border-b px-4 py-2 flex justify-between items-center">
                    <h5 class="text-lg font-semibold">Current Sit-in Records</h5>
                    <button class="text-gray-500 hover:text-gray-700" data-bs-dismiss="modal" aria-label="Close">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div class="p-4">
                    <!-- Two Pie Charts in a row -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <!-- Programming Language Pie Chart -->
                        <div class="bg-white shadow-md rounded-lg p-4">
                            <canvas id="languagePieChart"></canvas>
                        </div>
                        <!-- Lab Distribution Pie Chart -->
                        <div class="bg-white shadow-md rounded-lg p-4">
                            <canvas id="labPieChart"></canvas>
                        </div>
                    </div>

                    <div class="mb-3 flex items-center space-x-4">
                        <select class="px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500" id="entriesPerPage">
                            <option value="10" selected>10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        <label for="entriesPerPage" class="text-gray-700">entries per page</label>

                        <div class="ml-auto flex items-center space-x-2">
                            <label for="sitInSearch" class="text-gray-700">Search:</label>
                            <input type="text" class="px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500" id="sitInSearch" name="sitInSearch">
                        </div>
                    </div>

                    <table class="min-w-full bg-white border rounded-lg overflow-hidden">
                        <thead>
                            <tr>
                                <th class="py-2 px-4 border-b">Sit-in Number</th>
                                <th class="py-2 px-4 border-b">ID Number</th>
                                <th class="py-2 px-4 border-b">Name</th>
                                <th class="py-2 px-4 border-b">Purpose</th>
                                <th class="py-2 px-4 border-b">Lab</th>
                                <th class="py-2 px-4 border-b">Login</th>
                                <th class="py-2 px-4 border-b">Logout</th>
                                <th class="py-2 px-4 border-b">Date</th>
                            </tr>
                        </thead>
                        <tbody id="currentSitInTableBody">
                            <?php
                            // Fetch sit-in records
                            $sit_in_query = "SELECT s.*, u.firstname, u.lastname
                                           FROM sit_in_records s
                                           LEFT JOIN users u ON s.student_id = u.idno
                                           ORDER BY s.time_in DESC";
                            $sit_in_result = mysqli_query($conn, $sit_in_query);

                            // Prepare data for lab distribution chart
                            $lab_data = [];
                            $language_data = [];

                            if (mysqli_num_rows($sit_in_result) > 0) {
                                while ($sit_in = mysqli_fetch_assoc($sit_in_result)) {
                                    // Collect data for charts
                                    if (!isset($lab_data[$sit_in['sitlab']])) {
                                        $lab_data[$sit_in['sitlab']] = 0;
                                    }
                                    $lab_data[$sit_in['sitlab']]++;

                                    if (!isset($language_data[$sit_in['purpose']])) {
                                        $language_data[$sit_in['purpose']] = 0;
                                    }
                                    $language_data[$sit_in['purpose']]++;

                                    // Display table row
                                    $name = isset($sit_in['firstname']) ? $sit_in['firstname'] . ' ' . $sit_in['lastname'] : 'Unknown';
                                    $login_time = date('h:i:sa', strtotime($sit_in['time_in']));
                                    $logout_time = ($sit_in['time_out']) ? date('h:i:sa', strtotime($sit_in['time_out'])) : 'Active';
                                    $date = date('Y-m-d', strtotime($sit_in['time_in']));

                                    echo "<tr>
                                            <td class='py-2 px-4 border-b'>{$sit_in['id']}</td>
                                            <td class='py-2 px-4 border-b'>{$sit_in['student_id']}</td>
                                            <td class='py-2 px-4 border-b'>{$name}</td>
                                            <td class='py-2 px-4 border-b'>{$sit_in['purpose']}</td>
                                            <td class='py-2 px-4 border-b'>{$sit_in['sitlab']}</td>
                                            <td class='py-2 px-4 border-b'>{$login_time}</td>
                                            <td class='py-2 px-4 border-b'>{$logout_time}</td>
                                            <td class='py-2 px-4 border-b'>{$date}</td>
                                        </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='8' class='py-2 px-4 border-b'>No data available</td></tr>";
                            }

                            // Prepare chart data in JSON
                            $lab_labels = array_keys($lab_data);
                            $lab_counts = array_values($lab_data);

                            $language_labels = array_keys($language_data);
                            $language_counts = array_values($language_data);
                            ?>
                        </tbody>
                    </table>
                    <div class="flex justify-between items-center mt-4">
                        <div>Showing 1 to 1 of 1 entry</div>
                        <div class="space-x-2">
                            <button class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded">1</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal for Sit-in Reports -->
        <div id="sitInReportsModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden">
            <div class="bg-white rounded-lg w-full max-w-4xl mx-auto">
                <div class="border-b px-4 py-2 flex justify-between items-center">
                    <h5 class="text-lg font-semibold">Generate Reports</h5>
                    <button class="text-gray-500 hover:text-gray-700" data-bs-dismiss="modal" aria-label="Close">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div class="p-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-3">
                        <div>
                            <input type="date" class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500" id="reportDate">
                        </div>
                        <div>
                            <button class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded w-full" id="searchReport">Search</button>
                        </div>
                        <div>
                            <button class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded w-full" id="resetReport">Reset</button>
                        </div>
                    </div>

                    <div class="mb-3 flex justify-end items-center space-x-2">
                        <label for="reportFilter" class="text-gray-700">Filter:</label>
                        <input type="text" class="px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500" id="reportFilter" name="reportFilter">
                    </div>

                    <table class="min-w-full bg-white border rounded-lg overflow-hidden">
                        <thead>
                            <tr>
                                <th class="py-2 px-4 border-b">ID Number</th>
                                <th class="py-2 px-4 border-b">Name</th>
                                <th class="py-2 px-4 border-b">Purpose</th>
                                <th class="py-2 px-4 border-b">Laboratory</th>
                                <th class="py-2 px-4 border-b">Login</th>
                                <th class="py-2 px-4 border-b">Logout</th>
                                <th class="py-2 px-4 border-b">Date</th>
                            </tr>
                        </thead>
                        <tbody id="reportTableBody">
                            <?php
                            // Fetch all sit-in records
                            $reports_query = "SELECT s.*, u.firstname, u.lastname
                                             FROM sit_in_records s
                                             LEFT JOIN users u ON s.student_id = u.idno
                                             ORDER BY s.time_in DESC";
                            $reports_result = mysqli_query($conn, $reports_query);
                            if (mysqli_num_rows($reports_result) > 0) {
                                while ($report = mysqli_fetch_assoc($reports_result)) {
                                    $login_time = date('h:i:s a', strtotime($report['time_in']));
                                    $logout_time = ($report['time_out']) ? date('h:i:s a', strtotime($report['time_out'])) : 'Active';
                                    $date = date('Y-m-d', strtotime($report['time_in']));
                                    $name = isset($report['firstname']) ? $report['firstname'] . ' ' . $report['lastname'] : 'Unknown';

                                    echo "<tr>
                                            <td class='py-2 px-4 border-b'>{$report['student_id']}</td>
                                            <td class='py-2 px-4 border-b'>{$name}</td>
                                            <td class='py-2 px-4 border-b'>{$report['purpose']}</td>
                                            <td class='py-2 px-4 border-b'>{$report['sitlab']}</td>
                                            <td class='py-2 px-4 border-b'>{$login_time}</td>
                                            <td class='py-2 px-4 border-b'>{$logout_time}</td>
                                            <td class='py-2 px-4 border-b'>{$date}</td>
                                        </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='7' class='py-2 px-4 border-b'>No data available</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal for Pending Reservations -->
        <div id="pendingReservationsModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden">
            <div class="bg-white rounded-lg w-full max-w-md mx-auto">
                <div class="border-b px-4 py-2 flex justify-between items-center">
                    <h5 class="text-lg font-semibold">Pending Reservations</h5>
                    <button class="text-gray-500 hover:text-gray-700" data-bs-dismiss="modal" aria-label="Close">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div class="p-4">
                    <!-- Content for pending reservations -->
                    <p>Reservation functionality will be implemented here.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart.js for Pie Chart -->
    <script>
        // Chart.js script for pie chart
        var ctx = document.getElementById('subjectPieChart').getContext('2d');
        var subjectPieChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode($subjects); ?>,
                datasets: [{
                    label: 'Sit-ins per Subject',
                    data: <?php echo json_encode($counts); ?>,
                    backgroundColor: ['#ff6384', '#36a2eb', '#ffcd56', '#4bc0c0', '#f742c8', '#9966ff'],
                    borderColor: '#fff',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(tooltipItem) {
                                return tooltipItem.label + ": " + tooltipItem.raw + " Sit-ins";
                            }
                        }
                    }
                }
            }
        });

        // Charts for View Sit-in Records Modal
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize language distribution chart
            var languageChartCtx = document.getElementById('languagePieChart');
            if (languageChartCtx) {
                var languagePieChart = new Chart(languageChartCtx, {
                    type: 'pie',
                    data: {
                        labels: <?php echo json_encode($language_labels ?? []); ?>,
                        datasets: [{
                            label: 'Programming Languages',
                            data: <?php echo json_encode($language_counts ?? []); ?>,
                            backgroundColor: ['#ff6384', '#36a2eb', '#ffcd56', '#4bc0c0', '#f742c8', '#9966ff'],
                            borderColor: '#fff',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'top',
                            },
                            title: {
                                display: true,
                                text: 'Programming Languages'
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(tooltipItem) {
                                        return tooltipItem.label + ": " + tooltipItem.raw;
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // Initialize lab distribution chart
            var labChartCtx = document.getElementById('labPieChart');
            if (labChartCtx) {
                var labColors = {
                    '524': '#ff9999',
                    '526': '#ffd699',
                    '528': '#ffff99',
                    '530': '#99ff99',
                    '542': '#99ffff',
                    'Mac': '#ff99ff'
                };

                var labLabels = <?php echo json_encode($lab_labels ?? []); ?>;
                var labBackgroundColors = labLabels.map(lab => labColors[lab] || '#36a2eb');

                var labPieChart = new Chart(labChartCtx, {
                    type: 'pie',
                    data: {
                        labels: labLabels,
                        datasets: [{
                            label: 'Labs',
                            data: <?php echo json_encode($lab_counts ?? []); ?>,
                            backgroundColor: labBackgroundColors,
                            borderColor: '#fff',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'top',
                            },
                            title: {
                                display: true,
                                text: 'Laboratory Distribution'
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(tooltipItem) {
                                        return "Laboratory " + tooltipItem.label + ": " + tooltipItem.raw;
                                    }
                                }
                            }
                        }
                    }
                });
            }
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Add JavaScript for sit-in functionality -->
    <script>
        // Function to handle search and show sit-in form
        function handleStudentSearch(studentId) {
            if (studentId) {
                // Fetch student info and populate sit-in form
                fetch(`get_student.php?id=${studentId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Fill in the form fields with student data
                            document.getElementById('id_number').value = studentId;
                            document.getElementById('student_name').value = data.student.firstname + ' ' + data.student.lastname;
                            document.getElementById('remaining_session').value = data.remaining_session;

                            // Show the sit-in form modal
                            const sitInModal = new bootstrap.Modal(document.getElementById('currentSitInModal'));
                            sitInModal.show();
                        } else {
                            alert(data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error fetching student information');
                    });
            } else {
                alert('Please enter a student ID');
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Handle search button click
            const searchStudentBtn = document.getElementById('searchStudentBtn');
            if (searchStudentBtn) {
                searchStudentBtn.addEventListener('click', function() {
                    const studentId = document.getElementById('student_id_search').value.trim();
                    handleStudentSearch(studentId);
                });
            }

            // Handle Enter key in search input
            const searchInput = document.getElementById('student_id_search');
            if (searchInput) {
                searchInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        const studentId = this.value.trim();
                        handleStudentSearch(studentId);
                    }
                });
            }

            // Filter functionality for reports
            const reportFilter = document.getElementById('reportFilter');
            if (reportFilter) {
                reportFilter.addEventListener('keyup', function() {
                    const filterValue = this.value.toLowerCase();
                    const rows = document.querySelectorAll('#reportTableBody tr');

                    rows.forEach(row => {
                        const text = row.textContent.toLowerCase();
                        row.style.display = text.includes(filterValue) ? '' : 'none';
                    });
                });
            }

            // Filter functionality for current sit-ins
            const sitInSearch = document.getElementById('sitInSearch');
            if (sitInSearch) {
                sitInSearch.addEventListener('keyup', function() {
                    const filterValue = this.value.toLowerCase();
                    const rows = document.querySelectorAll('#currentSitInTableBody tr');

                    rows.forEach(row => {
                        const text = row.textContent.toLowerCase();
                        row.style.display = text.includes(filterValue) ? '' : 'none';
                    });
                });
            }

            // Handle entries per page change
            const entriesPerPage = document.getElementById('entriesPerPage');
            if (entriesPerPage) {
                entriesPerPage.addEventListener('change', function() {
                    // This would typically call a function to reload data with the new page size
                    // For demo purposes, we're just reloading the page
                    window.location.reload();
                });
            }

            // Handle report date search
            const searchReportBtn = document.getElementById('searchReport');
            if (searchReportBtn) {
                searchReportBtn.addEventListener('click', function() {
                    const reportDate = document.getElementById('reportDate').value;
                    if (reportDate) {
                        // In a real implementation, this would filter the table by date
                        // For demo purposes, we're just alerting
                        alert(`Searching for reports on date: ${reportDate}`);
                    } else {
                        alert('Please select a date to search for reports.');
                    }
                });
            }

            // Handle report reset
            const resetReportBtn = document.getElementById('resetReport');
            if (resetReportBtn) {
                resetReportBtn.addEventListener('click', function() {
                    document.getElementById('reportDate').value = '';
                    // In a real implementation, this would reload all reports
                    // For demo purposes, we're just reloading the page
                    window.location.reload();
                });
            }

            // Handle export buttons
            const exportButtons = ['exportCSV', 'exportExcel', 'exportPDF', 'printReport'];
            exportButtons.forEach(buttonId => {
                const button = document.getElementById(buttonId);
                if (button) {
                    button.addEventListener('click', function() {
                        alert(`The ${buttonId} functionality would be implemented here.`);
                    });
                }
            });
        });
    </script>
</body>

</html>
