<?php
include_once __DIR__ . '/../includes/db.php';
include_once __DIR__ . '/../includes/security.php';
include_once __DIR__ . '/../includes/logger.php';
include_once __DIR__ . '/../includes/mailer.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    verify_csrf_token($_POST['csrf_token'] ?? '');

    if (isset($_POST['add_fee_structure'])) {
        $class = $_POST['class'];
        $term = $_POST['term'];
        $title = $_POST['title'];
        $amount = $_POST['amount'];
        
        $stmt = $conn->prepare("INSERT INTO fee_structure (class, term, title, amount) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssd", $class, $term, $title, $amount);
        if ($stmt->execute()) {
            $message = "Fee structure added.";
            log_activity($conn, $_SESSION['user_id'], 'admin', "Added Fee Structure", "$class - $title");
        } else {
            $error = "Error adding fee structure: " . $conn->error;
        }
    } 
    elseif (isset($_POST['record_payment'])) {
        $admission_no = $_POST['admission_no'];
        $amount = $_POST['amount'];
        $term = $_POST['term'];
        $session = $_POST['session'];
        $method = $_POST['method'];
        $receipt = 'RCP-' . time() . rand(100,999);
        
        // Find student ID
        $s_query = $conn->query("SELECT id, full_name, parent_email FROM students WHERE admission_no = '$admission_no'");
        if ($s_query->num_rows > 0) {
            $student_data = $s_query->fetch_assoc();
            $student_id = $student_data['id'];
            $full_name = $student_data['full_name'];
            $parent_email = $student_data['parent_email'];
            
            $stmt = $conn->prepare("INSERT INTO payments (student_id, amount_paid, payment_date, payment_method, term, session, receipt_no) VALUES (?, ?, CURDATE(), ?, ?, ?, ?)");
            $stmt->bind_param("idssss", $student_id, $amount, $method, $term, $session, $receipt);
            if ($stmt->execute()) {
                $message = "Payment recorded successfully! Receipt: $receipt";
                log_activity($conn, $_SESSION['user_id'], 'admin', "Recorded Payment", "Amount: $amount, Receipt: $receipt");
                
                // Send Email Receipt
                if (!empty($parent_email)) {
                    $mailer = new Mailer();
                    $mailer->sendPaymentReceipt($parent_email, $full_name, $amount, $receipt, "School Fees - $term ($session)");
                }
                
            } else {
                $error = "Error recording payment: " . $conn->error;
            }
        } else {
            $error = "Student not found!";
        }
    }
    elseif (isset($_POST['delete_fee'])) {
        $fee_id = $_POST['fee_id'];
        $stmt = $conn->prepare("DELETE FROM fee_structure WHERE id = ?");
        $stmt->bind_param("i", $fee_id);
        if ($stmt->execute()) {
            $message = "Fee item deleted successfully.";
            log_activity($conn, $_SESSION['user_id'], 'admin', "Deleted Fee Structure", "ID: $fee_id");
        } else {
            $error = "Error deleting fee item: " . $conn->error;
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Fees | TISM Admin</title>
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
        <a href="manage_fees.php" class="menu-item active">
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
        <h4 class="mb-0 fw-bold text-primary">Fees & Payments</h4>
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
        <!-- Record Payment -->
        <div class="col-lg-6 mb-4">
            <div class="dashboard-card h-100">
                <div class="card-header-custom">
                    <span><i class="fas fa-cash-register me-2"></i>Record New Payment</span>
                </div>
                <div class="card-body p-4">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <input type="hidden" name="record_payment" value="1">
                        <div class="mb-3">
                            <label class="form-label text-muted small">Student Admission No</label>
                            <input type="text" name="admission_no" class="form-control" required placeholder="e.g. TISM/2024/001">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted small">Amount (₦)</label>
                                <input type="number" name="amount" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted small">Payment Method</label>
                                <select name="method" class="form-select">
                                    <option>Cash</option>
                                    <option>Bank Transfer</option>
                                    <option>POS</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted small">Term</label>
                                <select name="term" class="form-select">
                                    <option>First Term</option>
                                    <option>Second Term</option>
                                    <option>Third Term</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted small">Session</label>
                                <input type="text" name="session" class="form-control" value="2024/2025">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-success w-100">
                            <i class="fas fa-check me-2"></i> Record Payment
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Set Fee Structure -->
        <div class="col-lg-6 mb-4">
            <div class="dashboard-card h-100">
                <div class="card-header-custom">
                    <span><i class="fas fa-tags me-2"></i>Set Fee Structure</span>
                </div>
                <div class="card-body p-4">
                    <form method="POST">
                        <input type="hidden" name="add_fee_structure" value="1">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted small">Class</label>
                                <select name="class" class="form-select">
                                    <?php
                                    $classes = ['Playgroup', 'Nursery 1', 'Nursery 2', 'Primary 1', 'Primary 2', 'Primary 3', 'Primary 4', 'Primary 5', 'Primary 6', 'JSS 1', 'JSS 2', 'JSS 3', 'SSS 1', 'SSS 2', 'SSS 3'];
                                    foreach($classes as $c) echo "<option value='$c'>$c</option>";
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted small">Term</label>
                                <select name="term" class="form-select">
                                    <option>First Term</option>
                                    <option>Second Term</option>
                                    <option>Third Term</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small">Title</label>
                            <input type="text" name="title" class="form-control" placeholder="e.g. Tuition Fee" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small">Amount (₦)</label>
                            <input type="number" name="amount" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-plus me-2"></i> Add Fee Item
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Current Fee Structure Table -->
    <div class="dashboard-card mb-4">
        <div class="card-header-custom d-flex justify-content-between align-items-center">
            <span><i class="fas fa-list-alt me-2"></i>Current Fee Structure</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th class="ps-4">Class</th>
                            <th>Term</th>
                            <th>Title</th>
                            <th>Amount</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $fee_res = $conn->query("SELECT * FROM fee_structure ORDER BY FIELD(class, 'Playgroup', 'Nursery 1', 'Nursery 2', 'Primary 1', 'Primary 2', 'Primary 3', 'Primary 4', 'Primary 5', 'Primary 6', 'JSS 1', 'JSS 2', 'JSS 3', 'SSS 1', 'SSS 2', 'SSS 3'), term");
                        if ($fee_res->num_rows > 0):
                            while($row = $fee_res->fetch_assoc()):
                        ?>
                        <tr>
                            <td class="ps-4 fw-bold"><?php echo htmlspecialchars($row['class']); ?></td>
                            <td><span class="badge bg-info text-dark"><?php echo htmlspecialchars($row['term']); ?></span></td>
                            <td><?php echo htmlspecialchars($row['title']); ?></td>
                            <td class="fw-bold text-primary">₦<?php echo number_format($row['amount'], 2); ?></td>
                            <td class="text-end pe-4">
                                <form method="POST" onsubmit="return confirm('Are you sure you want to delete this fee item?');" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                    <input type="hidden" name="delete_fee" value="1">
                                    <input type="hidden" name="fee_id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">No fee structure defined yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Recent Payments Table -->
    <div class="dashboard-card">
        <div class="card-header-custom">
            <span><i class="fas fa-history me-2"></i>Recent Payments</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Date</th>
                            <th>Student</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Receipt</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $pay_res = $conn->query("SELECT p.*, s.full_name, s.admission_no FROM payments p JOIN students s ON p.student_id = s.id ORDER BY p.id DESC LIMIT 10");
                        if ($pay_res->num_rows > 0):
                            while($row = $pay_res->fetch_assoc()):
                        ?>
                        <tr>
                            <td class="ps-4"><?php echo date('M d, Y', strtotime($row['payment_date'])); ?></td>
                            <td>
                                <div class="fw-bold"><?php echo htmlspecialchars($row['full_name']); ?></div>
                                <small class="text-muted"><?php echo htmlspecialchars($row['admission_no']); ?></small>
                            </td>
                            <td class="fw-bold text-success">₦<?php echo number_format($row['amount_paid']); ?></td>
                            <td><?php echo htmlspecialchars($row['payment_method']); ?></td>
                            <td><span class="badge bg-secondary"><?php echo htmlspecialchars($row['receipt_no']); ?></span></td>
                        </tr>
                        <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">No payments recorded yet.</td>
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
