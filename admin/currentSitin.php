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
 <style>
        /* Custom colors using Tailwind's @apply directive */
        .bg-dark-blue { background-color: #2A3735; }
        .text-light-pink { color: #ABAAAA; }
        .bg-light-pink { background-color: #ABAAAA; }
        .text-dark-green { color: #3A3A3A; }
        .bg-light-gray { background-color: #C3BCC2; }
        .border-gray { border-color: #494D49; }
    </style>
</head>
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
    </div>
</body>
</html>
