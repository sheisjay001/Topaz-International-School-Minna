<?php
include 'includes/db.php';

$result = $conn->query("SHOW TABLES");
while ($row = $result->fetch_row()) {
    echo $row[0] . "\n";
}
?>