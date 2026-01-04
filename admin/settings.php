<?php
include __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if ($new_password !== $confirm_password) {
            $error = "New passwords do not match.";
        } else {
            // Verify current password
            $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows === 1) {
                $user_data = $result->fetch_assoc();
                if (password_verify($current_password, $user_data['password'])) {
                    // Update password
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $update->bind_param("si", $hashed_password, $user_id);
                    if ($update->execute()) {
                        $message = "Password changed successfully.";
                    } else {
                        $error = "Error updating password.";
                    }
                } else {
                    $error = "Incorrect current password.";
                }
            } else {
                $error = "User not found.";
            }
        }
    }
    
}

// Fetch User Details
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Load SMTP Config (Read-only)
$smtp_config = [
    'Host' => defined('SMTP_HOST') ? SMTP_HOST : 'Not Set',
    'Username' => defined('SMTP_USER') ? SMTP_USER : 'Not Set',
    'Port' => defined('SMTP_PORT') ? SMTP_PORT : 'Not Set',
    'From Email' => defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : 'Not Set',
    'From Name' => defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'Not Set',
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings | TISM Admin</title>
    <link rel="icon" href="../assets/images/logo.jpg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css?v=<?php echo time(); ?>">
    <style>
        .profile-img-large { width: 150px; height: 150px; object-fit: cover; border: 5px solid #fff; box-shadow: 0 0 15px rgba(0,0,0,0.1); }
    </style>
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
        <a href="manage_notifications.php" class="menu-item">
            <i class="fas fa-bell"></i> Notifications
        </a>
        <a href="send_email.php" class="menu-item">
            <i class="fas fa-envelope"></i> Send Email
        </a>
        <a href="manage_pins.php" class="menu-item">
            <i class="fas fa-key"></i> Scratch Cards
        </a>
        <a href="settings.php" class="menu-item active">
            <i class="fas fa-cog"></i> Settings
        </a>
        <a href="../includes/logout.php" class="menu-item mt-5 text-danger">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>

<!-- Main Content -->
<div class="main-content" id="main-content">
    <div class="topbar">
        <button class="btn btn-light d-lg-none" id="sidebarToggle"><i class="fas fa-bars"></i></button>
        <h4 class="mb-0 fw-bold text-primary">Settings</h4>
        <div class="user-profile">
            <div class="text-end d-none d-md-block">
                <small class="text-muted d-block">Admin Panel</small>
                <span class="fw-bold"><?php echo htmlspecialchars($user['full_name']); ?></span>
            </div>
            <div class="user-avatar bg-primary text-white">
                <?php if($user['photo']): ?>
                    <img src="../<?php echo $user['photo']; ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                <?php else: ?>
                    <i class="fas fa-user"></i>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <?php if($message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i> <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Profile Picture Section -->
            <div class="col-md-4 mb-4">
                <div class="card border-0 shadow-sm text-center p-4">
                    <h5 class="fw-bold mb-3">Profile Picture</h5>
                    <div class="mb-3 position-relative d-inline-block">
                        <?php if($user['photo']): ?>
                            <img src="../<?php echo $user['photo']; ?>" class="rounded-circle profile-img-large" alt="Admin Photo">
                        <?php else: ?>
                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user['full_name']); ?>&background=0D8ABC&color=fff&size=150" class="rounded-circle profile-img-large" alt="Admin Photo">
                        <?php endif; ?>
                        
                    </div>
                </div>
            </div>

            <!-- Change Password Section -->
            <div class="col-md-8 mb-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h5 class="fw-bold mb-0 text-primary">Change Password</h5>
                    </div>
                    <div class="card-body p-4">
                        <form method="POST">
                            <input type="hidden" name="change_password" value="1">
                            
                            <div class="mb-3">
                                <label class="form-label">Current Password</label>
                                <input type="password" name="current_password" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">New Password</label>
                                <input type="password" name="new_password" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Confirm New Password</label>
                                <input type="password" name="confirm_password" class="form-control" required>
                            </div>

                            <button type="submit" name="change_password" class="btn btn-primary w-100">Update Password</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- SMTP Settings (Read-only) -->
            <div class="col-md-6 mb-4">
                <div class="card p-4">
                    <h5 class="mb-4">SMTP Configuration (Read-Only)</h5>
                    <div class="mb-3">
                        <label class="form-label">SMTP Host</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($smtp_config['Host']); ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">SMTP Username</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($smtp_config['Username']); ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">SMTP Port</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($smtp_config['Port']); ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">From Email</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($smtp_config['From Email']); ?>" readonly>
                    </div>
                </div>
            </div>

            <!-- 2FA Settings (Removed) -->

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/dashboard.js"></script>
</body>
</html>
