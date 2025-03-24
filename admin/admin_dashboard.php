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
</head>
<body class="bg-gray-100">
    <!-- Navbar -->
    <div class="bg-black text-white p-4 flex justify-between">
        <h1 class="text-lg">CCS Admin</h1>
        <div>
            <a href="index.php" class="px-3">Home</a>
            <button onclick="openSearchModal()" class="px-3">Search Students</button>
            <a href="stud_list.php" class="px-3">Students</a>
            <a href="currentSitin.php" class="px-3">Sit-in</a>
            <a href="sitin_record.php" class="px-3">View Sit-in Records</a>
            <a href="sit_in_reports.php" class="px-3">Sit-in Reports</a>
            <a href="feedback.php" class="px-3">Feedback Reports</a>
            <a href="reservation.php" class="px-3">Reservation</a>
            <a href="logout.php" class="bg-red-500 text-white px-3 py-1 rounded">Log out</a>
        </div>
    </div>

    <div class="flex p-4">
        <!-- Statistics -->
        <div class="w-1/2 p-4 bg-white shadow rounded">
            <h2 class="bg-black text-white p-2 rounded">📊 Statistics</h2>
            <p><strong>Students Registered:</strong> 180</p>
            <p><strong>Currently Sit-in:</strong> 0</p>
            <p><strong>Total Sit-in:</strong> 79</p>
            <canvas id="statsChart"></canvas>
        </div>

        <!-- Announcements -->
        <div class="w-1/2 p-4 bg-white shadow rounded">
            <h2 class="bg-black text-white p-2 rounded">📢 Announcements</h2>
            <form action="announcement.php" method="POST">
                <textarea name="announcement" class="w-full border p-2" placeholder="New Announcement"></textarea>
                <button type="submit" class="bg-green-500 text-white px-3 py-1 mt-2 rounded">Submit</button>
            </form>

            <h3 class="font-bold mt-4">Posted Announcements</h3>
            <ul id="announcementsList"></ul>
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
                        <li class="p-2 border-b">
                            <strong>CCS Admin | ${announcement.date}</strong>
                            <p>${announcement.message}</p>
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
