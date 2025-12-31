<?php
include __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['student_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../student_login.php");
    exit();
}

$student_class = $_SESSION['student_class'];

// Fetch Active Exams for Student's Class
$query = "SELECT * FROM cbt_exams WHERE class = ? AND status = 'active'";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $student_class);
$stmt->execute();
$exams = $stmt->get_result();

// Fetch CBT History
$history_query = "SELECT r.*, e.title, e.duration_minutes 
                  FROM cbt_results r 
                  JOIN cbt_exams e ON r.exam_id = e.id 
                  WHERE r.student_id = ? 
                  ORDER BY r.date_taken DESC";
$stmt_hist = $conn->prepare($history_query);
$stmt_hist->bind_param("i", $_SESSION['student_id']);
$stmt_hist->execute();
$history = $stmt_hist->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CBT Exams | TISM Student Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/dark-mode.css">
    <style>
        body { background-color: #f8f9fa; }
        @media print {
            .sidebar, .topbar, .no-print { display: none !important; }
            .main-content { margin-left: 0 !important; width: 100% !important; padding: 0 !important; }
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
        <a href="timetable.php" class="menu-item">
            <i class="fas fa-calendar-alt"></i> Timetables
        </a>
        <a href="attendance.php" class="menu-item">
            <i class="fas fa-calendar-check"></i> Attendance
        </a>
        <a href="fees.php" class="menu-item">
            <i class="fas fa-money-bill-wave"></i> School Fees
        </a>
        <a href="cbt.php" class="menu-item active">
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
        <h4 class="mb-0 fw-bold text-primary">CBT Exams</h4>
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
        <h2 class="fw-bold mb-4">Computer Based Tests</h2>

        <div class="row">
            <?php while($exam = $exams->fetch_assoc()): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body text-center">
                        <div class="mb-3 text-primary">
                            <i class="fas fa-file-alt fa-3x"></i>
                        </div>
                        <h5 class="card-title fw-bold"><?php echo htmlspecialchars($exam['title']); ?></h5>
                        <p class="card-text text-muted">Duration: <?php echo $exam['duration_minutes']; ?> mins</p>
                        <a href="#" class="btn btn-primary w-100 disabled">Start Exam (Coming Soon)</a>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>

            <?php if($exams->num_rows == 0): ?>
            <div class="col-12">
                <div class="alert alert-info">No active exams scheduled for your class at the moment.</div>
            </div>
            <?php endif; ?>
        </div>

        <!-- CBT History Section -->
        <h4 class="fw-bold mt-5 mb-3"><i class="fas fa-history me-2"></i> Exam History</h4>
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Date Taken</th>
                                <th>Exam Title</th>
                                <th>Score</th>
                                <th>Percentage</th>
                                <th>Performance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($history->num_rows > 0): ?>
                                <?php while($row = $history->fetch_assoc()): 
                                    $percentage = ($row['score'] / $row['total_questions']) * 100;
                                    $badge_class = $percentage >= 70 ? 'bg-success' : ($percentage >= 50 ? 'bg-warning' : 'bg-danger');
                                    $performance = $percentage >= 70 ? 'Excellent' : ($percentage >= 50 ? 'Good' : 'Needs Improvement');
                                ?>
                                <tr>
                                    <td><?php echo date('M d, Y h:i A', strtotime($row['date_taken'])); ?></td>
                                    <td><?php echo htmlspecialchars($row['title']); ?></td>
                                    <td class="fw-bold"><?php echo $row['score']; ?> / <?php echo $row['total_questions']; ?></td>
                                    <td><?php echo number_format($percentage, 1); ?>%</td>
                                    <td><span class="badge <?php echo $badge_class; ?>"><?php echo $performance; ?></span></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="text-center py-4 text-muted">No exam history found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/dashboard.js"></script>
<script src="../assets/js/dark-mode.js"></script>
</body>
</html>
