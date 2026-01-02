<?php
// Script to create profile photo upload directory and update database
include "config.php";

// Create uploads/profile directory
$upload_dir = __DIR__ . '/uploads/profile';
if (!file_exists($upload_dir)) {
    if (mkdir($upload_dir, 0755, true)) {
        echo "Directory 'uploads/profile' created successfully.<br>";
    } else {
        echo "Failed to create directory 'uploads/profile'.<br>";
    }
} else {
    echo "Directory 'uploads/profile' already exists.<br>";
}

// Add photo_path column to tb_karyawan table if it doesn't exist
$check_column = mysqli_query($conn, "SHOW COLUMNS FROM tb_karyawan LIKE 'photo_path'");
if (mysqli_num_rows($check_column) == 0) {
    // Add photo_path column at the end of table
    $alter_sql = "ALTER TABLE tb_karyawan ADD COLUMN photo_path VARCHAR(255) NULL";
    if (mysqli_query($conn, $alter_sql)) {
        echo "Column 'photo_path' added to tb_karyawan table successfully.<br>";
    } else {
        echo "Error adding photo_path column: " . mysqli_error($conn) . "<br>";
    }
} else {
    echo "Column 'photo_path' already exists in tb_karyawan table.<br>";
}

echo "<br>Setup completed! <a href='dashboard.php'>Go to Dashboard</a>";
?>
