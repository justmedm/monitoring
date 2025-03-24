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
            echo "<tr>
                    <td class='border p-2 text-center'>{$row['idno']}</td>
                    <td class='border p-2 text-center'>{$row['lastname']}, {$row['firstname']} {$row['middlename']}</td>
                    <td class='border p-2 text-center'>{$row['course']}</td>
                    <td class='border p-2 text-center'>{$row['yearlevel']}</td>
                    <td class='border p-2 text-center'>{$row['email']}</td>
                    <td class='border p-2 text-center'>
                        <a href='edit_student.php?id={$row['id']}' class='bg-blue-500 text-white px-2 py-1 rounded'>Edit</a>
                        <a href='delete_student.php?id={$row['id']}' class='bg-red-500 text-white px-2 py-1 rounded' onclick='return confirm(\"Are you sure you want to delete this student?\")'>Delete</a>
                    </td>
                </tr>";
        }
    } else {
        echo "<tr><td colspan='6' class='border p-2 text-center text-gray-500 italic'>No matching students found</td></tr>";
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
        <h2 class="text-2xl font-semibold text-center mb-4">Student List</h2>

        <!-- Search Bar -->
        <div class="flex justify-between items-center mb-4">
            <input
                type="text"
                id="searchInput"
                placeholder="Search by ID or Name..."
                class="border p-2 rounded w-full"
                oninput="searchStudent()"
            />
        </div>

        <!-- Data Table -->
        <div class="overflow-x-auto">
            <table class="w-full border-collapse border border-gray-300">
                <thead class="bg-gray-200">
                    <tr>
                        <th class="border p-2">Student ID</th>
                        <th class="border p-2">Name</th>
                        <th class="border p-2">Course</th>
                        <th class="border p-2">Year Level</th>
                        <th class="border p-2">Email</th>
                        <th class="border p-2">Actions</th>
                    </tr>
                </thead>
                <tbody id="studentTable">
                    <?php
                    if (mysqli_num_rows($result) > 0) {
                        while ($row = mysqli_fetch_assoc($result)) {
                            echo "<tr>
                                    <td class='border p-2 text-center'>{$row['idno']}</td>
                                    <td class='border p-2 text-center'>{$row['lastname']}, {$row['firstname']} {$row['middlename']}</td>
                                    <td class='border p-2 text-center'>{$row['course']}</td>
                                    <td class='border p-2 text-center'>{$row['yearlevel']}</td>
                                    <td class='border p-2 text-center'>{$row['email']}</td>
                                    <td class='border p-2 text-center'>
                                        <a href='edit_student.php?id={$row['id']}' class='bg-blue-500 text-white px-2 py-1 rounded'>Edit</a>
                                        <a href='delete_student.php?id={$row['id']}' class='bg-red-500 text-white px-2 py-1 rounded' onclick='return confirm(\"Are you sure you want to delete this student?\")'>Delete</a>
                                    </td>
                                </tr>";
                        }
                    } else {
                        echo "<tr><td class='border p-2 text-center text-gray-500' colspan='6'>No students found</td></tr>";
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