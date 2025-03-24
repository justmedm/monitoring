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

// Fetch data for the table
$query = "SELECT stt_id, student_id, name, purpose, lab, created_at, logged_out, logout_time FROM sit_in_records ORDER BY stt_id DESC";
$result = mysqli_query($conn, $query);

// Debugging: Check if query executed successfully
if (!$result) {
    error_log("⚠️ WARNING: Query failed: " . mysqli_error($conn));
    die("Error: Could not fetch data.");
}

// Fetch data for the statistics (pie charts)
$lab_data = [];
$status_data = [];

// Fetch lab distribution
$lab_query = "SELECT lab, COUNT(*) as count FROM sit_in_records GROUP BY lab";
$lab_result = mysqli_query($conn, $lab_query);
while ($row = mysqli_fetch_assoc($lab_result)) {
    $lab_data[$row['lab']] = $row['count'];
}

// Fetch status distribution
$status_query = "SELECT status, COUNT(*) as count FROM sit_in_records GROUP BY status";
$status_result = mysqli_query($conn, $status_query);
while ($row = mysqli_fetch_assoc($status_result)) {
    $status_data[$row['status']] = $row['count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Current Sit-in Records</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100">
    <!-- Navigation Bar -->
    <div class="bg-blue-800 text-white p-4 flex justify-between">
        <h1 class="text-lg">CCS Admin</h1>
        <div>
            <a href="admin_dashboard.php" class="px-3">Home</a>
            <button onclick="openSearchModal()" class="px-3">Search</button>
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
        <h2 class="text-2xl font-semibold text-center mb-4">Current Sit-in Records</h2>

        <!-- Statistics Section -->
        <div class="grid grid-cols-2 gap-4 mb-6">
            <!-- Lab Distribution Chart -->
            <div class="bg-white p-4 shadow-md rounded-md">
                <h3 class="text-lg font-semibold text-center mb-2">Lab Distribution</h3>
                <canvas id="labChart" ></canvas>
            </div>

            <!-- Status Distribution Chart -->
            <div class="bg-white p-4 shadow-md rounded-md">
                <h3 class="text-lg font-semibold text-center mb-2">Status Distribution</h3>
                <canvas id="statusChart"></canvas>
            </div>
        </div>

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
                <tbody>
                    <?php
                    if (mysqli_num_rows($result) > 0) {
                        while ($row = mysqli_fetch_assoc($result)) {
                            // Debugging: Log the fetched logout_time value
                            error_log("DEBUG: Fetched logout_time for student_id {$row['student_id']}: " . $row['logout_time']);

                            $login_time = isset($row['created_at']) ? date('h:i A', strtotime($row['created_at'])) : 'N/A';
                            $logout_time = isset($row['logout_time']) && $row['logout_time'] !== null ? date('h:i A', strtotime($row['logout_time'])) : 'N/A';
                            $date = isset($row['created_at']) ? date('Y-m-d', strtotime($row['created_at'])) : 'N/A';

                            echo "<tr>
                                    <td class='border p-2 text-center'>{$row['stt_id']}</td>
                                    <td class='border p-2 text-center'>{$row['student_id']}</td>
                                    <td class='border p-2 text-center'>{$row['name']}</td>
                                    <td class='border p-2 text-center'>{$row['purpose']}</td>
                                    <td class='border p-2 text-center'>{$row['lab']}</td>
                                    <td class='border p-2 text-center'>{$login_time}</td>
                                    <td class='border p-2 text-center'>{$logout_time}</td>
                                    <td class='border p-2 text-center'>{$date}</td>
                                </tr>";
                        }
                    } else {
                        echo "<tr><td class='border p-2 text-center text-gray-500' colspan='8'>No records found</td></tr>";
                    }
                    mysqli_close($conn);
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Chart.js Script -->
    <script>
        // Lab Distribution Chart
        const labData = <?php echo json_encode($lab_data); ?>;
        const labLabels = Object.keys(labData);
        const labCounts = Object.values(labData);

        const labChartCtx = document.getElementById('labChart').getContext('2d');
        new Chart(labChartCtx, {
            type: 'pie',
            data: {
                labels: labLabels,
                datasets: [{
                    label: 'Lab Distribution',
                    data: labCounts,
                    backgroundColor: ['#4CAF50', '#FF9800', '#2196F3', '#F44336', '#9C27B0'],
                }]
            }
        });

        // Status Distribution Chart
        const statusData = <?php echo json_encode($status_data); ?>;
        const statusLabels = Object.keys(statusData);
        const statusCounts = Object.values(statusData);

        const statusChartCtx = document.getElementById('statusChart').getContext('2d');
        new Chart(statusChartCtx, {
            type: 'pie',
            data: {
                labels: statusLabels,
                datasets: [{
                    label: 'Status Distribution',
                    data: statusCounts,
                    backgroundColor: ['#4CAF50', '#FF9800', '#2196F3', '#F44336', '#9C27B0'],
                }]
            }
        });
    </script>
</body>
</html>