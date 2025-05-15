<?php
session_start();
require '../db.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
    
}

$firstName = isset($_SESSION['first_name']) ? $_SESSION['first_name'] : 'Guest';
$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

if ($userId) {
    $stmt = $conn->prepare("SELECT IDNO, LAST_NAME, FIRST_NAME, MID_NAME, COURSE, YEAR_LEVEL, EMAIL, ADDRESS, UPLOAD_IMAGE FROM users WHERE STUD_NUM = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->bind_result($idNo, $lastName, $dbFirstName, $midName, $course, $yearLevel, $email, $address, $userImage);
    $stmt->fetch();
    $stmt->close();
    
    $profileImage = !empty($userImage) ? '../images/' . $userImage : "../images/image.jpg";
} else {
    $profileImage = "../images/image.jpg";
    $idNo = '';
    $lastName = '';
    $dbFirstName = '';
    $midName = '';
    $course = '';
    $yearLevel = '';
    $email = '';
    $address = '';
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Handle profile update
    if (isset($_POST['action']) && $_POST['action'] == 'update_profile') {
        $idno = $_POST['Idno'];
        $lastname = $_POST['Lastname'];
        $firstname = $_POST['Firstname'];
        $midname = $_POST['Midname'];
        $course = $_POST['Course'];
        $year_level = $_POST['Year_Level'];
        $email = $_POST['Email'];
        $address = $_POST['Address'];
        
        $uploadImagePath = $userImage; // Keep existing image by default
        
        // Handle image upload
        if (isset($_FILES['profileImage']) && $_FILES['profileImage']['error'] == 0) {
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
            $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
            $fileType = finfo_file($fileInfo, $_FILES['profileImage']['tmp_name']);
            finfo_close($fileInfo);
            
            if (in_array($fileType, $allowedTypes)) {
                // Create images directory if it doesn't exist
                $targetDir = "../images/";
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0777, true);
                }
                
                // Generate unique filename
                $fileName = uniqid() . '_' . basename($_FILES["profileImage"]["name"]);
                $targetFile = $targetDir . $fileName;
                
                if (move_uploaded_file($_FILES["profileImage"]["tmp_name"], $targetFile)) {
                    $uploadImagePath = $fileName;
                    $_SESSION['profile_image'] = $fileName;
                    
                    // Delete old image if it exists and is not the default image
                    if (!empty($userImage) && $userImage != "image.jpg" && file_exists($targetDir . $userImage)) {
                        unlink($targetDir . $userImage);
                    }
                }
            }
        }

        try {
            $stmt = $conn->prepare("UPDATE users SET IDNO = ?, LAST_NAME = ?, FIRST_NAME = ?, MID_NAME = ?, COURSE = ?, YEAR_LEVEL = ?, EMAIL = ?, ADDRESS = ?, UPLOAD_IMAGE = ? WHERE STUD_NUM = ?");
            $stmt->bind_param("sssssssssi", $idno, $lastname, $firstname, $midname, $course, $year_level, $email, $address, $uploadImagePath, $userId);
            
            if ($stmt->execute()) {
                echo json_encode([
                    "status" => "success",
                    "message" => "Profile updated successfully.",
                    "image" => $uploadImagePath
                ]);
            } else {
                throw new Exception("Failed to update profile");
            }
            
            $stmt->close();
        } catch (Exception $e) {
            echo json_encode([
                "status" => "error",
                "message" => $e->getMessage()
            ]);
        }
        
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="icon" href="../logo/ccs.png" type="image/x-icon">
    <title>Edit</title>
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
         body {
            font-family: 'Poppins', sans-serif;
            background: white;
            min-height: 100vh;
        }
        /* Add gradient text class for the footer */
        .gradient-text {
            background: linear-gradient(to right, #ec4899, #a855f7, #6366f1);
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
        .tab-button.active {
            color: #2563eb;
            border-bottom: 2px solid #2563eb;
        }
        .btn-gradient {
            background: linear-gradient(to bottom right, #2563eb, #3b82f6);
        }
        .accent-gradient {
            background: linear-gradient(to right, #2563eb, #3b82f6);
        }
        /* Header nav items */
        .nav-item {
            display: flex;
            align-items: center;
            padding: 0.5rem 1rem;
            color: rgba(255, 255, 255, 0.9);
            font-size: 0.875rem;
            font-weight: 500;
            border-radius: 0.5rem;
            transition: all 0.2s;
        }
        .nav-item:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }
        .nav-item.active {
            background-color: rgba(255, 255, 255, 0.3);
        }
        .nav-item i {
            width: 1.25rem;
            text-align: center;
            margin-right: 0.75rem;
        }
        /* Mobile menu styles */
        @media (max-width: 768px) {
            .nav-item span {
                display: none;
            }
            .nav-item i {
                margin-right: 0;
            }
        }
        /* Responsive adjustments */
        @media (max-width: 1024px) {
            header .container {
                padding-left: 1rem;
                padding-right: 1rem;
            }
        }
    </style>
</head>
<body class="min-h-screen font-poppins bg-gray-100">
    <div class="flex justify-center items-center min-h-screen p-2">
        <div class="bg-white rounded-lg shadow-lg max-w-3xl w-full border border-gray-200">
            <!-- Header -->
            <div class="flex justify-between items-center px-6 py-3 border-b border-gray-200 rounded-t-lg bg-gradient-to-r from-blue-500 to-blue-400">
                <h2 class="text-lg font-bold text-white tracking-wide">ACCOUNT SETTINGS</h2>
                <button class="text-white text-xl font-bold focus:outline-none" onclick="window.location.href='dashboard.php'">&times;</button>
            </div>
            <!-- Profile Section -->
            <div class="flex flex-col items-center py-6 px-6">
                <div class="relative w-24 h-24 mb-2">
                    <img src="<?php echo htmlspecialchars($profileImage); ?>" alt="Profile" id="profileImage" title="Click to change profile picture" class="w-24 h-24 rounded-full object-cover border-2 border-gray-200 shadow cursor-pointer transition-transform duration-200 hover:scale-105">
                    <input type="file" id="fileInput" name="profileImage" accept="image/*" class="hidden" form="editForm">
                </div>
                <div class="text-center">
                    <div class="text-lg font-semibold text-gray-800 mb-1"><?php echo htmlspecialchars($dbFirstName . ' ' . $lastName); ?></div>
                    <div class="text-xs text-gray-500 mb-2"><?php echo htmlspecialchars($email); ?></div>
                </div>
            </div>
            <!-- Original Content and Form Section -->
            <div class="px-6 pb-6">
                <!-- Tabs Navigation -->
                <div class="flex justify-center border-b border-gray-200 mb-6">
                    <button type="button" 
                            class="tab-button px-6 py-3 font-medium text-sm active text-blue-600 border-b-2 border-blue-600" 
                            data-tab="edit-profile">
                        Edit Profile
                    </button>
                </div>
                <!-- Edit Profile Tab Content -->
                <div id="edit-profile" class="tab-content block">
                    <form id="editForm" method="POST" action="" class="mt-6" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="update_profile">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- ID Number -->
                            <div class="relative col-span-1 md:col-span-2 group">
                                <div class="absolute -inset-0.5 bg-gradient-to-r from-blue-500 to-purple-500 rounded-lg blur opacity-10 group-hover:opacity-30 transition duration-200"></div>
                                <div class="relative bg-white rounded-lg overflow-hidden">
                                    <input type="text" 
                                           id="Idno" 
                                           name="Idno" 
                                           class="w-full px-4 py-3 border border-gray-300 rounded-md cursor-not-allowed bg-gray-50" 
                                           value="<?php echo htmlspecialchars($idNo); ?>" 
                                           readonly>
                                    <span class="absolute top-1/2 -translate-y-1/2 right-3 text-xs font-semibold text-gray-500">ID Number</span>
                                </div>
                            </div>
                            <!-- Name Fields -->
                            <div class="mb-0 col-span-1 md:col-span-2 grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="group relative">
                                    <div class="absolute -inset-0.5 bg-gradient-to-r from-blue-500 to-purple-500 rounded-lg blur opacity-10 group-hover:opacity-30 transition duration-200"></div>
                                    <div class="relative bg-white rounded-lg overflow-hidden">
                                        <input type="text" id="Lastname" name="Lastname" class="w-full px-4 py-3 border border-gray-300 rounded-md" placeholder="Last Name" value="<?php echo htmlspecialchars($lastName); ?>" required>
                                    </div>
                                </div>
                                <div class="group relative">
                                    <div class="absolute -inset-0.5 bg-gradient-to-r from-blue-500 to-purple-500 rounded-lg blur opacity-10 group-hover:opacity-30 transition duration-200"></div>
                                    <div class="relative bg-white rounded-lg overflow-hidden">
                                        <input type="text" id="Firstname" name="Firstname" class="w-full px-4 py-3 border border-gray-300 rounded-md" placeholder="First Name" value="<?php echo htmlspecialchars($dbFirstName); ?>" required>
                                    </div>
                                </div>
                                <div class="group relative">
                                    <div class="absolute -inset-0.5 bg-gradient-to-r from-blue-500 to-purple-500 rounded-lg blur opacity-10 group-hover:opacity-30 transition duration-200"></div>
                                    <div class="relative bg-white rounded-lg overflow-hidden">
                                        <input type="text" id="Midname" name="Midname" class="w-full px-4 py-3 border border-gray-300 rounded-md" placeholder="Middle Name" value="<?php echo htmlspecialchars($midName); ?>">
                                    </div>
                                </div>
                            </div>
                            <!-- Course -->
                            <div class="group relative">
                                <div class="absolute -inset-0.5 bg-gradient-to-r from-blue-500 to-purple-500 rounded-lg blur opacity-10 group-hover:opacity-30 transition duration-200"></div>
                                <div class="relative bg-white rounded-lg overflow-hidden">
                                    <select id="Course" name="Course" class="w-full px-4 py-3 border border-gray-300 rounded-md" required>
                                        <option value="" disabled>Select a Course</option>
                                        <option value="BS IN ACCOUNTANCY" <?php if ($course == 'BS IN ACCOUNTANCY') echo 'selected'; ?>>BS IN ACCOUNTANCY</option>
                                        <option value="BS IN BUSINESS ADMINISTRATION" <?php if ($course == 'BS IN BUSINESS ADMINISTRATION') echo 'selected'; ?>>BS IN BUSINESS ADMINISTRATION</option>
                                        <option value="BS IN CRIMINOLOGY" <?php if ($course == 'BS IN CRIMINOLOGY') echo 'selected'; ?>>BS IN CRIMINOLOGY</option>
                                        <option value="BS IN CUSTOMS ADMINISTRATION" <?php if ($course == 'BS IN CUSTOMS ADMINISTRATION') echo 'selected'; ?>>BS IN CUSTOMS ADMINISTRATION</option>
                                        <option value="BS IN INFORMATION TECHNOLOGY" <?php if ($course == 'BS IN INFORMATION TECHNOLOGY') echo 'selected'; ?>>BS IN INFORMATION TECHNOLOGY</option>
                                        <option value="BS IN COMPUTER SCIENCE" <?php if ($course == 'BS IN COMPUTER SCIENCE') echo 'selected'; ?>>BS IN COMPUTER SCIENCE</option>
                                        <option value="BS IN OFFICE ADMINISTRATION" <?php if ($course == 'BS IN OFFICE ADMINISTRATION') echo 'selected'; ?>>BS IN OFFICE ADMINISTRATION</option>
                                        <option value="BS IN SOCIAL WORK" <?php if ($course == 'BS IN SOCIAL WORK') echo 'selected'; ?>>BS IN SOCIAL WORK</option>
                                        <option value="BACHELOR OF SECONDARY EDUCATION" <?php if ($course == 'BACHELOR OF SECONDARY EDUCATION') echo 'selected'; ?>>BACHELOR OF SECONDARY EDUCATION</option>
                                        <option value="BACHELOR OF ELEMENTARY EDUCATION" <?php if ($course == 'BACHELOR OF ELEMENTARY EDUCATION') echo 'selected'; ?>>BACHELOR OF ELEMENTARY EDUCATION</option>
                                    </select>
                                </div>
                            </div>
                            <!-- Year Level -->
                            <div class="group relative">
                                <div class="absolute -inset-0.5 bg-gradient-to-r from-blue-500 to-purple-500 rounded-lg blur opacity-10 group-hover:opacity-30 transition duration-200"></div>
                                <div class="relative bg-white rounded-lg overflow-hidden">
                                    <select id="Year_Level" name="Year_Level" class="w-full px-4 py-3 border border-gray-300 rounded-md" required>
                                        <option value="" disabled>Select a Year Level</option>
                                        <option value="1st Year" <?php if ($yearLevel == '1st Year') echo 'selected'; ?>>1st Year</option>
                                        <option value="2nd Year" <?php if ($yearLevel == '2nd Year') echo 'selected'; ?>>2nd Year</option>
                                        <option value="3rd Year" <?php if ($yearLevel == '3rd Year') echo 'selected'; ?>>3rd Year</option>
                                        <option value="4th Year" <?php if ($yearLevel == '4th Year') echo 'selected'; ?>>4th Year</option>
                                    </select>
                                </div>
                            </div>
                            <!-- Email and Address -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Email -->
                                <div class="group relative">
                                    <div class="absolute -inset-0.5 bg-gradient-to-r from-blue-500 to-purple-500 rounded-lg blur opacity-10 group-hover:opacity-30 transition duration-200"></div>
                                    <div class="relative bg-white rounded-lg overflow-hidden">
                                        <input type="email" 
                                            id="Email" 
                                            name="Email" 
                                            class="w-full px-4 py-3 border border-gray-300 rounded-md" 
                                            placeholder="Email Address" 
                                            value="<?php echo htmlspecialchars($email); ?>" 
                                            required>
                                    </div>
                                </div>
                                <!-- Address -->
                                <div class="group relative">
                                    <div class="absolute -inset-0.5 bg-gradient-to-r from-blue-500 to-purple-500 rounded-lg blur opacity-10 group-hover:opacity-30 transition duration-200"></div>
                                    <div class="relative bg-white rounded-lg overflow-hidden">
                                        <input type="text" 
                                            id="Address" 
                                            name="Address" 
                                            class="w-full px-4 py-3 border border-gray-300 rounded-md" 
                                            placeholder="Complete Address" 
                                            value="<?php echo htmlspecialchars($address); ?>" 
                                            required>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="text-center mt-10 mb-4">
                            <button type="submit" class="relative inline-flex items-center justify-center overflow-hidden rounded-lg group bg-gradient-to-br from-purple-600 to-blue-500 p-0.5 text-sm font-medium hover:text-white">
                                <span class="relative rounded-md bg-white px-8 py-3 transition-all duration-300 ease-in-out group-hover:bg-opacity-0 text-purple-700 font-bold group-hover:text-white">
                                    Save Profile Changes
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Main content continues -->
    <script>
        function toggleNav(x) {
            document.getElementById("mySidenav").classList.toggle("-translate-x-0");
            document.getElementById("mySidenav").classList.toggle("-translate-x-full");
        }

        function closeNav() {
            document.getElementById("mySidenav").classList.remove("-translate-x-0");
            document.getElementById("mySidenav").classList.add("-translate-x-full");
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Tab switching functionality
            const tabButtons = document.querySelectorAll('.tab-button');
            const tabContents = document.querySelectorAll('.tab-content');
            
            tabButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Remove active class from all buttons
                    tabButtons.forEach(btn => {
                        btn.classList.remove('active', 'text-blue-600', 'border-b-2', 'border-blue-600');
                        btn.classList.add('text-gray-500');
                    });
                    
                    // Add active class to clicked button
                    this.classList.add('active', 'text-blue-600', 'border-b-2', 'border-blue-600');
                    this.classList.remove('text-gray-500');
                    
                    // Hide all tab contents
                    tabContents.forEach(content => {
                        content.classList.add('hidden');
                        content.classList.remove('block');
                    });
                    
                    // Show the target tab content
                    const tabId = this.getAttribute('data-tab');
                    document.getElementById(tabId).classList.remove('hidden');
                    document.getElementById(tabId).classList.add('block');
                });
            });

            const idnoInput = document.getElementById('Idno');
            idnoInput.addEventListener('input', function() {
                this.value = this.value.replace(/\D/g, '').slice(0, 8);
            });

            const nameInputs = ['Lastname', 'Firstname', 'Midname'];
            nameInputs.forEach(function(id) {
                const input = document.getElementById(id);
                input.addEventListener('input', function() {
                    this.value = this.value.replace(/[^a-zA-Z\s]/g, '');
                });
            });

            const logoutLink = document.querySelector("a[href='../logout.php']");
            if (logoutLink) {
                logoutLink.addEventListener("click", function(e) {
                    e.preventDefault();
                    fetch("../login.php", {
                        method: "POST"
                    })
                    .then(response => {
                        if (response.ok) {
                            window.location.href = "../login.php";
                        } else {
                            console.error("Logout failed");
                        }
                    })
                    .catch(error => {
                        console.error("Error:", error);
                    });
                });
            }
        });

        // Add image upload handler
        document.getElementById('profileImage').addEventListener('click', function() {
            document.getElementById('fileInput').click();
        });

        document.getElementById('fileInput').addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('profileImage').src = e.target.result;
                };
                reader.readAsDataURL(this.files[0]);
            }
        });

        // Update profile form submission
        document.getElementById('editForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
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
                        icon: 'success',
                        title: data.message,
                        background: '#10B981'
                    }).then(() => {
                        // Update the profile image in the sidebar if it was changed
                        if (data.image) {
                            const sidebarImage = document.querySelector('#mySidenav img');
                            if (sidebarImage) {
                                sidebarImage.src = '../images/' + data.image;
                            }
                        }
                    });
                } else {
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
                        icon: 'error',
                        title: data.message,
                        background: '#EF4444'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
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
                    icon: 'error',
                    title: 'An unexpected error occurred',
                    background: '#EF4444'
                });
            });
        });
    </script>
    
</body>
</html>