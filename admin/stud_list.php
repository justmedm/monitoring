<?php
// Start session
session_start();

if (isset($_SESSION['success_message'])) {
    echo "<script>alert('" . $_SESSION['success_message'] . "');</script>";
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    echo "<script>alert('" . $_SESSION['error_message'] . "');</script>";
    unset($_SESSION['error_message']);
}

// Database connection
include '../db.php';
include 'search.php';

// Handle AJAX search requests
if (isset($_GET['action']) && $_GET['action'] === 'search') {
    $query = $_GET['query'] ?? '';

    // Fetch records matching the search query
    $search_sql = "SELECT * FROM users 
                   WHERE idno LIKE ? 
                   OR firstname LIKE ? 
                   OR lastname LIKE ? 
                   ORDER BY id DESC";
    $search_stmt = $conn->prepare($search_sql);
    $search_query = '%' . $query . '%';
    $search_stmt->bind_param("sss", $search_query, $search_query, $search_query);
    $search_stmt->execute();
    $search_result = $search_stmt->get_result();

    // Generate the table rows dynamically
    if ($search_result->num_rows > 0) {
        while ($row = $search_result->fetch_assoc()) {
            $student_id = $row['idno'];

            // Fetch the session count from the sit_in_records table
            $session_query = "SELECT SUM(session) AS total_sessions FROM sit_in_records WHERE student_id = ?";
            $session_stmt = $conn->prepare($session_query);
            $session_stmt->bind_param("s", $student_id);
            $session_stmt->execute();
            $session_result = $session_stmt->get_result();
            $session_row = $session_result->fetch_assoc();
            $total_sessions = $session_row['total_sessions'] ?? 0;

            // Output the table row with the correct columns
            echo "<tr>
                    <td class='border p-2 text-center'>{$row['idno']}</td>
                    <td class='border p-2 text-center'>{$row['lastname']}, {$row['firstname']} {$row['middlename']}</td>
                    <td class='border p-2 text-center'>{$row['course']}</td>
                    <td class='border p-2 text-center'>{$row['yearlevel']}</td>
                    <td class='border p-2 text-center'>{$row['email']}</td>
                    <td class='border p-2 text-center'>{$total_sessions}</td>
                    <td class='border p-2 text-center'>
                        <a href='edit_student.php?id={$row['id']}' class='bg-blue-500 text-white px-2 py-1 rounded hover:bg-blue-600'>Edit</a>
                        <a href='delete_student.php?id={$row['id']}' class='bg-red-500 text-white px-2 py-1 rounded hover:bg-red-600' onclick='return confirm(\"Are you sure you want to delete this student?\")'>Delete</a>
                    </td>
                </tr>";
        }
    } else {
        echo "<tr><td colspan='7' class='border p-2 text-center text-gray-500 italic'>No matching students found</td></tr>";
    }

    $search_stmt->close();
    exit();
}

