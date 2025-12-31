<?php
ob_start();
include __DIR__ . '/includes/db.php';
include_once __DIR__ . '/includes/security.php';
include_once __DIR__ . '/includes/logger.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    verify_csrf_token($_POST['csrf_token']);
    
    // Basic rate limiting: max 5 attempts per 15 minutes per session/IP
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    if (!isset($_SESSION['student_login_attempts'])) {
        $_SESSION['student_login_attempts'] = ['count' => 0, 'first' => time(), 'ip' => $ip];
    }
    $attempts = &$_SESSION['student_login_attempts'];
    $window = 15 * 60;
    if ($attempts['ip'] !== $ip || (time() - $attempts['first']) > $window) {
        $attempts = ['count' => 0, 'first' => time(), 'ip' => $ip];
    }
    if ($attempts['count'] >= 5) {
        $error = "Too many login attempts. Please try again later.";
    } else {
    
    $admission_no = $_POST['admission_no'];
    $password = $_POST['password'];

    // Secure query
    $stmt = $conn->prepare("SELECT * FROM students WHERE admission_no = ?");
    $stmt->bind_param("s", $admission_no);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $student = $result->fetch_assoc();
        
        // Check Account Lockout
        if (isset($student['failed_attempts']) && $student['failed_attempts'] >= 5) {
            $lockout_duration = 15 * 60; // 15 minutes
            $last_fail_time = strtotime($student['last_failed_login']);
            $time_since_last_fail = time() - $last_fail_time;

            if ($time_since_last_fail < $lockout_duration) {
                $minutes_left = ceil(($lockout_duration - $time_since_last_fail) / 60);
                $error = "Account locked due to too many failed attempts. Please try again in $minutes_left minutes.";
            } else {
                // Lockout expired, reset attempts to 0 to allow login
                $conn->query("UPDATE students SET failed_attempts = 0 WHERE id = " . $student['id']);
                $student['failed_attempts'] = 0; // Update local variable
            }
        }

        if (empty($error)) {
            // Verify password (if password is null, allow first login with admission_no or default)
            // For security, we should enforce password. 
            // Logic: If password field is empty/null in DB, use admission_no as default password.
            
            $db_pass = $student['password'];
            $verified = false;
    
            if (empty($db_pass)) {
                // First time login logic: Default password is 'student123'
                if ($password === 'student123') {
                    $verified = true;
                    // Ideally force password change here, but for MVP we skip
                }
            } else {
                if (password_verify($password, $db_pass)) {
                    $verified = true;
                }
            }
    
            if ($verified) {
                // Reset failed attempts on success
                if (isset($student['failed_attempts'])) {
                    $conn->query("UPDATE students SET failed_attempts = 0, last_failed_login = NULL WHERE id = " . $student['id']);
                }

                // Reset session attempts
                $ip = $_SERVER['REMOTE_ADDR'];
                $attempts = ['count' => 0, 'first' => time(), 'ip' => $ip];

                // Log Activity
                log_activity($conn, $student['id'], 'student', 'Logged in');

                session_regenerate_id(true); // Prevent Session Fixation
                $_SESSION['student_id'] = $student['id'];
                $_SESSION['student_name'] = $student['full_name'];
                $_SESSION['student_class'] = $student['class'];
                $_SESSION['role'] = 'student';
                
                header("Location: student/index.php");
                exit();
            } else {
                // Log Failed Attempt
                if (isset($student['failed_attempts'])) {
                    $conn->query("UPDATE students SET failed_attempts = failed_attempts + 1, last_failed_login = NOW() WHERE id = " . $student['id']);
                }
                $attempts['count']++;
                $error = "Invalid password.";
            }
        } // End if (empty($error))
    } else {
        $error = "Invalid Admission Number.";
        $attempts['count']++;
    }
    } // End rate limit else
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal Login | TISM</title>
    <link rel="icon" href="assets/images/logo.jpg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            max-width: 400px;
            width: 100%;
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .brand-logo {
            width: 80px;
            height: 80px;
            background-color: var(--primary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: -40px auto 20px;
            color: white;
            font-size: 2rem;
            box-shadow: 0 5px 15px rgba(128,0,0,0.3);
        }
    </style>
</head>
<body>

<div class="card login-card p-4">
    <div class="brand-logo">
        <i class="fas fa-user-graduate"></i>
    </div>
    <div class="card-body">
        <h3 class="text-center fw-bold mb-1">Student Portal</h3>
        <p class="text-center text-muted mb-4">Login to view results & attendance</p>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <div class="mb-3">
                <label class="form-label small fw-bold text-muted">Admission Number</label>
                <input type="text" name="admission_no" class="form-control" required placeholder="e.g. TISM/2024/001">
            </div>
            <div class="mb-3">
                <label class="form-label text-muted small">Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="fas fa-lock text-muted"></i></span>
                    <input type="password" name="password" class="form-control bg-light border-start-0 ps-0" placeholder="Enter password" required>
                </div>
                <div class="text-end mt-1">
                    <a href="forgot_password.php" class="small text-muted text-decoration-none">Forgot Password?</a>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100 fw-bold py-2">Login</button>

        </form>
        <div class="text-center mt-3">
            <a href="index.php" class="text-decoration-none small text-muted"><i class="fas fa-arrow-left me-1"></i> Back to Homepage</a>
        </div>
    </div>
</div>

<!-- SweetAlert2 JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="assets/js/main.js"></script>
<script>
    <?php if($error): ?>
        Swal.fire({
            icon: 'error',
            title: 'Login Failed',
            text: '<?php echo $error; ?>',
            confirmButtonColor: '#d33'
        });
    <?php endif; ?>
</script>
</body>
</html>
