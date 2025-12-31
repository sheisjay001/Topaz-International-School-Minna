<?php
include __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['student_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../student_login.php");
    exit();
}

if (!isset($_GET['receipt_no'])) {
    die("Invalid Request");
}

$receipt_no = $_GET['receipt_no'];
$student_id = $_SESSION['student_id'];

// Fetch Payment Details
$stmt = $conn->prepare("SELECT * FROM payments WHERE receipt_no = ? AND student_id = ?");
$stmt->bind_param("si", $receipt_no, $student_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Receipt not found or access denied.");
}

$payment = $result->fetch_assoc();
$student_name = $_SESSION['student_name'];
$student_class = $_SESSION['student_class'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Receipt | <?php echo $receipt_no; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/dark-mode.css">
    <style>
        body { background: #f5f5f5; }
        .receipt-container {
            max-width: 800px;
            margin: 50px auto;
            background: white;
            padding: 40px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            border-radius: 10px;
        }
        .header-title { color: #003366; font-weight: bold; }
        .school-info p { margin-bottom: 2px; }
        .receipt-details tr td { padding: 10px; font-size: 1.1rem; }
        .signature-line { border-top: 2px solid #000; width: 200px; margin-top: 50px; }
        @media print {
            .no-print { display: none !important; }
            body { background: white; }
            .receipt-container { box-shadow: none; margin: 0; padding: 20px; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="receipt-container">
        <!-- Header -->
        <div class="text-center mb-5 border-bottom pb-4">
            <h2 class="header-title">TOPAZ INTERNATIONAL SCHOOL MINNA</h2>
            <div class="school-info text-muted">
                <p>Opposite Niger State Secretariat, Minna, Niger State</p>
                <p>Email: info@topazschool.com | Phone: +234 800 000 0000</p>
            </div>
            <h3 class="mt-4 text-uppercase border p-2 d-inline-block">Official Payment Receipt</h3>
        </div>

        <!-- Receipt Info -->
        <div class="row mb-4">
            <div class="col-6">
                <h5 class="fw-bold">Receipt No: <span class="text-danger"><?php echo $payment['receipt_no']; ?></span></h5>
                <p>Date: <?php echo date('d F, Y', strtotime($payment['payment_date'])); ?></p>
            </div>
            <div class="col-6 text-end">
                <p class="fw-bold mb-1">Session: <?php echo $payment['session']; ?></p>
                <p class="mb-0">Term: <?php echo $payment['term']; ?></p>
            </div>
        </div>

        <!-- Student Info -->
        <div class="card mb-4 border-0 bg-light">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <strong>Student Name:</strong> <br> <?php echo $student_name; ?>
                    </div>
                    <div class="col-md-6 text-end">
                        <strong>Class:</strong> <br> <?php echo $student_class; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment Details Table -->
        <table class="table table-bordered receipt-details mb-4">
            <thead class="table-dark">
                <tr>
                    <th>Description</th>
                    <th class="text-end">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>School Fees Payment (<?php echo $payment['term']; ?>)</td>
                    <td class="text-end">₦<?php echo number_format($payment['amount_paid'], 2); ?></td>
                </tr>
                <tr>
                    <td class="fw-bold text-end">TOTAL PAID</td>
                    <td class="fw-bold text-end bg-light">₦<?php echo number_format($payment['amount_paid'], 2); ?></td>
                </tr>
            </tbody>
        </table>

        <div class="row mt-5">
            <div class="col-6">
                <p>Payment Method: <strong><?php echo $payment['payment_method']; ?></strong></p>
            </div>
            <div class="col-6 text-end">
                <div class="d-flex justify-content-end">
                    <div class="text-center">
                        <div class="signature-line"></div>
                        <p class="fw-bold">Bursar's Signature</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center mt-5 no-print">
            <button onclick="window.print()" class="btn btn-primary btn-lg"><i class="fas fa-print me-2"></i> Print / Download PDF</button>
            <a href="fees.php" class="btn btn-secondary btn-lg ms-2">Back to History</a>
            <p class="mt-2 text-muted small"><i class="fas fa-info-circle me-1"></i> To download, click Print and select "Save as PDF" as the printer.</p>
        </div>
</div>
</div>

<script src="../assets/js/dark-mode.js"></script>
</body>
</html>