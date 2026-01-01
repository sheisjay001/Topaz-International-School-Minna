<?php
include __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['student_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../student_login.php");
    exit();
}

$student_id = $_SESSION['student_id'];

// Fetch attendance
$query = "SELECT * FROM attendance WHERE student_id = ? ORDER BY date DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<?php
$page_title = 'My Attendance';
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
        <h2 class="fw-bold mb-4">Attendance Record</h2>

        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Remark</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $result->fetch_assoc()): 
                                $status_badge = ($row['status'] == 'present') ? 'success' : 'danger';
                            ?>
                            <tr>
                                <td><?php echo date('F j, Y', strtotime($row['date'])); ?></td>
                                <td><span class="badge bg-<?php echo $status_badge; ?> text-uppercase"><?php echo $row['status']; ?></span></td>
                                <td><?php echo ($row['status'] == 'present') ? 'Present in class' : 'Absent without leave'; ?></td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if($result->num_rows == 0): ?>
                            <tr><td colspan="3" class="text-center text-muted">No attendance records found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>
