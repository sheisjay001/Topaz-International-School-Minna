<?php
include 'includes/db.php';

function addColumn($conn, $table, $column, $type) {
    $check = $conn->query("SHOW COLUMNS FROM $table LIKE '$column'");
    if ($check->num_rows == 0) {
        $sql = "ALTER TABLE $table ADD COLUMN $column $type";
        if ($conn->query($sql) === TRUE) {
            echo "Added $column to $table successfully.\n";
        } else {
            echo "Error adding $column to $table: " . $conn->error . "\n";
        }
    } else {
        echo "Column $column already exists in $table.\n";
    }
}

echo "Updating database schema...\n";

// Update students table
addColumn($conn, 'students', 'reset_token', 'VARCHAR(64) NULL');
addColumn($conn, 'students', 'reset_token_expiry', 'DATETIME NULL');
addColumn($conn, 'students', 'parent_email', 'VARCHAR(100) NULL');
addColumn($conn, 'students', 'parent_phone', 'VARCHAR(20) NULL');

// Update users table
addColumn($conn, 'users', 'reset_token', 'VARCHAR(64) NULL');
addColumn($conn, 'users', 'reset_token_expiry', 'DATETIME NULL');

echo "Database schema update completed.\n";
?>
