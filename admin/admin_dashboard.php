<?php
include '../db.php'; 
include 'search.php';  

// Handle new announcements
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['announcement'])) {
    $message = trim($_POST['announcement']);

    if (!empty($message)) {
        $stmt = $conn->prepare("INSERT INTO announcements (message) VALUES (?)");
        $stmt->bind_param("s", $message);
        $stmt->execute();
        $stmt->close();
        
        // Redirect to refresh the page and avoid form resubmission
        header("Location: admin_dashboard.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
            <a href="admin_dashboard.php" class="hover:underline text-light-pink">Home</a>
            <button onclick="openSearchModal()" class="hover:underline text-light-pink">Search Students</button>
            <a href="stud_list.php" class="hover:underline text-light-pink">Students</a>
            <a href="currentSitin.php" class="hover:underline text-light-pink">Sit-in</a>
            <a href="sitin_record.php" class="hover:underline text-light-pink">View Sit-in Records</a>
            <a href="sit_in_reports.php" class="hover:underline text-light-pink">Sit-in Reports</a>
            <a href="feedback.php" class="hover:underline text-light-pink">Feedback Reports</a>
            <a href="reservation.php" class="hover:underline text-light-pink">Reservation</a>
            <a href="../logout.php" class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600">Log out</a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Statistics Section -->
        <div class="bg-white shadow-md rounded-lg p-6">
            <h2 class="text-xl font-bold text-blue-800 mb-4">📊 Statistics</h2>
            <div class="space-y-2">
                <p><strong>Students Registered:</strong> <span class="text-blue-600">180</span></p>
                <p><strong>Currently Sit-in:</strong> <span class="text-green-600">0</span></p>
                <p><strong>Total Sit-in:</strong> <span class="text-yellow-600">79</span></p>
            </div>
            <canvas id="statsChart" class="mt-4"></canvas>
        </div>

        <!-- Announcements Section -->
        <div class="bg-white shadow-md rounded-lg p-6">
            <h2 class="text-xl font-bold text-blue-800 mb-4">📢 Announcements</h2>
            <form action="announcement.php" method="POST" class="space-y-4">
                <textarea name="announcement" class="w-full border border-gray-300 p-2 rounded" placeholder="New Announcement"></textarea>
                <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">Submit</button>
            </form>

            <h3 class="text-lg font-bold mt-6">Posted Announcements</h3>
            <ul id="announcementsList" class="mt-4 space-y-2">
                <!-- Announcements will be dynamically loaded here -->
            </ul>
        </div>
    </div>

    <!-- Chart.js for Statistics -->
    <script>
        var ctx = document.getElementById('statsChart').getContext('2d');
        var statsChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: ['C#', 'C', 'Java', 'ASP.Net', 'Php'],
                datasets: [{
                    data: [70, 5, 15, 5, 5], // Example data
                    backgroundColor: ['#ff6384', '#ff9f40', '#ffcd56', '#4bc0c0', '#36a2eb']
                }]
            }
        });

        // Fetch Announcements Dynamically
        function loadAnnouncements() {
            $.get("announcement.php", function(data) {
                let announcements = JSON.parse(data);
                let list = "";
                announcements.forEach(announcement => {
                    list += `
                        <li class="p-2 border-b border-gray-300">
                            <strong class="text-blue-800">CCS Admin | ${announcement.date}</strong>
                            <p class="text-gray-700">${announcement.message}</p>
                        </li>`;
                });
                $("#announcementsList").html(list);
            });
        }

        $(document).ready(function() {
            loadAnnouncements();
            setInterval(loadAnnouncements, 5000); // Refresh every 5 seconds
        });
    </script>
</body>
</html>
