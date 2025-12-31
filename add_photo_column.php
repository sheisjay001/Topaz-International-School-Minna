<?php
include 'includes/db.php';

$sql = "ALTER TABLE users ADD COLUMN photo VARCHAR(255) DEFAULT NULL";
if ($conn->query($sql) === TRUE) {
    echo "Column 'photo' added successfully to 'users' table.";
} else {
    echo "Error adding column: " . $conn->error;
}
?>