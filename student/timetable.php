<?php
include __DIR__ . '/../includes/db.php';
if (!isset($_SESSION['student_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../student_login.php");
    exit();
}

$student_id = $_SESSION['student_id'];
$student_class = $_SESSION['student_class'];

// Fetch Timetables
// Show timetables for 'All' classes OR the student's specific class
$query = "SELECT * FROM timetables WHERE class = 'All' OR class = ? ORDER BY uploaded_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $student_class);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Timetables | TISM Student Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/dark-mode.css">
    <style>
        body { background-color: #f8f9fa; }
        @media print {
            .sidebar, .topbar, .no-print { display: none !important; }
            .main-content { margin-left: 0 !important; width: 100% !important; padding: 0 !important; }
            .card { border: none !important; box-shadow: none !important; }
        }
    </style>
</head>
<body>

<!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="#" class="sidebar-brand">
                <i class="fas fa-user-graduate me-2"></i>TISM STUDENT
            </a>
            <button class="sidebar-close" id="sidebarClose"><i class="fas fa-times"></i></button>
        </div>
        <div class="sidebar-menu">
            <a href="index.php" class="menu-item">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="profile.php" class="menu-item">
                <i class="fas fa-user"></i> My Profile
            </a>
            <a href="results.php" class="menu-item">
                <i class="fas fa-chart-bar"></i> My Results
            </a>
            <a href="timetable.php" class="menu-item active">
                <i class="fas fa-calendar-alt"></i> Timetables
            </a>
            <a href="attendance.php" class="menu-item">
                <i class="fas fa-calendar-check"></i> Attendance
            </a>
            <a href="fees.php" class="menu-item">
                <i class="fas fa-money-bill-wave"></i> School Fees
            </a>
            <a href="cbt.php" class="menu-item">
                <i class="fas fa-laptop-code"></i> CBT Exams
            </a>
            <a href="notifications.php" class="menu-item">
                <i class="fas fa-bell"></i> Notifications
            </a>
            <a href="activity_log.php" class="menu-item">
                <i class="fas fa-history"></i> Activity Log
            </a>
            <a href="#" id="darkModeToggle" class="menu-item">
                <i class="fas fa-moon"></i> Dark Mode
            </a>
            <a href="../includes/logout.php" class="menu-item text-danger mt-3">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content" id="main-content">
        <!-- Topbar -->
        <div class="topbar">
            <button class="btn btn-light d-lg-none" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <h4 class="mb-0 fw-bold text-primary">Timetables</h4>
            <div class="user-profile">
                <div class="text-end d-none d-md-block">
                    <small class="text-muted d-block">Student Panel</small>
                </div>
                <div class="user-avatar bg-primary text-white">
                    <i class="fas fa-user"></i>
                </div>
            </div>
        </div>

        <div class="container-fluid">

        <h2 class="fw-bold mb-4">Exam & Test Timetables</h2>

        <div class="row">
            <?php if($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100 shadow-sm border-0">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h5 class="card-title fw-bold text-primary"><?php echo htmlspecialchars($row['title']); ?></h5>
                                    <span class="badge <?php echo ($row['type'] == 'exam') ? 'bg-danger' : 'bg-warning text-dark'; ?>">
                                        <?php echo ucfirst($row['type']); ?>
                                    </span>
                                </div>
                                <p class="text-muted small mb-2">
                                    <i class="fas fa-layer-group me-1"></i> <?php echo $row['class']; ?>
                                </p>
                                <p class="text-muted small mb-3">
                                    <i class="fas fa-clock me-1"></i> Posted: <?php echo date('M d, Y', strtotime($row['uploaded_at'])); ?>
                                </p>
                                <a href="../uploads/timetables/<?php echo $row['file_path']; ?>" target="_blank" class="btn btn-outline-primary w-100">
                                    <i class="fas fa-download me-2"></i> View / Download
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> No timetables have been uploaded for your class yet.
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/dashboard.js"></script>
<script src="../assets/js/dark-mode.js"></script>
</body>
</html>
