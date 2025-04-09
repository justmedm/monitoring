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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Current Sit in</title>
    <link rel="icon" type="image/x-icon" href="ccs.png">
    <link rel="icon" type="image/x-icon" href="ccs.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        /* Navbar Styling */
        .navbar {
            background-color: #0d47a1;
            padding: 10px 20px;
        }

        .navbar-brand {
            color: white;
            font-weight: 500;
        }

        .navbar-nav .nav-link {
            color: white;
            margin: 0 5px;
            cursor: pointer;
        }

        .navbar-nav .nav-link:hover {
            color: #f0f0f0;
        }

        .btn-warning {
            background-color: #ffc107;
            border-radius: 3px;
            font-weight: 500;
        }
        
        .pagination button {
            margin: 0 2px;
        }
    </style>
</head>
<body>
    <!-- Navbar (will be included from admin_header.php) -->
    
    <div class="container mt-5">
        <h2 class="text-center mb-4 text-xl font-bold">Current Sit in</h2>
        
        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['sitin_success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $_SESSION['sitin_success'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['sitin_success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['sitin_error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $_SESSION['sitin_error'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['sitin_error']); ?>
        <?php endif; ?>
        
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
                    <th>Sit ID Number</th>
                    <th>ID Number</th>
                    <th>Name</th>
                    <th>Purpose</th>
                    <th>Sit Lab</th>
                    <th>Session</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="currentSitInTableBody">
                <?php
                // Fetch current sit-in records - only active ones
                $sit_in_query = "SELECT s.*, u.firstname, u.lastname 
                               FROM sit_in_records s 
                               LEFT JOIN users u ON s.student_id = u.idno
                               WHERE s.time_out IS NULL";
                $sit_in_result = mysqli_query($conn, $sit_in_query);
                
                if (mysqli_num_rows($sit_in_result) > 0) {
                    while ($sit_in = mysqli_fetch_assoc($sit_in_result)) {
                        // Display table row
                        $name = isset($sit_in['firstname']) ? $sit_in['firstname'] . ' ' . $sit_in['lastname'] : 'Unknown';
                        
                        echo "<tr>
                                <td>{$sit_in['id']}</td>
                                <td>{$sit_in['student_id']}</td>
                                <td>{$name}</td>
                                <td>{$sit_in['purpose']}</td>
                                <td>{$sit_in['sitlab']}</td>
                                <td>1</td>
                                <td>Active</td>
                                <td>
                                    <button class='btn btn-sm btn-primary' onclick='completeSitIn({$sit_in['id']})'>Complete</button>
                                </td>
                            </tr>";
                    }
                } else {
                    echo "<tr><td colspan='8'>No data available</td></tr>";
                }
                ?>
            </tbody>
        </table>
        <div class="d-flex justify-content-between align-items-center">
            <div>Showing 1 to 1 of 1 entry</div>
            <div class="pagination">
                <button class="btn btn-sm btn-outline-secondary">1</button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Function to handle sit-in completion (checkout)
        function completeSitIn(sitInId) {
            if (confirm('Are you sure you want to complete this sit-in session?')) {
                // Make AJAX request to complete_sitin.php
                fetch(`complete_sitin.php?id=${sitInId}&ajax=1`)
                    .then(response => response.json())
                    .then(data => {
                        alert(data.message);
                        // Reload the page to refresh the data
                        window.location.reload();
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while completing the sit-in session.');
                    });
            }
        }

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