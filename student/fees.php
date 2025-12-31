<?php
include __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['student_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../student_login.php");
    exit();
}

$student_id = $_SESSION['student_id'];
$student_class = $_SESSION['student_class'];

// Fetch Fee Structure for current class (Handle potential space mismatch like "JSS 1" vs "JSS1")
$fee_query = $conn->query("SELECT * FROM fee_structure WHERE REPLACE(class, ' ', '') = REPLACE('$student_class', ' ', '')");
$fees = [];
$total_fees = 0;
while($row = $fee_query->fetch_assoc()) {
    $fees[] = $row;
    $total_fees += $row['amount'];
}

// Fetch Student Details (Email)
$student_query = $conn->prepare("SELECT parent_email, full_name FROM students WHERE id = ?");
$student_query->bind_param("i", $student_id);
$student_query->execute();
$student_data = $student_query->get_result()->fetch_assoc();
$student_email = $student_data['parent_email'] ?? 'student@topazschool.com'; // Fallback if empty

// Fetch Payment History (Securely)
$stmt_hist = $conn->prepare("SELECT * FROM payments WHERE student_id = ? ORDER BY payment_date DESC");
$stmt_hist->bind_param("i", $student_id);
$stmt_hist->execute();
$payment_result = $stmt_hist->get_result();

$payments = [];
$total_paid = 0;
while($row = $payment_result->fetch_assoc()) {
    $payments[] = $row;
    $total_paid += $row['amount_paid'];
}

