<?php
include __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['student_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../student_login.php");
    exit();
}

$student_id = $_SESSION['student_id'];
$stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if (!$student) {
    die("Student record not found.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admission Letter - <?php echo htmlspecialchars($student['full_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Times New Roman', Times, serif; background: #fff; color: #000; padding: 40px; }
        .letter-head { text-align: center; border-bottom: 2px solid #000; padding-bottom: 20px; margin-bottom: 30px; }
        .school-name { font-size: 28px; font-weight: bold; text-transform: uppercase; color: #003366; }
        .school-address { font-size: 14px; margin-top: 5px; }
        .letter-body { font-size: 16px; line-height: 1.8; text-align: justify; }
        .student-details { margin: 30px 0; border: 1px solid #000; padding: 20px; width: 100%; }
        .signature-section { margin-top: 60px; display: flex; justify-content: space-between; }
        .signature-box { text-align: center; border-top: 1px solid #000; padding-top: 10px; width: 200px; }
        
        @media print {
            .no-print { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="container">
        <div class="text-end mb-4 no-print">
            <button onclick="window.print()" class="btn btn-primary">Print / Download PDF</button>
            <a href="profile.php" class="btn btn-secondary">Back to Profile</a>
        </div>

        <div class="letter-head">
            <div class="school-name">Topaz International School Minna</div>
            <div class="school-address">
                No 123 School Road, Minna, Niger State, Nigeria.<br>
                Email: info@topazschoolminna.com | Phone: +234 800 000 0000
            </div>
            <h3 class="mt-4 text-decoration-underline">PROVISIONAL ADMISSION LETTER</h3>
        </div>

        <div class="text-end mb-4">
            <strong>Date:</strong> <?php echo date('F d, Y'); ?>
        </div>

        <div class="letter-body">
            <p>Dear <strong><?php echo htmlspecialchars($student['full_name']); ?></strong>,</p>
            
            <p>We are pleased to offer you provisional admission into <strong>Topaz International School Minna</strong> for the 2024/2025 Academic Session.</p>
            
            <p>Your admission details are as follows:</p>

            <table class="table table-bordered student-details">
                <tr>
                    <td width="30%"><strong>Name:</strong></td>
                    <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                </tr>
                <tr>
                    <td><strong>Admission Number:</strong></td>
                    <td><?php echo htmlspecialchars($student['admission_no']); ?></td>
                </tr>
                <tr>
                    <td><strong>Class Admitted:</strong></td>
                    <td><?php echo htmlspecialchars($student['class']); ?></td>
                </tr>
                <tr>
                    <td><strong>Gender:</strong></td>
                    <td><?php echo ucfirst(htmlspecialchars($student['gender'])); ?></td>
                </tr>
                <tr>
                    <td><strong>Date of Birth:</strong></td>
                    <td><?php echo $student['dob'] ? date('d-m-Y', strtotime($student['dob'])) : 'N/A'; ?></td>
                </tr>
            </table>

            <p>This offer is subject to your acceptance of the school's rules and regulations. You are expected to complete your registration and pay the necessary school fees before resumption.</p>
            
            <p>We congratulate you on your admission and look forward to having you as a student of this great citadel of learning.</p>
            
            <p>Yours faithfully,</p>
        </div>

        <div class="signature-section">
            <div class="signature-box">
                <strong>The Registrar</strong><br>
                Topaz Int'l School
            </div>
            <div class="signature-box">
                <strong>The Principal</strong><br>
                Topaz Int'l School
            </div>
        </div>
        
        <div class="text-center mt-5 text-muted small">
            <em>This is a computer-generated document and is valid without a physical seal.</em>
        </div>
    </div>

</body>
</html>
