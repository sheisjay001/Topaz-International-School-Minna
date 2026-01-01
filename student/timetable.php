<?php
include __DIR__ . '/../includes/db.php';
if (!isset($_SESSION['student_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../student_login.php");
    exit();
}

$student_id = $_SESSION['student_id'];
$student_class = $_SESSION['student_class'];

// Fetch Timetables
// Show timetables for 'All' classes OR the student's specific class
$query = "SELECT * FROM timetables WHERE class = 'All' OR class = ? ORDER BY uploaded_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $student_class);
$stmt->execute();
$result = $stmt->get_result();
?>

<?php
$page_title = 'Timetables';
$extra_css = '
<style>
    body { background-color: #f8f9fa; }
    @media print {
        .sidebar, .topbar, .no-print { display: none !important; }
        .main-content { margin-left: 0 !important; width: 100% !important; padding: 0 !important; }
        .card { border: none !important; box-shadow: none !important; }
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

        <h2 class="fw-bold mb-4">Exam & Test Timetables</h2>

        <div class="row">
            <?php if($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100 shadow-sm border-0">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h5 class="card-title fw-bold text-primary"><?php echo htmlspecialchars($row['title']); ?></h5>
                                    <span class="badge <?php echo ($row['type'] == 'exam') ? 'bg-danger' : 'bg-warning text-dark'; ?>">
                                        <?php echo ucfirst($row['type']); ?>
                                    </span>
                                </div>
                                <p class="text-muted small mb-2">
                                    <i class="fas fa-layer-group me-1"></i> <?php echo $row['class']; ?>
                                </p>
                                <p class="text-muted small mb-3">
                                    <i class="fas fa-clock me-1"></i> Posted: <?php echo date('M d, Y', strtotime($row['uploaded_at'])); ?>
                                </p>
                                <a href="../uploads/timetables/<?php echo $row['file_path']; ?>" target="_blank" class="btn btn-outline-primary w-100">
                                    <i class="fas fa-download me-2"></i> View / Download
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> No timetables have been uploaded for your class yet.
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>
