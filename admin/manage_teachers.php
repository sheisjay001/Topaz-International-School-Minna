<?php
include_once __DIR__ . '/../includes/db.php';
include_once __DIR__ . '/../includes/security.php';
include_once __DIR__ . '/../includes/logger.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$message = '';
$error = '';

// Handle Add Teacher
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_teacher'])) {
    verify_csrf_token($_POST['csrf_token'] ?? '');

    $full_name = $_POST['full_name'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $staff_id = $_POST['staff_id'];
    $assigned_classes = isset($_POST['assigned_classes']) ? implode(',', $_POST['assigned_classes']) : '';
    $assigned_subjects = $_POST['assigned_subjects']; // Comma separated

    // Check if username exists
    $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $check->bind_param("s", $username);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        $error = "Username already exists.";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $role = 'teacher';
        $stmt = $conn->prepare("INSERT INTO users (username, password, role, full_name, staff_id, assigned_classes, assigned_subjects) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $username, $hashed_password, $role, $full_name, $staff_id, $assigned_classes, $assigned_subjects);
        
        if ($stmt->execute()) {
            log_activity($conn, $_SESSION['user_id'], 'admin', 'Added new teacher', "Name: $full_name");
            $message = "Teacher added successfully.";
        } else {
            $error = "Error adding teacher: " . $conn->error;
        }
    }
}

// Handle CSV Upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_csv'])) {
    verify_csrf_token($_POST['csrf_token'] ?? '');

    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == 0) {
        $file = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file, "r");
        
        if ($handle !== FALSE) {
            $row = 0;
            $success_count = 0;
            $fail_count = 0;
            
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $row++;
                if ($row == 1) continue; // Skip header row (assuming headers exist)

                // Expected format: Full Name, Staff ID, Username (Email), Password, Assigned Classes (comma sep), Assigned Subjects (comma sep)
                // Example: John Doe, TISM/ST/001, john@school.com, pass123, "JSS 1, JSS 2", "Maths, Physics"
                
                $full_name = $conn->real_escape_string($data[0] ?? '');
                $staff_id = $conn->real_escape_string($data[1] ?? '');
                $username = $conn->real_escape_string($data[2] ?? '');
                $password_plain = $data[3] ?? 'teacher123'; // Default if missing
                $assigned_classes = $conn->real_escape_string($data[4] ?? '');
                $assigned_subjects = $conn->real_escape_string($data[5] ?? '');

                if (empty($username) || empty($full_name)) {
                    $fail_count++;
                    continue;
                }

                // Check if username exists
                $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
                $check_stmt->bind_param("s", $username);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();

                if ($check_result->num_rows > 0) {
                    // Update existing teacher
                    $stmt = $conn->prepare("UPDATE users SET full_name=?, staff_id=?, assigned_classes=?, assigned_subjects=? WHERE username=?");
                    $stmt->bind_param("sssss", $full_name, $staff_id, $assigned_classes, $assigned_subjects, $username);
                } else {
                    // Insert new teacher
                    $hashed_password = password_hash($password_plain, PASSWORD_DEFAULT);
                    $role = 'teacher';
                    $stmt = $conn->prepare("INSERT INTO users (username, password, role, full_name, staff_id, assigned_classes, assigned_subjects) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssssss", $username, $hashed_password, $role, $full_name, $staff_id, $assigned_classes, $assigned_subjects);
                }

                if ($stmt->execute()) {
                    $success_count++;
                } else {
                    $fail_count++;
                }
            }
            fclose($handle);
            log_activity($conn, $_SESSION['user_id'], 'admin', 'Bulk uploaded teachers', "Success: $success_count, Failed: $fail_count");
            $message = "CSV Upload processed. Added/Updated: $success_count, Failed: $fail_count";
        } else {
            $error = "Could not open CSV file.";
        }
    } else {
        $error = "Please upload a valid CSV file.";
    }
}

// Handle Delete Teacher
if (isset($_POST['delete_teacher'])) {
    verify_csrf_token($_POST['csrf_token'] ?? '');
    
    $id = $_POST['teacher_id'];
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'teacher'");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        log_activity($conn, $_SESSION['user_id'], 'admin', 'Deleted teacher', "ID: $id");
        $message = "Teacher deleted successfully.";
    } else {
        $error = "Error deleting teacher.";
    }
}

