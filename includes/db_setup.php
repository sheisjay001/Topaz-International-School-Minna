<?php
// Prevent double connection if included from db.php
if (!isset($conn) || !($conn instanceof mysqli)) {
    // Get environment variables or fallback to provided TiDB credentials
    $host = getenv('DB_HOST') ?: 'gateway01.eu-central-1.prod.aws.tidbcloud.com';
    $user = getenv('DB_USER') ?: '2BkB857kEgJJLLq.root';
    $pass = getenv('DB_PASS') ?: 'OgauaDn0XUAZRuQ0';
    $dbname = getenv('DB_NAME') ?: 'test';
    $port = getenv('DB_PORT') ?: 4000;

    // Initialize MySQLi
    $conn = mysqli_init();

    // TiDB requires SSL/TLS.
    mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL);

    // Connect directly to the database
    if (!@mysqli_real_connect($conn, $host, $user, $pass, $dbname, $port, NULL, MYSQLI_CLIENT_SSL)) {
        die("Connection failed: " . mysqli_connect_error());
    }
}

// Create tables...
// (We skip CREATE DATABASE as we are already connected to 'test')

// Users Table
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL, -- Will store the email address
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'teacher') NOT NULL,
    full_name VARCHAR(100),
    staff_id VARCHAR(50) UNIQUE -- New column for Staff ID
)";
$conn->query($sql);

// Add assigned_classes column if it doesn't exist (for Teachers)
$result = $conn->query("SHOW COLUMNS FROM users LIKE 'assigned_classes'");
if ($result->num_rows == 0) {
    $conn->query("ALTER TABLE users ADD COLUMN assigned_classes TEXT AFTER staff_id");
}

// Add assigned_subjects column if it doesn't exist (for Teachers)
$result = $conn->query("SHOW COLUMNS FROM users LIKE 'assigned_subjects'");
if ($result->num_rows == 0) {
    $conn->query("ALTER TABLE users ADD COLUMN assigned_subjects TEXT AFTER assigned_classes");
}

// Add staff_id column if it doesn't exist (for existing DBs)
$result = $conn->query("SHOW COLUMNS FROM users LIKE 'staff_id'");
if ($result->num_rows == 0) {
    $conn->query("ALTER TABLE users ADD COLUMN staff_id VARCHAR(50) UNIQUE AFTER full_name");
}

// Modify username column to be longer if needed
// This might fail if the column is already unique, so we wrap it in a try-catch block
try {
    @$conn->query("ALTER TABLE users MODIFY COLUMN username VARCHAR(100) NOT NULL");
    
    // Check if unique index exists
    $idx = $conn->query("SHOW INDEX FROM users WHERE Key_name = 'username'");
    if ($idx->num_rows == 0) {
        @$conn->query("ALTER TABLE users ADD UNIQUE (username)");
    }
} catch (Exception $e) {
    // Ignore error if column is already modified/unique
}

// Students Table
$sql = "CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admission_no VARCHAR(20) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    class VARCHAR(20) NOT NULL,
    password VARCHAR(255), -- For Student Portal Login
    parent_email VARCHAR(100),
    parent_phone VARCHAR(20),
    dob DATE,
    photo VARCHAR(255),
    gender VARCHAR(10)
)";
$conn->query($sql);

// Update Students Table for existing records
$cols = $conn->query("SHOW COLUMNS FROM students");
$existing_cols = [];
while ($row = $cols->fetch_assoc()) {
    $existing_cols[] = $row['Field'];
}

if (!in_array('password', $existing_cols)) $conn->query("ALTER TABLE students ADD COLUMN password VARCHAR(255)");
if (!in_array('parent_email', $existing_cols)) $conn->query("ALTER TABLE students ADD COLUMN parent_email VARCHAR(100)");
if (!in_array('parent_phone', $existing_cols)) $conn->query("ALTER TABLE students ADD COLUMN parent_phone VARCHAR(20)");
if (!in_array('dob', $existing_cols)) $conn->query("ALTER TABLE students ADD COLUMN dob DATE");
if (!in_array('photo', $existing_cols)) $conn->query("ALTER TABLE students ADD COLUMN photo VARCHAR(255)");
if (!in_array('gender', $existing_cols)) $conn->query("ALTER TABLE students ADD COLUMN gender VARCHAR(10)");

