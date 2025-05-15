<?php
session_start();
require '../db.php';

// Check if admin is not logged in, redirect to login page
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header("Location: ../login.php");
    exit;
}

// Add time-in handling
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['time_in'])) {
    $idno = $_POST['idno'];
    $fullName = $_POST['full_name'];
    $purpose = $_POST['purpose'];
    $laboratory = $_POST['laboratory'];
    
    // Check if student already has an active session
    $checkStmt = $conn->prepare("SELECT * FROM curr_sitin WHERE IDNO = ? AND STATUS = 'Active'");
    $checkStmt->bind_param("i", $idno);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows > 0) {
        $_SESSION['toast'] = [
            'icon' => 'error',
            'title' => 'Student already has an active sit-in session.',
            'background' => '#EF4444'
        ];
    } else {
        // Insert new sit-in record
        $stmt = $conn->prepare("INSERT INTO curr_sitin (IDNO, FULL_NAME, PURPOSE, LABORATORY, TIME_IN, DATE, STATUS) VALUES (?, ?, ?, ?, NOW(), CURDATE(), 'Active')");
        $stmt->bind_param("isss", $idno, $fullName, $purpose, $laboratory);
        
        if ($stmt->execute()) {
            $_SESSION['toast'] = [
                'icon' => 'success',
                'title' => 'Time-in recorded successfully',
                'background' => '#10B981'
            ];
        } else {
            $_SESSION['toast'] = [
                'icon' => 'error',
                'title' => 'Error recording time-in',
                'background' => '#EF4444'
            ];
        }
    }
    header("Location: admin_search.php");
    exit();
}

// Only fetch student data when search button is clicked via POST
$student = null;
$searched = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search']) && !empty($_POST['search'])) {
    $searched = true;
    $stmt = $conn->prepare("SELECT * FROM users WHERE IDNO = ?");
    $stmt->bind_param("s", $_POST['search']);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();
}

// Add this after your session checks, before the HTML
if (isset($_SESSION['toast'])) {
    ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-right',
                iconColor: 'white',
                customClass: {
                    popup: 'colored-toast'
                },
                showConfirmButton: false,
                timer: 1500,
                timerProgressBar: true
            });
            Toast.fire({
                icon: '<?php echo $_SESSION['toast']['icon']; ?>',
                title: '<?php echo $_SESSION['toast']['title']; ?>',
                background: '<?php echo $_SESSION['toast']['background']; ?>'
            });
        });
    </script>
    <?php
    unset($_SESSION['toast']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Search</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="icon" href="../logo/ccs.png" type="image/x-icon">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'poppins': ['Poppins', 'sans-serif']
                    },
                }
            }
        }
    </script>
    <style>
        .gradient-text {
            background: linear-gradient(to right, #2563eb, #3b82f6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            display: inline-block;
        }
        
        .colored-toast.swal2-icon-success {
            background-color: #10B981 !important;
        }
        .colored-toast.swal2-icon-error {
            background-color: #EF4444 !important;
        }
        .colored-toast {
            color: #fff !important;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            font-weight: 500;
            color: white;
            transition: background-color 0.3s ease;
        }

        .nav-item:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }

        .nav-item.active {
            background-color: rgba(255, 255, 255, 0.3);
        }

        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            font-weight: 500;
            color: #333;
            transition: background-color 0.3s ease;
        }

        .dropdown-item:hover {
            background-color: rgba(0, 0, 0, 0.05);
        }
    </style>