// Fetch all students for initial page load
$query = "SELECT * FROM users ORDER BY id DESC";
$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student List</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Custom colors using Tailwind's @apply directive */
        .bg-dark-blue { background-color: #2A3735; }
        .text-light-pink { color: #ABAAAA; }
        .bg-light-pink { background-color: #ABAAAA; }
        .text-dark-green { color: #3A3A3A; }
        .bg-light-gray { background-color: #C3BCC2; }
        .border-gray { border-color: #494D49; }
    </style>
    <script>
        // Function to search students dynamically
        function searchStudent() {
            const searchQuery = document.getElementById('searchInput').value;

            // Send an AJAX request to the server
            fetch(`stud_list.php?action=search&query=${encodeURIComponent(searchQuery)}`)
                .then(response => response.text())
                .then(data => {
                    // Clear the existing table body
                    document.getElementById('studentTable').innerHTML = '';

                    // Update the table body with the search results
                    document.getElementById('studentTable').innerHTML = data;
                })
                .catch(error => console.error('Error fetching search results:', error));
        }
    </script>
</head>
<body class="bg-light-gray text-dark-green">
    <!-- Navigation Bar -->
    <body class="bg-light-gray text-dark-green">
    <!-- Navigation Bar -->
    <div class="bg-dark-blue text-white p-4 flex justify-between items-center shadow-md">
        <h1 class="text-2xl font-bold">CCS Admin</h1>
        <div class="flex items-center space-x-4">
            <a href="admin_dashboard.php" class="hover:underline text-light-pink">Home</a>
            <button onclick="openSearchModal()" class="hover:underline text-light-pink">Search Students</button>
            <a href="stud_list.php" class="hover:underline text-light-pink">Students</a>
            <a href="currentSitin.php" class="hover:underline text-light-pink">Sit-in</a>
            <a href="sitin_record.php" class="hover:underline text-light-pink">View Sit-in Records</a>
            <a href="sit_in_reports.php" class="hover:underline text-light-pink">Sit-in Reports</a>
            <a href="feedback.php" class="hover:underline text-light-pink">Feedback Reports</a>
            <a href="reservation.php" class="hover:underline text-light-pink">Reservation</a>
            <a href="login.php" class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600">Log out</a>
        </div>
    </div> 
    <!-- Main Content -->
    <div class="max-w-6xl mx-auto mt-6 p-4 bg-white shadow-lg rounded-md">
        <h2 class="text-2xl font-semibold text-center mb-4 text-dark-blue">Student List</h2>

        <!-- Buttons and Search Bar -->
        <div class="flex justify-between items-center mb-4">
            <!-- Left Section: Buttons -->
            <div class="flex space-x-2">
                <a href="add_student.php" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Add Students</a>
                <form method="POST" action="reset_sessions.php">
                    <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">Reset All Session</button>
                </form>
            </div>

            <!-- Right Section: Entries Dropdown and Search -->
            <div class="flex items-center space-x-2">
                <label for="entries" class="text-gray-700">Show</label>
                <select id="entries" class="border border-gray-300 p-2 rounded focus:outline-none focus:ring-2 focus:ring-light-pink">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                <span class="text-gray-700">entries per page</span>
                <input
                    type="text"
                    id="searchInput"
                    placeholder="Search..."
                    class="border border-gray-300 p-2 rounded focus:outline-none focus:ring-2 focus:ring-light-pink"
                    oninput="searchStudent()"
                />
            </div>
        </div>

        <!-- Data Table -->
        <div class="overflow-x-auto">
            <table class="w-full border-collapse border border-gray">
                <thead class="bg-gray-200">
                    <tr>
                        <th class="border p-2">Student ID</th>
                        <th class="border p-2">Name</th>
                        <th class="border p-2">Course</th>
                        <th class="border p-2">Year Level</th>
                        <th class="border p-2">Email</th>
                        <th class="border p-2">Remaining Sessions</th> <!-- Add a new column header -->
                        <th class="border p-2">Actions</th>
                    </tr>
                </thead>
                <tbody id="studentTable">
                    <?php
                    if (mysqli_num_rows($result) > 0) {
                        while ($row = mysqli_fetch_assoc($result)) {
                            $student_id = $row['idno']; // Assuming 'idno' is the student ID in the 'users' table

                            // Fetch the session count from the sit_in_records table
                            $session_query = "SELECT SUM(session) AS total_sessions FROM sit_in_records WHERE student_id = '$student_id'";
                            $session_result = mysqli_query($conn, $session_query);
                            $total_sessions = 0;

                            if ($session_result && mysqli_num_rows($session_result) > 0) {
                                $session_row = mysqli_fetch_assoc($session_result);
                                $total_sessions = $session_row['total_sessions'] ?? 0; // Default to 0 if no sessions are found
                            }

                            echo "<tr>
                                    <td class='border p-2 text-center'>{$row['idno']}</td>
                                    <td class='border p-2 text-center'>{$row['lastname']}, {$row['firstname']} {$row['middlename']}</td>
                                    <td class='border p-2 text-center'>{$row['course']}</td>
                                    <td class='border p-2 text-center'>{$row['yearlevel']}</td>
                                    <td class='border p-2 text-center'>{$row['email']}</td>
                                    <td class='border p-2 text-center'>{$total_sessions}</td> <!-- Display Total Sessions -->
                                    <td class='border p-2 text-center'>
                                        <a href='edit_student.php?id={$row['id']}' class='bg-blue-500 text-white px-2 py-1 rounded hover:bg-blue-600'>Edit</a>
                                        <a href='delete_student.php?id={$row['id']}' class='bg-red-500 text-white px-2 py-1 rounded hover:bg-red-600' onclick='return confirm(\"Are you sure you want to delete this student?\")'>Delete</a>
                                    </td>
                                </tr>";
                        }
                    } else {
                        echo "<tr><td class='border p-2 text-center text-gray-500' colspan='7'>No students found</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Placeholder function for "Search Students" button in the navbar
        function openSearchModal() {
            alert('Search Students functionality is not implemented yet.');
        }
    </script>
</body>
</html>
