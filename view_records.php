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

// Include the header/navbar
include('admin_header.php');

// Fetch sit-in records
$sit_in_query = "SELECT s.*, u.firstname, u.lastname 
               FROM sit_in_records s 
               LEFT JOIN users u ON s.student_id = u.idno
               ORDER BY s.time_in DESC";
$sit_in_result = mysqli_query($conn, $sit_in_query);

// Prepare data for charts
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
    }
}

// Reset the result pointer
mysqli_data_seek($sit_in_result, 0);

// Prepare chart data in JSON
$lab_labels = array_keys($lab_data);
$lab_counts = array_values($lab_data);

$language_labels = array_keys($language_data);
$language_counts = array_values($language_data);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Sit-in Records</title>
    <link rel="icon" type="image/x-icon" href="ccs.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Other styles */
        body        
        .pagination button {
            margin: 0 2px;
        }
        
    </style>
</head>
<body>
    <!-- Navbar (will be included from admin_header.php) -->
    
    <div class="container mt-5">
    <h2 class="text-center mb-4 text-xl font-bold">Current Sit-in Records</h2>        
        
        <div class="mb-3">
            <select class="form-select form-select-sm d-inline-block w-auto" id="entriesPerPage">
                <option value="10" selected>10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
            <label for="entriesPerPage" class="ms-1">entries per page</label>
            
            <div class="float-end">
                <label for="sitInSearch" class="form-label me-2">Search:</label>
                <input type="text" class="form-control-sm" id="sitInSearch" name="sitInSearch">
            </div>
        </div>
        
        <table class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th>ID Number</th>
                    <th>Name</th>
                    <th>Purpose</th>
                    <th>Lab</th>
                    <th>Login</th>
                    <th>Logout</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody id="currentSitInTableBody">
                <?php
                if (mysqli_num_rows($sit_in_result) > 0) {
                    while ($sit_in = mysqli_fetch_assoc($sit_in_result)) {
                        // Display table row
                        $name = isset($sit_in['firstname']) ? $sit_in['firstname'] . ' ' . $sit_in['lastname'] : 'Unknown';
                        $login_time = date('h:i:sa', strtotime($sit_in['time_in']));
                        $logout_time = ($sit_in['time_out']) ? date('h:i:sa', strtotime($sit_in['time_out'])) : 'Active';
                        $date = date('Y-m-d', strtotime($sit_in['time_in']));
                        
                        echo "<tr>
                                <td>{$sit_in['student_id']}</td>
                                <td>{$name}</td>
                                <td>{$sit_in['purpose']}</td>
                                <td>{$sit_in['sitlab']}</td>
                                <td>{$login_time}</td>
                                <td>{$logout_time}</td>
                                <td>{$date}</td>
                            </tr>";
                    }
                } else {
                    echo "<tr><td colspan='8'>No data available</td></tr>";
                }
                ?>
            </tbody>
        </table>
        <div class="d-flex justify-content-between align-items-center">
            <div>Showing 1 to <?php echo mysqli_num_rows($sit_in_result); ?> of <?php echo mysqli_num_rows($sit_in_result); ?> entries</div>
            <div class="pagination">
                <button class="btn btn-sm btn-outline-secondary">1</button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>

        // Filter functionality for current sit-ins
        document.addEventListener('DOMContentLoaded', function() {
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
        });
    </script>
</body>
</html> 