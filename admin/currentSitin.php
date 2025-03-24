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

// Handle AJAX search requests
if (isset($_GET['action']) && $_GET['action'] === 'search') {
    $query = $_GET['query'] ?? '';

    // Fetch records matching the search query
    $search_sql = "SELECT * FROM sit_in_records 
                   WHERE student_id LIKE ? 
                   OR name LIKE ? 
                   OR purpose LIKE ? 
                   OR lab LIKE ? 
                   ORDER BY stt_id DESC";
    $search_stmt = $conn->prepare($search_sql);
    $search_query = '%' . $query . '%';
    $search_stmt->bind_param("ssss", $search_query, $search_query, $search_query, $search_query);
    $search_stmt->execute();
    $search_result = $search_stmt->get_result();

    // Generate the table rows dynamically
    if ($search_result->num_rows > 0) {
        while ($row = $search_result->fetch_assoc()) {
            $status_class = $row['status'] === 'Ongoing' ? 'text-green-500' : 'text-red-500';

            echo "<tr>
                    <td class='border p-2 text-center'>{$row['stt_id']}</td>
                    <td class='border p-2 text-center'>{$row['student_id']}</td>
                    <td class='border p-2 text-center'>{$row['name']}</td>
                    <td class='border p-2 text-center'>{$row['purpose']}</td>
                    <td class='border p-2 text-center'>{$row['lab']}</td>
                    <td class='border p-2 text-center'>{$row['session']}</td>
                    <td class='border p-2 font-bold text-center {$status_class}'>{$row['status']}</td>
                    <td class='border p-2 text-center'>
                        <form method='POST' action='timeout_sitin.php'>
                            <input type='hidden' name='id' value='{$row['stt_id']}'>
                            <input type='hidden' name='student_id' value='{$row['student_id']}'>
                            <button type='submit' class='bg-yellow-500 text-white px-2 py-1 rounded'>Time-out</button>
                        </form>
                    </td>
                </tr>";
        }
    } else {
        echo "<tr><td colspan='8' class='border p-2 text-center text-gray-500 italic'>No matching records found</td></tr>";
    }

    $search_stmt->close();
    exit();
}

// Check if this is an AJAX request for session data
if (isset($_GET['action']) && $_GET['action'] == 'fetch_session') {
    if (!isset($_SESSION['username'])) {
        http_response_code(401);
        echo json_encode(["error" => "Unauthorized"]);
        exit();
    }

    $username = $_SESSION['username'];

    // Fetch user ID
    $user_sql = "SELECT id FROM users WHERE username = ?";
    $user_stmt = $conn->prepare($user_sql);
    $user_stmt->bind_param("s", $username);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    $user = $user_result->fetch_assoc();

    if (!$user) {
        http_response_code(404);
        echo json_encode(["error" => "User not found"]);
        exit();
    }

    $student_id = $user['id'];

    // Fetch the latest session data for the user
    $session_sql = "SELECT session, status FROM sit_in_records WHERE student_id = ? AND status = 'Ongoing' ORDER BY stt_id DESC LIMIT 1";
    $session_stmt = $conn->prepare($session_sql);
    $session_stmt->bind_param("s", $student_id);
    $session_stmt->execute();
    $session_result = $session_stmt->get_result();
    $session_data = $session_result->fetch_assoc();

    echo json_encode($session_data);
    exit();
}

// Check if this is a form submission to add a new sit-in record
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_sitin'])) {
    $student_id = $_POST['student_id'];
    $name = $_POST['name'];
    $purpose = $_POST['purpose'];
    $lab = $_POST['lab'];
    $session = $_POST['session'];

    // Check if the user already has an ongoing sit-in record
    $check_sql = "SELECT * FROM sit_in_records WHERE student_id = ? AND status = 'Ongoing'";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $student_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        // If a record exists, prevent duplicate entry
        $_SESSION['error_message'] = "This user already has an ongoing sit-in session.";
        header("Location: currentSitin.php");
        exit();
    }

    // If no record exists, insert a new one
    $insert_sql = "INSERT INTO sit_in_records (student_id, name, purpose, lab, session, status) VALUES (?, ?, ?, ?, ?, 'Ongoing')";
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param("sssss", $student_id, $name, $purpose, $lab, $session);

    if ($insert_stmt->execute()) {
        $_SESSION['success_message'] = "Sit-in session added successfully.";
    } else {
        $_SESSION['error_message'] = "Failed to add sit-in session.";
    }

    header("Location: currentSitin.php");
    exit();
}

