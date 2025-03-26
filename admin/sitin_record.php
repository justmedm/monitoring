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

// Handle AJAX request to fetch all updated sit-in records
if (isset($_POST['action']) && $_POST['action'] === 'fetch_all_records') {
    $query = "SELECT stt_id, student_id, name, purpose, lab, created_at, logged_out, logout_time 
              FROM sit_in_records 
              ORDER BY stt_id DESC";
    $result = mysqli_query($conn, $query);

    $records = [];
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $records[] = $row;
        }
    }

    echo json_encode(['success' => true, 'data' => $records]);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Current Sit-in Records</title>
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
        // Function to fetch and update all sit-in records automatically
        function fetchAllRecords() {
            // Send an AJAX request to fetch all updated sit-in records
            fetch('sitin_record.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=fetch_all_records',
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Clear the existing table rows
                    const tableBody = document.getElementById('recordTable');
                    tableBody.innerHTML = '';

                    // Populate the table with the updated data
                    data.data.forEach(record => {
                        const loginTime = record.created_at ? new Date(record.created_at).toLocaleTimeString() : 'N/A';
                        const logoutTime = record.logout_time ? new Date(record.logout_time).toLocaleTimeString() : 'N/A';
                        const date = record.created_at ? new Date(record.created_at).toLocaleDateString() : 'N/A';

                        const row = `
                            <tr>
                                <td class='border p-2 text-center'>${record.stt_id}</td>
                                <td class='border p-2 text-center'>${record.student_id}</td>
                                <td class='border p-2 text-center'>${record.name}</td>
                                <td class='border p-2 text-center'>${record.purpose}</td>
                                <td class='border p-2 text-center'>${record.lab}</td>
                                <td class='border p-2 text-center'>${loginTime}</td>
                                <td class='border p-2 text-center'>${logoutTime}</td>
                                <td class='border p-2 text-center'>${date}</td>
                            </tr>
                        `;
                        tableBody.innerHTML += row;
                    });
                } else {
                    console.error('Failed to fetch sit-in records.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }

        // Fetch the data every 5 seconds
        setInterval(fetchAllRecords, 5000);

        // Fetch the data immediately on page load
        fetchAllRecords();
    </script>
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
        <h2 class="text-2xl font-semibold text-center mb-4">Current Sit-in Records</h2>

        <!-- Data Table -->
        <div class="overflow-x-auto">
            <table class="w-full border-collapse border border-gray-300">
                <thead class="bg-gray-200">
                    <tr>
                        <th class="border p-2">Sit-in Number</th>
                        <th class="border p-2">ID Number</th>
                        <th class="border p-2">Name</th>
                        <th class="border p-2">Purpose</th>
                        <th class="border p-2">Lab</th>
                        <th class="border p-2">Login Time</th>
                        <th class="border p-2">Logout Time</th>
                        <th class="border p-2">Date</th>
                    </tr>
                </thead>
                <tbody id="recordTable">
                    <!-- Rows will be populated dynamically -->
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>