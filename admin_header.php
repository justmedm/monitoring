<?php
// Count current active sit-ins for badge
$active_sit_in_query = "SELECT COUNT(*) AS active_count FROM sit_in_records WHERE time_out IS NULL";
$active_sit_in_result = mysqli_query($conn, $active_sit_in_query);
$active_sit_in_count = 0;
if ($active_sit_in_result) {
    $active_data = mysqli_fetch_assoc($active_sit_in_result);
    $active_sit_in_count = $active_data['active_count'];
}
?>
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
    <nav class="bg-dark-blue text-white p-4 flex justify-between items-center shadow-md">
        <h1 class="text-2xl font-bold">CCS Admin</h1>
        <div class="flex items-center space-x-4">
            <a href="admindashboard.php" class="hover:underline text-light-pink">Home</a>
            <button onclick="openSearchModal()" class="hover:underline text-light-pink">Search</button>
            <a href="students.php" class="hover:underline text-light-pink">Students</a>
            <a href="sit_in.php" class="hover:underline text-light-pink">Sit-in</a>
            <a href="view_records.php" class="hover:underline text-light-pink flex items-center">
                View Sit-in Records
                <?php if ($active_sit_in_count > 0): ?>
                    <span class="bg-red-500 text-white text-xs rounded-full px-2 py-1 ml-2"><?php echo $active_sit_in_count; ?></span>
                <?php endif; ?>
            </a>
            <a href="sit_in_reports.php" class="hover:underline text-light-pink">Sit-in Reports</a>
            <a href="feedback_reports.php" class="hover:underline text-light-pink">Feedback Reports</a>
            <a href="reservation.php" class="hover:underline text-light-pink">Reservation</a>
            <a href="logout.php" class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600">Log out</a>
        </div>
    </nav>

    <!-- Search Modal -->
    <div class="modal fade" id="searchModal" tabindex="-1" aria-labelledby="searchModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="searchModalLabel">Search Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="searchStudentForm">
                        <div class="mb-3">
                            <label for="student_id_search" class="form-label">Student ID</label>
                            <input type="text" class="form-control" id="student_id_search" name="search" placeholder="Enter Student ID" required>
                        </div>
                        <button type="button" id="searchStudentBtn" class="btn btn-primary w-100">Search</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Include modals for Search, Sit In Form, etc. -->
    <div class="modal fade" id="currentSitInModal" tabindex="-1" aria-labelledby="currentSitInModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="currentSitInModalLabel">Sit In Form</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="sitInForm" method="POST" action="process_sitin.php">
                        <div class="row mb-3">
                            <label for="id_number" class="col-sm-4 col-form-label text-end">ID Number:</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" id="id_number" name="id_number" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label for="student_name" class="col-sm-4 col-form-label text-end">Student Name:</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" id="student_name" name="student_name" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label for="purpose" class="col-sm-4 col-form-label text-end">Purpose:</label>
                            <div class="col-sm-8">
                                <select class="form-select" id="purpose" name="purpose" required>
                                    <option value="" selected disabled>Select Purpose</option>
                                    <option value="C Programming">C Programming</option>
                                    <option value="Java Programming">Java Programming</option>
                                    <option value="C# Programming">C# Programming</option>
                                    <option value="PHP Programming">PHP Programming</option>
                                    <option value=".Net Programming">.Net Programming</option>
                                    <option value="Python Programming">Python Programming</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label for="lab" class="col-sm-4 col-form-label text-end">Lab:</label>
                            <div class="col-sm-8">
                                <select class="form-select" id="lab" name="lab" required>
                                    <option value="" selected disabled>Select Laboratory</option>
                                    <option value="524">524</option>
                                    <option value="526">526</option>
                                    <option value="528">528</option>
                                    <option value="530">530</option>
                                    <option value="542">542</option>
                                    <option value="Mac Lab">Mac Lab</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label for="remaining_session" class="col-sm-4 col-form-label text-end">Remaining Session:</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" id="remaining_session" name="remaining_session" required>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mt-4">
                            <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary" id="sitInButton">Sit In</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Pending Reservations -->
    <div class="modal fade" id="pendingReservationsModal" tabindex="-1" aria-labelledby="pendingReservationsModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="pendingReservationsModalLabel">Pending Reservations</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Content for pending reservations -->
                    <p>Reservation functionality will be implemented here.</p>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Common JavaScript for all pages
    document.addEventListener('DOMContentLoaded', function() {
        // Search functionality
        const searchStudentBtn = document.getElementById('searchStudentBtn');
        if (searchStudentBtn) {
            searchStudentBtn.addEventListener('click', function() {
                const studentId = document.getElementById('student_id_search').value.trim();

                if (!studentId) {
                    alert('Please enter a student ID');
                    return;
                }

                // Hide search modal
                const searchModal = bootstrap.Modal.getInstance(document.getElementById('searchModal'));
                searchModal.hide();

                // Fetch student info and populate sit-in form
                fetch(`get_student.php?id=${studentId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Fill in the form fields with student data
                            document.getElementById('id_number').value = studentId;
                            document.getElementById('student_name').value = data.student.firstname + ' ' + data.student.lastname;
                            document.getElementById('remaining_session').value = data.remaining_session;

                            // Show the sit-in form modal
                            const sitInModal = new bootstrap.Modal(document.getElementById('currentSitInModal'));
                            sitInModal.show();
                        } else {
                            alert(data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error fetching student information');
                    });
            });
        }

        // Auto-populate student info when ID is entered
        const idNumberInput = document.getElementById('id_number');
        if (idNumberInput) {
            idNumberInput.addEventListener('blur', function() {
                const studentId = this.value.trim();
                if (studentId) {
                    fetch(`get_student.php?id=${studentId}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                document.getElementById('student_name').value = data.student.firstname + ' ' + data.student.lastname;
                                document.getElementById('remaining_session').value = data.remaining_session;
                            } else {
                                alert(data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                        });
                }
            });
        }

        // Reset form when modals are closed
        const searchModal = document.getElementById('searchModal');
        if (searchModal) {
            searchModal.addEventListener('hidden.bs.modal', function () {
                document.getElementById('searchStudentForm').reset();
            });
        }

        const sitInModal = document.getElementById('currentSitInModal');
        if (sitInModal) {
            sitInModal.addEventListener('hidden.bs.modal', function () {
                document.getElementById('sitInForm').reset();
            });
        }
    });

    function openSearchModal() {
        const searchModal = new bootstrap.Modal(document.getElementById('searchModal'));
        searchModal.show();
    }
    </script>
</body>