// Fetch all sit-in records for initial page load
$query = "SELECT * FROM sit_in_records ORDER BY stt_id DESC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Current Sit In</title>
    <script src="https://cdn.tailwindcss.com"></script>

    <script>
        // Function to search students dynamically
        function searchStudent() {
    const searchQuery = document.getElementById('searchInput').value;

    // Send an AJAX request to the server
    fetch(`currentSitin.php?action=search&query=${encodeURIComponent(searchQuery)}`)
        .then(response => response.text())
        .then(data => {
            // Clear the existing table body
            document.getElementById('sitInTable').innerHTML = '';

            // Update the table body with the search results
            document.getElementById('sitInTable').innerHTML = data;
        })
        .catch(error => console.error('Error fetching search results:', error));
}

        // Function to refresh the table every 10 seconds
        function refreshTable() {
            fetch('currentSitin.php?action=fetch')
                .then(response => response.text())
                .then(data => {
                    document.getElementById('sitInTable').innerHTML = data;
                });
        }

        // Refresh every 10 seconds
        setInterval(refreshTable, 10000);
    </script>
</head>
<body class="bg-gray-100">
    <!-- Navigation Bar -->
    <div class="bg-blue-800 text-white p-4 flex justify-between">
        <h1 class="text-lg">CCS Admin</h1>
        <div>
            <a href="admin_dashboard.php" class="px-3">Home</a>
            <button onclick="openSearchModal()" class="px-3">Search Students</button>
            <a href="stud_list.php" class="px-3">Students</a>
            <a href="currentSitin.php" class="px-3">Sit-in</a>
            <a href="sitin_record.php" class="px-3">View Sit-in Records</a>
            <a href="sit_in_reports.php" class="px-3">Sit-in Reports</a>
            <a href="feedback.php" class="px-3">Feedback Reports</a>
            <a href="reservation.php" class="px-3">Reservation</a>
            <a href="logout.php" class="bg-yellow-500 text-black px-3 py-1 rounded">Log out</a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-6xl mx-auto mt-6 p-4 bg-white shadow-md rounded-md">
        <h2 class="text-2xl font-semibold text-center mb-4">Current Sit In</h2>

        <!-- Entries per page and search bar -->
        <div class="flex justify-between items-center mb-4">
            <div>
                <label class="mr-2">Show</label>
                <select class="border p-2 rounded">
                    <option>10</option>
                    <option>25</option>
                    <option>50</option>
                </select>
                <span class="ml-2">entries per page</span>
            </div>
            <input
                type="text"
                id="searchInput"
                placeholder="Search..."
                class="border p-2 rounded w-64"
                oninput="searchStudent()"
            />
        </div>

        <!-- Data Table -->
        <div class="overflow-x-auto">
            <table class="w-full border-collapse border border-gray-300">
                <thead class="bg-gray-200">
                    <tr>
                        <th class="border p-2">Sit ID Number</th>
                        <th class="border p-2">ID Number</th>
                        <th class="border p-2">Name</th>
                        <th class="border p-2">Purpose</th>
                        <th class="border p-2">Sit Lab</th>
                        <th class="border p-2">Session</th>
                        <th class="border p-2">Status</th>
                        <th class="border p-2">Actions</th>
                    </tr>
                </thead>
                <tbody id="sitInTable">
                    <?php
                    if (mysqli_num_rows($result) > 0) {
                        while ($row = mysqli_fetch_assoc($result)) {
                            $status_class = $row['status'] === 'Ongoing' ? 'text-green-500' : 'text-red-500';

                            echo "<tr>
                                    <td class='border p-2 text-center'>{$row['stt_id']}</td>
                                    <td class='border p-2 text-center'>{$row['student_id']}</td>
                                    <td class='border p-2 text-center'>{$row['name']}</td>
                                    <td class='border p-2 text-center'>{$row['purpose']}</td>
                                    <td class='border p-2 text-center'>{$row['lab']}</td>
                                    <td class='border p-2 text-center'>{$row['session']}</td>
                                    <td class='border p-2 font-bold text-center {$status_class}'>{$row['status']}</td>
                                    <td class='border p-2 text-center'>
                                        <form method='POST' action='timeout_sitin.php'>
                                            <input type='hidden' name='id' value='{$row['stt_id']}'>
                                            <input type='hidden' name='student_id' value='{$row['student_id']}'>
                                            <button type='submit' class='bg-yellow-500 text-white px-2 py-1 rounded'>Time-out</button>
                                        </form>
                                    </td>
                                </tr>";
                        }
                    } else {
                        echo "<tr><td class='border p-2 text-center text-gray-500' colspan='8'>No data available</td></tr>";
                    }
                    mysqli_close($conn);
                    ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="flex justify-center mt-4 space-x-2">
            <button class="px-3 py-1 bg-gray-300 rounded hover:bg-gray-400">&lt;</button>
            <button class="px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600">1</button>
            <button class="px-3 py-1 bg-gray-300 rounded hover:bg-gray-400">&gt;</button>
        </div>
    </div>
</body>
</html>