// Results Table
$sql = "CREATE TABLE IF NOT EXISTS results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT,
    subject VARCHAR(50),
    ca_score INT DEFAULT 0,
    exam_score INT DEFAULT 0,
    score INT,
    term VARCHAR(20),
    session VARCHAR(20),
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
)";
$conn->query($sql);

// Add CA and Exam Score columns if they don't exist
$result = $conn->query("SHOW COLUMNS FROM results LIKE 'ca_score'");
if ($result->num_rows == 0) {
    $conn->query("ALTER TABLE results ADD COLUMN ca_score INT DEFAULT 0 AFTER subject");
    $conn->query("ALTER TABLE results ADD COLUMN exam_score INT DEFAULT 0 AFTER ca_score");
}


// CBT Exams Table
$sql = "CREATE TABLE IF NOT EXISTS cbt_exams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    class VARCHAR(20) NOT NULL,
    duration_minutes INT NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'inactive'
)";
$conn->query($sql);

// CBT Questions Table
$sql = "CREATE TABLE IF NOT EXISTS cbt_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    exam_id INT,
    question_text TEXT NOT NULL,
    option_a VARCHAR(255) NOT NULL,
    option_b VARCHAR(255) NOT NULL,
    option_c VARCHAR(255) NOT NULL,
    option_d VARCHAR(255) NOT NULL,
    correct_option ENUM('A', 'B', 'C', 'D') NOT NULL,
    FOREIGN KEY (exam_id) REFERENCES cbt_exams(id) ON DELETE CASCADE
)";
$conn->query($sql);

// CBT Results Table
$sql = "CREATE TABLE IF NOT EXISTS cbt_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT,
    exam_id INT,
    score INT NOT NULL,
    total_questions INT NOT NULL,
    date_taken TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (exam_id) REFERENCES cbt_exams(id) ON DELETE CASCADE
)";
$conn->query($sql);

// Attendance Table
$sql = "CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT,
    date DATE,
    status ENUM('present', 'absent'),
    teacher_id INT,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
)";
$conn->query($sql);

// Transcripts Table
$sql = "CREATE TABLE IF NOT EXISTS transcripts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT,
    file_path VARCHAR(255),
    uploaded_by INT,
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
)";
$conn->query($sql);

// Timetables Table
$sql = "CREATE TABLE IF NOT EXISTS timetables (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100),
    file_path VARCHAR(255),
    type ENUM('test', 'exam', 'class') DEFAULT 'class',
    class VARCHAR(20),
    session VARCHAR(20),
    term VARCHAR(20),
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$conn->query($sql);

// Create default admin if not exists
$admin_user = 'admin@topazschoolminna.com';
$check_admin = $conn->query("SELECT * FROM users WHERE username='$admin_user'");
if ($check_admin->num_rows == 0) {
    $admin_pass = password_hash('admin123', PASSWORD_DEFAULT);
    $conn->query("INSERT INTO users (username, password, role, full_name) VALUES ('$admin_user', '$admin_pass', 'admin', 'System Administrator')");
}

// Create default teacher if not exists
$teacher_user = 'teacher@topazschoolminna.com';
$check_teacher = $conn->query("SELECT * FROM users WHERE username='$teacher_user'");
if ($check_teacher->num_rows == 0) {
    $teacher_pass = password_hash('teacher123', PASSWORD_DEFAULT);
    $conn->query("INSERT INTO users (username, password, role, full_name) VALUES ('$teacher_user', '$teacher_pass', 'teacher', 'Default Teacher')");
}

