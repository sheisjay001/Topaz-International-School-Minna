<?php
include __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['student_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../student_login.php");
    exit();
}

if (!isset($_GET['session']) || !isset($_GET['term'])) {
    die("Invalid request. Session and Term are required.");
}

$student_id = $_SESSION['student_id'];
$session = $_GET['session'];
$term = $_GET['term'];

// Fetch Student Details
$stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if (!$student) {
    die("Student record not found.");
}

// Fetch Results for specific session and term
$query = "SELECT * FROM results WHERE student_id = ? AND session = ? AND term = ? ORDER BY subject ASC";
$stmt = $conn->prepare($query);
$stmt->bind_param("iss", $student_id, $session, $term);
$stmt->execute();
$result = $stmt->get_result();

$results = [];
$total_score = 0;
$subject_count = 0;

while ($row = $result->fetch_assoc()) {
    $results[] = $row;
    $total_score += $row['score'];
    $subject_count++;
}

$average = $subject_count > 0 ? round($total_score / $subject_count, 2) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Result Sheet - <?php echo htmlspecialchars($student['full_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #fff; color: #000; padding: 20px; }
        .report-card { border: 2px solid #003366; padding: 30px; max-width: 900px; margin: 0 auto; }
        .header { text-align: center; border-bottom: 2px solid #003366; padding-bottom: 20px; margin-bottom: 20px; }
        .school-name { font-size: 28px; font-weight: bold; color: #003366; text-transform: uppercase; }
        .sub-header { font-size: 16px; font-weight: bold; }
        .student-info { margin-bottom: 20px; }
        .student-info table { width: 100%; }
        .student-info td { padding: 5px; }
        .footer { margin-top: 40px; display: flex; justify-content: space-between; font-size: 14px; }
        .signature { border-top: 1px solid #000; padding-top: 5px; width: 200px; text-align: center; }
        
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; }
            .report-card { border: none; padding: 0; }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="container">
        <div class="text-end mb-4 no-print">
            <button onclick="window.print()" class="btn btn-primary"><i class="fas fa-print"></i> Print / Download PDF</button>
            <a href="results.php" class="btn btn-secondary">Back to Results</a>
        </div>

        <div class="report-card">
            <!-- Header -->
            <div class="header">
                <div class="school-name">Topaz International School Minna</div>
                <p class="mb-1">No 123 School Road, Minna, Niger State, Nigeria</p>
                <p class="mb-0"><strong>TERMLY STUDENT REPORT SHEET</strong></p>
                <div class="sub-header mt-2"><?php echo htmlspecialchars($term); ?> Term, <?php echo htmlspecialchars($session); ?> Session</div>
            </div>

            <!-- Student Info -->
            <div class="student-info">
                <table class="table table-bordered">
                    <tr>
                        <td class="bg-light fw-bold" width="20%">Name:</td>
                        <td width="30%"><?php echo htmlspecialchars($student['full_name']); ?></td>
                        <td class="bg-light fw-bold" width="20%">Admission No:</td>
                        <td width="30%"><?php echo htmlspecialchars($student['admission_no']); ?></td>
                    </tr>
                    <tr>
                        <td class="bg-light fw-bold">Class:</td>
                        <td><?php echo htmlspecialchars($student['class']); ?></td>
                        <td class="bg-light fw-bold">Gender:</td>
                        <td><?php echo ucfirst(htmlspecialchars($student['gender'])); ?></td>
                    </tr>
                </table>
            </div>

            <!-- Results Table -->
            <table class="table table-bordered table-striped text-center align-middle">
                <thead class="table-dark">
                    <tr>
                        <th class="text-start">Subject</th>
                        <th>CA Score</th>
                        <th>Exam Score</th>
                        <th>Total</th>
                        <th>Grade</th>
                        <th>Remark</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($results)): ?>
                        <tr><td colspan="6">No results found for this term.</td></tr>
                    <?php else: ?>
                        <?php foreach($results as $row): 
                            $score = $row['score'];
                            $grade = ($score >= 70) ? 'A' : (($score >= 60) ? 'B' : (($score >= 50) ? 'C' : (($score >= 45) ? 'D' : 'F')));
                            $remark = ($grade == 'A') ? 'Excellent' : (($grade == 'B') ? 'Very Good' : (($grade == 'C') ? 'Credit' : (($grade == 'D') ? 'Pass' : 'Fail')));
                        ?>
                        <tr>
                            <td class="text-start fw-bold"><?php echo htmlspecialchars($row['subject']); ?></td>
                            <td><?php echo $row['ca_score'] ?: '-'; ?></td>
                            <td><?php echo $row['exam_score'] ?: '-'; ?></td>
                            <td class="fw-bold"><?php echo $score; ?></td>
                            <td><?php echo $grade; ?></td>
                            <td><?php echo $remark; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Summary -->
            <div class="row mt-4">
                <div class="col-6">
                    <table class="table table-bordered table-sm">
                        <thead class="table-light">
                            <tr><th colspan="2" class="text-center">Performance Summary</th></tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>Total Score:</strong></td>
                                <td><?php echo $total_score; ?></td>
                            </tr>
                            <tr>
                                <td><strong>Average Score:</strong></td>
                                <td><?php echo $average; ?></td>
                            </tr>
                            <tr>
                                <td><strong>Subjects Taken:</strong></td>
                                <td><?php echo $subject_count; ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="col-6">
                    <table class="table table-bordered table-sm text-center">
                        <thead class="table-light">
                            <tr><th colspan="2">Grading Scale</th></tr>
                        </thead>
                        <tbody>
                            <tr><td>70 - 100</td><td>A (Excellent)</td></tr>
                            <tr><td>60 - 69</td><td>B (Very Good)</td></tr>
                            <tr><td>50 - 59</td><td>C (Credit)</td></tr>
                            <tr><td>45 - 49</td><td>D (Pass)</td></tr>
                            <tr><td>0 - 44</td><td>F (Fail)</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Footer -->
            <div class="footer">
                <div class="signature">
                    <p class="mb-0"><strong>Class Teacher</strong></p>
                </div>
                <div class="signature">
                    <p class="mb-0"><strong>Principal</strong></p>
                </div>
                <div class="text-end">
                    <small>Generated on: <?php echo date('d-m-Y h:i A'); ?></small>
                </div>
            </div>
        </div>
    </div>
</body>
</html>