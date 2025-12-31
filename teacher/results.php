<?php
include __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../login.php");
    exit();
}

$teacher_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Fetch Teacher's Assigned Classes
$stmt = $conn->prepare("SELECT assigned_classes, photo FROM users WHERE id = ?");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();
$teacher_photo = $user_data['photo'];
$assigned_classes_str = $user_data['assigned_classes'] ?? '';
$assigned_classes = array_filter(explode(',', $assigned_classes_str));

// Escape classes for SQL IN clause
$escaped_classes = array_map(function($class) use ($conn) {
    return "'" . $conn->real_escape_string(trim($class)) . "'";
}, $assigned_classes);
$classes_sql_list = implode(',', $escaped_classes);

// Handle Result Upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_result'])) {
    $student_id = $_POST['student_id'];
    $subject = $_POST['subject'];
    $score = $_POST['score'];
    $term = $_POST['term'];
    $session = $_POST['session'];

    // Basic Validation
    if (empty($student_id) || empty($subject) || empty($score)) {
        $error = "All fields are required.";
    } else {
        // Check if result already exists to avoid duplicates (optional, but good)
        $check = $conn->prepare("SELECT id FROM results WHERE student_id = ? AND subject = ? AND term = ? AND session = ?");
        $check->bind_param("isss", $student_id, $subject, $term, $session);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            // Update existing
            $update = $conn->prepare("UPDATE results SET score = ? WHERE student_id = ? AND subject = ? AND term = ? AND session = ?");
            $update->bind_param("iisss", $score, $student_id, $subject, $term, $session);
            if ($update->execute()) {
                $message = "Result updated successfully.";
            } else {
                $error = "Failed to update result.";
            }
        } else {
            // Insert new
            $insert = $conn->prepare("INSERT INTO results (student_id, subject, score, term, session) VALUES (?, ?, ?, ?, ?)");
            $insert->bind_param("isiss", $student_id, $subject, $score, $term, $session);
            if ($insert->execute()) {
                $message = "Result added successfully.";
            } else {
                $error = "Failed to add result.";
            }
        }
    }
}

// Fetch Students for Dropdown
$students = [];
if (!empty($classes_sql_list)) {
    $sql = "SELECT id, full_name, class, admission_no FROM students WHERE class IN ($classes_sql_list) ORDER BY class, full_name";
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $students[] = $row;
        }
    }
}

// Fetch Recent Results (for display)
// Note: 'results' table doesn't have 'uploaded_by' or 'teacher_id', so we can only show results for students in teacher's classes
$recent_results = [];
if (!empty($classes_sql_list)) {
    $sql_recent = "SELECT r.*, s.full_name, s.class, s.admission_no 
                   FROM results r 
                   JOIN students s ON r.student_id = s.id 
                   WHERE s.class IN ($classes_sql_list) 
                   ORDER BY r.id DESC LIMIT 20";
    $res_recent = $conn->query($sql_recent);
    if ($res_recent) {
        while ($row = $res_recent->fetch_assoc()) {
            $recent_results[] = $row;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Results | TISM Teacher</title>
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
        <a href="results.php" class="menu-item active">
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
        <button class="btn btn-light d-lg-none" id="sidebarToggle"><i class="fas fa-bars"></i></button>
        <h4 class="mb-0 fw-bold text-primary">Manage Results</h4>
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

    <div class="row">
        <!-- Upload Form -->
        <div class="col-lg-4 mb-4">
            <div class="dashboard-card h-100">
                <div class="card-header-custom">
                    <span><i class="fas fa-plus-circle me-2"></i>Add Result</span>
                </div>
                <div class="card-body p-4">
                    <form method="POST">
                        <input type="hidden" name="add_result" value="1">
                        
                        <div class="mb-3">
                            <label class="form-label text-muted small">Student</label>
                            <select name="student_id" class="form-select" required>
                                <option value="">Select Student</option>
                                <?php foreach ($students as $student): ?>
                                    <option value="<?php echo $student['id']; ?>">
                                        <?php echo htmlspecialchars($student['full_name'] . ' (' . $student['class'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-muted small">Subject</label>
                            <select name="subject" class="form-select" required>
                                <option value="">Select Subject</option>
                                <option value="Mathematics">Mathematics</option>
                                <option value="English Language">English Language</option>
                                <option value="Basic Science">Basic Science</option>
                                <option value="Civic Education">Civic Education</option>
                                <option value="Social Studies">Social Studies</option>
                                <option value="Computer Studies">Computer Studies</option>
                                <option value="Agricultural Science">Agricultural Science</option>
                                <option value="Home Economics">Home Economics</option>
                                <option value="Physics">Physics</option>
                                <option value="Chemistry">Chemistry</option>
                                <option value="Biology">Biology</option>
                                <option value="Economics">Economics</option>
                                <option value="Government">Government</option>
                                <option value="Literature">Literature</option>
                                <option value="Geography">Geography</option>
                                <option value="Commerce">Commerce</option>
                                <option value="Accounting">Accounting</option>
                                <option value="CRS">CRS</option>
                                <option value="IRS">IRS</option>
                                <!-- Add more subjects as needed -->
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-muted small">Score (0-100)</label>
                            <input type="number" name="score" class="form-control" min="0" max="100" required>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted small">Term</label>
                                <select name="term" class="form-select" required>
                                    <option value="First Term">First Term</option>
                                    <option value="Second Term">Second Term</option>
                                    <option value="Third Term">Third Term</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted small">Session</label>
                                <input type="text" name="session" class="form-control" value="<?php echo date('Y') . '/' . (date('Y')+1); ?>" required placeholder="2024/2025">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-save me-2"></i> Save Result
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Recent Results List -->
        <div class="col-lg-8 mb-4">
            <div class="dashboard-card h-100">
                <div class="card-header-custom">
                    <span><i class="fas fa-list me-2"></i>Recent Results (My Classes)</span>
                </div>
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Student</th>
                                    <th>Subject</th>
                                    <th>Score</th>
                                    <th>Grade</th>
                                    <th>Term</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recent_results)): ?>
                                    <tr><td colspan="5" class="text-center text-muted">No results found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($recent_results as $res): 
                                        $s = $res['score'];
                                        $grade = ($s >= 70) ? 'A' : (($s >= 60) ? 'B' : (($s >= 50) ? 'C' : (($s >= 45) ? 'D' : 'F')));
                                        $badge_class = ($grade == 'A') ? 'bg-success' : (($grade == 'F') ? 'bg-danger' : 'bg-secondary');
                                    ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold"><?php echo htmlspecialchars($res['full_name']); ?></div>
                                                <small class="text-muted"><?php echo htmlspecialchars($res['class']); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($res['subject']); ?></td>
                                            <td class="fw-bold"><?php echo $s; ?></td>
                                            <td><span class="badge <?php echo $badge_class; ?>"><?php echo $grade; ?></span></td>
                                            <td><small><?php echo htmlspecialchars($res['term'] . ' ' . $res['session']); ?></small></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/dashboard.js?v=<?php echo time(); ?>"></script>
</body>
</html>
