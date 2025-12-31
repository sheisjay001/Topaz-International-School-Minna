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

// Filter
$filter_date = isset($_GET['date']) ? $_GET['date'] : '';
$filter_class = isset($_GET['class']) ? $_GET['class'] : '';

// Fetch Attendance History
$query = "SELECT a.*, s.full_name, s.class, s.admission_no 
          FROM attendance a 
          JOIN students s ON a.student_id = s.id 
          WHERE a.teacher_id = ?";

$params = [$teacher_id];
$types = "i";

if ($filter_date) {
    $query .= " AND a.date = ?";
    $params[] = $filter_date;
    $types .= "s";
}

if ($filter_class) {
    $query .= " AND s.class = ?";
    $params[] = $filter_class;
    $types .= "s";
}

$query .= " ORDER BY a.date DESC, s.class, s.full_name LIMIT 100";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance History | TISM Teacher</title>
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
        <button class="sidebar-close" id="sidebarClose"><i class="fas fa-times"></i></button>
    </div>
    <div class="sidebar-menu">
        <a href="index.php" class="menu-item">
            <i class="fas fa-th-large"></i> Dashboard
        </a>
        <a href="my_students.php" class="menu-item">
            <i class="fas fa-user-graduate"></i> My Students
        </a>
        <a href="attendance.php" class="menu-item active">
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
        <h4 class="mb-0 fw-bold text-primary">Attendance History</h4>
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

    <!-- Filters -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form class="row g-3" method="GET">
                <div class="col-md-4">
                    <label class="form-label small text-muted">Filter by Date</label>
                    <input type="date" name="date" class="form-control" value="<?php echo htmlspecialchars($filter_date); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label small text-muted">Filter by Class</label>
                    <select name="class" class="form-select">
                        <option value="">All Classes</option>
                        <?php foreach ($assigned_classes as $class): ?>
                            <option value="<?php echo htmlspecialchars(trim($class)); ?>" <?php echo ($filter_class == trim($class)) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars(trim($class)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-filter me-2"></i>Apply Filter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Table -->
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="card-title fw-bold text-secondary">Recent Records</h5>
                <a href="index.php" class="btn btn-sm btn-outline-success"><i class="fas fa-plus-circle me-2"></i>Mark New</a>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Student</th>
                            <th>Class</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($row['date'])); ?></td>
                                    <td>
                                        <div class="fw-bold"><?php echo htmlspecialchars($row['full_name']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($row['admission_no']); ?></small>
                                    </td>
                                    <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($row['class']); ?></span></td>
                                    <td>
                                        <?php if ($row['status'] == 'present'): ?>
                                            <span class="badge bg-success bg-opacity-10 text-success">Present</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger bg-opacity-10 text-danger">Absent</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">No attendance records found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/dashboard.js?v=<?php echo time(); ?>"></script>
</body>
</html>
