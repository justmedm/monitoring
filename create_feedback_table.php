<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('database.php'); // Database connection

echo "Starting feedback table check...\n";

// Check if the feedback table exists
$table_query = "SHOW TABLES LIKE 'feedback'";
$table_result = mysqli_query($conn, $table_query);
if (mysqli_num_rows($table_result) == 0) {
    echo "Table 'feedback' does not exist. Creating it...\n";
    
    // Create the table with necessary columns
    $create_table_query = "CREATE TABLE `feedback` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `student_id` varchar(20) NOT NULL,
        `laboratory` varchar(50) NOT NULL,
        `message` text NOT NULL,
        `created_at` datetime NOT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    if (mysqli_query($conn, $create_table_query)) {
        echo "Table 'feedback' created successfully.\n";
    } else {
        echo "Error creating table: " . mysqli_error($conn) . "\n";
        exit();
    }
} else {
    echo "Table 'feedback' already exists.\n";
}

echo "Feedback table check completed.\n";
?> 