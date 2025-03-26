<?php
include '../db.php'; // Ensure correct database connection

// Handle sit-in form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $idno = $conn->real_escape_string($_POST['idno']);
    $name = $conn->real_escape_string($_POST['name']);
    $purpose = $conn->real_escape_string($_POST['purpose']);
    $lab = $conn->real_escape_string($_POST['lab']);
    $session = $conn->real_escape_string($_POST['session']); // Match with your database column

    // Check if the user already has an ongoing sit-in record
    $check_sql = "SELECT * FROM sit_in_records WHERE student_id = ? AND status = 'Ongoing'";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $idno);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        // If a record exists, prevent duplicate entry
        echo "<script>alert('This user already has an ongoing sit-in session.'); window.location.href='currentSitin.php';</script>";
        exit();
    }

    // If no record exists, insert a new one
    $sql = "INSERT INTO sit_in_records (student_id, name, purpose, lab, session, status) 
            VALUES ('$idno', '$name', '$purpose', '$lab', '$session', 'Ongoing')";

    if ($conn->query($sql) === TRUE) {
        echo "<script>alert('Sit-in recorded successfully!'); window.location.href='currentSitin.php';</script>";
    } else {
        echo "<script>alert('Error: " . $conn->error . "');</script>";
    }

    exit; // Stop further execution
}

// Handle search requests (AJAX)
if (isset($_GET['query'])) {
    $query = $conn->real_escape_string($_GET['query']);

    // Search for student by ID or name
    $sql = "SELECT idno, firstname, middlename, lastname 
            FROM users 
            WHERE idno LIKE '%$query%' OR 
                  firstname LIKE '%$query%' OR 
                  middlename LIKE '%$query%' OR 
                  lastname LIKE '%$query%' 
            LIMIT 1";

    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $student = $result->fetch_assoc();

        // Fetch the most recent sit-in record for the student
        $session_sql = "SELECT session FROM sit_in_records 
                        WHERE student_id = ? 
                        ORDER BY stt_id DESC 
                        LIMIT 1"; // Fetch the latest record
        $session_stmt = $conn->prepare($session_sql);
        $session_stmt->bind_param("s", $student['idno']);
        $session_stmt->execute();
        $session_result = $session_stmt->get_result();
        $session_data = $session_result->fetch_assoc();

        // Default remaining sessions to 0 if no records are found
        $remaining_sessions = $session_data['session'] ?? 0;

        // Add remaining sessions to the response
        $student['remaining_sessions'] = $remaining_sessions;

        echo json_encode($student); // Return student data with remaining sessions
        exit;
    } else {
        echo json_encode(["error" => "Student not found."]);
        exit;
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Student</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">

    <!-- Search Student Modal -->
    <div id="searchModal" class="fixed inset-0 flex items-center justify-center bg-gray-900 bg-opacity-50 hidden">
        <div class="bg-white p-5 rounded shadow-lg w-1/3 relative">
            
            <!-- "X" Close Button -->
            <button onclick="closeSearchModal()" class="absolute top-2 right-2 text-gray-600 hover:text-black text-xl">❌</button>

            <h2 class="text-lg font-bold mb-4">🔍 Search Student</h2>

            <!-- Search Form -->
            <input type="text" id="searchInput" class="w-full p-2 border" placeholder="Enter Student Name or ID">
            <button onclick="searchStudent()" class="mt-3 bg-blue-500 text-white px-3 py-1 rounded">Search</button>
        </div>
    </div>

    <!-- Sit-In Form Modal -->
    <div id="sitInModal" class="fixed inset-0 flex items-center justify-center bg-gray-900 bg-opacity-50 hidden">
        <div class="bg-white p-5 rounded shadow-lg w-1/3 relative">
            
            <!-- "X" Close Button -->
            <button onclick="closeSitInModal()" class="absolute top-2 right-2 text-gray-600 hover:text-black text-xl">❌</button>

            <h2 class="text-lg font-bold mb-4">Sit In Form</h2>

            <!-- Sit-in Form -->
            <form id="sitInForm" method="POST">
                
                <label><strong>ID Number:</strong></label>
                <input type="text" id="idno" name="idno" class="w-full p-2 border" readonly>

                <label><strong>Student Name:</strong></label>
                <input type="text" id="name" name="name" class="w-full p-2 border" readonly>

                <label><strong>Purpose:</strong></label>
                <select name="purpose" id="purpose" class="w-full p-2 border">
                    <option value="C Programming">C Programming</option>
                    <option value="Java Programming">Java Programming</option>
                    <option value="Web Development">Web Development</option>
                    <option value="Data Structures">Data Structures</option>
                    <option value="Networking">Networking</option>
                </select>

                <label><strong>Lab:</strong></label>
                <select name="lab" id="lab" class="w-full p-2 border">
                    <option value="524">Lab 524</option>
                    <option value="526">Lab 526</option>
                    <option value="530">Lab 530</option>
                    <option value="550">Lab 550</option>
                </select>
                
                <label><strong>Remaining Sessions:</strong></label>
                <input type="number" id="session" name="session" class="w-full p-2 border" readonly>
                <!-- Buttons -->
                <div class="mt-4 flex justify-end">
                    <button type="button" onclick="closeSitInModal()" class="bg-gray-500 text-white px-3 py-1 rounded mr-2">Close</button>
                    <button type="submit" class="bg-blue-500 text-white px-3 py-1 rounded">Sit In</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Open the search modal
        function openSearchModal() {
            document.getElementById('searchModal').classList.remove('hidden');
        }

        // Close the search modal
        function closeSearchModal() {
            document.getElementById('searchModal').classList.add('hidden');
        }

        // Search function (AJAX)
        function searchStudent() {
            let query = document.getElementById('searchInput').value;
            if (query.length === 0) {
                alert("Please enter a student ID or name.");
                return;
            }

            fetch("search.php?query=" + query)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        alert(data.error); // If student not found
                    } else {
                        // Fill input fields
                        document.getElementById("idno").value = data.idno;
                        document.getElementById("name").value = data.firstname + " " + data.middlename + " " + data.lastname;
                        document.getElementById("session").value = data.remaining_sessions; // Set remaining sessions

                        // Show sit-in form modal
                        document.getElementById("sitInModal").classList.remove("hidden");
                    }
                })
                .catch(error => console.error("Error:", error));
        }

        // Close Sit-in Modal
        function closeSitInModal() {
            document.getElementById("sitInModal").classList.add("hidden");
        }
    </script>

</body>
</html>