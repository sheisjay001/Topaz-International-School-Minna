<?php
include_once __DIR__ . '/../includes/db.php';
include_once __DIR__ . '/../includes/security.php';
include_once __DIR__ . '/../includes/logger.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$message = '';
$error = '';

// Fetch Admin Photo
$admin_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT photo FROM users WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$admin_photo = $stmt->get_result()->fetch_assoc()['photo'] ?? null;

// Fetch Stats
$total_students = $conn->query("SELECT COUNT(*) as count FROM students")->fetch_assoc()['count'];
$total_teachers = $conn->query("SELECT COUNT(*) as count FROM users WHERE role='teacher'")->fetch_assoc()['count'];
$total_classes = count([
    'Playgroup', 'Nursery 1', 'Nursery 2', 
    'Primary 1', 'Primary 2', 'Primary 3', 'Primary 4', 'Primary 5', 'Primary 6',
    'JSS 1', 'JSS 2', 'JSS 3', 
    'SSS 1', 'SSS 2', 'SSS 3'
]);

// Fetch Chart Data
// 1. Students per Class
$class_labels = [];
$class_counts = [];
$class_query = $conn->query("SELECT class, COUNT(*) as count FROM students GROUP BY class ORDER BY FIELD(class, 'Playgroup', 'Nursery 1', 'Nursery 2', 'Primary 1', 'Primary 2', 'Primary 3', 'Primary 4', 'Primary 5', 'Primary 6', 'JSS 1', 'JSS 2', 'JSS 3', 'SSS 1', 'SSS 2', 'SSS 3')");
while($row = $class_query->fetch_assoc()) {
    $class_labels[] = $row['class'];
    $class_counts[] = $row['count'];
}

// 2. Gender Distribution
$gender_labels = [];
$gender_counts = [];
$gender_query = $conn->query("SELECT gender, COUNT(*) as count FROM students GROUP BY gender");
while($row = $gender_query->fetch_assoc()) {
    $gender_labels[] = ucfirst($row['gender']);
    $gender_counts[] = $row['count'];
}

// 3. Financial Overview
$total_collected = $conn->query("SELECT SUM(amount_paid) as total FROM payments")->fetch_assoc()['total'] ?? 0;
// Calculate Expected Revenue (Fee Structure * Student Count per Class)
$expected_revenue = 0;
$fee_query = $conn->query("SELECT class, SUM(amount) as total_fee FROM fee_structure GROUP BY class");
while($fee_row = $fee_query->fetch_assoc()) {
    $class_name = $fee_row['class'];
    $fee_amount = $fee_row['total_fee'];
    $student_count_query = $conn->query("SELECT COUNT(*) as count FROM students WHERE class = '$class_name'");
    $student_count = $student_count_query->fetch_assoc()['count'];
    $expected_revenue += ($fee_amount * $student_count);
}
$outstanding_revenue = $expected_revenue - $total_collected;
if ($outstanding_revenue < 0) $outstanding_revenue = 0; // Prevent negative if overpaid or data mismatch

// 4. Pass/Fail Rates (Last Term)
// Assuming pass mark is 40
$pass_count = $conn->query("SELECT COUNT(*) as count FROM results WHERE score >= 40")->fetch_assoc()['count'];
$fail_count = $conn->query("SELECT COUNT(*) as count FROM results WHERE score < 40")->fetch_assoc()['count'];


