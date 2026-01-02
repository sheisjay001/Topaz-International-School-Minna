<?php
include 'includes/db.php';

// Create user_logins table
$sql = "CREATE TABLE IF NOT EXISTS user_logins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent VARCHAR(255),
    login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Table user_logins created successfully.\n";
} else {
    echo "Error creating table: " . $conn->error . "\n";
}

// Add OTP columns to users table
$columns = [
    "otp_code VARCHAR(6) NULL",
    "otp_expiry DATETIME NULL"
];

foreach ($columns as $col) {
    $col_name = explode(' ', $col)[0];
    $check = $conn->query("SHOW COLUMNS FROM users LIKE '$col_name'");
    if ($check->num_rows == 0) {
        $sql = "ALTER TABLE users ADD COLUMN $col";
        if ($conn->query($sql) === TRUE) {
            echo "Column $col_name added successfully.\n";
        } else {
            echo "Error adding column $col_name: " . $conn->error . "\n";
        }
    } else {
        echo "Column $col_name already exists.\n";
    }
}
?>