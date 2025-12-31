<?php
include __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$message = '';
$error = '';
$admin_id = $_SESSION['user_id'];

// Handle Timetable Upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_timetable'])) {
    $title = $_POST['title'];
    $type = $_POST['type'];
    $class = $_POST['class']; // 'All' or specific class
    $session = $_POST['session'];
    $term = $_POST['term'];

    if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
        $upload_dir = '../uploads/timetables/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $file_ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
        $allowed_ext = ['pdf', 'jpg', 'jpeg', 'png'];
        
        if (in_array($file_ext, $allowed_ext)) {
            $file_name = time() . '_' . basename($_FILES['file']['name']);
            $target_file = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['file']['tmp_name'], $target_file)) {
                $stmt = $conn->prepare("INSERT INTO timetables (title, file_path, type, class, session, term) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssss", $title, $file_name, $type, $class, $session, $term);
                if ($stmt->execute()) {
                    $message = "Timetable uploaded successfully.";
                } else {
                    $error = "Database error: " . $stmt->error;
                }
            } else {
                $error = "Failed to move uploaded file.";
            }
        } else {
            $error = "Invalid file type. Only PDF, JPG, PNG allowed.";
        }
    } else {
        $error = "Please select a file.";
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("SELECT file_path FROM timetables WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $file_path = '../uploads/timetables/' . $row['file_path'];
        if (file_exists($file_path)) unlink($file_path);
        
        $del = $conn->prepare("DELETE FROM timetables WHERE id = ?");
        $del->bind_param("i", $id);
        $del->execute();
        $message = "Timetable deleted.";
    }
}

// Fetch Timetables
$query = "SELECT * FROM timetables ORDER BY uploaded_at DESC";
$timetables = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Timetables | TISM Admin</title>
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
        <a href="manage_timetable.php" class="menu-item active">
            <i class="fas fa-calendar-alt"></i> Timetables
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
        <h4 class="mb-0 fw-bold text-primary">Manage Timetables</h4>
        <div class="user-profile">
            <div class="text-end d-none d-md-block">
                <small class="text-muted d-block">Admin Panel</small>
                <span class="fw-bold">Administrator</span>
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

    <div class="row">
        <!-- Upload Form -->
        <div class="col-lg-4 mb-4">
            <div class="dashboard-card h-100">
                <div class="card-header-custom">
                    <span><i class="fas fa-cloud-upload-alt me-2"></i>Upload Timetable</span>
                </div>
                <div class="card-body p-4">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="upload_timetable" value="1">
                        
                        <div class="mb-3">
                            <label class="form-label text-muted small">Title</label>
                            <input type="text" name="title" class="form-control" placeholder="e.g. JSS 1 First Term Exam" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label text-muted small">Type</label>
                            <select name="type" class="form-select" required>
                                <option value="test">Test / CA</option>
                                <option value="exam">Examination</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-muted small">Target Class</label>
                            <select name="class" class="form-select" required>
                                <option value="All">All Classes</option>
                                <?php
                                $classes = ['Playgroup', 'Nursery 1', 'Nursery 2', 'Primary 1', 'Primary 2', 'Primary 3', 'Primary 4', 'Primary 5', 'Primary 6', 'JSS 1', 'JSS 2', 'JSS 3', 'SSS 1', 'SSS 2', 'SSS 3'];
                                foreach($classes as $c) echo "<option value='$c'>$c</option>";
                                ?>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label text-muted small">Term</label>
                                <select name="term" class="form-select" required>
                                    <option>First Term</option>
                                    <option>Second Term</option>
                                    <option>Third Term</option>
                                </select>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label text-muted small">Session</label>
                                <input type="text" name="session" class="form-control" value="2024/2025" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label text-muted small">File (PDF/Image)</label>
                            <input type="file" name="file" class="form-control" accept=".pdf, .jpg, .jpeg, .png" required>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-upload me-2"></i> Upload
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- List -->
        <div class="col-lg-8 mb-4">
            <div class="dashboard-card h-100">
                <div class="card-header-custom">
                    <span><i class="fas fa-list me-2"></i>Uploaded Timetables</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">Title</th>
                                    <th>Type</th>
                                    <th>Class</th>
                                    <th>Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($timetables && $timetables->num_rows > 0): ?>
                                    <?php while($row = $timetables->fetch_assoc()): ?>
                                        <tr>
                                            <td class="ps-4 fw-bold">
                                                <a href="../uploads/timetables/<?php echo $row['file_path']; ?>" target="_blank" class="text-decoration-none text-dark">
                                                    <?php echo htmlspecialchars($row['title']); ?>
                                                </a>
                                            </td>
                                            <td>
                                                <span class="badge <?php echo ($row['type'] == 'exam') ? 'bg-danger' : 'bg-warning text-dark'; ?>">
                                                    <?php echo ucfirst($row['type']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary bg-opacity-10 text-secondary">
                                                    <?php echo $row['class']; ?>
                                                </span>
                                            </td>
                                            <td class="small text-muted">
                                                <?php echo date('M d, Y', strtotime($row['uploaded_at'])); ?>
                                            </td>
                                            <td>
                                                <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">No timetables uploaded yet.</td>
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
