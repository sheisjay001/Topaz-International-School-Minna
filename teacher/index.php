<?php
include __DIR__ . '/../includes/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../login.php");
    exit();
}

$message = '';
$error = '';
$teacher_id = $_SESSION['user_id'];

// Fetch Teacher's Assigned Classes
$stmt = $conn->prepare("SELECT assigned_classes, photo FROM users WHERE id = ?");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();
$teacher_photo = $user_data['photo'];
$assigned_classes_str = $user_data['assigned_classes'] ?? '';
$assigned_classes = array_filter(explode(',', $assigned_classes_str)); // Remove empty entries

// Escape classes for SQL IN clause
$escaped_classes = array_map(function($class) use ($conn) {
    return "'" . $conn->real_escape_string(trim($class)) . "'";
}, $assigned_classes);
$classes_sql_list = implode(',', $escaped_classes);

// Fetch Stats
$total_my_students = 0;
if (!empty($classes_sql_list)) {
    $total_my_students = $conn->query("SELECT COUNT(*) as count FROM students WHERE class IN ($classes_sql_list)")->fetch_assoc()['count'];
}

$attendance_today = $conn->query("SELECT COUNT(*) as count FROM attendance WHERE teacher_id = '$teacher_id' AND date = CURDATE()")->fetch_assoc()['count'];

// Handle Attendance
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['mark_attendance'])) {
    $date = $_POST['date'];
    $student_id = $_POST['student_id'];
    $status = $_POST['status'];
    
    // Check if student belongs to assigned classes (security check)
    // Optional but good practice. For now trusting the dropdown logic.

    $stmt = $conn->prepare("INSERT INTO attendance (student_id, date, status, teacher_id) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("issi", $student_id, $date, $status, $teacher_id);
    if ($stmt->execute()) {
        $message = "Attendance marked successfully.";
        // Refresh stats
        $attendance_today = $conn->query("SELECT COUNT(*) as count FROM attendance WHERE teacher_id = '$teacher_id' AND date = CURDATE()")->fetch_assoc()['count'];
    } else {
        $error = "Error marking attendance.";
    }
}

// Handle Transcript Upload
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['upload_transcript'])) {
    $student_id = $_POST['student_id'];
    
    if (isset($_FILES['transcript_file']) && $_FILES['transcript_file']['error'] == 0) {
        $upload_dir = '../uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $file_name = time() . '_' . basename($_FILES['transcript_file']['name']);
        $target_file = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['transcript_file']['tmp_name'], $target_file)) {
            $stmt = $conn->prepare("INSERT INTO transcripts (student_id, file_path, uploaded_by) VALUES (?, ?, ?)");
            $stmt->bind_param("isi", $student_id, $file_name, $teacher_id);
            if ($stmt->execute()) {
                $message = "Transcript uploaded successfully.";
            } else {
                $error = "Database error.";
            }
        } else {
            $error = "Failed to move uploaded file.";
        }
    } else {
        $error = "No file uploaded.";
    }
}

