<?php
include __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['student_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../student_login.php");
    exit();
}

$student_id = $_SESSION['student_id'];
$student_name = $_SESSION['student_name'];
$student_class = $_SESSION['student_class'];

// Fetch Student Photo
$stmt_photo = $conn->prepare("SELECT photo FROM students WHERE id = ?");
$stmt_photo->bind_param("i", $student_id);
$stmt_photo->execute();
$student_photo = $stmt_photo->get_result()->fetch_assoc()['photo'] ?? null;

// Legacy path support
if ($student_photo && !strpos($student_photo, '/')) {
    $student_photo = 'uploads/photos/' . $student_photo;
}

// Fetch Attendance Stats
$att_query = $conn->prepare("SELECT 
    COUNT(*) as total, 
    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present 
    FROM attendance WHERE student_id = ?");
$att_query->bind_param("i", $student_id);
$att_query->execute();
$att_stats = $att_query->get_result()->fetch_assoc();
$attendance_pct = ($att_stats['total'] > 0) ? round(($att_stats['present'] / $att_stats['total']) * 100) : 0;

// Fetch Recent Results (Last 5)
$res_query = $conn->prepare("SELECT subject, score, term FROM results WHERE student_id = ? ORDER BY id DESC LIMIT 5");
$res_query->bind_param("i", $student_id);
$res_query->execute();
$recent_results = $res_query->get_result();

// Calculate Fee Status
// 1. Get Total Fees for Class
$fee_query = $conn->query("SELECT SUM(amount) as total FROM fee_structure WHERE REPLACE(class, ' ', '') = REPLACE('$student_class', ' ', '')");
$fee_data = $fee_query->fetch_assoc();
$total_fees = $fee_data['total'] ?? 0;

// 2. Get Total Paid by Student
$pay_query = $conn->query("SELECT SUM(amount_paid) as total FROM payments WHERE student_id = $student_id");
$pay_data = $pay_query->fetch_assoc();
$total_paid = $pay_data['total'] ?? 0;

$outstanding = $total_fees - $total_paid;

// Fetch Performance Analytics
$analytics_query = "SELECT term, AVG(score) as avg_score FROM results WHERE student_id = ? GROUP BY term ORDER BY term";
$stmt_analytics = $conn->prepare($analytics_query);
$stmt_analytics->bind_param("i", $student_id);
$stmt_analytics->execute();
$analytics_res = $stmt_analytics->get_result();

$terms = [];
$avg_scores = [];
while($row = $analytics_res->fetch_assoc()) {
    $terms[] = $row['term'];
    $avg_scores[] = round($row['avg_score'], 1);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard | TISM</title>
    <link rel="icon" href="../assets/images/logo.jpg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/dark-mode.css">
    <style>
        .stat-card { border-radius: 10px; border: none; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
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
        <a href="index.php" class="menu-item active">
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
        <a href="fees.php" class="menu-item">
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
        <a href="../includes/logout.php" class="menu-item mt-5 text-danger">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>

<!-- Main Content -->
<div class="main-content" id="main-content">
    <!-- Topbar -->
    <div class="topbar">
        <button class="btn btn-light d-lg-none" id="sidebarToggle"><i class="fas fa-bars"></i></button>
        <h4 class="mb-0 fw-bold text-primary">Dashboard</h4>
        <div class="user-profile">
            <div class="text-end d-none d-md-block">
                <small class="text-muted d-block">Welcome,</small>
                <span class="fw-bold"><?php echo htmlspecialchars($student_name); ?></span>
            </div>
            <div class="user-avatar bg-primary text-white">
                <?php if($student_photo): ?>
                    <img src="../<?php echo $student_photo; ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                <?php else: ?>
                    <?php echo strtoupper(substr($student_name, 0, 1)); ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Welcome Section -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark">Welcome, <?php echo htmlspecialchars($student_name); ?></h2>
            <p class="text-muted">Class: <span class="badge bg-primary"><?php echo htmlspecialchars($student_class); ?></span></p>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card stat-card bg-white p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted text-uppercase small fw-bold">Attendance</h6>
                        <h3 class="fw-bold text-primary mb-0"><?php echo $attendance_pct; ?>%</h3>
                    </div>
                    <div class="icon-box bg-light rounded-circle p-3 text-primary">
                        <i class="fas fa-user-clock fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card bg-white p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted text-uppercase small fw-bold">Next Exam</h6>
                        <h3 class="fw-bold text-success mb-0">Dec 15</h3>
                    </div>
                    <div class="icon-box bg-light rounded-circle p-3 text-success">
                        <i class="fas fa-calendar-alt fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card bg-white p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted text-uppercase small fw-bold">Fee Status</h6>
                        <?php if($outstanding > 0): ?>
                            <h4 class="fw-bold text-danger mb-0">₦<?php echo number_format($outstanding); ?></h4>
                            <small class="text-danger fw-bold">Outstanding</small>
                        <?php else: ?>
                            <h3 class="fw-bold text-success mb-0">Paid</h3>
                            <small class="text-success fw-bold">No Debts</small>
                        <?php endif; ?>
                    </div>
                    <div class="icon-box bg-light rounded-circle p-3 text-<?php echo ($outstanding > 0) ? 'danger' : 'success'; ?>">
                        <i class="fas fa-money-bill-wave fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Analytics Chart -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white fw-bold py-3"><i class="fas fa-chart-line me-2"></i> Performance Overview</div>
                <div class="card-body">
                    <canvas id="performanceChart" style="max-height: 300px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Results -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white fw-bold py-3">Recent Academic Performance</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Score</th>
                            <th>Grade</th>
                            <th>Term</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $recent_results->fetch_assoc()): 
                            $grade = ($row['score'] >= 70) ? 'A' : (($row['score'] >= 60) ? 'B' : (($row['score'] >= 50) ? 'C' : 'F'));
                            $badge = ($grade == 'A') ? 'success' : (($grade == 'F') ? 'danger' : 'secondary');
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['subject']); ?></td>
                            <td><?php echo $row['score']; ?></td>
                            <td><span class="badge bg-<?php echo $badge; ?>"><?php echo $grade; ?></span></td>
                            <td><?php echo htmlspecialchars($row['term']); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php if($recent_results->num_rows == 0): ?>
                <p class="text-center text-muted my-3">No results found yet.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="../assets/js/dashboard.js"></script>
<script src="../assets/js/dark-mode.js"></script>
<script>
    // Pass PHP data to JS
    const terms = <?php echo json_encode($terms); ?>;
    const scores = <?php echo json_encode($avg_scores); ?>;

    const ctx = document.getElementById('performanceChart').getContext('2d');
    const performanceChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: terms,
            datasets: [{
                label: 'Average Score',
                data: scores,
                borderColor: '#003366',
                backgroundColor: 'rgba(0, 51, 102, 0.1)',
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#FFD700',
                pointBorderColor: '#003366',
                pointRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    grid: { color: '#f0f0f0' }
                },
                x: {
                    grid: { display: false }
                }
            },
            plugins: {
                legend: { display: true }
            }
        }
    });
</script>
</body>
</html>