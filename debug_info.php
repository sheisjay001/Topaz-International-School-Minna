<?php
// debug_info.php - Debugging script for Vercel environment
// DELETE THIS FILE IN PRODUCTION OR SECURE IT

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Vercel Environment Debug Info</h1>";

// 1. PHP Version
echo "<h2>PHP Version</h2>";
echo phpversion();

// 2. Loaded Extensions
echo "<h2>Loaded Extensions</h2>";
$extensions = get_loaded_extensions();
sort($extensions);
echo "<ul>";
foreach ($extensions as $ext) {
    echo "<li>$ext</li>";
}
echo "</ul>";

// 3. Environment Variables (Redacted)
echo "<h2>Environment Variables</h2>";
echo "<pre>";
foreach (getenv() as $key => $value) {
    if (stripos($key, 'PASS') !== false || stripos($key, 'KEY') !== false || stripos($key, 'SECRET') !== false || stripos($key, 'TOKEN') !== false) {
        echo "$key = [REDACTED]\n";
    } else {
        echo "$key = $value\n";
    }
}
echo "</pre>";

// 4. Database Connection Test
echo "<h2>Database Connection Test</h2>";
include __DIR__ . '/includes/config.php';

echo "DB_HOST: " . DB_HOST . "<br>";
echo "DB_USER: " . DB_USER . "<br>";
echo "DB_PORT: " . DB_PORT . "<br>";

$conn = mysqli_init();
mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL);
if (@mysqli_real_connect($conn, DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT, NULL, MYSQLI_CLIENT_SSL)) {
    echo "<p style='color:green'>Database connection successful!</p>";
    echo "Server Info: " . $conn->server_info;
} else {
    echo "<p style='color:red'>Database connection failed!</p>";
    echo "Error: " . mysqli_connect_error();
}

// 5. File System Check
echo "<h2>File System Check</h2>";
echo "Current Directory: " . __DIR__ . "<br>";
echo "Files in current directory:<br>";
$files = scandir(__DIR__);
echo "<ul>";
foreach ($files as $file) {
    echo "<li>$file</li>";
}
echo "</ul>";

echo "Files in includes directory:<br>";
if (is_dir(__DIR__ . '/includes')) {
    $files = scandir(__DIR__ . '/includes');
    echo "<ul>";
    foreach ($files as $file) {
        echo "<li>$file</li>";
    }
    echo "</ul>";
} else {
    echo "includes directory not found!<br>";
}
?>