// Sessions Table (For Vercel Persistence)
$sql = "CREATE TABLE IF NOT EXISTS sessions (
    id VARCHAR(128) NOT NULL PRIMARY KEY,
    data TEXT,
    timestamp INT(10) UNSIGNED
)";
$conn->query($sql);

// Applications Table (Online Admission)
$sql = "CREATE TABLE IF NOT EXISTS applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    child_name VARCHAR(100) NOT NULL,
    dob DATE,
    gender ENUM('Male', 'Female'),
    parent_name VARCHAR(100) NOT NULL,
    parent_email VARCHAR(100),
    parent_phone VARCHAR(20) NOT NULL,
    address TEXT,
    previous_school VARCHAR(100),
    class_applied VARCHAR(20) NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    date_applied TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$conn->query($sql);


// Fee Structure Table
$sql = "CREATE TABLE IF NOT EXISTS fee_structure (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class VARCHAR(20) NOT NULL,
    term VARCHAR(20) NOT NULL,
    title VARCHAR(100) NOT NULL,
    amount DECIMAL(10, 2) NOT NULL
)";
$conn->query($sql);

// Populate Default Fees if empty
$check_fees = $conn->query("SELECT * FROM fee_structure");
if ($check_fees->num_rows == 0) {
    $classes = [
        'Playgroup', 'Nursery 1', 'Nursery 2', 
        'Primary 1', 'Primary 2', 'Primary 3', 'Primary 4', 'Primary 5', 'Primary 6',
        'JSS 1', 'JSS 2', 'JSS 3', 
        'SSS 1', 'SSS 2', 'SSS 3'
    ];
    
    $stmt = $conn->prepare("INSERT INTO fee_structure (class, term, title, amount) VALUES (?, 'First Term', 'School Fees', 50000.00)");
    
    foreach ($classes as $class) {
        $stmt->bind_param("s", $class);
        $stmt->execute();
    }
}

// Payments Table
$sql = "CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    amount_paid DECIMAL(10, 2) NOT NULL,
    payment_date DATE NOT NULL,
    payment_method VARCHAR(50) DEFAULT 'Cash',
    term VARCHAR(20) NOT NULL,
    session VARCHAR(20) NOT NULL,
    receipt_no VARCHAR(50) UNIQUE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
)";
$conn->query($sql);

// --- SECURITY & NEW FEATURES TABLES ---

// 1. Update Students Table for Lockout
// Check and add failed_attempts
$result = $conn->query("SHOW COLUMNS FROM students LIKE 'failed_attempts'");
if ($result->num_rows == 0) {
    $conn->query("ALTER TABLE students ADD COLUMN failed_attempts INT DEFAULT 0");
}
// Check and add last_failed_login
$result = $conn->query("SHOW COLUMNS FROM students LIKE 'last_failed_login'");
if ($result->num_rows == 0) {
    $conn->query("ALTER TABLE students ADD COLUMN last_failed_login DATETIME NULL");
}

// 2. Create Activity Logs Table
$sql = "CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    user_type VARCHAR(20) NOT NULL,
    activity VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45),
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP
)";
$conn->query($sql);

// 3. Create Password Resets Table
$sql = "CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)";
$conn->query($sql);

// 4. Create Notifications Table
$sql = "CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    target_audience VARCHAR(50) DEFAULT 'all', -- 'student', 'teacher', 'all'
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)";
$conn->query($sql);

// 5. Scratch Card PINs Table
$sql = "CREATE TABLE IF NOT EXISTS pins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pin_code VARCHAR(20) UNIQUE NOT NULL,
    serial_number VARCHAR(20) UNIQUE NOT NULL,
    status ENUM('unused', 'used') DEFAULT 'unused',
    usage_count INT DEFAULT 0,
    max_usage INT DEFAULT 5,
    student_id INT DEFAULT NULL,
    generated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    used_at DATETIME DEFAULT NULL,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE SET NULL
)";
$conn->query($sql);

?>
