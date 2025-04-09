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

// Ensure database connection is included
include('database.php');


// Include the header/navbar
include('admin_header.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students</title>
    <link rel="icon" type="image/x-icon" href="ccs.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
     <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Optional: Add some spacing between action buttons */
        .table td .btn { margin-right: 5px; margin-bottom: 5px; }
        .action-buttons-cell { white-space: nowrap; }
        @media (max-width: 992px) { .action-buttons-cell { white-space: normal; } }
         .modal { z-index: 1070 !important; }
        .swal2-container { z-index: 1060 !important; }
        /* Style for N/A or specific session count text */
        .text-muted { color: #6c757d !important; }
        .session-count { /* Add styles if needed */ }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center mb-4 text-xl font-bold">All Registered Students</h2>

        <div id="messageContainer">
            <?php if (isset($_SESSION['student_success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($_SESSION['student_success']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['student_success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['student_error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($_SESSION['student_error']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['student_error']); ?>
            <?php endif; ?>
        </div>

        <div class="row mb-3 align-items-center">
            <div class="col-md-6 mb-2 mb-md-0">
                <div class="input-group">
                    <input type="text" id="studentSearch" class="form-control" placeholder="Search students...">
                    <button class="btn btn-outline-secondary" type="button" id="searchButton"><i class="fas fa-search"></i> Search</button>
                </div>
            </div>
            <div class="col-md-6 text-md-end">
                <button class="btn btn-primary mb-2 mb-md-0" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                   <i class="fas fa-user-plus"></i> Add New Student
                </button>
                <button class="btn btn-danger ms-md-2 mb-2 mb-md-0" id="resetAllSessionBtn">
                   <i class="fas fa-history"></i> Reset All Sessions
                </button>
            </div>
        </div>


        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>ID Number</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Course</th>
                        <th>Remaining Sessions</th> <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="studentTableBody">
                    <?php
                    $students_query = "SELECT * FROM users ORDER BY lastname ASC";
                    $students_result = mysqli_query($conn, $students_query);

                    if ($students_result && mysqli_num_rows($students_result) > 0) {
                        while ($student = mysqli_fetch_assoc($students_result)) {
                            // Sanitize output
                            $id = htmlspecialchars($student['id']);
                            $idno = htmlspecialchars($student['idno']);
                            $name = htmlspecialchars($student['lastname']) . ', ' . htmlspecialchars($student['firstname']);
                            $email = htmlspecialchars($student['emailadd']);
                            $course = htmlspecialchars($student['course']);

                            // Calculate remaining sessions dynamically
                            $session_query = "SELECT COUNT(*) AS total_sessions FROM sit_in_records WHERE student_id = ?";
                            $session_stmt = mysqli_prepare($conn, $session_query);
                            mysqli_stmt_bind_param($session_stmt, "s", $idno);
                            mysqli_stmt_execute($session_stmt);
                            $session_result = mysqli_stmt_get_result($session_stmt);
                            $session_data = mysqli_fetch_assoc($session_result);

                            $total_sessions = $session_data['total_sessions'] ?? 0;
                            $max_sessions = 30;
                            $remaining_sessions = $max_sessions - $total_sessions;

                            // Display the remaining sessions or 'N/A' if not applicable
                            $remaining_sessions_display = ($remaining_sessions >= 0) ? $remaining_sessions : "<span class='text-muted'>N/A</span>";

                            // Output table row
                            echo "<tr data-student-id='{$id}'>
                                    <td>{$idno}</td>
                                    <td>{$name}</td>
                                    <td>{$email}</td>
                                    <td>{$course}</td>
                                    <td class='session-count'>{$remaining_sessions_display}</td>
                                    <td class='action-buttons-cell'>
                                        <button class='btn btn-info btn-sm edit-student' data-id='{$id}' title='Edit Student'><i class='fas fa-edit'></i></button>
                                        <button class='btn btn-danger btn-sm delete-student' data-id='{$id}' title='Delete Student'><i class='fas fa-trash'></i></button>
                                        <button class='btn btn-warning btn-sm change-password' data-id='{$id}' title='Change Password'><i class='fas fa-key'></i></button>
                                        <button class='btn btn-secondary btn-sm reset-session' data-id='{$id}' title='Restore Session Count'><i class='fas fa-undo'></i></button>
                                    </td>
                                </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6' class='text-center'>No students found</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="editStudentModal" tabindex="-1" aria-labelledby="editStudentModalLabel" aria-hidden="true">
         <div class="modal-dialog">
             <div class="modal-content">
                 <div class="modal-header">
                     <h5 class="modal-title" id="editStudentModalLabel">Edit Student</h5>
                     <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                 </div>
                 <div class="modal-body">
                     <div id="editStudentFormContainer">Loading form...</div>
                 </div>
             </div>
         </div>
     </div>

    <div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
         <div class="modal-dialog">
             <div class="modal-content">
                 <div class="modal-header">
                     <h5 class="modal-title" id="changePasswordModalLabel">Change Password</h5>
                     <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                 </div>
                 <div class="modal-body">
                     <form id="changePasswordForm" method="POST" action="change_password.php">
                         <input type="hidden" id="password_student_id" name="student_id">
                         <div class="mb-3">
                             <label for="change_student_name" class="form-label">Student:</label>
                             <input type="text" class="form-control" id="change_student_name" readonly disabled>
                          </div>
                         <div class="mb-3">
                             <label for="new_password" class="form-label">New Password</label>
                             <input type="password" class="form-control" id="new_password" name="new_password" required>
                         </div>
                         <div class="mb-3">
                             <label for="confirm_password" class="form-label">Confirm Password</label>
                             <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                         </div>
                         <div class="modal-footer">
                             <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                             <button type="submit" class="btn btn-primary">Save Changes</button>
                         </div>
                     </form>
                 </div>
             </div>
         </div>
     </div>

    <div class="modal fade" id="addStudentModal" tabindex="-1" aria-labelledby="addStudentModalLabel" aria-hidden="true">
         <div class="modal-dialog">
             <div class="modal-content">
                 <div class="modal-header">
                     <h5 class="modal-title" id="addStudentModalLabel">Add New Student</h5>
                     <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                 </div>
                 <div class="modal-body">
                     <form id="addStudentForm" method="POST" action="add_student.php">
                          <div class="mb-3"><label class="form-label">ID Number</label><input type="text" class="form-control" name="idno" required></div>
                          <div class="mb-3"><label class="form-label">First Name</label><input type="text" class="form-control" name="firstname" required></div>
                          <div class="mb-3"><label class="form-label">Last Name</label><input type="text" class="form-control" name="lastname" required></div>
                          <div class="mb-3"><label class="form-label">Email</label><input type="email" class="form-control" name="emailadd" required></div>
                          <div class="mb-3"><label class="form-label">Course</label><input type="text" class="form-control" name="course" required></div>
                          <div class="mb-3"><label class="form-label">Password</label><input type="password" class="form-control" id="add_password" name="password" required></div>
                          <div class="mb-3"><label class="form-label">Confirm Password</label><input type="password" class="form-control" id="add_confirm_password" name="confirm_password" required></div>
                         <div class="modal-footer">
                             <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                             <button type="submit" class="btn btn-primary">Add Student</button>
                         </div>
                     </form>
                 </div>
             </div>
         </div>
     </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const studentTableBody = document.getElementById('studentTableBody');
            const messageContainer = document.getElementById('messageContainer');
            const editModalEl = document.getElementById('editStudentModal');
            const editModal = editModalEl ? new bootstrap.Modal(editModalEl) : null;
            const passwordModalEl = document.getElementById('changePasswordModal');
            const passwordModal = passwordModalEl ? new bootstrap.Modal(passwordModalEl) : null;

             // Helper function for AJAX Fetch requests
             async function sendRequest(url, formData) { /* ... same as previous response ... */
                 try { const response = await fetch(url, { method: 'POST', body: formData }); const responseText = await response.text(); if (!response.ok) { let errorMessage = response.statusText; try { const errorJson = JSON.parse(responseText); errorMessage = errorJson.message || responseText; } catch (e) {} throw new Error(`HTTP ${response.status}: ${errorMessage}`); } try { return JSON.parse(responseText); } catch (e) { throw new Error(`Invalid JSON: ${responseText}`); } } catch (error) { console.error('Fetch Error:', error); return { success: false, message: `Client/Server Error. ${error.message}` }; }
             }

            // Event Delegation for table buttons
            if (studentTableBody) {
                studentTableBody.addEventListener('click', function(event) {
                    const targetButton = event.target.closest('button');
                    if (!targetButton) return;
                    const studentId = targetButton.getAttribute('data-id');
                    const studentRow = targetButton.closest('tr');
                    const studentName = studentRow ? studentRow.cells[1].textContent.trim() : 'this student';

                    // --- EDIT ---
                    if (targetButton.classList.contains('edit-student') && editModal) { /* ... same as previous response ... */
                         const formContainer = document.getElementById('editStudentFormContainer'); if (!formContainer) return; formContainer.innerHTML = '<div class="text-center p-3"><i class="fas fa-spinner fa-spin"></i> Loading...</div>'; editModal.show();
                         fetch(`get_student_by_id.php?id=${studentId}`).then(response => response.ok ? response.json() : Promise.reject(`HTTP ${response.status}`)).then(data => { if (data.success && data.student) { formContainer.innerHTML = `<form id="dynamicEditStudentForm" method="POST" action="update_student.php"><input type="hidden" name="student_id" value="${data.student.id||''}"><div class="mb-3"><label class="form-label">ID Number</label><input type="text" class="form-control" name="idno" value="${data.student.idno||''}" required></div><div class="mb-3"><label class="form-label">First Name</label><input type="text" class="form-control" name="firstname" value="${data.student.firstname||''}" required></div><div class="mb-3"><label class="form-label">Last Name</label><input type="text" class="form-control" name="lastname" value="${data.student.lastname||''}" required></div><div class="mb-3"><label class="form-label">Email</label><input type="email" class="form-control" name="emailadd" value="${data.student.emailadd||''}" required></div><div class="mb-3"><label class="form-label">Course</label><input type="text" class="form-control" name="course" value="${data.student.course||''}" required></div><div class="modal-footer mt-3 border-top pt-3"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button><button type="submit" class="btn btn-primary">Save Changes</button></div></form>`; const dynForm = document.getElementById('dynamicEditStudentForm'); if(dynForm) { dynForm.addEventListener('submit', function(e){ e.preventDefault(); sendRequest('update_student.php', new FormData(this)).then(r => { Swal.fire({title:r.success?'Success!':'Error!', text:r.message, icon:r.success?'success':'error'}).then(() => {if(r.success){editModal.hide();window.location.reload();}}); }); }); } } else { formContainer.innerHTML = `<div class="alert alert-danger">${data.message||'Could not load data.'}</div>`; } }).catch(error => { console.error('Edit fetch error:', error); formContainer.innerHTML = `<div class="alert alert-danger">Error loading form: ${error}.</div>`; });
                    }
                    // --- DELETE ---
                    else if (targetButton.classList.contains('delete-student')) { /* ... same as previous response ... */
                         Swal.fire({ title: 'Are you sure?', html: `Delete student:<br><b>${studentName}</b>?<br>Cannot be undone.`, icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Yes, delete it!' }).then((result) => { if (result.isConfirmed) { const fd = new FormData(); fd.append('student_id', studentId); sendRequest('delete_student.php', fd).then(data => { if (data.success) { Swal.fire('Deleted!', data.message||`${studentName} deleted.`, 'success'); studentRow.remove(); if (studentTableBody.rows.length === 0) { studentTableBody.innerHTML = `<tr><td colspan='6' class='text-center'>No students remaining</td></tr>`; } } else { Swal.fire('Error!', data.message||'Could not delete.', 'error'); } }); } });
                    }
                    // --- CHANGE PASSWORD ---
                    else if (targetButton.classList.contains('change-password') && passwordModal) { /* ... same as previous response ... */
                         const sIdInput = document.getElementById('password_student_id'); const sNameDisp = document.getElementById('change_student_name'); const nPass = document.getElementById('new_password'); const cPass = document.getElementById('confirm_password'); if(sIdInput) sIdInput.value = studentId; if(sNameDisp) sNameDisp.value = studentName; if(nPass) nPass.value = ''; if(cPass) cPass.value = ''; passwordModal.show();
                    }
                    // --- RESET SESSION (to 30) ---
                    else if (targetButton.classList.contains('reset-session')) {
                        Swal.fire({
                            title: 'Restore Session Count?',
                            html: `Set remaining sessions back to <b>30</b> for student:<br><b>${studentName}</b>?`,
                            icon: 'question',
                            showCancelButton: true,
                            confirmButtonColor: '#198754',
                            confirmButtonText: 'Yes, restore it!'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                // Send request to reset the session count for the specific student
                                const formData = new FormData();
                                formData.append('student_id', studentId);

                                fetch('reset_all_student_session.php', {
                                    method: 'POST',
                                    body: formData
                                })
                                    .then(response => response.json())
                                    .then(data => {
                                        Swal.fire({
                                            title: data.success ? 'Success!' : 'Error!',
                                            text: data.message,
                                            icon: data.success ? 'success' : 'error'
                                        });

                                        // Update the "Remaining Sessions" column if successful
                                        if (data.success && studentRow) {
                                            const sessionCell = studentRow.querySelector('.session-count');
                                            if (sessionCell) {
                                                sessionCell.textContent = '30'; // Update visually
                                            }
                                        }
                                    })
                                    .catch(error => {
                                        console.error('Reset Session Error:', error);
                                        Swal.fire('Error!', 'An error occurred while resetting the session.', 'error');
                                    });
                            }
                        });
                    }
                });
            } // end table body listener

            // --- Form Submission Handlers ---
            // Change Password Form
            const passwordForm = document.getElementById('changePasswordForm');
             if (passwordForm && passwordModal) { /* ... same as previous response ... */
                 passwordForm.addEventListener('submit', function(e) { e.preventDefault(); const nP = document.getElementById('new_password').value; const cP = document.getElementById('confirm_password').value; if(!nP||nP.length<6){Swal.fire('Validation Error','Password >= 6 chars.','warning');return;} if(nP!==cP){Swal.fire('Validation Error','Passwords do not match!','warning');return;} sendRequest('change_password.php', new FormData(this)).then(r => { Swal.fire({title:r.success?'Success!':'Error!', text:r.message, icon:r.success?'success':'error'}).then(() => {if(r.success){passwordModal.hide();}}); }); });
             }
            // Add Student Form (using default submit)
             const addStudentForm = document.getElementById('addStudentForm');
             if(addStudentForm) { /* ... same as previous response ... */
                addStudentForm.addEventListener('submit', function(e) { const p = document.getElementById('add_password').value; const c = document.getElementById('add_confirm_password').value; if(!p||p.length<6){e.preventDefault();Swal.fire('Validation Error','Password >= 6 chars.','warning');return;} if(p!==c){e.preventDefault();Swal.fire('Validation Error','Passwords do not match!','warning');return;} });
             }

            // --- Search Functionality ---
            const searchInput = document.getElementById('studentSearch');
            const searchButton = document.getElementById('searchButton');
            function performSearch() {
                if (!studentTableBody) return;
                const filterValue = searchInput.value.toLowerCase().trim();
                const rows = studentTableBody.querySelectorAll('tr');
                let isFound = false;
                const cellCount = 6; // Now 6 columns
                const existingNoResultsRow = studentTableBody.querySelector('.no-results');
                if (existingNoResultsRow) existingNoResultsRow.remove();

                rows.forEach(row => {
                    if (row.cells.length === cellCount && !row.classList.contains('no-results')) {
                        const idnoText = row.cells[0]?.textContent.toLowerCase() || '';
                        const nameText = row.cells[1]?.textContent.toLowerCase() || '';
                        const emailText = row.cells[2]?.textContent.toLowerCase() || '';
                        const courseText = row.cells[3]?.textContent.toLowerCase() || '';
                        // *** Include search in the new 'Remaining Sessions' column (index 4) ***
                        const sessionCountText = row.cells[4]?.textContent.toLowerCase() || '';

                        const match = idnoText.includes(filterValue) || nameText.includes(filterValue) || emailText.includes(filterValue) || courseText.includes(filterValue) || sessionCountText.includes(filterValue);
                        row.style.display = match ? '' : 'none';
                        if (match) isFound = true;
                    } else if (row.cells.length === 1) { row.style.display = 'none'; } // Hide 'no students found' row
                });

                if (!isFound && filterValue !== '') {
                    studentTableBody.insertAdjacentHTML('beforeend', `<tr class='no-results'><td colspan='${cellCount}' class='text-center text-warning'>No students match search.</td></tr>`);
                } else if (!isFound && filterValue === '') {
                     const originalEmptyRow = studentTableBody.querySelector(`td[colspan='${cellCount}']`);
                     if (originalEmptyRow) originalEmptyRow.style.display = '';
                     else if (studentTableBody.rows.length === 0) studentTableBody.innerHTML = `<tr><td colspan='${cellCount}' class='text-center'>No students found</td></tr>`;
                }
            }
            if (searchInput && searchButton) {
                 searchButton.addEventListener('click', performSearch);
                 searchInput.addEventListener('keyup', function(event) { if (event.key === 'Enter') performSearch(); });
                 searchInput.addEventListener('input', function() { if (this.value === '') performSearch(); });
            }


           // --- RESET ALL SESSIONS Button Action ---
const resetAllBtn = document.getElementById('resetAllSessionBtn');
if (resetAllBtn) {
    resetAllBtn.addEventListener('click', function () {
        Swal.fire({
            title: 'Reset All Sessions?',
            text: "This will reset the remaining sessions to 30 for ALL students. Are you sure?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, reset all!'
        }).then((result) => {
            if (result.isConfirmed) {
                // Send request to reset all sessions
                fetch('reset_all_student_session.php', {
                    method: 'POST'
                })
                .then(response => response.json())
                .then(data => {
                    console.log('Reset All Sessions Response:', data); // Debug response
                    Swal.fire({
                        title: data.success ? 'Success!' : 'Error!',
                        text: data.message,
                        icon: data.success ? 'success' : 'error'
                    }).then(() => {
                        if (data.success) {
                            // Update all rows in the table to show 30 remaining sessions
                            const rows = document.querySelectorAll('#studentTableBody tr');
                            rows.forEach(row => {
                                const sessionCell = row.querySelector('.session-count');
                                if (sessionCell) {
                                    sessionCell.textContent = '30'; // Update visually
                                }
                            });
                        }
                    });
                })
                .catch(error => {
                    console.error('Reset All Sessions Error:', error);
                    Swal.fire('Error!', 'An error occurred while resetting sessions.', 'error');
                });
            }
        });
    });
}

        }); // End DOMContentLoaded
    </script>
</body>
</html>