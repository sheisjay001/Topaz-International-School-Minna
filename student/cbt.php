<?php
include __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['student_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../student_login.php");
    exit();
}

$student_class = $_SESSION['student_class'];

// Fetch Active Exams for Student's Class
$query = "SELECT * FROM cbt_exams WHERE class = ? AND status = 'active'";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $student_class);
$stmt->execute();
$exams = $stmt->get_result();

// Fetch CBT History
$history_query = "SELECT r.*, e.title, e.duration_minutes 
                  FROM cbt_results r 
                  JOIN cbt_exams e ON r.exam_id = e.id 
                  WHERE r.student_id = ? 
                  ORDER BY r.date_taken DESC";
$stmt_hist = $conn->prepare($history_query);
$stmt_hist->bind_param("i", $_SESSION['student_id']);
$stmt_hist->execute();
$history = $stmt_hist->get_result();
?>

<?php
$page_title = 'CBT Exams';
$extra_css = '
<style>
    body { background-color: #f8f9fa; }
    @media print {
        .sidebar, .topbar, .no-print { display: none !important; }
        .main-content { margin-left: 0 !important; width: 100% !important; padding: 0 !important; }
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

    <div class="container-fluid">
        <h2 class="fw-bold mb-4 text-white">Computer Based Tests</h2>

        <div class="row">
            <?php while($exam = $exams->fetch_assoc()): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body text-center">
                        <div class="mb-3 text-primary">
                            <i class="fas fa-file-alt fa-3x"></i>
                        </div>
                        <h5 class="card-title fw-bold"><?php echo htmlspecialchars($exam['title']); ?></h5>
                        <p class="card-text text-muted">Duration: <?php echo $exam['duration_minutes']; ?> mins</p>
                        <a href="#" class="btn btn-primary w-100 disabled">Start Exam (Coming Soon)</a>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>

            <?php if($exams->num_rows == 0): ?>
            <div class="col-12">
                <div class="alert alert-info">No active exams scheduled for your class at the moment.</div>
            </div>
            <?php endif; ?>
        </div>

        <!-- CBT History Section -->
        <h4 class="fw-bold mt-5 mb-3 text-white"><i class="fas fa-history me-2"></i> Exam History</h4>
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Date Taken</th>
                                <th>Exam Title</th>
                                <th>Score</th>
                                <th>Percentage</th>
                                <th>Performance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($history->num_rows > 0): ?>
                                <?php while($row = $history->fetch_assoc()): 
                                    $percentage = ($row['score'] / $row['total_questions']) * 100;
                                    $badge_class = $percentage >= 70 ? 'bg-success' : ($percentage >= 50 ? 'bg-warning' : 'bg-danger');
                                    $performance = $percentage >= 70 ? 'Excellent' : ($percentage >= 50 ? 'Good' : 'Needs Improvement');
                                ?>
                                <tr>
                                    <td><?php echo date('M d, Y h:i A', strtotime($row['date_taken'])); ?></td>
                                    <td><?php echo htmlspecialchars($row['title']); ?></td>
                                    <td class="fw-bold"><?php echo $row['score']; ?> / <?php echo $row['total_questions']; ?></td>
                                    <td><?php echo number_format($percentage, 1); ?>%</td>
                                    <td><span class="badge <?php echo $badge_class; ?>"><?php echo $performance; ?></span></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="text-center py-4 text-muted">No exam history found.</td></tr>
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
</body>
</html>
