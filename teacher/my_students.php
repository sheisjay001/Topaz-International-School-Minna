<?php
include __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../login.php");
    exit();
}

$teacher_id = $_SESSION['user_id'];

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

// Fetch Students
$students = [];
if (!empty($classes_sql_list)) {
    $sql = "SELECT * FROM students WHERE class IN ($classes_sql_list) ORDER BY class, full_name";
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $students[] = $row;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Students | TISM Teacher</title>
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
    </div>
    <div class="sidebar-menu">
        <a href="index.php" class="menu-item">
            <i class="fas fa-th-large"></i> Dashboard
        </a>
        <a href="my_students.php" class="menu-item active">
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
        <button class="btn btn-light d-lg-none" id="sidebarToggle"><i class="fas fa-bars"></i></button>
        <h4 class="mb-0 fw-bold text-primary">My Students</h4>
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

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <?php if (empty($students)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i> No students found in your assigned classes (<?php echo htmlspecialchars(implode(', ', $assigned_classes)); ?>).
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Admission No</th>
                                <th>Full Name</th>
                                <th>Class</th>
                                <th>Gender</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($student['admission_no']); ?></span></td>
                                    <td class="fw-bold"><?php echo htmlspecialchars($student['full_name']); ?></td>
                                    <td><span class="badge bg-primary bg-opacity-10 text-primary"><?php echo htmlspecialchars($student['class']); ?></span></td>
                                    <td><?php echo htmlspecialchars($student['gender']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/dashboard.js?v=<?php echo time(); ?>"></script>
</body>
</html>
