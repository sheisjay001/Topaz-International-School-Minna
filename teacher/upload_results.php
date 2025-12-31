<?php
include __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../login.php");
    exit();
}

$message = '';
$error = '';
$teacher_id = $_SESSION['user_id'];

// Fetch Teacher's Photo
$stmt = $conn->prepare("SELECT photo FROM users WHERE id = ?");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$teacher_photo = $stmt->get_result()->fetch_assoc()['photo'] ?? null;

// Handle CSV Upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_results'])) {
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == 0) {
        $file = $_FILES['csv_file']['tmp_name'];
        $subject = $_POST['subject'];
        $term = $_POST['term'];
        $session = $_POST['session'];
        
        $handle = fopen($file, "r");
        $row = 0;
        $success_count = 0;
        $fail_count = 0;

        // Skip header if exists (optional, logic below assumes header if first row is non-numeric)
        // Better: user assumes standard format. Let's assume Header exists.
        fgetcsv($handle); // Skip first line

        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            // Expected Format: Admission No, CA Score, Exam Score
            $admission_no = trim($data[0]);
            $ca_score = intval($data[1]);
            $exam_score = intval($data[2]);
            $total_score = $ca_score + $exam_score;

            if (empty($admission_no)) continue;

            // Find Student ID
            $stmt = $conn->prepare("SELECT id FROM students WHERE admission_no = ?");
            $stmt->bind_param("s", $admission_no);
            $stmt->execute();
            $res = $stmt->get_result();

            if ($res->num_rows > 0) {
                $student_id = $res->fetch_assoc()['id'];

                // Check if result exists
                $check = $conn->prepare("SELECT id FROM results WHERE student_id = ? AND subject = ? AND term = ? AND session = ?");
                $check->bind_param("isss", $student_id, $subject, $term, $session);
                $check->execute();
                
                if ($check->get_result()->num_rows > 0) {
                    // Update
                    $update = $conn->prepare("UPDATE results SET ca_score = ?, exam_score = ?, score = ? WHERE student_id = ? AND subject = ? AND term = ? AND session = ?");
                    $update->bind_param("iiiisss", $ca_score, $exam_score, $total_score, $student_id, $subject, $term, $session);
                    $update->execute();
                } else {
                    // Insert
                    $insert = $conn->prepare("INSERT INTO results (student_id, subject, ca_score, exam_score, score, term, session) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $insert->bind_param("isiiiss", $student_id, $subject, $ca_score, $exam_score, $total_score, $term, $session);
                    $insert->execute();
                }
                $success_count++;
            } else {
                $fail_count++;
            }
        }
        fclose($handle);
        $message = "Results uploaded successfully! ($success_count records updated, $fail_count failed/not found)";
    } else {
        $error = "Please upload a valid CSV file.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Results | TISM Teacher</title>
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
        <a href="index.php" class="menu-item">
            <i class="fas fa-th-large"></i> Dashboard
        </a>
        <a href="my_students.php" class="menu-item">
            <i class="fas fa-user-graduate"></i> My Students
        </a>
        <a href="attendance.php" class="menu-item">
            <i class="fas fa-clipboard-check"></i> Attendance
        </a>
        <a href="upload_results.php" class="menu-item active">
            <i class="fas fa-file-upload"></i> Upload Results
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
        <button class="btn btn-light d-lg-none" id="sidebarToggle"><i class="fas fa-bars"></i></button>
        <h4 class="mb-0 fw-bold text-primary">Upload Results (CSV)</h4>
        <div class="user-profile">
            <div class="text-end d-none d-md-block">
                <small class="text-muted d-block">Welcome back,</small>
                <span class="fw-bold"><?php echo $_SESSION['full_name']; ?></span>
            </div>
            <div class="user-avatar">
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

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="dashboard-card">
                <div class="card-header-custom">
                    <span><i class="fas fa-cloud-upload-alt me-2"></i>Upload Student Results</span>
                </div>
                <div class="card-body p-4">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="upload_results" value="1">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted small">Subject</label>
                                <select name="subject" class="form-select" required>
                                    <option value="">Select Subject</option>
                                    <option>Mathematics</option>
                                    <option>English Language</option>
                                    <option>Basic Science</option>
                                    <option>Social Studies</option>
                                    <option>Civic Education</option>
                                    <option>Agricultural Science</option>
                                    <option>Home Economics</option>
                                    <option>Computer Studies</option>
                                    <!-- Add more -->
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted small">Session</label>
                                <input type="text" name="session" class="form-control" value="2024/2025" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-muted small">Term</label>
                            <select name="term" class="form-select" required>
                                <option>First Term</option>
                                <option>Second Term</option>
                                <option>Third Term</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label text-muted small">CSV File</label>
                            <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                            <div class="form-text mt-2">
                                <i class="fas fa-info-circle me-1"></i> Format: <strong>Admission No, CA Score, Exam Score</strong> (No header needed, or 1 header row)
                            </div>
                        </div>

                        <div class="alert alert-info small">
                            <strong>Sample CSV Content:</strong><br>
                            TISM/2024/001, 30, 60<br>
                            TISM/2024/002, 25, 55
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2">
                            <i class="fas fa-upload me-2"></i> Upload Results
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