</head>
<body class="min-h-screen font-poppins" style="background: white">
    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-600 to-blue-800 text-white shadow-lg">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-between py-4">
                <!-- Left - System Title with Logo -->
                <div class="flex items-center space-x-4">
                    <img src="../logo/ccs.png" alt="Logo" class="w-10 h-10">
                    <h1 class="font-bold text-xl">CCS SIT-IN MONITORING SYSTEM</h1>
                </div>

                <!-- Center/Right - Navigation Menu -->
                <nav class="flex items-center space-x-6">
                    <a href="admin_dashboard.php" class="nav-item">
                        <span>Home</span>
                    </a>
                    <a href="admin_search.php" class="nav-item active">
                       
                        <span>Search</span>
                    </a>
                    <a href="admin_sitin.php" class="nav-item">
                        <span>Sit-in</span>
                    </a>
                    <!-- View Dropdown -->
                    <div class="relative group">
                        <button class="nav-item">
                            <span>View</span>
                        </button>
                        <div class="absolute top-full left-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-2 z-50 hidden group-hover:block">
                            <a href="admin_sitinrec.php" class="dropdown-item">
                                Sit-in Records
                            </a>
                            <a href="admin_studlist.php" class="dropdown-item">
                                List of Students
                            </a>
                            <a href="admin_feedback.php" class="dropdown-item">
                                Feedbacks
                            </a>
                        </div>
                    </div>

                    <!-- Lab Dropdown -->
                    <div class="relative group">
                        <button class="nav-item">
                            <span>Lab</span>
                        </button>
                        <div class="absolute top-full left-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-2 z-50 hidden group-hover:block">
                            <a href="admin_resources.php" class="dropdown-item">
                                Resources
                            </a>
                            <a href="admin_lab_schedule.php" class="dropdown-item">
                                Lab Schedule
                            </a>
                            <a href="admin_lab_usage.php" class="dropdown-item">
                                Lab Usage Point
                            </a>
                        </div>
                    </div>

                    <a href="admin_reports.php" class="nav-item">
                        <span>Reports</span>
                    </a>
                    <a href="admin_reservation.php" class="nav-item">
                        <span>Reservation</span>
                    </a>
                    <a href="../logout.php" class="nav-item hover:bg-red-500/20">
                        <span>Logout</span>
                    </a>
                </nav>
            </div>
        </div>
    </div>

    <!-- Dashboard Content -->
    <div class="px-8 py-8 w-full flex flex-wrap gap-8">
        <div class="flex-1 min-w-[200px] max-w-4xl mx-auto bg-white rounded-xl shadow-lg overflow-hidden border border-[rgba(255,255,255,1)]">
            <div class="text-white p-4 flex items-center justify-center relative overflow-hidden" style="background: linear-gradient(135deg, #2563eb 100%, #3b82f6 100%)">
                <h2 class="text-xl font-bold tracking-wider uppercase relative z-10">Search Student</h2>
            </div>
            <div class="p-6 h-[calc(100%-4rem)] flex flex-col">
                <div class="mb-6">
                    <form method="POST" action="" class="space-y-3">
                        <div class="flex gap-2">
                            <input type="text" name="search" placeholder="Enter ID Number..." 
                                   class="flex-1 p-3 border border-gray-300 rounded-lg">
                            <button type="submit" class="relative inline-flex items-center justify-center overflow-hidden rounded-lg group bg-gradient-to-br from-blue-600 to-blue-500 p-0.5 text-sm font-medium hover:text-white">
                                <span class="relative rounded-md bg-white px-8 py-3 transition-all duration-300 ease-in-out group-hover:bg-opacity-0 text-blue-700 font-bold group-hover:text-white">
                                    Search
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Student Results Area -->
                <div class="flex-1 overflow-y-auto">
                    <div class="space-y-6 pr-2">
                        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && $searched): ?>
                            <?php if ($student): ?>
                                <!-- Student Card -->
                                <form method="POST" action="" class="bg-gradient-to-br from-white to-gray-50 p-8 rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300">
                                    <!-- Student Header -->
                                    <div class="flex flex-col md:flex-row items-center md:items-start gap-4 md:gap-6 lg:gap-8">
                                        <!-- Left side - Profile Image -->
                                        <div class="w-28 flex-shrink-0 flex flex-col items-center gap-2">
                                            <div class="relative group">
                                                <?php if (!empty($student['UPLOAD_IMAGE'])): ?>
                                                    <img src="../images/<?php echo htmlspecialchars($student['UPLOAD_IMAGE']); ?>" 
                                                         alt="Student Photo"
                                                         class="w-24 h-24 rounded-2xl object-cover shadow-md group-hover:scale-105 transition-transform duration-300"
                                                         onerror="this.src='../images/image.jpg'">
                                                <?php else: ?>
                                                    <img src="../images/image.jpg"
                                                         alt="Default Photo"
                                                         class="w-24 h-24 rounded-2xl object-cover shadow-md group-hover:scale-105 transition-transform duration-300">
                                                <?php endif; ?>
                                                <div class="absolute -bottom-2 -right-2 bg-green-400 text-white rounded-full px-2 py-1 text-xs">
                                                    Verified
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Center - Student Information -->
                                        <div class="flex-1 flex flex-col justify-center min-w-0">
                                            <!-- Name and ID -->
                                            <div class="mb-4">
                                                <h3 class="text-xl font-bold text-gray-800 mb-1 truncate">
                                                    <?php echo htmlspecialchars($student['FIRST_NAME'] . ' ' . $student['LAST_NAME']); ?>
                                                </h3>
                                                <div class="inline-block bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-xs font-medium">
                                                    <?php echo htmlspecialchars($student['IDNO']); ?>
                                                </div>
                                            </div>

                                            <!-- Student Details -->
                                            <div class="space-y-1 text-sm">
                                                <div class="flex items-center text-gray-700">
                                                    <span class="font-medium min-w-[70px]">Course:</span>
                                                    <span class="truncate"><?php echo htmlspecialchars($student['COURSE']); ?></span>
                                                </div>
                                                <div class="flex items-center text-gray-700">
                                                    <span class="font-medium min-w-[70px]">Year Level:</span>
                                                    <span><?php echo htmlspecialchars($student['YEAR_LEVEL']); ?></span>
                                                </div>
                                                <div class="flex items-center text-gray-700">
                                                    <span class="font-medium min-w-[70px]">Email:</span>
                                                    <span class="text-blue-600 truncate"><?php echo htmlspecialchars($student['EMAIL']); ?></span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Right side - Dropdowns -->
                                        <div class="flex flex-col gap-2 w-44 min-w-[150px] max-w-[180px]">
                                            <!-- Purpose and Laboratory Dropdowns -->
                                            <select name="purpose" class="w-full p-2 bg-gray-50 border border-gray-300 rounded-xl cursor-pointer hover:border-purple-500 transition-colors text-xs" required>
                                                <option value=""disabled selected>Select Purpose</option>
                                                <option value="C Programming">C Programming</option>
                                                <option value="C++ Programming">C++ Programming</option>
                                                <option value="C# Programming">C# Programming</option>
                                                <option value="Java Programming">Java Programming</option>
                                                <option value="Php Programming">Php Programming</option>
                                                <option value="Python Programming">Python Programming</option>
                                                <option value="Database">Database</option>
                                                <option value="Digital Logic & Design">Digital Logic & Design</option>
                                                <option value="Embedded System & IOT">Embedded System & IOT</option>
                                                <option value="System Integration & Architecture">System Integration & Architecture</option>
                                                <option value="Computer Application">Computer Application</option>
                                                <option value="Web Design & Development">Web Design & Development</option>
                                                <option value="Project Management">Project Management</option>
                                            </select>
                                            <select name="laboratory" class="w-full p-2 bg-gray-50 border border-gray-300 rounded-xl cursor-pointer hover:border-purple-500 transition-colors text-xs" required>
                                                <option value=""disabled selected>Select Laboratory</option>
                                                <option value="Lab 517">Lab 517</option>
                                                <option value="Lab 524">Lab 524</option>
                                                <option value="Lab 526">Lab 526</option>
                                                <option value="Lab 528">Lab 528</option>
                                                <option value="Lab 530">Lab 530</option>
                                                <option value="Lab 542">Lab 542</option>
                                                <option value="Lab 544">Lab 544</option>
                                            </select>
                                            <!-- Sessions Progress -->
                                            <div class="mt-2">
                                                <div class="flex justify-between items-center mb-1">
                                                    <p class="text-xs text-gray-600">Sessions Progress</p>
                                                    <p class="text-base font-bold text-black-600"><?php echo $student['SESSION']; ?>/30</p>
                                                </div>
                                                <div class="w-full bg-gray-200 rounded-full h-2">
                                                    <div class="bg-gradient-to-r from-red-500 to-red-500 h-2 rounded-full transition-all duration-500" 
                                                         style="width: <?php echo ($student['SESSION'] / 30) * 100; ?>%">
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- Time-In Button -->
                                            <div class="flex justify-end mt-2">
                                                <input type="hidden" name="idno" value="<?php echo htmlspecialchars($student['IDNO']); ?>">
                                                <input type="hidden" name="full_name" value="<?php echo htmlspecialchars($student['FIRST_NAME'] . ' ' . $student['LAST_NAME']); ?>">
                                                <button type="submit" name="time_in" class="relative inline-flex items-center justify-center overflow-hidden rounded-lg group bg-gradient-to-br from-blue-600 to-blue-500 p-0.5 text-xs font-medium hover:text-white">
                                                    <span class="relative rounded-md bg-white px-5 py-2 transition-all duration-300 ease-in-out group-hover:bg-opacity-0 text-blue-700 font-bold group-hover:text-white">
                                                        Time - In
                                                    </span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            <?php else: ?>
                                <div class="text-center py-8">
                                    <div class="bg-red-50 p-6 rounded-xl inline-block">
                                        <i class="fas fa-user-times text-4xl text-red-400 mb-3"></i>
                                        <p class="text-gray-600">No student found with ID Number: <span class="font-semibold"><?php echo htmlspecialchars($_POST['search']); ?></span></p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // SweetAlert for success and error messages
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-right',
            iconColor: 'white',
            customClass: {
                popup: 'colored-toast'
            },
            showConfirmButton: false,
            timer: 1500,
            timerProgressBar: true
        });

        <?php if(isset($_SESSION['swal_error'])): ?>
        Toast.fire({
            icon: 'error',
            title: '<?php echo $_SESSION['swal_error']; ?>',
            background: '#EF4444'
        });
        <?php unset($_SESSION['swal_error']); endif; ?>

        <?php if(isset($_SESSION['swal_success'])): ?>
        Toast.fire({
            icon: 'success',
            title: '<?php echo $_SESSION['swal_success']; ?>',
            background: '#10B981'
        });
        <?php unset($_SESSION['swal_success']); endif; ?>

        // Add to your existing script section
        document.addEventListener('alpine:init', () => {
            Alpine.data('notificationData', () => ({
                open: false,
                notifications: [],
                unreadCount: 0,
                
                fetchNotifications() {
                    // ... copy the entire notification functions from admin_dashboard.php ...
                },
                
                readNotification(id, notification) {
                    // ... copy notification handling functions ...
                },
                
                markAllAsRead() {
                    // ... copy mark all as read function ...
                },
                
                getNotificationType(notification) {
                    // ... copy type function ...
                },
                
                getNotificationIcon(notification) {
                    // ... copy icon function ...
                },
                
                formatDate(dateString) {
                    // ... copy date formatting function ...
                }
            }));
        });
    </script>
</body>
</html>