// Handle CSV Uploads and Student Addition (Logic preserved)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // CSRF Check
    verify_csrf_token($_POST['csrf_token'] ?? '');

    if (isset($_POST['upload_students']) && isset($_FILES['student_file']) && $_FILES['student_file']['error'] == 0) {
        $file = $_FILES['student_file']['tmp_name'];
        $handle = fopen($file, "r");
        fgetcsv($handle); // Skip header
        $count = 0;
        $duplicates = 0;
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $admission_no = $conn->real_escape_string($data[0] ?? '');
            $full_name = $conn->real_escape_string($data[1] ?? '');
            $class = $conn->real_escape_string($data[2] ?? '');
            $gender = $conn->real_escape_string($data[3] ?? '');
            
            if (empty($admission_no) || empty($full_name)) continue;

            $check = $conn->query("SELECT id FROM students WHERE admission_no = '$admission_no'");
            if ($check->num_rows > 0) {
                $conn->query("UPDATE students SET full_name='$full_name', class='$class', gender='$gender' WHERE admission_no='$admission_no'");
                $duplicates++;
            } else {
                $password = password_hash($admission_no, PASSWORD_DEFAULT);
                if ($conn->query("INSERT INTO students (admission_no, full_name, class, gender, password) VALUES ('$admission_no', '$full_name', '$class', '$gender', '$password')")) {
                    $count++;
                }
            }
        }
        fclose($handle);
        $message = "$count students added. $duplicates updated.";
        log_activity($conn, $_SESSION['user_id'], 'admin', "Uploaded Students CSV", "$count added, $duplicates updated");

    } elseif (isset($_POST['add_student'])) {
        $admission_no = $_POST['admission_no'];
        $full_name = $_POST['full_name'];
        $class = $_POST['class'];
        $gender = $_POST['gender']; // Added gender field
        $password = password_hash($admission_no, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("INSERT INTO students (admission_no, full_name, class, gender, password) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $admission_no, $full_name, $class, $gender, $password);
        if ($stmt->execute()) {
            $message = "Student added successfully.";
            log_activity($conn, $_SESSION['user_id'], 'admin', "Added Student", "Admission No: $admission_no");
        } else {
            $error = "Error: " . $conn->error;
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Topaz International School</title>
    <link rel="icon" href="../assets/images/logo.jpg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../assets/css/dashboard.css?v=<?php echo time(); ?>">
</head>
<body>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="#" class="sidebar-brand">
            <i class="fas fa-graduation-cap me-2"></i>TISM ADMIN
        </a>
        <button class="sidebar-close" id="sidebarClose"><i class="fas fa-times"></i></button>
    </div>
    <div class="sidebar-menu">
        <a href="index.php" class="menu-item active">
            <i class="fas fa-th-large"></i> Dashboard
        </a>
        <a href="manage_students.php" class="menu-item">
            <i class="fas fa-user-graduate"></i> Manage Students
        </a>
        <a href="manage_teachers.php" class="menu-item">
            <i class="fas fa-chalkboard-teacher"></i> Teachers
        </a>
        <a href="manage_fees.php" class="menu-item">
            <i class="fas fa-money-bill-wave"></i> Fees & Payments
        </a>
        <a href="manage_results.php" class="menu-item">
            <i class="fas fa-chart-bar"></i> Results
        </a>
        <a href="manage_timetable.php" class="menu-item">
            <i class="fas fa-calendar-alt"></i> Timetables
        </a>
        <a href="manage_notifications.php" class="menu-item">
            <i class="fas fa-bell"></i> Notifications
        </a>
        <a href="send_email.php" class="menu-item">
            <i class="fas fa-envelope"></i> Send Email
        </a>
        <a href="manage_pins.php" class="menu-item">
            <i class="fas fa-key"></i> Scratch Cards
        </a>
        <a href="settings.php" class="menu-item">
            <i class="fas fa-cog"></i> Settings
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
        <h4 class="mb-0 fw-bold text-primary">Dashboard Overview</h4>
        <div class="user-profile">
            <div class="text-end d-none d-md-block">
                <small class="text-muted d-block">Welcome back,</small>
                <span class="fw-bold"><?php echo $_SESSION['full_name']; ?></span>
            </div>
            <div class="user-avatar">
                <?php if($admin_photo): ?>
                    <img src="../<?php echo $admin_photo; ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                <?php else: ?>
                    <?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Alerts (Handled by SweetAlert2) -->


    <!-- Stats Widgets -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="dashboard-card stat-card">
                <div class="stat-info">
                    <h3><?php echo $total_students; ?></h3>
                    <p>Total Students</p>
                </div>
                <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                    <i class="fas fa-user-graduate"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="dashboard-card stat-card">
                <div class="stat-info">
                    <h3><?php echo $total_teachers; ?></h3>
                    <p>Total Teachers</p>
                </div>
                <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="dashboard-card stat-card">
                <div class="stat-info">
                    <h3><?php echo $total_classes; ?></h3>
                    <p>Active Classes</p>
                </div>
                <div class="stat-icon bg-success bg-opacity-10 text-success">
                    <i class="fas fa-school"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Financial Overview -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="dashboard-card bg-success text-white h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="card-icon bg-white text-success rounded-circle p-3">
                            <i class="fas fa-coins fa-lg"></i>
                        </div>
                        <span class="badge bg-white text-success">Total Collected</span>
                    </div>
                    <h3 class="mb-0 fw-bold">₦<?php echo number_format($total_collected); ?></h3>
                    <small class="text-white-50">Verified Payments</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="dashboard-card bg-primary text-white h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="card-icon bg-white text-primary rounded-circle p-3">
                            <i class="fas fa-file-invoice-dollar fa-lg"></i>
                        </div>
                        <span class="badge bg-white text-primary">Expected Revenue</span>
                    </div>
                    <h3 class="mb-0 fw-bold">₦<?php echo number_format($expected_revenue); ?></h3>
                    <small class="text-white-50">Based on Active Students</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="dashboard-card bg-danger text-white h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="card-icon bg-white text-danger rounded-circle p-3">
                            <i class="fas fa-hand-holding-usd fa-lg"></i>
                        </div>
                        <span class="badge bg-white text-danger">Outstanding</span>
                    </div>
                    <h3 class="mb-0 fw-bold">₦<?php echo number_format($outstanding_revenue); ?></h3>
                    <small class="text-white-50">Unpaid Fees</small>
                </div>
            </div>
        </div>
    </div>

<!-- Charts Section -->
<div class="row mb-4">
    <div class="col-lg-6 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold text-primary"><i class="fas fa-chart-bar me-2"></i>Students per Class</h6>
            </div>
            <div class="card-body">
                <canvas id="studentsChart" height="250"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-6 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold text-primary"><i class="fas fa-chart-pie me-2"></i>Gender Distribution</h6>
            </div>
            <div class="card-body">
                <canvas id="genderChart" height="250"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-lg-6 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold text-success"><i class="fas fa-coins me-2"></i>Fee Collection Status</h6>
            </div>
            <div class="card-body">
                <canvas id="feesChart" height="250"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-6 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold text-danger"><i class="fas fa-poll me-2"></i>Pass/Fail Rates (Last Term)</h6>
            </div>
            <div class="card-body">
                <canvas id="passFailChart" height="250"></canvas>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Students per Class Chart
    const ctxStudents = document.getElementById('studentsChart').getContext('2d');
    new Chart(ctxStudents, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($class_labels); ?>,
            datasets: [{
                label: 'Number of Students',
                data: <?php echo json_encode($class_counts); ?>,
                backgroundColor: 'rgba(13, 110, 253, 0.7)',
                borderColor: 'rgba(13, 110, 253, 1)',
                borderWidth: 1,
                borderRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { precision: 0 }
                }
            }
        }
    });

    // Gender Distribution Chart
    const ctxGender = document.getElementById('genderChart').getContext('2d');
    new Chart(ctxGender, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($gender_labels); ?>,
            datasets: [{
                data: <?php echo json_encode($gender_counts); ?>,
                backgroundColor: [
                    'rgba(54, 162, 235, 0.8)', // Blue
                    'rgba(255, 99, 132, 0.8)', // Red
                    'rgba(255, 206, 86, 0.8)'  // Yellow (Other)
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Fee Collection Chart
    const ctxFees = document.getElementById('feesChart').getContext('2d');
    new Chart(ctxFees, {
        type: 'pie',
        data: {
            labels: ['Collected', 'Outstanding'],
            datasets: [{
                data: [<?php echo $total_collected; ?>, <?php echo $outstanding_revenue; ?>],
                backgroundColor: [
                    'rgba(25, 135, 84, 0.8)', // Green
                    'rgba(220, 53, 69, 0.8)'  // Red
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });

    // Pass/Fail Chart
    const ctxPassFail = document.getElementById('passFailChart').getContext('2d');
    new Chart(ctxPassFail, {
        type: 'doughnut',
        data: {
            labels: ['Pass', 'Fail'],
            datasets: [{
                data: [<?php echo $pass_count; ?>, <?php echo $fail_count; ?>],
                backgroundColor: [
                    'rgba(25, 135, 84, 0.8)', // Green
                    'rgba(220, 53, 69, 0.8)'  // Red
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });
</script>

    <!-- Quick Actions -->
    <div class="row">
        <!-- Add Student Form -->
        <div class="col-lg-6">
            <div class="dashboard-card">
                <div class="card-header-custom">
                    <span><i class="fas fa-plus-circle me-2"></i>Register New Student</span>
                </div>
                <div class="card-body p-4">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <input type="hidden" name="add_student" value="1">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted small">Full Name</label>
                                <input type="text" name="full_name" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted small">Admission No</label>
                                <input type="text" name="admission_no" class="form-control" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted small">Class</label>
                                <select name="class" class="form-select" required>
                                    <option value="">Select Class</option>
                                    <?php
                                    $classes = ['Playgroup', 'Nursery 1', 'Nursery 2', 'Primary 1', 'Primary 2', 'Primary 3', 'Primary 4', 'Primary 5', 'Primary 6', 'JSS 1', 'JSS 2', 'JSS 3', 'SSS 1', 'SSS 2', 'SSS 3'];
                                    foreach($classes as $c) echo "<option value='$c'>$c</option>";
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted small">Gender</label>
                                <select name="gender" class="form-select" required>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-save me-2"></i> Save Student
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Bulk Upload -->
        <div class="col-lg-6">
            <div class="dashboard-card">
                <div class="card-header-custom">
                    <span><i class="fas fa-file-upload me-2"></i>Bulk Actions</span>
                </div>
                <div class="card-body p-4">
                    <div class="mb-4">
                        <h6 class="fw-bold mb-3 text-secondary">Upload Students (CSV)</h6>
                        <form method="POST" enctype="multipart/form-data" class="d-flex gap-2">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            <input type="hidden" name="upload_students" value="1">
                            <input type="file" name="student_file" class="form-control" accept=".csv" required>
                            <button type="submit" class="btn btn-outline-primary">Upload</button>
                        </form>
                        <small class="text-muted d-block mt-2">Format: AdmissionNo, FullName, Class, Gender</small>
                    </div>
                    <hr>
                    <div class="mb-2">
                        <h6 class="fw-bold mb-3 text-secondary">Upload Results (CSV)</h6>
                        <form method="POST" enctype="multipart/form-data" class="d-flex gap-2">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            <input type="hidden" name="upload_results" value="1">
                            <input type="file" name="result_file" class="form-control" accept=".csv" required>
                            <button type="submit" class="btn btn-outline-success">Upload</button>
                        </form>
                        <small class="text-muted d-block mt-2">Format: AdmissionNo, Subject, CA, Exam, Term, Session</small>
                        <small class="text-info d-block">Note: Updates existing results if match found.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/dashboard.js?v=<?php echo time(); ?>"></script>
<script>
    // Initialize Charts
    document.addEventListener('DOMContentLoaded', function() {
        // Class Chart
        const ctxClass = document.getElementById('classChart').getContext('2d');
        new Chart(ctxClass, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($class_labels); ?>,
                datasets: [{
                    label: 'Students',
                    data: <?php echo json_encode($class_counts); ?>,
                    backgroundColor: '#003366',
                    borderRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { display: false }
                    },
                    x: {
                        grid: { display: false }
                    }
                },
                plugins: {
                    legend: { display: false }
                }
            }
        });

        // Gender Chart
        const ctxGender = document.getElementById('genderChart').getContext('2d');
        new Chart(ctxGender, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($gender_labels); ?>,
                datasets: [{
                    data: <?php echo json_encode($gender_counts); ?>,
                    backgroundColor: ['#0056b3', '#FFD700', '#28a745'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    });

    // SweetAlert2 Toast
    <?php if($message): ?>
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'success',
        title: '<?php echo $message; ?>',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true
    });
    <?php endif; ?>

    <?php if($error): ?>
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'error',
        title: '<?php echo $error; ?>',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true
    });
    <?php endif; ?>
</script>
</body>
</html>