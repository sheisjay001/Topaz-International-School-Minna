<?php
include __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $admission_no = $_POST['admission_no'];
    $full_name = $_POST['full_name'];
    $class = $_POST['class'];
    $gender = $_POST['gender'];
    $parent_email = $_POST['parent_email'];
    $parent_phone = $_POST['parent_phone'];
    $dob = $_POST['dob'];
    
    // Default password is '123456'
    $password = password_hash('123456', PASSWORD_DEFAULT);

    // Check if admission number exists
    $check = $conn->prepare("SELECT id FROM students WHERE admission_no = ?");
    $check->bind_param("s", $admission_no);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        $error = "Admission number already exists.";
    } else {
        $stmt = $conn->prepare("INSERT INTO students (admission_no, full_name, class, gender, parent_email, parent_phone, dob, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssss", $admission_no, $full_name, $class, $gender, $parent_email, $parent_phone, $dob, $password);
        
        if ($stmt->execute()) {
            $message = "Student added successfully.";
            // Send welcome email to parent, if provided
            if (!empty($parent_email)) {
                include_once __DIR__ . '/../includes/mailer.php';
                $mailer = new Mailer();
                $mailer->sendWelcomeEmail($parent_email, $full_name, $admission_no, '123456');
            }
        } else {
            $error = "Error adding student: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Student | TISM Admin</title>
    <link rel="icon" href="../assets/images/logo.jpg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
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
        <a href="manage_students.php" class="menu-item active">
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
        <h4 class="mb-0 fw-bold text-primary">Add New Student</h4>
        <div class="user-profile">
            <div class="text-end d-none d-md-block">
                <small class="text-muted d-block">Admin Panel</small>
                <span class="fw-bold"><?php echo $_SESSION['full_name']; ?></span>
            </div>
            <div class="user-avatar">
                <i class="fas fa-user"></i>
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

    <div class="dashboard-card">
        <div class="card-body p-4">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="full_name" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Admission Number</label>
                        <input type="text" name="admission_no" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Class</label>
                        <select name="class" class="form-select" required>
                            <option value="">Select Class</option>
                            <option value="Playgroup">Playgroup</option>
                            <option value="Nursery 1">Nursery 1</option>
                            <option value="Nursery 2">Nursery 2</option>
                            <option value="Primary 1">Primary 1</option>
                            <option value="Primary 2">Primary 2</option>
                            <option value="Primary 3">Primary 3</option>
                            <option value="Primary 4">Primary 4</option>
                            <option value="Primary 5">Primary 5</option>
                            <option value="Primary 6">Primary 6</option>
                            <option value="JSS 1">JSS 1</option>
                            <option value="JSS 2">JSS 2</option>
                            <option value="JSS 3">JSS 3</option>
                            <option value="SSS 1">SSS 1</option>
                            <option value="SSS 2">SSS 2</option>
                            <option value="SSS 3">SSS 3</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Gender</label>
                        <select name="gender" class="form-select" required>
                            <option value="">Select Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Date of Birth</label>
                        <input type="date" name="dob" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Parent Email</label>
                        <input type="email" name="parent_email" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Parent Phone</label>
                        <input type="text" name="parent_phone" class="form-control">
                    </div>
                </div>
                
                <div class="mt-4 d-flex justify-content-end gap-2">
                    <a href="manage_students.php" class="btn btn-light">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i> Save Student
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/dashboard.js?v=<?php echo time(); ?>"></script>
</body>
</html>
