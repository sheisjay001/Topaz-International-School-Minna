<?php
include __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$msg = '';

// Handle Exam Creation
if (isset($_POST['create_exam'])) {
    $title = $_POST['title'];
    $class = $_POST['class'];
    $duration = $_POST['duration'];
    $status = $_POST['status'];
    
    $stmt = $conn->prepare("INSERT INTO cbt_exams (title, class, duration_minutes, status) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssis", $title, $class, $duration, $status);
    if($stmt->execute()) $msg = "Exam created successfully!";
}

// Handle Question Addition
if (isset($_POST['add_question'])) {
    $exam_id = $_POST['exam_id'];
    $q_text = $_POST['question_text'];
    $opt_a = $_POST['option_a'];
    $opt_b = $_POST['option_b'];
    $opt_c = $_POST['option_c'];
    $opt_d = $_POST['option_d'];
    $correct = $_POST['correct_option'];
    
    $stmt = $conn->prepare("INSERT INTO cbt_questions (exam_id, question_text, option_a, option_b, option_c, option_d, correct_option) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssss", $exam_id, $q_text, $opt_a, $opt_b, $opt_c, $opt_d, $correct);
    if($stmt->execute()) $msg = "Question added!";
}

$exams = $conn->query("SELECT * FROM cbt_exams ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage CBT | Admin Dashboard</title>
    <link rel="icon" href="../assets/images/logo.jpg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>

<div class="d-flex">
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="d-flex justify-content-between align-items-center p-3">
            <h4 class="fw-bold mb-0">TISM Admin</h4>
            <button class="btn-close sidebar-close d-md-none" id="sidebarClose" aria-label="Close"></button>
        </div>
        <div class="py-2">
            <a href="index.php"><i class="fas fa-home me-2"></i> Dashboard</a>
            <a href="manage_students.php"><i class="fas fa-user-graduate me-2"></i> Students</a>
            <a href="manage_teachers.php"><i class="fas fa-chalkboard-teacher me-2"></i> Staff</a>
            <a href="applications.php"><i class="fas fa-file-alt me-2"></i> Applications</a>
            <a href="manage_results.php"><i class="fas fa-poll me-2"></i> Results</a>
            <a href="manage_fees.php"><i class="fas fa-money-bill-wave me-2"></i> Fees</a>
            <a href="manage_cbt.php" class="active text-warning"><i class="fas fa-laptop-code me-2"></i> CBT</a>
            <a href="manage_timetable.php"><i class="fas fa-calendar-alt me-2"></i> Timetables</a>
            <a href="manage_notifications.php"><i class="fas fa-bell me-2"></i> Notifications</a>
            <a href="manage_pins.php"><i class="fas fa-key me-2"></i> Scratch Cards</a>
            <a href="settings.php"><i class="fas fa-cog me-2"></i> Settings</a>
            <a href="../includes/logout.php" class="mt-5"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
        </div>
    </div>

    <div class="main-content flex-grow-1 p-4 bg-light">
        <div class="d-md-none mb-3">
            <button class="btn btn-primary" id="sidebarToggle">
                <i class="fas fa-bars"></i> Menu
            </button>
        </div>
        <h2 class="fw-bold mb-4">CBT Management</h2>
        
        <?php if($msg): ?>
            <div class="alert alert-success"><?php echo $msg; ?></div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-4">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white fw-bold">Create New Exam</div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="create_exam" value="1">
                            <div class="mb-3">
                                <label>Title</label>
                                <input type="text" name="title" class="form-control" required placeholder="e.g. 1st Term Maths">
                            </div>
                            <div class="mb-3">
                                <label>Class</label>
                                <select name="class" class="form-select">
                                    <option>JSS 1</option>
                                    <option>JSS 2</option>
                                    <option>JSS 3</option>
                                    <option>SSS 1</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label>Duration (Minutes)</label>
                                <input type="number" name="duration" class="form-control" required value="40">
                            </div>
                            <div class="mb-3">
                                <label>Status</label>
                                <select name="status" class="form-select">
                                    <option value="inactive">Inactive</option>
                                    <option value="active">Active</option>
                                </select>
                            </div>
                            <button class="btn btn-primary w-100">Create Exam</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white fw-bold">Existing Exams</div>
                    <div class="card-body">
                        <?php while($exam = $exams->fetch_assoc()): ?>
                            <div class="border rounded p-3 mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h5 class="mb-0"><?php echo htmlspecialchars($exam['title']); ?> <small class="text-muted">(<?php echo $exam['class']; ?>)</small></h5>
                                    <span class="badge bg-<?php echo $exam['status'] == 'active' ? 'success' : 'secondary'; ?>"><?php echo $exam['status']; ?></span>
                                </div>
                                <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#addQ<?php echo $exam['id']; ?>">
                                    <i class="fas fa-plus"></i> Add Question
                                </button>
                                
                                <div class="collapse mt-3" id="addQ<?php echo $exam['id']; ?>">
                                    <div class="card card-body bg-light">
                                        <form method="POST">
                                            <input type="hidden" name="add_question" value="1">
                                            <input type="hidden" name="exam_id" value="<?php echo $exam['id']; ?>">
                                            <div class="mb-2">
                                                <input type="text" name="question_text" class="form-control" placeholder="Question Text" required>
                                            </div>
                                            <div class="row g-2 mb-2">
                                                <div class="col-6"><input type="text" name="option_a" class="form-control form-control-sm" placeholder="Option A" required></div>
                                                <div class="col-6"><input type="text" name="option_b" class="form-control form-control-sm" placeholder="Option B" required></div>
                                                <div class="col-6"><input type="text" name="option_c" class="form-control form-control-sm" placeholder="Option C" required></div>
                                                <div class="col-6"><input type="text" name="option_d" class="form-control form-control-sm" placeholder="Option D" required></div>
                                            </div>
                                            <div class="mb-2">
                                                <select name="correct_option" class="form-select form-select-sm" required>
                                                    <option value="">Select Correct Answer...</option>
                                                    <option value="A">Option A</option>
                                                    <option value="B">Option B</option>
                                                    <option value="C">Option C</option>
                                                    <option value="D">Option D</option>
                                                </select>
                                            </div>
                                            <button class="btn btn-sm btn-success">Save Question</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/dashboard.js?v=<?php echo time(); ?>"></script>
</body>
</html>