$balance = $total_fees - $total_paid;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Fees | TISM Student Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/dark-mode.css">
    <style>
        body { background-color: #f8f9fa; }
        @media print {
            .sidebar, .topbar, .no-print { display: none !important; }
            .main-content { margin-left: 0 !important; width: 100% !important; padding: 0 !important; }
            .card { border: none !important; box-shadow: none !important; }
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="#" class="sidebar-brand">
            <i class="fas fa-user-graduate me-2"></i>TISM STUDENT
        </a>
        <button class="sidebar-close" id="sidebarClose"><i class="fas fa-times"></i></button>
    </div>
    <div class="sidebar-menu">
        <a href="index.php" class="menu-item">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a>
        <a href="profile.php" class="menu-item">
            <i class="fas fa-user"></i> My Profile
        </a>
        <a href="results.php" class="menu-item">
            <i class="fas fa-chart-bar"></i> My Results
        </a>
        <a href="timetable.php" class="menu-item">
            <i class="fas fa-calendar-alt"></i> Timetables
        </a>
        <a href="attendance.php" class="menu-item">
            <i class="fas fa-calendar-check"></i> Attendance
        </a>
        <a href="fees.php" class="menu-item active">
            <i class="fas fa-money-bill-wave"></i> School Fees
        </a>
        <a href="cbt.php" class="menu-item">
            <i class="fas fa-laptop-code"></i> CBT Exams
        </a>
        <a href="notifications.php" class="menu-item">
            <i class="fas fa-bell"></i> Notifications
        </a>
        <a href="activity_log.php" class="menu-item">
            <i class="fas fa-history"></i> Activity Log
        </a>
        <a href="#" id="darkModeToggle" class="menu-item">
            <i class="fas fa-moon"></i> Dark Mode
        </a>
        <a href="../includes/logout.php" class="menu-item text-danger mt-3">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>

<!-- Main Content -->
<div class="main-content" id="main-content">
    <!-- Topbar -->
    <div class="topbar">
        <button class="btn btn-light d-lg-none" id="sidebarToggle"><i class="fas fa-bars"></i></button>
        <h4 class="mb-0 fw-bold text-primary">School Fees</h4>
        <div class="user-profile">
            <div class="text-end d-none d-md-block">
                <small class="text-muted d-block">Student Panel</small>
            </div>
            <div class="user-avatar bg-primary text-white">
                <i class="fas fa-user"></i>
            </div>
        </div>
    </div>

    <div class="container-fluid">

        <h2 class="fw-bold mb-4 no-print">School Fees & Payment History</h2>

        <?php if(isset($_GET['status'])): ?>
            <?php if($_GET['status'] == 'success'): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <strong>Payment Successful!</strong> Your payment has been recorded. Receipt No: <?php echo htmlspecialchars($_GET['receipt']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php elseif($_GET['status'] == 'error'): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>Payment Failed!</strong> <?php echo htmlspecialchars($_GET['message'] ?? 'An error occurred.'); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if($total_fees == 0): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i> No fee structure found for your class (<?php echo htmlspecialchars($student_class); ?>). Please contact the admin.
            </div>
        <?php else: ?>
            <div class="row mb-4 no-print">
                <div class="col-md-4">
                    <div class="card text-white bg-primary mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Total Fees (Term)</h5>
                            <h3 class="fw-bold">₦<?php echo number_format($total_fees, 2); ?></h3>
                            <small class="text-white-50">Due for <?php echo htmlspecialchars($student_class); ?></small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-white bg-success mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Total Paid</h5>
                            <h3 class="fw-bold">₦<?php echo number_format($total_paid, 2); ?></h3>
                            <small class="text-white-50">Verified Payments</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-white bg-danger mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Outstanding Balance</h5>
                            <h3 class="fw-bold">₦<?php echo number_format($balance, 2); ?></h3>
                            <?php if($balance > 0): ?>
                            <button class="btn btn-light btn-sm mt-2 w-100 fw-bold text-danger" data-bs-toggle="modal" data-bs-target="#paystackModal">
                                <i class="fas fa-credit-card me-2"></i> Pay Outstanding Balance
                            </button>
                            <?php else: ?>
                            <small class="text-white-50">Fully Paid</small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Paystack Payment Modal -->
        <div class="modal fade" id="paystackModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">Make a Payment</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="paymentForm">
                            <div class="mb-3">
                                <label class="form-label">Amount to Pay (₦)</label>
                                <input type="number" class="form-control" id="amount" value="<?php echo $balance; ?>" min="100" required>
                                <div class="form-text">You can pay the full balance or a partial amount.</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($student_email); ?>" required>
                            </div>
                            <!-- Hidden Fields -->
                            <input type="hidden" id="term" value="First Term">
                            <input type="hidden" id="session" value="2024/2025">

                            <div class="d-grid">
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="fas fa-lock me-2"></i> Pay Securely with Paystack
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fee Breakdown Table -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
                <span><i class="fas fa-list-alt me-2"></i> Fee Breakdown (<?php echo htmlspecialchars($student_class); ?>)</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Title</th>
                                <th>Term</th>
                                <th class="text-end pe-4">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($fees) > 0): ?>
                                <?php foreach($fees as $fee): ?>
                                <tr>
                                    <td class="ps-4"><?php echo htmlspecialchars($fee['title']); ?></td>
                                    <td><span class="badge bg-info text-dark"><?php echo htmlspecialchars($fee['term']); ?></span></td>
                                    <td class="text-end pe-4 fw-bold">₦<?php echo number_format($fee['amount'], 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <tr class="table-light">
                                    <td colspan="2" class="ps-4 fw-bold text-uppercase">Total Payable</td>
                                    <td class="text-end pe-4 fw-bold text-primary fs-5">₦<?php echo number_format($total_fees, 2); ?></td>
                                </tr>
                            <?php else: ?>
                                <tr><td colspan="3" class="text-center py-4 text-muted">No fee structure available.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Payment History -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white fw-bold no-print d-flex justify-content-between align-items-center">
                <span><i class="fas fa-history me-2"></i> Payment History</span>
                <button class="btn btn-sm btn-outline-secondary" onclick="window.print()"><i class="fas fa-print me-2"></i> Print History</button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Receipt No</th>
                                <th>Amount Paid</th>
                                <th>Payment Method</th>
                                <th>Term</th>
                                <th class="no-print">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($payments) > 0): ?>
                                <?php foreach($payments as $payment): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($payment['payment_date'])); ?></td>
                                    <td><span class="badge bg-secondary"><?php echo $payment['receipt_no']; ?></span></td>
                                    <td class="fw-bold text-success">₦<?php echo number_format($payment['amount_paid'], 2); ?></td>
                                    <td><?php echo $payment['payment_method']; ?></td>
                                    <td><?php echo $payment['term']; ?></td>
                                    <td class="no-print">
                                        <a href="receipt.php?receipt_no=<?php echo $payment['receipt_no']; ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-download"></i> Receipt
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="6" class="text-center py-4 text-muted">No payment records found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/dashboard.js"></script>
<script src="../assets/js/dark-mode.js"></script>
<script src="https://js.paystack.co/v1/inline.js"></script>
<script>
    const paymentForm = document.getElementById('paymentForm');
    paymentForm.addEventListener("submit", payWithPaystack, false);

    function payWithPaystack(e) {
        e.preventDefault();
        
        let amount = document.getElementById("amount").value * 100; // Convert to kobo
        let email = document.getElementById("email").value;
        let term = document.getElementById("term").value;
        let session = document.getElementById("session").value;
        
        let handler = PaystackPop.setup({
            key: 'pk_test_xxxxxxxxxxxxxxxxxxxx', // REPLACE THIS WITH YOUR PUBLIC KEY
            email: email,
            amount: amount,
            currency: 'NGN',
            ref: ''+Math.floor((Math.random() * 1000000000) + 1), // Generate a random reference
            onClose: function(){
                // alert('Window closed.');
            },
            callback: function(response){
                // Redirect to verification script with extra params
                window.location.href = 'verify_payment.php?reference=' + response.reference + '&term=' + encodeURIComponent(term) + '&session=' + encodeURIComponent(session);
            }
        });

        handler.openIframe();
    }
</script>
</body>
</html>
