<?php
include __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['student_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../student_login.php");
    exit();
}

// Ensure table exists (Graceful fallback)
$conn->query("CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    target_audience VARCHAR(50) DEFAULT 'all',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// Insert dummy notification if empty
$check = $conn->query("SELECT * FROM notifications");
if ($check->num_rows == 0) {
    $conn->query("INSERT INTO notifications (title, message, target_audience) VALUES 
    ('Welcome to the New Portal', 'We are excited to launch our new student portal. Please update your profile.', 'all'),
    ('School Fees Deadline', 'Please note that the deadline for school fees payment is next Friday.', 'student')");
}

// Fetch Notifications
// Logic: Get 'all' OR 'student' target
$query = "SELECT * FROM notifications WHERE target_audience IN ('all', 'student') ORDER BY created_at DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications | TISM Student Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/dark-mode.css">
    <style>
        body { background-color: #f8f9fa; }
        .notification-card { transition: transform 0.2s; border-left: 4px solid #003366; }
        .notification-card:hover { transform: translateY(-2px); box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important; }
        .notification-icon { width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; background: #e9ecef; border-radius: 50%; color: #003366; }
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
        <a href="cbt.php" class="menu-item">
            <i class="fas fa-laptop-code"></i> CBT Exams
        </a>
        <a href="notifications.php" class="menu-item active">
            <i class="fas fa-bell"></i> Notifications
        </a>
        <a href="activity_log.php" class="menu-item">
            <i class="fas fa-history"></i> Activity Log
        </a>
        <a href="#" id="darkModeToggle" class="menu-item">
            <i class="fas fa-moon"></i> Dark Mode
        </a>
        <a href="../includes/logout.php?type=student" class="menu-item text-danger mt-3">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>

<!-- Main Content -->
<div class="main-content" id="main-content">
    <!-- Topbar -->
    <div class="topbar">
        <button class="btn btn-light d-lg-none" id="sidebarToggle"><i class="fas fa-bars"></i></button>
        <h4 class="mb-0 fw-bold text-primary">Notifications</h4>
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

        <h2 class="fw-bold mb-4">Notifications & Announcements</h2>

        <div class="row">
            <div class="col-lg-8">
                <?php if($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                    <div class="card shadow-sm mb-3 notification-card">
                        <div class="card-body d-flex align-items-start">
                            <div class="notification-icon me-3 flex-shrink-0">
                                <i class="fas fa-bullhorn fa-lg"></i>
                            </div>
                            <div>
                                <h5 class="card-title fw-bold mb-1"><?php echo htmlspecialchars($row['title']); ?></h5>
                                <small class="text-muted d-block mb-2">
                                    <i class="far fa-clock me-1"></i> <?php echo date('F d, Y h:i A', strtotime($row['created_at'])); ?>
                                </small>
                                <p class="card-text text-secondary"><?php echo nl2br(htmlspecialchars($row['message'])); ?></p>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> No new notifications at the moment.
                    </div>
                <?php endif; ?>
            </div>

            <div class="col-lg-4">
                <div class="card shadow-sm border-0 bg-primary text-white">
                    <div class="card-body">
                        <h5 class="fw-bold"><i class="fas fa-info-circle me-2"></i> Information</h5>
                        <p class="small">Important announcements from the school administration will appear here. Check back regularly for updates on exams, holidays, and events.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>