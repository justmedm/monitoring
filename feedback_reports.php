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

// Fetch feedback data
$feedback_query = "SELECT f.*, u.firstname, u.lastname 
                   FROM feedback f
                   LEFT JOIN users u ON f.student_id = u.idno
                   ORDER BY f.created_at DESC";
$feedback_result = mysqli_query($conn, $feedback_query);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback Reports</title>
    <link rel="icon" type="image/x-icon" href="ccs.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
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

        .filter-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .export-buttons {
            display: flex;
            gap: 10px;
        }

        .print-btn {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <!-- Include the admin header/navbar -->
    <?php include('admin_header.php'); ?>
    
    <div class="container mt-5">
        <h2 class="text-center mb-4 text-xl font-bold">Feedback Report</h2>
        
        <!-- Print Button -->
        <div class="d-flex justify-content-start mb-3">
            <button class="btn btn-secondary" id="printReport">Print</button>
        </div>

        <!-- Filter and Export Buttons -->
        <div class="filter-container">
            <div class="filter-input">
                <label for="feedbackFilter">Filter: </label>
                <input type="text" id="feedbackFilter" class="form-control-sm">
            </div>
            <div class="export-buttons">
                <button id="exportCSV" class="btn btn-sm btn-outline-secondary">CSV</button>
                <button id="exportExcel" class="btn btn-sm btn-outline-secondary">Excel</button>
                <button id="exportPDF" class="btn btn-sm btn-outline-secondary">PDF</button>
            </div>
        </div>
        
        <table class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th>Student ID Number</th>
                    <th>Name</th>
                    <th>Laboratory</th>
                    <th>Date</th>
                    <th>Message</th>
                </tr>
            </thead>
            <tbody id="feedbackTableBody">
                <?php
                if (mysqli_num_rows($feedback_result) > 0) {
                    while ($feedback = mysqli_fetch_assoc($feedback_result)) {
                        $date = date('Y-m-d', strtotime($feedback['created_at']));
                        $name = $feedback['firstname'] . ' ' . $feedback['lastname'];
                        echo "<tr>
                                <td>{$feedback['student_id']}</td>
                                <td>{$name}</td>
                                <td>{$feedback['laboratory']}</td>
                                <td>{$date}</td>
                                <td>{$feedback['message']}</td>
                            </tr>";
                    }
                } else {
                    echo "<tr><td colspan='5' class='text-center'>No feedback data available</td></tr>";
                }
                ?>
            </tbody>
        </table>
        <div class="d-flex justify-content-between align-items-center">
            <div>Showing <?php echo mysqli_num_rows($feedback_result); ?> entries</div>
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
            const feedbackFilter = document.getElementById('feedbackFilter');
            if (feedbackFilter) {
                feedbackFilter.addEventListener('keyup', function() {
                    const filterValue = this.value.toLowerCase();
                    const rows = document.querySelectorAll('#feedbackTableBody tr');
                    
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
                    exportTableToCSV('feedback_report.csv');
                });
            }

            // Export to Excel
            const exportExcelBtn = document.getElementById('exportExcel');
            if (exportExcelBtn) {
                exportExcelBtn.addEventListener('click', function() {
                    exportTableToExcel('feedback_report.xlsx');
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
            XLSX.utils.book_append_sheet(wb, ws, "Feedback");
            XLSX.writeFile(wb, filename);
        }

        // Function to export table to PDF
        function exportTableToPDF() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            
            // Add title
            doc.text("Feedback Report", 14, 16);
            
            // Add current date
            const today = new Date();
            const dateStr = today.toLocaleDateString();
            doc.text(`Generated on: ${dateStr}`, 14, 24);
            
            // Add table
            doc.autoTable({ 
                html: 'table',
                startY: 30,
                theme: 'grid',
                headStyles: { fillColor: [13, 71, 161] },
                alternateRowStyles: { fillColor: [240, 240, 240] }
            });
            
            // Save the PDF
            doc.save('feedback_report.pdf');
        }
    </script>
</body>
</html> 