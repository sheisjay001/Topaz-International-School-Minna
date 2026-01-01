<?php
include __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['student_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../student_login.php");
    exit();
}

$student_id = $_SESSION['student_id'];

// Fetch student details
$stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

// Fetch available sessions for dropdown
$sessions_query = $conn->query("SELECT DISTINCT session FROM results ORDER BY session DESC");
$sessions = [];
while ($row = $sessions_query->fetch_assoc()) {
    $sessions[] = $row['session'];
}

$show_result = false;
$error_msg = '';
$results_data = null;

// Handle Result Checking
if (isset($_POST['check_result'])) {
    $session = $_POST['session'];
    $term = $_POST['term'];
    $serial_number = trim($_POST['serial_number']);
    $pin_code = str_replace([' ', '-'], '', trim($_POST['pin']));

    // Verify PIN & Serial
    $stmt_pin = $conn->prepare("SELECT * FROM pins WHERE pin_code = ? AND serial_number = ?");
    $stmt_pin->bind_param("ss", $pin_code, $serial_number);
    $stmt_pin->execute();
    $pin_res = $stmt_pin->get_result();
    $pin_data = $pin_res->fetch_assoc();

    if ($pin_data) {
        $allow_access = false;

        // Check PIN Status
        if ($pin_data['status'] == 'unused') {
            // Bind to student
            $update = $conn->prepare("UPDATE pins SET status='used', student_id=?, usage_count=1, used_at=NOW() WHERE id=?");
            $update->bind_param("ii", $student_id, $pin_data['id']);
            if ($update->execute()) $allow_access = true;
            else $error_msg = "System Error: Could not activate PIN.";
        } 
        elseif ($pin_data['status'] == 'used') {
            // Check ownership
            if ($pin_data['student_id'] == $student_id) {
                // Check usage limit
                if ($pin_data['usage_count'] < $pin_data['max_usage']) {
                    $update = $conn->prepare("UPDATE pins SET usage_count = usage_count + 1 WHERE id=?");
                    $update->bind_param("i", $pin_data['id']);
                    if ($update->execute()) $allow_access = true;
                    else $error_msg = "System Error: Could not update usage.";
                } else {
                    $error_msg = "PIN usage limit exceeded (Max {$pin_data['max_usage']} uses).";
                }
            } else {
                $error_msg = "This PIN is already used by another student.";
            }
        }

        if ($allow_access) {
            // Fetch Results
            $stmt = $conn->prepare("SELECT * FROM results WHERE student_id = ? AND term = ? AND session = ?");
            $stmt->bind_param("iss", $student_id, $term, $session);
            $stmt->execute();
            $results_data = $stmt->get_result();

            if ($results_data->num_rows > 0) {
                $show_result = true;
            } else {
                $error_msg = "No results found for $term $session.";
            }
        }
    } else {
        $error_msg = "Invalid Scratch Card PIN or Serial Number.";
    }
}
?>

<?php
$page_title = 'My Results';
$topbar_class = 'no-print';
$extra_css = '
<style>
    @media print {
        .sidebar, .topbar, .no-print { display: none !important; }
        .main-content { margin-left: 0 !important; padding: 0 !important; }
        .card { border: none !important; box-shadow: none !important; }
        body * { visibility: hidden; }
        .print-area, .print-area * { visibility: visible; }
        .print-area { position: absolute; left: 0; top: 0; width: 100%; }
    }
</style>';
include 'includes/header.php';
?>

<!-- Sidebar -->
<?php include 'includes/sidebar.php'; ?>

