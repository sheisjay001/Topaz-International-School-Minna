<?php
// setup_db.php - Run this once to initialize the database
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Database Setup</h1>";
echo "<p>Initializing database connection...</p>";

include 'includes/config.php';

// Manually connect
$host = DB_HOST;
$user = DB_USER;
$pass = DB_PASS;
$dbname = DB_NAME;
$port = DB_PORT;

$conn = mysqli_init();
mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL);

if (!@mysqli_real_connect($conn, $host, $user, $pass, $dbname, $port, NULL, MYSQLI_CLIENT_SSL)) {
    die("<h2 style='color:red'>Connection Failed: " . mysqli_connect_error() . "</h2>");
}

echo "<p style='color:green'>Connected successfully!</p>";

// Include the setup logic
echo "<p>Running schema updates (db_setup.php)...</p>";
$start = microtime(true);
include 'includes/db_setup.php';
$end = microtime(true);

echo "<p style='color:green'>Schema updates completed in " . round($end - $start, 2) . " seconds.</p>";
echo "<hr>";
echo "<h3>Tables in Database:</h3>";
$tables = $conn->query("SHOW TABLES");
echo "<ul>";
while ($row = $tables->fetch_array()) {
    echo "<li>" . $row[0] . "</li>";
}
echo "</ul>";
echo "<p>You can now go to the <a href='index.php'>Homepage</a>.</p>";
