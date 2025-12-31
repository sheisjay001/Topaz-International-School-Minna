<?php
include __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Handle Status Updates
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $status = $_GET['action']; // approved or rejected
    
    if (in_array($status, ['approved', 'rejected'])) {
        $stmt = $conn->prepare("UPDATE applications SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $id);
        $stmt->execute();
        $msg = "Application marked as " . $status;
    }
}

// Fetch Applications
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$query = "SELECT * FROM applications";
if ($status_filter != 'all') {
    $query .= " WHERE status = '$status_filter'";
}
$query .= " ORDER BY date_applied DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Applications | Admin Dashboard</title>
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
            <i class="fas fa-user-graduate"></i> Students
        </a>
        <a href="manage_teachers.php" class="menu-item">
            <i class="fas fa-chalkboard-teacher"></i> Teachers
        </a>
        <a href="applications.php" class="menu-item active">
            <i class="fas fa-file-alt"></i> Applications
        </a>
        <a href="manage_fees.php" class="menu-item">
            <i class="fas fa-money-bill-wave"></i> Fees & Payments
        </a>
        <a href="manage_results.php" class="menu-item">
            <i class="fas fa-chart-bar"></i> Results
        </a>
        <a href="manage_cbt.php" class="menu-item">
            <i class="fas fa-laptop-code"></i> CBT
        </a>
        <a href="manage_timetable.php" class="menu-item">
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

<div class="main-content" id="main-content">
    <div class="topbar">
        <button class="btn btn-light d-lg-none" id="sidebarToggle"><i class="fas fa-bars"></i></button>
        <h4 class="mb-0 fw-bold text-primary">Online Applications</h4>
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
    
    <div class="p-4">
        <?php if(isset($msg)): ?>
            <div class="alert alert-success"><?php echo $msg; ?></div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <div>
                    <a href="?status=all" class="btn btn-sm btn-outline-secondary <?php echo $status_filter == 'all' ? 'active' : ''; ?>">All</a>
                    <a href="?status=pending" class="btn btn-sm btn-outline-warning <?php echo $status_filter == 'pending' ? 'active' : ''; ?>">Pending</a>
                    <a href="?status=approved" class="btn btn-sm btn-outline-success <?php echo $status_filter == 'approved' ? 'active' : ''; ?>">Approved</a>
                </div>
                <button class="btn btn-primary btn-sm" onclick="window.print()"><i class="fas fa-print"></i> Print List</button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Child Name</th>
                                <th>Class</th>
                                <th>Parent Info</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo date('M d, Y', strtotime($row['date_applied'])); ?></td>
                                <td>
                                    <div class="fw-bold"><?php echo htmlspecialchars($row['child_name']); ?></div>
                                    <small class="text-muted"><?php echo $row['gender']; ?>, Age: <?php echo date_diff(date_create($row['dob']), date_create('today'))->y; ?></small>
                                </td>
                                <td><span class="badge bg-info text-dark"><?php echo $row['class_applied']; ?></span></td>
                                <td>
                                    <div class="small fw-bold"><?php echo htmlspecialchars($row['parent_name']); ?></div>
                                    <div class="small text-muted"><?php echo htmlspecialchars($row['parent_phone']); ?></div>
                                </td>
                                <td>
                                    <?php 
                                    $badge = $row['status'] == 'approved' ? 'success' : ($row['status'] == 'rejected' ? 'danger' : 'warning');
                                    echo "<span class='badge bg-$badge text-uppercase'>{$row['status']}</span>";
                                    ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#viewModal<?php echo $row['id']; ?>">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <?php if($row['status'] == 'pending'): ?>
                                        <a href="?action=approved&id=<?php echo $row['id']; ?>" class="btn btn-outline-success" onclick="return confirm('Approve this application?')"><i class="fas fa-check"></i></a>
                                        <a href="?action=rejected&id=<?php echo $row['id']; ?>" class="btn btn-outline-danger" onclick="return confirm('Reject this application?')"><i class="fas fa-times"></i></a>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Modal -->
                                    <div class="modal fade" id="viewModal<?php echo $row['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Application Details</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p><strong>Child:</strong> <?php echo htmlspecialchars($row['child_name']); ?></p>
                                                    <p><strong>DOB:</strong> <?php echo $row['dob']; ?></p>
                                                    <p><strong>Previous School:</strong> <?php echo htmlspecialchars($row['previous_school']); ?></p>
                                                    <hr>
                                                    <p><strong>Parent:</strong> <?php echo htmlspecialchars($row['parent_name']); ?></p>
                                                    <p><strong>Email:</strong> <?php echo htmlspecialchars($row['parent_email']); ?></p>
                                                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($row['parent_phone']); ?></p>
                                                    <p><strong>Address:</strong> <?php echo htmlspecialchars($row['address']); ?></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/dashboard.js?v=<?php echo time(); ?>"></script>
</body>
</html>
