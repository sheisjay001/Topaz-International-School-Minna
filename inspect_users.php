<?php
include 'includes/db.php';

echo "Users columns: ";
$result = $conn->query("SHOW COLUMNS FROM users");
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . " ";
}
echo "\n";

echo "\nSample Users:\n";
$users = $conn->query("SELECT * FROM users LIMIT 5");
while ($row = $users->fetch_assoc()) {
    print_r($row);
}
?>