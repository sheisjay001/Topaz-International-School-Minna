<?php
include 'includes/db.php';

// Add failed_attempts column
$sql1 = "ALTER TABLE students ADD COLUMN failed_attempts INT DEFAULT 0";
if ($conn->query($sql1) === TRUE) {
    echo "Column failed_attempts added successfully.\n";
} else {
    echo "Error adding failed_attempts: " . $conn->error . "\n";
}

// Add last_failed_login column
$sql2 = "ALTER TABLE students ADD COLUMN last_failed_login DATETIME NULL";
if ($conn->query($sql2) === TRUE) {
    echo "Column last_failed_login added successfully.\n";
} else {
    echo "Error adding last_failed_login: " . $conn->error . "\n";
}

$conn->close();
?>