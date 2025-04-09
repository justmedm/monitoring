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

// Get date filter if provided
$date_filter = '';
$filter_clause = '';
if (isset($_GET['date']) && !empty($_GET['date'])) {
    $date_filter = $_GET['date'];
    $filter_clause = " WHERE DATE(s.time_in) = '$date_filter'";
}

// Fetch sit-in records with optional date filter
$reports_query = "SELECT s.*, u.firstname, u.lastname 
                 FROM sit_in_records s 
                 LEFT JOIN users u ON s.student_id = u.idno 
                 $filter_clause
                 ORDER BY s.time_in DESC";
$reports_result = mysqli_query($conn, $reports_query);

if (!$reports_result) {
    die("Error fetching reports: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sit-in Reports</title>
    <link rel="icon" type="image/x-icon" href="ccs.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
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
        .filter-row {
            margin-bottom: 20px;
        }
        
        .export-buttons {
            gap: 10px;
        }
    </style>
</head>
<body>
    <!-- Navbar (will be included from admin_header.php) -->
    
    <div class="container mt-5">
        <h2 class="text-center mb-4 text-xl font-bold">Generate Reports</h2>
        
        <!-- Date Filter and Export Buttons -->
        <div class="row mb-3 filter-row">
            <div class="col-md-6">
                <form method="GET" action="sit_in_reports.php" class="d-flex align-items-center">
                    <input type="date" class="form-control me-2" id="reportDate" name="date" value="<?= $date_filter ?>">
                    <button type="submit" class="btn btn-primary me-2">Search</button>
                    <a href="sit_in_reports.php" class="btn btn-secondary">Reset</a>
                </form>
            </div>
            <div class="col-md-6 text-end export-buttons d-flex justify-content-end">
                <button class="btn btn-outline-secondary me-2" id="exportCSV">CSV</button>
                <button class="btn btn-outline-secondary me-2" id="exportExcel">Excel</button>
                <button class="btn btn-outline-secondary me-2" id="exportPDF">PDF</button>
                <button class="btn btn-outline-secondary" id="printReport">Print</button>
            </div>
        </div>
        
        <div class="mb-3 text-end">
            <label for="reportFilter" class="form-label me-2">Filter:</label>
            <input type="text" class="form-control-sm" id="reportFilter" name="reportFilter">
        </div>
        
        <table class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th>ID Number</th>
                    <th>Name</th>
                    <th>Purpose</th>
                    <th>Laboratory</th>
                    <th>Login</th>
                    <th>Logout</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody id="reportTableBody">
                <?php
                if (mysqli_num_rows($reports_result) > 0) {
                    while ($report = mysqli_fetch_assoc($reports_result)) {
                        $login_time = date('h:i:s a', strtotime($report['time_in']));
                        $logout_time = ($report['time_out']) ? date('h:i:s a', strtotime($report['time_out'])) : 'Active';
                        $date = date('Y-m-d', strtotime($report['time_in']));
                        $name = isset($report['firstname']) ? $report['firstname'] . ' ' . $report['lastname'] : 'Unknown';
                        
                        echo "<tr>
                                <td>{$report['student_id']}</td>
                                <td>{$name}</td>
                                <td>{$report['purpose']}</td>
                                <td>{$report['sitlab']}</td>
                                <td>{$login_time}</td>
                                <td>{$logout_time}</td>
                                <td>{$date}</td>
                            </tr>";
                    }
                } else {
                    echo "<tr><td colspan='7' class='text-center'>No data available</td></tr>";
                }
                ?>
            </tbody>
        </table>
        <div class="d-flex justify-content-between align-items-center">
            <div>Showing <?php echo mysqli_num_rows($reports_result); ?> entries</div>
            <div class="pagination">
                <button class="btn btn-sm btn-primary">1</button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Filter functionality
            const reportFilter = document.getElementById('reportFilter');
            if (reportFilter) {
                reportFilter.addEventListener('keyup', function() {
                    const filterValue = this.value.toLowerCase();
                    const rows = document.querySelectorAll('#reportTableBody tr');
                    
                    rows.forEach(row => {
                        const text = row.textContent.toLowerCase();
                        row.style.display = text.includes(filterValue) ? '' : 'none';
                    });
                });
            }

            // Print functionality
            const printBtn = document.getElementById('printReport');
            if (printBtn) {
                printBtn.addEventListener('click', function() {
                    window.print();
                });
            }

            // Export to CSV
            const exportCSVBtn = document.getElementById('exportCSV');
            if (exportCSVBtn) {
                exportCSVBtn.addEventListener('click', function() {
                    exportTableToCSV('sit_in_report.csv');
                });
            }

            // Export to Excel
            const exportExcelBtn = document.getElementById('exportExcel');
            if (exportExcelBtn) {
                exportExcelBtn.addEventListener('click', function() {
                    exportTableToExcel('sit_in_report.xlsx');
                });
            }

            // Export to PDF
            const exportPDFBtn = document.getElementById('exportPDF');
            if (exportPDFBtn) {
                exportPDFBtn.addEventListener('click', function() {
                    exportTableToPDF();
                });
            }
        });

        // Function to export table to CSV
        function exportTableToCSV(filename) {
            const table = document.querySelector('table');
            const rows = table.querySelectorAll('tr');
            let csv = [];
            
            for (let i = 0; i < rows.length; i++) {
                const row = [], cols = rows[i].querySelectorAll('td, th');
                
                for (let j = 0; j < cols.length; j++) {
                    // Replace any commas in the cell text with space to avoid CSV issues
                    let cellText = cols[j].innerText.replace(/,/g, ' ');
                    // Enclose in quotes to handle any special characters
                    row.push('"' + cellText + '"');
                }
                
                csv.push(row.join(','));        
            }
            
            // Download CSV file
            downloadCSV(csv.join('\n'), filename);
        }

        function downloadCSV(csv, filename) {
            const csvFile = new Blob([csv], {type: "text/csv"});
            const downloadLink = document.createElement("a");
            
            // Create a download link
            downloadLink.download = filename;
            downloadLink.href = window.URL.createObjectURL(csvFile);
            downloadLink.style.display = "none";
            
            // Add the link to the DOM and trigger download
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
        }

        // Function to export table to Excel
        function exportTableToExcel(filename) {
            const table = document.querySelector('table');
            const ws = XLSX.utils.table_to_sheet(table);
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, "Sit-in Reports");
            XLSX.writeFile(wb, filename);
        }

        // Function to export table to PDF
        function exportTableToPDF() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            
            // Add title
            doc.text("Sit-in Report", 14, 16);
            
            // Add current date or filtered date
            const reportDate = document.getElementById('reportDate').value;
            const dateLabel = reportDate ? `Report for date: ${reportDate}` : `Generated on: ${new Date().toLocaleDateString()}`;
            doc.text(dateLabel, 14, 24);
            
            // Add table
            doc.autoTable({ 
                html: 'table',
                startY: 30,
                theme: 'grid',
                headStyles: { fillColor: [13, 71, 161] },
                alternateRowStyles: { fillColor: [240, 240, 240] }
            });
            
            // Save the PDF
            doc.save('sit_in_report.pdf');
        }
    </script>
</body>
</html> 