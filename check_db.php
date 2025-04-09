<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('database.php'); // Database connection

// Check if the announcements table exists
$table_query = "SHOW TABLES LIKE 'announcements'";
$table_result = mysqli_query($conn, $table_query);
if (mysqli_num_rows($table_result) == 0) {
    echo "Table 'announcements' does not exist.\n";
    exit();
}

// Get the structure of the announcements table
$structure_query = "DESCRIBE announcements";
$structure_result = mysqli_query($conn, $structure_query);
if (!$structure_result) {
    echo "Error fetching table structure: " . mysqli_error($conn) . "\n";
    exit();
}

echo "Announcements Table Structure:\n";
echo "Field\tType\tNull\tKey\tDefault\tExtra\n";
echo "------------------------------------------------\n";
while ($column = mysqli_fetch_assoc($structure_result)) {
    echo $column['Field'] . "\t" . 
         $column['Type'] . "\t" . 
         $column['Null'] . "\t" . 
         $column['Key'] . "\t" . 
         $column['Default'] . "\t" . 
         $column['Extra'] . "\n";
}

// Check for sample data
$data_query = "SELECT * FROM announcements LIMIT 5";
$data_result = mysqli_query($conn, $data_query);
if (!$data_result) {
    echo "Error fetching sample data: " . mysqli_error($conn) . "\n";
    exit();
}

echo "\nSample data in announcements table:\n";
if (mysqli_num_rows($data_result) > 0) {
    $first_row = mysqli_fetch_assoc($data_result);
    echo "Columns found: " . implode(", ", array_keys($first_row)) . "\n";
    
    // Reset pointer
    mysqli_data_seek($data_result, 0);
    
    while ($row = mysqli_fetch_assoc($data_result)) {
        echo "ID: " . ($row['id'] ?? 'N/A') . 
             ", Title: " . ($row['title'] ?? 'N/A') . 
             ", Created At: " . ($row['created_at'] ?? 'N/A') . 
             ", Admin: " . (isset($row['admin']) ? $row['admin'] : 'NO ADMIN COLUMN') . "\n";
    }
} else {
    echo "No data found in the announcements table.\n";
}

?> 