// Fetch Students for Dropdowns (Filtered by assigned classes)
$students_query = "SELECT * FROM students";
if (!empty($classes_sql_list)) {
    $students_query .= " WHERE class IN ($classes_sql_list)";
} else {
    $students_query .= " WHERE 1=0"; // Return no students if no classes assigned
}
$students_query .= " ORDER BY class, full_name";
$students = $conn->query($students_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard | TISM</title>
    <link rel="icon" href="../assets/images/logo.jpg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css?v=<?php echo time(); ?>">
</head>
<body>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="#" class="sidebar-brand">
            <i class="fas fa-chalkboard-teacher me-2"></i>TISM TEACHER
        </a>
        <button class="sidebar-close" id="sidebarClose"><i class="fas fa-times"></i></button>
    </div>
    <div class="sidebar-menu">
        <a href="index.php" class="menu-item active">
            <i class="fas fa-th-large"></i> Dashboard
        </a>
        <a href="my_students.php" class="menu-item">
            <i class="fas fa-user-graduate"></i> My Students
        </a>
        <a href="attendance.php" class="menu-item">
            <i class="fas fa-clipboard-check"></i> Attendance
        </a>
        <a href="results.php" class="menu-item">
            <i class="fas fa-file-alt"></i> Results
        </a>
        <a href="upload_results.php" class="menu-item">
            <i class="fas fa-file-csv"></i> Upload Results (CSV)
        </a>
        <a href="profile.php" class="menu-item">
            <i class="fas fa-user-cog"></i> Profile & Settings
        </a>
        <a href="../includes/logout.php" class="menu-item mt-5 text-danger">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>

<!-- Main Content -->
<div class="main-content" id="main-content">
    <!-- Topbar -->
    <div class="topbar">
        <button class="btn btn-light d-lg-none" id="sidebarToggle" type="button"><i class="fas fa-bars"></i></button>
        <h4 class="mb-0 fw-bold text-primary">Teacher Dashboard</h4>
        <div class="user-profile">
            <div class="text-end d-none d-md-block">
                <small class="text-muted d-block">Welcome,</small>
                <span class="fw-bold"><?php echo $_SESSION['full_name']; ?></span>
            </div>
            <div class="user-avatar bg-warning text-dark">
                <?php if($teacher_photo): ?>
                    <img src="../<?php echo $teacher_photo; ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                <?php else: ?>
                    <?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Alerts -->
    <?php if($message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Stats Widgets -->
    <div class="row mb-4">
        <div class="col-md-6 col-lg-4">
            <div class="dashboard-card stat-card">
                <div class="stat-info">
                    <h3><?php echo $total_my_students; ?></h3>
                    <p>My Students</p>
                </div>
                <div class="stat-icon bg-info bg-opacity-10 text-info">
                    <i class="fas fa-user-graduate"></i>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-4">
            <div class="dashboard-card stat-card">
                <div class="stat-info">
                    <h3><?php echo count($assigned_classes); ?></h3>
                    <p>Assigned Classes</p>
                </div>
                <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                    <i class="fas fa-chalkboard"></i>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-4">
            <div class="dashboard-card stat-card">
                <div class="stat-info">
                    <h3><?php echo $attendance_today; ?></h3>
                    <p>Attendance Today</p>
                </div>
                <div class="stat-icon bg-success bg-opacity-10 text-success">
                    <i class="fas fa-check-double"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Attendance -->
        <div class="col-lg-6 mb-4">
            <div class="dashboard-card h-100">
                <div class="card-header-custom">
                    <span><i class="fas fa-calendar-check me-2"></i>Mark Attendance</span>
                </div>
                <div class="card-body p-4">
                    <form method="POST">
                        <input type="hidden" name="mark_attendance" value="1">
                        <div class="mb-3">
                            <label class="form-label text-muted small">Date</label>
                            <input type="date" name="date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small">Student</label>
                            <select name="student_id" class="form-select" required>
                                <?php 
                                if ($students && $students->num_rows > 0) {
                                    $students->data_seek(0);
                                    while($row = $students->fetch_assoc()): ?>
                                        <option value="<?php echo $row['id']; ?>"><?php echo $row['full_name'] . " (" . $row['class'] . ")"; ?></option>
                                    <?php endwhile; 
                                } else {
                                    echo '<option value="">No students found in your assigned classes.</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="form-label text-muted small">Status</label>
                            <div class="d-flex gap-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="status" value="present" id="statusPresent" checked>
                                    <label class="form-check-label" for="statusPresent">Present</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="status" value="absent" id="statusAbsent">
                                    <label class="form-check-label" for="statusAbsent">Absent</label>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-save me-2"></i> Mark Attendance
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Transcript Upload -->
        <div class="col-lg-6 mb-4">
            <div class="dashboard-card h-100">
                <div class="card-header-custom">
                    <span><i class="fas fa-file-upload me-2"></i>Upload Transcript</span>
                </div>
                <div class="card-body p-4">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="upload_transcript" value="1">
                        <div class="mb-3">
                            <label class="form-label text-muted small">Student</label>
                            <select name="student_id" class="form-select" required>
                                <?php 
                                if ($students && $students->num_rows > 0) {
                                    $students->data_seek(0);
                                    while($row = $students->fetch_assoc()): ?>
                                        <option value="<?php echo $row['id']; ?>"><?php echo $row['full_name'] . " (" . $row['class'] . ")"; ?></option>
                                    <?php endwhile; 
                                } else {
                                    echo '<option value="">No students found in your assigned classes.</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="form-label text-muted small">Transcript File (PDF/Image)</label>
                            <input type="file" name="transcript_file" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-success w-100">
                            <i class="fas fa-upload me-2"></i> Upload Transcript
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/dashboard.js?v=<?php echo time(); ?>"></script>
</body>
</html>