<!-- Main Content -->
<div class="main-content" id="main-content">
    <!-- Topbar -->
    <?php include 'includes/topbar.php'; ?>

    <?php if (!$show_result): ?>
        <!-- Result Checker Form -->
        <div class="row justify-content-center no-print">
            <div class="col-md-8">
                <div class="dashboard-card">
                    <div class="card-header-custom">
                        <span><i class="fas fa-search me-2"></i>Check Your Result</span>
                    </div>
                    <div class="card-body p-4">
                        <?php if ($error_msg): ?>
                            <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?php echo $error_msg; ?></div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Session</label>
                                    <select name="session" class="form-select" required>
                                        <option value="">Select Session</option>
                                        <?php 
                                        foreach($sessions as $sess) {
                                            $selected = (isset($_POST['session']) && $_POST['session'] == $sess) ? 'selected' : '';
                                            echo "<option value='$sess' $selected>$sess</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Term</label>
                                    <select name="term" class="form-select" required>
                                        <option value="">Select Term</option>
                                        <option value="First Term" <?php echo (isset($_POST['term']) && $_POST['term'] == 'First Term') ? 'selected' : ''; ?>>First Term</option>
                                        <option value="Second Term" <?php echo (isset($_POST['term']) && $_POST['term'] == 'Second Term') ? 'selected' : ''; ?>>Second Term</option>
                                        <option value="Third Term" <?php echo (isset($_POST['term']) && $_POST['term'] == 'Third Term') ? 'selected' : ''; ?>>Third Term</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Scratch Card Serial Number</label>
                                    <input type="text" name="serial_number" class="form-control" placeholder="e.g. TISM-231230..." required value="<?php echo isset($_POST['serial_number']) ? htmlspecialchars($_POST['serial_number']) : ''; ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Scratch Card PIN</label>
                                    <input type="text" name="pin" class="form-control" placeholder="Enter PIN Code" required value="<?php echo isset($_POST['pin']) ? htmlspecialchars($_POST['pin']) : ''; ?>">
                                </div>
                                <div class="col-12 mt-4">
                                    <button type="submit" name="check_result" class="btn btn-primary w-100 fw-bold">
                                        <i class="fas fa-check-circle me-2"></i> View Result
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Result Display (Report Card) -->
        <div class="card shadow-lg border-0 print-area">
            <div class="card-body p-5">
                <!-- Header -->
                <div class="text-center mb-4">
                    <h3 class="fw-bold text-uppercase text-primary">Topaz International School Minna</h3>
                    <p class="mb-1">Student Result Sheet</p>
                    <h5 class="fw-bold"><?php echo htmlspecialchars($_POST['term'] . ' - ' . $_POST['session']); ?> Session</h5>
                </div>

                <!-- Student Info -->
                <div class="row mb-4 border p-3 rounded bg-light">
                    <div class="col-md-6"><strong>Name:</strong> <?php echo htmlspecialchars($student['full_name']); ?></div>
                    <div class="col-md-6"><strong>Admission No:</strong> <?php echo htmlspecialchars($student['admission_no']); ?></div>
                    <div class="col-md-6"><strong>Class:</strong> <?php echo htmlspecialchars($student['class']); ?></div>
                    <div class="col-md-6"><strong>Gender:</strong> <?php echo htmlspecialchars($student['gender']); ?></div>
                </div>

                <!-- Table -->
                <div class="table-responsive">
                    <table class="table table-bordered table-striped mt-3">
                        <thead class="table-dark text-white">
                            <tr>
                                <th>Subject</th>
                                <th class="text-center">CA (40)</th>
                                <th class="text-center">Exam (60)</th>
                                <th class="text-center">Total (100)</th>
                                <th class="text-center">Grade</th>
                                <th class="text-center">Remark</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $total_score = 0;
                            $count = 0;
                            while ($row = $results_data->fetch_assoc()): 
                                $ca = $row['ca_score'];
                                $exam = $row['exam_score'];
                                $score = ($row['score'] > 0) ? $row['score'] : ($ca + $exam);
                                $total_score += $score;
                                $count++;

                                $grade = 'F';
                                $remark = 'Fail';
                                if ($score >= 70) { $grade = 'A'; $remark = 'Excellent'; }
                                elseif ($score >= 60) { $grade = 'B'; $remark = 'Very Good'; }
                                elseif ($score >= 50) { $grade = 'C'; $remark = 'Good'; }
                                elseif ($score >= 45) { $grade = 'D'; $remark = 'Fair'; }
                                elseif ($score >= 40) { $grade = 'E'; $remark = 'Pass'; }
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['subject']); ?></td>
                                <td class="text-center"><?php echo $ca; ?></td>
                                <td class="text-center"><?php echo $exam; ?></td>
                                <td class="text-center fw-bold"><?php echo $score; ?></td>
                                <td class="text-center fw-bold"><?php echo $grade; ?></td>
                                <td class="text-center"><?php echo $remark; ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Summary -->
                <?php if ($count > 0): ?>
                    <div class="row mt-4">
                        <div class="col-md-4 offset-md-8">
                            <table class="table table-bordered">
                                <tr><th>Average Score</th><td class="fw-bold"><?php echo number_format($total_score / $count, 2); ?></td></tr>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Print Button -->
                <div class="text-center mt-5 no-print">
                    <button onclick="window.print()" class="btn btn-success me-2"><i class="fas fa-print me-2"></i> Print Result</button>
                    <a href="results.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i> Check Another</a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>