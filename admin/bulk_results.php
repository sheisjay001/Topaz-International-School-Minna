<?php
include __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Fetch Classes
$classes = ['Playgroup', 'Nursery 1', 'Nursery 2', 'Primary 1', 'Primary 2', 'Primary 3', 'Primary 4', 'Primary 5', 'Primary 6', 'JSS 1', 'JSS 2', 'JSS 3', 'SSS 1', 'SSS 2', 'SSS 3'];

// Fetch Sessions
$sessions_query = $conn->query("SELECT DISTINCT session FROM results ORDER BY session DESC");
$sessions = [];
while ($row = $sessions_query->fetch_assoc()) {
    $sessions[] = $row['session'];
}

$selected_class = $_GET['class'] ?? '';
$selected_term = $_GET['term'] ?? '';
$selected_session = $_GET['session'] ?? '';
$students_data = [];

if ($selected_class && $selected_term && $selected_session) {
    // Fetch Students
    $stmt = $conn->prepare("SELECT * FROM students WHERE class = ? ORDER BY full_name");
    $stmt->bind_param("s", $selected_class);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($student = $result->fetch_assoc()) {
        // Fetch Results for this student
        $r_stmt = $conn->prepare("SELECT * FROM results WHERE student_id = ? AND term = ? AND session = ?");
        $r_stmt->bind_param("iss", $student['id'], $selected_term, $selected_session);
        $r_stmt->execute();
        $res_result = $r_stmt->get_result();
        
        $results = [];
        while ($r = $res_result->fetch_assoc()) {
            $results[] = $r;
        }
        
        if (!empty($results)) {
            $student['results'] = $results;
            $students_data[] = $student;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Result Printing | TISM Admin</title>
    <link rel="icon" href="../assets/images/logo.jpg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f4f6f9; }
        .report-card {
            background: white;
            width: 210mm; /* A4 Width */
            min-height: 297mm; /* A4 Height */
            padding: 20mm;
            margin: 20px auto;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            position: relative;
        }
        .school-header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .school-logo { width: 80px; height: 80px; }
        .student-info { margin-bottom: 20px; }
        .result-table th, .result-table td { border: 1px solid #000; padding: 5px; text-align: center; }
        .result-table th { background: #eee; }
        .result-table { width: 100%; border-collapse: collapse; }
        .signature-section { margin-top: 50px; display: flex; justify-content: space-between; }
        .signature-box { text-align: center; border-top: 1px solid #000; padding-top: 5px; width: 200px; }
        
        @media print {
            body { background: white; }
            .no-print { display: none !important; }
            .report-card {
                width: 100%;
                height: 100%;
                margin: 0;
                box-shadow: none;
                page-break-after: always;
            }
        }
    </style>
</head>
<body>

<!-- Filter Section (No Print) -->
<div class="container-fluid mt-4 no-print">
    <div class="card shadow-sm">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Class</label>
                    <select name="class" class="form-select" required>
                        <option value="">Select Class</option>
                        <?php foreach($classes as $c) echo "<option value='$c' ".($selected_class==$c?'selected':'').">$c</option>"; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Term</label>
                    <select name="term" class="form-select" required>
                        <option value="">Select Term</option>
                        <option value="First Term" <?php if($selected_term=='First Term') echo 'selected'; ?>>First Term</option>
                        <option value="Second Term" <?php if($selected_term=='Second Term') echo 'selected'; ?>>Second Term</option>
                        <option value="Third Term" <?php if($selected_term=='Third Term') echo 'selected'; ?>>Third Term</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Session</label>
                    <select name="session" class="form-select" required>
                        <option value="">Select Session</option>
                        <?php foreach($sessions as $s) echo "<option value='$s' ".($selected_session==$s?'selected':'').">$s</option>"; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search me-2"></i>Generate</button>
                    <?php if(!empty($students_data)): ?>
                        <button type="button" onclick="window.print()" class="btn btn-success ms-2"><i class="fas fa-print me-2"></i>Print All</button>
                        <a href="bulk_results_pdf.php?class=<?php echo urlencode($selected_class); ?>&term=<?php echo urlencode($selected_term); ?>&session=<?php echo urlencode($selected_session); ?>" class="btn btn-danger ms-2"><i class="fas fa-file-pdf me-2"></i>Download PDF</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="container mt-4 no-print">
    <div class="card shadow-sm">
        <div class="card-body">
            <h5 class="card-title fw-bold"><i class="fas fa-print me-2"></i>Bulk Result Generator</h5>
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Class</label>
                    <select name="class" class="form-select" required>
                        <option value="">Select Class</option>
                        <?php foreach($classes as $c): ?>
                            <option value="<?php echo $c; ?>" <?php echo $selected_class == $c ? 'selected' : ''; ?>><?php echo $c; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Term</label>
                    <select name="term" class="form-select" required>
                        <option value="">Select Term</option>
                        <option value="1st Term" <?php echo $selected_term == '1st Term' ? 'selected' : ''; ?>>1st Term</option>
                        <option value="2nd Term" <?php echo $selected_term == '2nd Term' ? 'selected' : ''; ?>>2nd Term</option>
                        <option value="3rd Term" <?php echo $selected_term == '3rd Term' ? 'selected' : ''; ?>>3rd Term</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Session</label>
                    <select name="session" class="form-select" required>
                        <option value="">Select Session</option>
                        <?php foreach($sessions as $s): ?>
                            <option value="<?php echo $s; ?>" <?php echo $selected_session == $s ? 'selected' : ''; ?>><?php echo $s; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search me-2"></i>Generate</button>
                </div>
            </form>
            
            <?php if(!empty($students_data)): ?>
                <div class="mt-3 text-end">
                    <button onclick="window.print()" class="btn btn-success"><i class="fas fa-print me-2"></i>Print All Results</button>
                    <a href="bulk_results_pdf.php?class=<?php echo urlencode($selected_class); ?>&term=<?php echo urlencode($selected_term); ?>&session=<?php echo urlencode($selected_session); ?>" class="btn btn-danger ms-2"><i class="fas fa-file-pdf me-2"></i>Download PDF</a>
                    <a href="index.php" class="btn btn-secondary ms-2">Dashboard</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Results Output -->
<?php if($selected_class && empty($students_data)): ?>
    <div class="container mt-5 text-center no-print">
        <div class="alert alert-warning">No results found for the selected criteria.</div>
    </div>
<?php endif; ?>

<?php foreach($students_data as $std): ?>
    <div class="report-card">
        <div class="school-header">
            <div class="d-flex align-items-center justify-content-center mb-2">
                <img src="../assets/images/logo.jpg" class="school-logo me-3" alt="Logo">
                <div>
                    <h2 class="fw-bold mb-0 text-uppercase">Topaz International School</h2>
                    <p class="mb-0">Minna, Niger State, Nigeria</p>
                    <p class="small mb-0"><strong>Motto:</strong> Excellence in Education</p>
                </div>
            </div>
            <h4 class="fw-bold text-uppercase mt-2">Termly Report Sheet</h4>
        </div>

        <div class="student-info">
            <div class="row">
                <div class="col-6"><strong>Name:</strong> <?php echo strtoupper($std['full_name']); ?></div>
                <div class="col-6 text-end"><strong>Admission No:</strong> <?php echo $std['admission_no']; ?></div>
                <div class="col-6"><strong>Class:</strong> <?php echo $std['class']; ?></div>
                <div class="col-6 text-end"><strong>Session:</strong> <?php echo $selected_session; ?> | <strong>Term:</strong> <?php echo $selected_term; ?></div>
            </div>
        </div>

        <table class="result-table">
            <thead>
                <tr>
                    <th style="text-align: left;">Subject</th>
                    <th>CA 1 (20)</th>
                    <th>CA 2 (20)</th>
                    <th>Exam (60)</th>
                    <th>Total (100)</th>
                    <th>Grade</th>
                    <th>Remark</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($std['results'] as $res): ?>
                <tr>
                    <td style="text-align: left; font-weight: bold;"><?php echo $res['subject']; ?></td>
                    <td><?php echo $res['ca1']; ?></td>
                    <td><?php echo $res['ca2']; ?></td>
                    <td><?php echo $res['exam']; ?></td>
                    <td><?php echo $res['total']; ?></td>
                    <td><?php echo $res['grade']; ?></td>
                    <td><?php echo $res['remark']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="mt-4">
            <p><strong>Class Teacher's Remark:</strong> __________________________________________________________________</p>
            <p><strong>Principal's Remark:</strong> ______________________________________________________________________</p>
        </div>

        <div class="signature-section">
            <div class="signature-box">
                Class Teacher's Signature
            </div>
            <div class="signature-box">
                Principal's Signature & Stamp
            </div>
        </div>
        
        <div class="text-center mt-4 small text-muted">
            Generated on <?php echo date('d M Y, h:i A'); ?> from TISM Portal
        </div>
    </div>
<?php endforeach; ?>

</body>
</html>
