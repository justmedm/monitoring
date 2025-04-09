<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('database.php'); // Database connection

// Check if the updated_at column exists
$check_column_query = "SHOW COLUMNS FROM announcements LIKE 'updated_at'";
$column_result = mysqli_query($conn, $check_column_query);

if (mysqli_num_rows($column_result) == 0) {
    echo "Column 'updated_at' doesn't exist in the announcements table. Adding it...\n";
    
    $add_column_query = "ALTER TABLE announcements ADD COLUMN updated_at datetime DEFAULT NULL AFTER created_at";
    if (mysqli_query($conn, $add_column_query)) {
        echo "Column 'updated_at' added successfully.\n";
    } else {
        echo "Error adding column 'updated_at': " . mysqli_error($conn) . "\n";
    }
} else {
    echo "Column 'updated_at' already exists in the announcements table.\n";
}

echo "Done!\n";
?> 