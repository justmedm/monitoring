<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('database.php'); // Database connection

echo "Starting announcements table check...\n";

// Check if the announcements table exists
$table_query = "SHOW TABLES LIKE 'announcements'";
$table_result = mysqli_query($conn, $table_query);
if (mysqli_num_rows($table_result) == 0) {
    echo "Table 'announcements' does not exist. Creating it...\n";
    
    // Create the table with all necessary columns
    $create_table_query = "CREATE TABLE `announcements` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `message` varchar(255) NOT NULL,
        `created_at` datetime NOT NULL,
        `updated_at` datetime DEFAULT NULL,
        `active` tinyint(1) NOT NULL DEFAULT 1,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    if (mysqli_query($conn, $create_table_query)) {
        echo "Table 'announcements' created successfully.\n";
    } else {
        echo "Error creating table: " . mysqli_error($conn) . "\n";
        exit();
    }
} else {
    echo "Table 'announcements' already exists. Checking structure...\n";
    
    // Get the structure of the announcements table
    $structure_query = "DESCRIBE announcements";
    $structure_result = mysqli_query($conn, $structure_query);
    if (!$structure_result) {
        echo "Error fetching table structure: " . mysqli_error($conn) . "\n";
        exit();
    }
    
    // Check for missing columns
    $columns = [];
    while ($column = mysqli_fetch_assoc($structure_result)) {
        $columns[$column['Field']] = $column;
        echo "Found column: " . $column['Field'] . "\n";
    }
    
    // Check if the updated_at column exists
    if (!isset($columns['updated_at'])) {
        echo "Column 'updated_at' is missing. Adding it...\n";
        $add_column_query = "ALTER TABLE announcements ADD COLUMN updated_at datetime DEFAULT NULL AFTER created_at";
        if (mysqli_query($conn, $add_column_query)) {
            echo "Column 'updated_at' added successfully.\n";
        } else {
            echo "Error adding column 'updated_at': " . mysqli_error($conn) . "\n";
        }
    }
    
    // Check if the active column exists
    if (!isset($columns['active'])) {
        echo "Column 'active' is missing. Adding it...\n";
        $add_column_query = "ALTER TABLE announcements ADD COLUMN active tinyint(1) NOT NULL DEFAULT 1 AFTER updated_at";
        if (mysqli_query($conn, $add_column_query)) {
            echo "Column 'active' added successfully.\n";
        } else {
            echo "Error adding column 'active': " . mysqli_error($conn) . "\n";
        }
    }
    
    echo "Table structure check completed.\n";
}

echo "Announcements table check and fix completed.\n";
?> 