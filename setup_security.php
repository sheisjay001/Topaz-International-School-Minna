<?php
include 'includes/db.php';

echo "<h1>Security Setup Script</h1>";

// 1. Update Students Table for Lockout
$sql1 = "ALTER TABLE students ADD COLUMN failed_attempts INT DEFAULT 0";
$sql2 = "ALTER TABLE students ADD COLUMN last_failed_login DATETIME NULL";

if ($conn->query($sql1) === TRUE) echo "Added failed_attempts column.<br>";
else echo "failed_attempts column might already exist: " . $conn->error . "<br>";

if ($conn->query($sql2) === TRUE) echo "Added last_failed_login column.<br>";
else echo "last_failed_login column might already exist: " . $conn->error . "<br>";

// 2. Create Activity Logs Table
$sql3 = "CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    user_type VARCHAR(20) NOT NULL,
    activity VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45),
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP
)";
if ($conn->query($sql3) === TRUE) echo "Created activity_logs table.<br>";
else echo "Error creating activity_logs table: " . $conn->error . "<br>";

// 3. Create Password Resets Table
$sql4 = "CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)";
if ($conn->query($sql4) === TRUE) echo "Created password_resets table.<br>";
else echo "Error creating password_resets table: " . $conn->error . "<br>";

// 4. Create Notifications Table
$sql5 = "CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    target_audience VARCHAR(50) DEFAULT 'all', -- 'student', 'teacher', 'all'
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)";
if ($conn->query($sql5) === TRUE) echo "Created notifications table.<br>";
else echo "Error creating notifications table: " . $conn->error . "<br>";

echo "<h3>Setup Complete. You can now delete this file.</h3>";
echo "<a href='index.php'>Go Home</a>";
?>