<?php 
include __DIR__ . '/includes/db.php'; 
include 'includes/header.php'; 

// Fetch available sessions from results table to populate dropdown
$sessions_query = $conn->query("SELECT DISTINCT session FROM results ORDER BY session DESC");
$sessions = [];
while ($row = $sessions_query->fetch_assoc()) {
    $sessions[] = $row['session'];
}
?>

<div class="container py-5 my-5">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="text-center mb-5" data-aos="fade-down">
                <h2 class="fw-bold text-primary">Student Result Checker</h2>
                <p class="text-muted">Enter your details and scratch card information to check your results.</p>
            </div>

            <div class="card shadow-lg border-0" data-aos="fade-up">
                <div class="card-body p-5">
                    <form method="POST" action="">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Admission Number</label>
                                <input type="text" name="admission_no" class="form-control" placeholder="e.g. TISM/2024/001" required value="<?php echo isset($_POST['admission_no']) ? htmlspecialchars($_POST['admission_no']) : ''; ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Session</label>
                                <select name="session" class="form-select" required>
                                    <option value="">Select Session</option>
                                    <?php 
                                    if (empty($sessions)) {
                                        // Fallback if no sessions in DB yet
                                        $current_year = date('Y');
                                        echo "<option value='" . ($current_year-1) . "/" . $current_year . "'>" . ($current_year-1) . "/" . $current_year . "</option>";
                                        echo "<option value='" . $current_year . "/" . ($current_year+1) . "'>" . $current_year . "/" . ($current_year+1) . "</option>";
                                    } else {
                                        foreach($sessions as $sess) {
                                            $selected = (isset($_POST['session']) && $_POST['session'] == $sess) ? 'selected' : '';
                                            echo "<option value='$sess' $selected>$sess</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-3">
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
                                <button type="submit" name="check_result" class="btn btn-primary w-100 py-2 fw-bold">
                                    <i class="fas fa-search me-2"></i> Check Result
                                </button>
                            </div>
                        </div>
                    </form>

                    <?php
                    if (isset($_POST['check_result'])) {
                        $admission_no = trim($_POST['admission_no']);
                        $session = $_POST['session'];
                        $term = $_POST['term'];
                        $serial_number = trim($_POST['serial_number']);
                        $pin_code = str_replace([' ', '-'], '', trim($_POST['pin'])); // Remove spaces/dashes

                        // 1. Verify Student
                        $stmt = $conn->prepare("SELECT * FROM students WHERE admission_no = ?");
                        $stmt->bind_param("s", $admission_no);
                        $stmt->execute();
                        $student_res = $stmt->get_result();
                        $student = $student_res->fetch_assoc();

                        if ($student) {
                            $student_id = $student['id'];

                            // 2. Verify PIN & Serial
                            $stmt_pin = $conn->prepare("SELECT * FROM pins WHERE pin_code = ? AND serial_number = ?");
                            $stmt_pin->bind_param("ss", $pin_code, $serial_number);
                            $stmt_pin->execute();
                            $pin_res = $stmt_pin->get_result();
                            $pin_data = $pin_res->fetch_assoc();

                            if ($pin_data) {
                                $allow_access = false;
                                $error_msg = "";

                                // Check PIN Status
                                if ($pin_data['status'] == 'unused') {
                                    // First time use - Bind to student
                                    $update = $conn->prepare("UPDATE pins SET status='used', student_id=?, usage_count=1, used_at=NOW() WHERE id=?");
                                    $update->bind_param("ii", $student_id, $pin_data['id']);
                                    if ($update->execute()) $allow_access = true;
                                    else $error_msg = "System Error: Could not activate PIN.";
                                } 
                                elseif ($pin_data['status'] == 'used') {
                                    // Already used - Check ownership
                                    if ($pin_data['student_id'] == $student_id) {
                                        // Check usage limit
                                        if ($pin_data['usage_count'] < $pin_data['max_usage']) {
                                            // Increment usage
                                            $update = $conn->prepare("UPDATE pins SET usage_count = usage_count + 1 WHERE id=?");
                                            $update->bind_param("i", $pin_data['id']);
                                            if ($update->execute()) $allow_access = true;
                                            else $error_msg = "System Error: Could not update usage.";
                                        } else {
                                            $error_msg = "PIN usage limit exceeded (Max {$pin_data['max_usage']} uses).";
                                        }
                                    } else {
                                        $error_msg = "This PIN has already been used by another student.";
                                    }
                                }

                                if ($allow_access) {
                                    // Fetch Results
                                    $stmt = $conn->prepare("SELECT * FROM results WHERE student_id = ? AND term = ? AND session = ?");
                                    $stmt->bind_param("iss", $student['id'], $term, $session);
                                    $stmt->execute();
                                    $results = $stmt->get_result();

                                    if ($results->num_rows > 0) {
                                        echo '<hr class="my-5">';
                                        echo '<div class="print-area">';
                                        
                                        // Header for Print
                                        echo '<div class="text-center mb-4">';
                                        echo '<h3 class="fw-bold text-uppercase">Topaz International School Minna</h3>';
                                        echo '<p class="mb-1">Student Result Sheet</p>';
                                        echo '<h5 class="fw-bold">' . $term . ' - ' . $session . ' Session</h5>';
                                        echo '</div>';

                                        // Student Info
                                        echo '<div class="row mb-4 border p-3 rounded bg-light">';
                                        echo '<div class="col-md-6"><strong>Name:</strong> ' . $student['full_name'] . '</div>';
                                        echo '<div class="col-md-6"><strong>Admission No:</strong> ' . $student['admission_no'] . '</div>';
                                        echo '<div class="col-md-6"><strong>Class:</strong> ' . $student['class'] . '</div>';
                                        echo '<div class="col-md-6"><strong>Gender:</strong> ' . $student['gender'] . '</div>';
                                        echo '</div>';
                                        
                                        // Results Table
                                        echo '<table class="table table-bordered table-striped mt-3">';
                                        echo '<thead class="table-dark text-white"><tr>
                                                <th>Subject</th>
                                                <th class="text-center">CA Score (40)</th>
                                                <th class="text-center">Exam Score (60)</th>
                                                <th class="text-center">Total (100)</th>
                                                <th class="text-center">Grade</th>
                                                <th class="text-center">Remark</th>
                                              </tr></thead>';
                                        echo '<tbody>';
                                        
                                        $total_score = 0;
                                        $count = 0;

                                        while ($row = $results->fetch_assoc()) {
                                            $ca = $row['ca_score'];
                                            $exam = $row['exam_score'];
                                            // Calculate total if not set, or use stored total
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

                                            echo "<tr>
                                                    <td>{$row['subject']}</td>
                                                    <td class='text-center'>{$ca}</td>
                                                    <td class='text-center'>{$exam}</td>
                                                    <td class='text-center fw-bold'>{$score}</td>
                                                    <td class='text-center fw-bold'>{$grade}</td>
                                                    <td class='text-center'>{$remark}</td>
                                                  </tr>";
                                        }
                                        echo '</tbody>';
                                        echo '</table>';

                                        // Summary
                                        if ($count > 0) {
                                            $average = number_format($total_score / $count, 2);
                                            echo '<div class="row mt-4">';
                                            echo '<div class="col-md-4 offset-md-8">';
                                            echo '<table class="table table-bordered">';
                                            echo '<tr><th>Average Score</th><td class="fw-bold">' . $average . '</td></tr>';
                                            echo '</table>';
                                            echo '</div>';
                                            echo '</div>';
                                        }

                                        echo '<div class="text-center mt-5 no-print">';
                                        echo '<button onclick="window.print()" class="btn btn-success"><i class="fas fa-print me-2"></i> Print Result</button>';
                                        echo '</div>';
                                        
                                        echo '</div>'; // End print-area
                                    } else {
                                        echo '<div class="alert alert-warning mt-4 text-center">
                                                <i class="fas fa-exclamation-circle fa-2x mb-3 d-block"></i>
                                                <h4>No Results Found</h4>
                                                <p>No result records found for <strong>' . $student['full_name'] . '</strong> in <strong>' . $term . ' ' . $session . '</strong>.</p>
                                              </div>';
                                    }
                                } else {
                                    echo '<div class="alert alert-danger mt-4"><i class="fas fa-exclamation-triangle me-2"></i> ' . $error_msg . '</div>';
                                }
                            } else {
                                echo '<div class="alert alert-danger mt-4"><i class="fas fa-times-circle me-2"></i> Invalid Scratch Card PIN or Serial Number.</div>';
                            }
                        } else {
                            echo '<div class="alert alert-danger mt-4"><i class="fas fa-user-times me-2"></i> Invalid Admission Number.</div>';
                        }
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    body * { visibility: hidden; }
    .print-area, .print-area * { visibility: visible; }
    .print-area { position: absolute; left: 0; top: 0; width: 100%; }
    .no-print { display: none !important; }
}
</style>

<?php include 'includes/footer.php'; ?>