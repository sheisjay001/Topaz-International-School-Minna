<?php
include __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/security.php';
include __DIR__ . '/../includes/logger.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$message = '';
$error = '';

// Handle Delete Result
if (isset($_GET['delete'])) {
    // Note: GET requests are harder to CSRF protect without a token in URL.
    // Ideally, this should be a POST request.
    // For now, we will log it.
    
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM results WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $message = "Result deleted successfully.";
        log_activity($conn, $_SESSION['user_id'], 'admin', "Deleted Result", "Result ID: $id");
    } else {
        $error = "Error deleting result.";
    }
}

// Handle Edit Result
if (isset($_POST['edit_result'])) {
    verify_csrf_token($_POST['csrf_token'] ?? '');

    $id = $_POST['result_id'];
    $ca_score = floatval($_POST['ca_score']);
    $exam_score = floatval($_POST['exam_score']);
    $total = $ca_score + $exam_score;
    
    $stmt = $conn->prepare("UPDATE results SET ca_score = ?, exam_score = ?, score = ? WHERE id = ?");
    $stmt->bind_param("dddi", $ca_score, $exam_score, $total, $id);
    
    if ($stmt->execute()) {
        $message = "Result updated successfully.";
        log_activity($conn, $_SESSION['user_id'], 'admin', "Updated Result", "ID: $id, Total: $total");
    } else {
        $error = "Error updating result: " . $conn->error;
    }
}

// Filters
$filter_class = isset($_GET['class']) ? $_GET['class'] : '';
$filter_subject = isset($_GET['subject']) ? $_GET['subject'] : '';
$filter_term = isset($_GET['term']) ? $_GET['term'] : '';
$filter_session = isset($_GET['session']) ? $_GET['session'] : '';

// Build Query
$query = "SELECT r.*, s.full_name, s.class, s.admission_no 
          FROM results r 
          JOIN students s ON r.student_id = s.id 
          WHERE 1=1";

$params = [];
$types = "";

if ($filter_class) {
    $query .= " AND s.class = ?";
    $params[] = $filter_class;
    $types .= "s";
}
if ($filter_subject) {
    $query .= " AND r.subject = ?";
    $params[] = $filter_subject;
    $types .= "s";
}
if ($filter_term) {
    $query .= " AND r.term = ?";
    $params[] = $filter_term;
    $types .= "s";
}
if ($filter_session) {
    $query .= " AND r.session = ?";
    $params[] = $filter_session;
    $types .= "s";
}

$query .= " ORDER BY r.id DESC LIMIT 100";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$results = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Results | TISM Admin</title>
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
        <a href="manage_results.php" class="menu-item active">
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
        <h4 class="mb-0 fw-bold text-primary">Manage Results</h4>
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

    <!-- Filter Form -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form class="row g-3" method="GET">
                <div class="col-md-3">
                    <label class="form-label small text-muted">Class</label>
                    <select name="class" class="form-select">
                        <option value="">All Classes</option>
                        <?php
                        $classes = ['Playgroup', 'Nursery 1', 'Nursery 2', 'Primary 1', 'Primary 2', 'Primary 3', 'Primary 4', 'Primary 5', 'Primary 6', 'JSS 1', 'JSS 2', 'JSS 3', 'SSS 1', 'SSS 2', 'SSS 3'];
                        foreach($classes as $c) echo "<option value='$c' " . ($filter_class == $c ? 'selected' : '') . ">$c</option>";
                        ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted">Subject</label>
                    <select name="subject" class="form-select">
                        <option value="">All Subjects</option>
                        <option value="Mathematics" <?php if($filter_subject == 'Mathematics') echo 'selected'; ?>>Mathematics</option>
                        <option value="English Language" <?php if($filter_subject == 'English Language') echo 'selected'; ?>>English Language</option>
                        <!-- Add more subjects or fetch from DB if available -->
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted">Term</label>
                    <select name="term" class="form-select">
                        <option value="">All Terms</option>
                        <option value="First Term" <?php if($filter_term == 'First Term') echo 'selected'; ?>>First Term</option>
                        <option value="Second Term" <?php if($filter_term == 'Second Term') echo 'selected'; ?>>Second Term</option>
                        <option value="Third Term" <?php if($filter_term == 'Third Term') echo 'selected'; ?>>Third Term</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-filter me-2"></i>Apply Filter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Results Table -->
    <div class="dashboard-card">
        <div class="card-header-custom">
            <span><i class="fas fa-list me-2"></i>Results List</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Student</th>
                            <th>Class</th>
                            <th>Subject</th>
                            <th>Score</th>
                            <th>Term</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($results->num_rows > 0): ?>
                            <?php while ($row = $results->fetch_assoc()): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold"><?php echo htmlspecialchars($row['full_name']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($row['admission_no']); ?></small>
                                    </td>
                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($row['class']); ?></span></td>
                                    <td><?php echo htmlspecialchars($row['subject']); ?></td>
                                    <td class="fw-bold"><?php echo $row['score']; ?></td>
                                    <td><small><?php echo htmlspecialchars($row['term'] . ' ' . $row['session']); ?></small></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary edit-btn" 
                                                data-id="<?php echo $row['id']; ?>"
                                                data-ca="<?php echo $row['ca_score']; ?>"
                                                data-exam="<?php echo $row['exam_score']; ?>"
                                                data-subject="<?php echo htmlspecialchars($row['subject']); ?>"
                                                data-student="<?php echo htmlspecialchars($row['full_name']); ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">No results found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Edit Result Modal -->
<div class="modal fade" id="editResultModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Result</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <input type="hidden" name="edit_result" value="1">
                    <input type="hidden" name="result_id" id="edit_result_id">
                    
                    <div class="mb-3">
                        <label class="form-label text-muted small">Student</label>
                        <input type="text" class="form-control" id="edit_student_name" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small">Subject</label>
                        <input type="text" class="form-control" id="edit_subject" readonly>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">CA Score (40)</label>
                            <input type="number" step="0.01" name="ca_score" id="edit_ca_score" class="form-control" max="40" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">Exam Score (60)</label>
                            <input type="number" step="0.01" name="exam_score" id="edit_exam_score" class="form-control" max="60" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/dashboard.js?v=<?php echo time(); ?>"></script>
<script>

    // Handle Edit Modal
    const editButtons = document.querySelectorAll('.edit-btn');
    const editModal = new bootstrap.Modal(document.getElementById('editResultModal'));

    editButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('edit_result_id').value = this.dataset.id;
            document.getElementById('edit_student_name').value = this.dataset.student;
            document.getElementById('edit_subject').value = this.dataset.subject;
            document.getElementById('edit_ca_score').value = this.dataset.ca;
            document.getElementById('edit_exam_score').value = this.dataset.exam;
            editModal.show();
        });
    });
</script>
</body>
</html>
