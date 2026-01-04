<?php
include __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$message = '';
$error = '';

// Handle Create Notification
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_notification'])) {
    $title = $_POST['title'];
    $body = $_POST['message'];
    $audience = $_POST['target_audience'];

    if (empty($title) || empty($body)) {
        $error = "Title and Message are required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO notifications (title, message, target_audience) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $title, $body, $audience);
        
        if ($stmt->execute()) {
            $message = "Notification published successfully.";
        } else {
            $error = "Error publishing notification: " . $conn->error;
        }
    }
}

// Handle Delete Notification
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM notifications WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $message = "Notification deleted successfully.";
    } else {
        $error = "Error deleting notification.";
    }
}

// Fetch Notifications
$notifications = $conn->query("SELECT * FROM notifications ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Notifications | TISM Admin</title>
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
            <i class="fas fa-graduation-cap me-2"></i>TISM ADMIN
        </a>
        <button class="sidebar-close" id="sidebarClose"><i class="fas fa-times"></i></button>
    </div>
    <div class="sidebar-menu">
        <a href="index.php" class="menu-item">
            <i class="fas fa-th-large"></i> Dashboard
        </a>
        <a href="manage_students.php" class="menu-item">
            <i class="fas fa-user-graduate"></i> Manage Students
        </a>
        <a href="manage_teachers.php" class="menu-item">
            <i class="fas fa-chalkboard-teacher"></i> Teachers
        </a>
        <a href="manage_fees.php" class="menu-item">
            <i class="fas fa-money-bill-wave"></i> Fees & Payments
        </a>
        <a href="manage_results.php" class="menu-item">
            <i class="fas fa-chart-bar"></i> Results
        </a>
        <a href="manage_timetable.php" class="menu-item">
            <i class="fas fa-calendar-alt"></i> Timetables
        </a>
        <a href="manage_notifications.php" class="menu-item active">
            <i class="fas fa-bell"></i> Notifications
        </a>
        <a href="send_email.php" class="menu-item">
            <i class="fas fa-envelope"></i> Send Email
        </a>
        <a href="manage_pins.php" class="menu-item">
            <i class="fas fa-key"></i> Scratch Cards
        </a>
        <a href="settings.php" class="menu-item">
            <i class="fas fa-cog"></i> Settings
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
        <h4 class="mb-0 fw-bold text-primary">Notification Center</h4>
        <div class="user-profile">
            <div class="text-end d-none d-md-block">
                <small class="text-muted d-block">Welcome back,</small>
                <span class="fw-bold"><?php echo $_SESSION['full_name']; ?></span>
            </div>
            <div class="user-avatar">
                <?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?>
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
        <!-- Create Notification Form -->
        <div class="col-lg-4 mb-4">
            <div class="dashboard-card">
                <div class="card-header-custom">
                    <span><i class="fas fa-edit me-2"></i>Compose Notification</span>
                </div>
                <div class="card-body p-4">
                    <form method="POST">
                        <input type="hidden" name="create_notification" value="1">
                        
                        <div class="mb-3">
                            <label class="form-label text-muted small">Title</label>
                            <input type="text" name="title" class="form-control" placeholder="e.g. Mid-Term Break Update" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-muted small">Message Body</label>
                            <textarea name="message" class="form-control" rows="6" placeholder="Type your announcement here..." required></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-muted small">Target Audience</label>
                            <select name="target_audience" class="form-select">
                                <option value="all">All Users (Students & Teachers)</option>
                                <option value="student">Students Only</option>
                                <option value="teacher">Teachers Only</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-paper-plane me-2"></i> Publish Notification
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Notification History -->
        <div class="col-lg-8">
            <div class="dashboard-card">
                <div class="card-header-custom">
                    <span><i class="fas fa-history me-2"></i>Published Notifications</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Date</th>
                                    <th>Title</th>
                                    <th>Audience</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($notifications->num_rows > 0): ?>
                                    <?php while ($row = $notifications->fetch_assoc()): ?>
                                        <tr>
                                            <td class="ps-4 text-muted small">
                                                <?php echo date('M d, Y', strtotime($row['created_at'])); ?>
                                            </td>
                                            <td>
                                                <div class="fw-bold"><?php echo htmlspecialchars($row['title']); ?></div>
                                                <div class="small text-muted text-truncate" style="max-width: 250px;">
                                                    <?php echo htmlspecialchars($row['message']); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php 
                                                $badge = 'secondary';
                                                if($row['target_audience'] == 'student') $badge = 'info';
                                                if($row['target_audience'] == 'teacher') $badge = 'warning';
                                                if($row['target_audience'] == 'all') $badge = 'success';
                                                ?>
                                                <span class="badge bg-<?php echo $badge; ?> bg-opacity-10 text-<?php echo $badge; ?> text-uppercase">
                                                    <?php echo $row['target_audience']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this notification?');">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-5 text-muted">
                                            <i class="fas fa-bell-slash fa-2x mb-3 d-block"></i>
                                            No notifications published yet.
                                        </td>
                                    </tr>
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