// Fetch Teachers
$teachers = $conn->query("SELECT * FROM users WHERE role = 'teacher' ORDER BY full_name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Teachers | TISM Admin</title>
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
        <a href="manage_teachers.php" class="menu-item active">
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
        <button class="btn btn-light d-lg-none" id="sidebarToggle" type="button"><i class="fas fa-bars"></i></button>
        <h4 class="mb-0 fw-bold text-primary">Manage Teachers</h4>
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
        <!-- Add Teacher Form -->
        <div class="col-lg-4 mb-4">
            <!-- CSV Upload Card -->
            <div class="dashboard-card mb-4">
                <div class="card-header-custom bg-success text-white">
                    <span><i class="fas fa-file-csv me-2"></i>Bulk Upload Teachers</span>
                </div>
                <div class="card-body p-4">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="upload_csv" value="1">
                        <div class="mb-3">
                            <label class="form-label text-muted small">Upload CSV File</label>
                            <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                            <small class="text-muted d-block mt-2">
                                Format: Name, Staff ID, Email, Password, Classes (comma sep), Subjects (comma sep)<br>
                                <a href="#" onclick="downloadSample()" class="text-decoration-none"><i class="fas fa-download me-1"></i> Download Sample</a>
                            </small>
                        </div>
                        <button type="submit" class="btn btn-success w-100">
                            <i class="fas fa-upload me-2"></i> Upload CSV
                        </button>
                    </form>
                </div>
            </div>

            <div class="dashboard-card">
                <div class="card-header-custom">
                    <span><i class="fas fa-user-plus me-2"></i>Add Teacher</span>
                </div>
                <div class="card-body p-4">
                    <form method="POST">
                        <input type="hidden" name="add_teacher" value="1">
                        
                        <div class="mb-3">
                            <label class="form-label text-muted small">Full Name</label>
                            <input type="text" name="full_name" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-muted small">Staff ID</label>
                            <input type="text" name="staff_id" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-muted small">Email Address (Username)</label>
                            <input type="email" name="username" class="form-control" placeholder="e.g. teacher@school.com" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-muted small">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-muted small">Assigned Classes</label>
                            <select name="assigned_classes[]" class="form-select" multiple size="5">
                                <?php
                                $classes = ['Playgroup', 'Nursery 1', 'Nursery 2', 'Primary 1', 'Primary 2', 'Primary 3', 'Primary 4', 'Primary 5', 'Primary 6', 'JSS 1', 'JSS 2', 'JSS 3', 'SSS 1', 'SSS 2', 'SSS 3'];
                                foreach($classes as $c) echo "<option value='$c'>$c</option>";
                                ?>
                            </select>
                            <small class="text-muted">Hold Ctrl/Cmd to select multiple</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-muted small">Assigned Subjects (Comma Separated)</label>
                            <input type="text" name="assigned_subjects" class="form-control" placeholder="e.g. Mathematics, English, Physics">
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-save me-2"></i> Save Teacher
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Teachers List -->
        <div class="col-lg-8">
            <div class="dashboard-card">
                <div class="card-header-custom">
                    <span><i class="fas fa-users me-2"></i>Teachers List</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Staff ID</th>
                                    <th>Name</th>
                                    <th>Assigned Classes</th>
                                    <th>Assigned Subjects</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($teachers->num_rows > 0): ?>
                                    <?php while ($row = $teachers->fetch_assoc()): ?>
                                        <tr>
                                            <td class="ps-4"><span class="badge bg-secondary"><?php echo htmlspecialchars($row['staff_id'] ?? ''); ?></span></td>
                                            <td class="fw-bold">
                                                <?php echo htmlspecialchars($row['full_name'] ?? ''); ?>
                                                <div class="small text-muted fw-normal">@<?php echo htmlspecialchars($row['username'] ?? ''); ?></div>
                                            </td>
                                            <td>
                                                <?php 
                                                $classes = explode(',', $row['assigned_classes'] ?? '');
                                                foreach($classes as $c) {
                                                    if(trim($c)) echo "<span class='badge bg-info bg-opacity-10 text-info me-1'>" . htmlspecialchars(trim($c)) . "</span>";
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php 
                                                $subjects = explode(',', $row['assigned_subjects'] ?? '');
                                                foreach($subjects as $s) {
                                                    if(trim($s)) echo "<span class='badge bg-warning bg-opacity-10 text-warning me-1'>" . htmlspecialchars(trim($s)) . "</span>";
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure?');">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">No teachers found.</td>
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
<script>
    function downloadSample() {
        const csvContent = "Full Name,Staff ID,Username,Password,Assigned Classes,Assigned Subjects\nJohn Doe,TISM/ST/001,john@school.com,teacher123,\"JSS 1, JSS 2\",\"Mathematics, Physics\"";
        const blob = new Blob([csvContent], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.setAttribute('hidden', '');
        a.setAttribute('href', url);
        a.setAttribute('download', 'sample_teachers.csv');
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    }
</script>
</body>
</html>
