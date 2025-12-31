<?php
include __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$message = '';
$error = '';

// Handle PIN Generation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['generate_pins'])) {
    $quantity = (int)$_POST['quantity'];
    if ($quantity > 0 && $quantity <= 100) {
        $generated = 0;
        // Use 2-digit year to save space (10 chars: YYMMDDHHMM)
        $batch_prefix = date('ymdHi'); 
        
        $stmt = $conn->prepare("INSERT INTO pins (pin_code, serial_number) VALUES (?, ?)");
        
        for ($i = 0; $i < $quantity; $i++) {
            // Generate secure random PIN (12 digits)
            $pin = '';
            for ($j = 0; $j < 3; $j++) {
                $pin .= mt_rand(1000, 9999);
            }
            
            // Generate Serial Number: TISM-YYMMDDHHMM-001 (19 chars)
            $serial = 'TISM-' . $batch_prefix . '-' . str_pad($i + 1, 3, '0', STR_PAD_LEFT);
            
            $stmt->bind_param("ss", $pin, $serial);
            if ($stmt->execute()) {
                $generated++;
            } else {
                // Capture first error if any
                if (empty($error)) $error = "Error generating PIN: " . $stmt->error;
            }
        }
        if ($generated > 0) {
            $message = "Successfully generated $generated scratch card PINs.";
        } else if (empty($error)) {
            $error = "Failed to generate PINs. Please check database connection.";
        }
    } else {
        $error = "Please enter a valid quantity (1-100).";
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM pins WHERE id = $id");
    $message = "PIN deleted successfully.";
}

// Fetch PINs
$query = "SELECT p.*, s.full_name as student_name, s.admission_no 
          FROM pins p 
          LEFT JOIN students s ON p.student_id = s.id 
          ORDER BY p.id DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Scratch Cards | TISM Admin</title>
    <link rel="icon" href="../assets/images/logo.jpg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- DataTables & SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.bootstrap5.min.css">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css?v=<?php echo time(); ?>">
    <style>
        @media print {
            body * { visibility: hidden; }
            .print-area, .print-area * { visibility: visible; }
            .print-area { position: absolute; left: 0; top: 0; width: 100%; }
            .card-ticket { border: 1px dashed #000; page-break-inside: avoid; margin-bottom: 20px; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar no-print" id="sidebar">
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
        <a href="manage_notifications.php" class="menu-item">
            <i class="fas fa-bell"></i> Notifications
        </a>
        <a href="manage_pins.php" class="menu-item active">
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
    <div class="topbar no-print">
        <button class="btn btn-light d-lg-none" id="sidebarToggle"><i class="fas fa-bars"></i></button>
        <h4 class="mb-0 fw-bold text-primary">Manage Scratch Cards</h4>
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
        <div class="alert alert-success alert-dismissible fade show no-print" role="alert">
            <i class="fas fa-check-circle me-2"></i><?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if($error): ?>
        <div class="alert alert-danger alert-dismissible fade show no-print" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row no-print">
        <!-- Generate Form -->
        <div class="col-lg-4 mb-4">
            <div class="dashboard-card">
                <div class="card-header-custom">
                    <span><i class="fas fa-plus-circle me-2"></i>Generate Pins</span>
                </div>
                <div class="card-body p-4">
                    <form method="POST">
                        <input type="hidden" name="generate_pins" value="1">
                        <div class="mb-3">
                            <label class="form-label">Quantity to Generate</label>
                            <input type="number" name="quantity" class="form-control" min="1" max="100" value="10" required>
                            <small class="text-muted">Max 100 per batch</small>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-cogs me-2"></i> Generate
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="dashboard-card mt-4">
                <div class="card-body">
                    <button onclick="window.print()" class="btn btn-secondary w-100">
                        <i class="fas fa-print me-2"></i> Print View
                    </button>
                </div>
            </div>
        </div>

        <!-- List -->
        <div class="col-lg-8">
            <div class="dashboard-card">
                <div class="card-header-custom d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-list me-2"></i>Recent PINs</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table id="pinsTable" class="table table-hover align-middle mb-0 table-striped">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">Serial No</th>
                                    <th>PIN Code</th>
                                    <th>Status</th>
                                    <th>Usage</th>
                                    <th>Used By</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($result->num_rows > 0): ?>
                                    <?php while($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td class="ps-4 small"><?php echo $row['serial_number']; ?></td>
                                            <td class="font-monospace fw-bold"><?php echo chunk_split($row['pin_code'], 4, ' '); ?></td>
                                            <td>
                                                <?php if($row['status'] == 'unused'): ?>
                                                    <span class="badge bg-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Used</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $row['usage_count']; ?> / <?php echo $row['max_usage']; ?></td>
                                            <td class="small">
                                                <?php echo $row['student_name'] ? $row['student_name'] : '-'; ?>
                                            </td>
                                            <td>
                                                <button onclick="confirmDelete(<?php echo $row['id']; ?>)" class="btn btn-sm btn-outline-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Hidden Delete Form -->
                    <form id="deleteForm" method="GET" style="display:none;">
                        <input type="hidden" name="delete" id="deleteId">
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Printable Area (Hidden by default, visible on print) -->
    <div class="print-area row">
        <?php 
        // Re-fetch for printing (last batch logic or all visible? usually we print latest batch)
        // For simplicity, printing current view
        $result->data_seek(0);
        while($row = $result->fetch_assoc()): 
        ?>
        <div class="col-4 mb-4">
            <div class="card-ticket p-3 text-center">
                <h5 class="fw-bold mb-1">Topaz Int'l School</h5>
                <p class="small text-muted mb-2">Result Checker PIN</p>
                <h4 class="font-monospace fw-bold my-3"><?php echo chunk_split($row['pin_code'], 4, ' '); ?></h4>
                <div class="d-flex justify-content-between small text-muted">
                    <span>SN: <?php echo $row['serial_number']; ?></span>
                    <span>Usage: <?php echo $row['max_usage']; ?>x</span>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- jQuery, DataTables & SweetAlert2 JS -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../assets/js/dashboard.js?v=<?php echo time(); ?>"></script>
<script>
    $(document).ready(function() {
        $('#pinsTable').DataTable({
            dom: 'Bfrtip',
            buttons: [
                'copy', 
                'csv', 
                'excel', 
                'pdf', 
                {
                    extend: 'print',
                    text: 'Print Table',
                    autoPrint: true
                }
            ],
            pageLength: 25,
            order: [[0, 'desc']], // Sort by Serial No (approx ID desc)
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search PINs, Serial, Status..."
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
                document.getElementById('deleteId').value = id;
                document.getElementById('deleteForm').submit();
            }
        })
    }
</script>
</body>
</html>
