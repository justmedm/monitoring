<?php 
session_start();

// Redirect to login if the user is not logged in
if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit();
}

include('database.php');

// Fetch user data from the database (current user)
$user_id = $_SESSION["user"];
$query = "SELECT id, firstname, lastname, midname, emailadd, yearlvl, course,  idno, profile_pic  FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);

if (!$stmt) {
    die("Database query preparation failed: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Check if the user data was fetched successfully
if ($result && mysqli_num_rows($result) > 0) {
    $user = mysqli_fetch_assoc($result);
} else {
    // Handle the case where no user data is found
    die("User not found in the database.");
}

// Fetch total sit-in sessions from the database (no 'hours' field involved)
$session_query = "SELECT COUNT(*) AS total_sessions FROM sit_in_records WHERE student_id = ?";
$session_stmt = mysqli_prepare($conn, $session_query);

if (!$session_stmt) {
    die("Database query preparation failed: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($session_stmt, "s", $user['idno']);
mysqli_stmt_execute($session_stmt);
$session_result = mysqli_stmt_get_result($session_stmt);

if ($session_result && mysqli_num_rows($session_result) > 0) {
    $session_data = mysqli_fetch_assoc($session_result);
    $total_sessions = $session_data['total_sessions'] ?? 0; // default to 0 if no sessions found
} else {
    $total_sessions = 0; // If no sessions exist
}

// Calculate remaining sit-in sessions
$max_sessions = 30;
$remaining_sessions = $max_sessions - $total_sessions;

// Fetch active announcements from the database
$query = "SELECT * FROM announcements WHERE active = 1 ORDER BY created_at DESC";
$result = mysqli_query($conn, $query);

$announcements = [];
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $announcements[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link rel="icon" type="image/x-icon" href="ccs.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">    <script src="https://cdn.tailwindcss.com"></script>
</head>
<style>
    .bg-dark-blue { background-color: #2A3735; }
    .bg-light-pink { background-color: #ABAAAA; }
    .text-dark-green { color: #3A3A3A !important; }
</style>
<body class="bg-light-gray text-dark-green">
    <nav class="bg-dark-blue text-white p-4 flex justify-between items-center shadow-md">
        <div class="container mx-auto flex items-center justify-between px-4 py-2">
            <div class="flex items-center">
                <img class="h-10 mr-2" src="ccs.png" alt="CCS Logo">
                <a class="text-lg font-semibold" href="index.php">CCS Sit-in Dashboard</a>
            </div>
            <div class="flex items-center space-x-4">
                <span class="text-white">ID: <?php echo htmlspecialchars($user['idno'] ?? 'N/A'); ?></span>
                <a class="text-white hover:text-gray-300" href="#editProfileModal" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                    <i class="fas fa-id-card"></i> Account 
                </a>
                <a class="text-white hover:text-gray-300" href="#announcementModal" data-bs-toggle="modal" data-bs-target="#announcementModal">
                    <i class="fas fa-bell"></i> Announcements
                </a>
                <a class="text-white hover:text-gray-300" href="student_feedback.php">
                    <i class="fas fa-comment-dots"></i> Feedback
                </a>
                <a class="bg-yellow-500 text-dark px-3 py-1 rounded hover:bg-yellow-600" href="logout.php">
                    <i class="fas fa-door-open"></i> Exit
                </a>
            </div>
        </div>
    </nav>

    <!-- Welcome Section -->
    <div class="container mt-8">
        <div class="welcome-section bg-white p-8 rounded-lg shadow-md text-center">
            <div class="welcome-text text-3xl font-bold text-blue-900 mb-4">User Dashboard</div>
        </div>

        <!-- Rules Sections -->
        <div class="rules-section mt-8 space-y-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Laboratory Rules -->
                <div>
                    <div class="rules-card bg-white rounded-lg shadow-md overflow-hidden transition-transform transform hover:translate-y-[-5px] hover:shadow-lg">
                        <div class="rules-header bg-blue-900 text-white p-4 text-xl font-bold text-center">
                            LABORATORY RULES AND REGULATIONS
                        </div>
                        <div class="rules-body p-6 bg-gray-100">
                            <p class="mb-4">To avoid embarrassment and maintain camaraderie with your friends and superiors at our laboratories, please observe the following:</p>
                            <ul class="rules-list space-y-2 pl-6">
                                <li class="relative pl-4"><span class="absolute left-0 text-blue-900 font-bold">•</span> Maintain silence, proper decorum, and discipline inside the laboratory.</li>
                                <li class="relative pl-4"><span class="absolute left-0 text-blue-900 font-bold">•</span> Mobile phones, gadgets, and other electronic devices must be in silent mode.</li>
                                <li class="relative pl-4"><span class="absolute left-0 text-blue-900 font-bold">•</span> Eating and drinking are strictly prohibited inside the laboratory.</li>
                                <li class="relative pl-4"><span class="absolute left-0 text-blue-900 font-bold">•</span> Keep the laboratory clean and organized at all times.</li>
                                <li class="relative pl-4"><span class="absolute left-0 text-blue-900 font-bold">•</span> Handle equipment and facilities with care and responsibility.</li>
                                <li class="relative pl-4"><span class="absolute left-0 text-blue-900 font-bold">•</span> Follow proper computer usage and internet policies.</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Sit-in Rules -->
                <div>
                    <div class="rules-card bg-white rounded-lg shadow-md overflow-hidden transition-transform transform hover:translate-y-[-5px] hover:shadow-lg">
                        <div class="rules-header bg-blue-900 text-white p-4 text-xl font-bold text-center">
                            LABORATORY SIT-IN RULES AND REGULATIONS
                        </div>
                        <div class="rules-body p-6 bg-gray-100">
                            <p class="mb-4">To ensure fairness and proper usage of lab resources, please observe the following sit-in rules:</p>
                            <ul class="rules-list space-y-2 pl-6">
                                <li class="relative pl-4"><span class="absolute left-0 text-blue-900 font-bold">•</span> Sit in will be allowed effectively Midterm period onwards and the only exception is Prelim period.</li>
                                <li class="relative pl-4"><span class="absolute left-0 text-blue-900 font-bold">•</span> Sit in will only be allowed if there is no regular class using the laboratory.</li>
                                <li class="relative pl-4"><span class="absolute left-0 text-blue-900 font-bold">•</span> Students must present their ID and register before using the facilities.</li>
                                <li class="relative pl-4"><span class="absolute left-0 text-blue-900 font-bold">•</span> Time allocation for sit-in sessions will be strictly monitored.</li>
                                <li class="relative pl-4"><span class="absolute left-0 text-blue-900 font-bold">•</span> Users must save their work and log out properly after use.</li>
                                <li class="relative pl-4"><span class="absolute left-0 text-blue-900 font-bold">•</span> Report any technical issues to the laboratory staff immediately.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<!-- Modal to Edit Profile -->
<div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editProfileModalLabel">User Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="update_profile.php" enctype="multipart/form-data">
                    <div class="d-flex justify-content-center mb-3 position-relative">
                        <img src="<?php echo !empty($user['profile_pic']) ? 'uploads/' . htmlspecialchars($user['profile_pic']) : 'pp.png'; ?>"
                            alt="Profile Picture"
                            class="rounded-circle border"
                            id="profilePreview"
                            style="width: 120px; height: 120px; object-fit: cover; cursor: pointer;">
                        <label for="profileImage" class="position-absolute bottom-0 end-0 bg-light rounded-circle p-1" style="cursor:pointer;">
                            <i class="bi bi-pencil-fill"></i>
                        </label>
                        <input type="file" class="d-none" id="profileImage" name="profile_image" accept="image/*" onchange="previewProfileImage(event)">
                    </div>

                    <div class="mb-3">
                        <label for="idno" class="form-label">ID No</label>
                        <input type="text" class="form-control" id="idno" name="idno" value="<?php echo htmlspecialchars($user['idno']); ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="firstname" class="form-label">First Name</label>
                        <input type="text" class="form-control" id="firstname" name="firstname" value="<?php echo htmlspecialchars($user['firstname']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="middlename" class="form-label">Middle Name</label>
                        <input type="text" class="form-control" id="midname" name="midname" value="<?php echo htmlspecialchars($user['midname']); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="lastname" class="form-label">Last Name</label>
                        <input type="text" class="form-control" id="lastname" name="lastname" value="<?php echo htmlspecialchars($user['lastname']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="emailadd" class="form-label">Email</label>
                        <input type="email" class="form-control" id="emailadd" name="emailadd" value="<?php echo htmlspecialchars($user['emailadd']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password (Optional)</label>
                        <input type="password" class="form-control" id="password" name="password">
                    </div>
                    <div class="mb-3">
                        <label for="course" class="form-label">Course</label>
                        <select class="form-select" id="course" name="course">
                            <option value="BSIT" <?php echo ($user['course'] == 'BSIT') ? 'selected' : ''; ?>>BSIT</option>
                            <option value="BSCS" <?php echo ($user['course'] == 'BSCS') ? 'selected' : ''; ?>>BSCS</option>
                            <option value="BSIS" <?php echo ($user['course'] == 'BSIS') ? 'selected' : ''; ?>>BSIS</option>
                            </select>
                    </div>
                    <div class="mb-3">
                        <label for="yearlvl" class="form-label">Year Level</label>
                        <select class="form-select" id="yearlvl" name="yearlvl">
                            <option value="1" <?php echo ($user['yearlvl'] == '1') ? 'selected' : ''; ?>>1st Year</option>
                            <option value="2" <?php echo ($user['yearlvl'] == '2') ? 'selected' : ''; ?>>2nd Year</option>
                            <option value="3" <?php echo ($user['yearlvl'] == '3') ? 'selected' : ''; ?>>3rd Year</option>
                            <option value="4" <?php echo ($user['yearlvl'] == '4') ? 'selected' : ''; ?>>4th Year</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="remaining_hours" class="form-label">Remaining Sit-In Hours</label>
                        <input type="text" class="form-control" id="remaining_hours" name="remaining_hours" value="<?php echo $remaining_sessions; ?>" readonly>
                    </div>
                    <button type="submit" class="btn btn-primary">Update Information</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- JS to preview profile image on select -->
<script>
function previewProfileImage(event) {
    const reader = new FileReader();
    reader.onload = function(){
        const output = document.getElementById('profilePreview');
        output.src = reader.result;
    };
    reader.readAsDataURL(event.target.files[0]);
}
</script>



    <!-- Announcement Modal -->
    <div class="modal fade" id="announcementModal" tabindex="-1" aria-labelledby="announcementModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="announcementModalLabel">Important Announcements</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php if (count($announcements) > 0): ?>
                        <div class="announcement-list">
                            <?php foreach ($announcements as $announcement): ?>
                                <div class="announcement-card mb-4 p-4 bg-white rounded shadow">
                                    <h5 class="announcement-title text-lg font-semibold"><?php echo htmlspecialchars($announcement['title']); ?></h5>
                                    <p class="announcement-message"><?php echo nl2br(htmlspecialchars($announcement['message'])); ?></p>
                                    <small class="announcement-date text-gray-500"><?php echo date('F j, Y, g:i a', strtotime($announcement['created_at'])); ?></small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p>No announcements at the moment.</p>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
