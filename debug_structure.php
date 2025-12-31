<?php
include 'includes/db.php';

function getColumns($conn, $table) {
    $result = $conn->query("SHOW COLUMNS FROM $table");
    $columns = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
    }
    return $columns;
}

echo "Admins columns: " . implode(", ", getColumns($conn, 'admins')) . "\n";
echo "Teachers columns: " . implode(", ", getColumns($conn, 'teachers')) . "\n";
echo "Students columns: " . implode(", ", getColumns($conn, 'students')) . "\n";
?>