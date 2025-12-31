<?php
include __DIR__ . '/../includes/db.php';
include_once __DIR__ . '/../includes/logger.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$message = '';
$error = '';

// Handle Delete Request
if (isset($_POST['delete_student'])) {
    $student_id = $_POST['student_id'];
    $delete_stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
    $delete_stmt->bind_param("i", $student_id);
    
    if ($delete_stmt->execute()) {
        log_activity($conn, $_SESSION['user_id'], 'admin', 'Deleted student', "ID: $student_id");
        $message = "Student deleted successfully.";
    } else {
        $error = "Error deleting student.";
    }
}

// Fetch all students for DataTables (no server-side processing for simplicity unless large dataset)
$sql = "SELECT * FROM students ORDER BY class, full_name";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students | TISM Admin</title>
    <link rel="icon" href="../assets/images/logo.jpg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css" rel="stylesheet">
    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    
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
        <button class="btn btn-light d-lg-none" id="sidebarToggle" type="button"><i class="fas fa-bars"></i></button>
        <h4 class="mb-0 fw-bold text-primary">Manage Students</h4>
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

    <!-- Student List -->
    <div class="dashboard-card">
        <div class="card-header-custom d-flex justify-content-between align-items-center">
            <span><i class="fas fa-users me-2"></i>Student Database</span>
            <a href="add_student.php" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i> Add Student</a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="studentsTable" class="table table-hover align-middle mb-0 table-striped">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Admission No</th>
                            <th>Full Name</th>
                            <th>Class</th>
                            <th>Gender</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td class="ps-4"><span class="badge bg-secondary"><?php echo $row['admission_no']; ?></span></td>
                                    <td class="fw-bold"><?php echo $row['full_name']; ?></td>
                                    <td><span class="badge bg-primary bg-opacity-10 text-primary"><?php echo $row['class']; ?></span></td>
                                    <td><?php echo $row['gender']; ?></td>
                                    <td>
                                        <button onclick="confirmDelete(<?php echo $row['id']; ?>)" class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <a href="edit_student.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Hidden Delete Form -->
            <form id="deleteForm" method="POST" style="display:none;">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <input type="hidden" name="delete_student" value="1">
                <input type="hidden" name="student_id" id="deleteStudentId">
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- DataTables & Plugins -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../assets/js/dashboard.js?v=<?php echo time(); ?>"></script>

<script>
    $(document).ready(function() {
        $('#studentsTable').DataTable({
            dom: 'Bfrtip',
            buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
            pageLength: 25,
            order: [[2, 'asc']], // Order by Class
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search students..."
            }
        });
    });

    function confirmDelete(id) {
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('deleteStudentId').value = id;
                document.getElementById('deleteForm').submit();
            }
        })
    }
</script>
</body>
</html>