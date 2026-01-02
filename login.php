<?php
ob_start();
include __DIR__ . '/includes/db.php';
include_once __DIR__ . '/includes/security.php';
include_once __DIR__ . '/includes/logger.php';

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    verify_csrf_token($_POST['csrf_token']);

    // Basic rate limiting: max 5 attempts per 15 minutes per session/IP
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = ['count' => 0, 'first' => time(), 'ip' => $ip];
    }
    $attempts = &$_SESSION['login_attempts'];
    $window = 15 * 60;
    if ($attempts['ip'] !== $ip || (time() - $attempts['first']) > $window) {
        $attempts = ['count' => 0, 'first' => time(), 'ip' => $ip];
    }
    if ($attempts['count'] >= 5) {
        $error = "Too many login attempts. Please try again later.";
    } else {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            // Check for New Device (IP)
            $stmt_ip = $conn->prepare("SELECT id FROM user_logins WHERE user_id = ? AND ip_address = ?");
            $stmt_ip->bind_param("is", $row['id'], $ip);
            $stmt_ip->execute();
            $is_known_device = $stmt_ip->get_result()->num_rows > 0;

            // Check for 2FA or New Device
            if ($row['two_factor_enabled'] || !$is_known_device) {
                $_SESSION['2fa_pending_user_id'] = $row['id'];
                $_SESSION['2fa_role'] = $row['role'];
                
                if (!$is_known_device) {
                    // New Device -> Email OTP
                    $otp = sprintf("%06d", mt_rand(100000, 999999));
                    $expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));
                    
                    $update = $conn->prepare("UPDATE users SET otp_code = ?, otp_expiry = ? WHERE id = ?");
                    $update->bind_param("ssi", $otp, $expiry, $row['id']);
                    $update->execute();
                    
                    // Send Email
                    require_once __DIR__ . '/includes/mailer.php';
                    $mailer = new Mailer();
                    // Send to username (email)
                    $mailer->sendOTP($row['username'], $row['full_name'], $otp);
                    
                    $_SESSION['2fa_method'] = 'email';
                } else {
                    // Known Device but 2FA Enabled -> TOTP
                    $_SESSION['2fa_method'] = 'totp';
                }
                
                header("Location: verify_2fa.php");
                exit();
            }

            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = $row['role'];
            $_SESSION['full_name'] = $row['full_name'];

            // Register Known Device
            $stmt_log = $conn->prepare("INSERT INTO user_logins (user_id, ip_address, user_agent) VALUES (?, ?, ?)");
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
            $stmt_log->bind_param("iss", $row['id'], $ip, $ua);
            $stmt_log->execute();

            // Log Login
            log_activity($conn, $row['id'], $row['role'], 'Logged in');

            if ($row['role'] == 'admin') {
                header("Location: admin/index.php");
            } else {
                header("Location: teacher/index.php");
            }
            exit();
        } else {
            $error = "Invalid password.";
            $attempts['count']++;
            // Log Failed Attempt (optional, but good practice)
            // log_activity($conn, 0, 'guest', 'Failed login attempt: ' . $username);
        }
    } else {
        $error = "User not found.";
        $attempts['count']++;
    }
    } // end rate limit else
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Topaz International School</title>
    <link rel="icon" href="assets/images/logo.jpg">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: url('assets/images/school-building.jfif') no-repeat center center fixed;
            background-size: cover;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            overflow: hidden;
        }
        
        /* Dark Overlay */
        body::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0, 33, 71, 0.65); /* Topaz Navy Blue Overlay */
            backdrop-filter: blur(4px);
            z-index: 0;
        }

        .login-card {
            position: relative;
            z-index: 1;
            max-width: 400px;
            width: 100%;
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(16px) saturate(180%);
            -webkit-backdrop-filter: blur(16px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.5);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            border-radius: 24px;
            overflow: hidden; /* For header radius */
        }
        .login-header {
            background: transparent; /* Remove solid background */
            color: #003366; /* Dark text for contrast on glass */
            padding: 30px;
            text-align: center;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }
        .form-control {
            background: rgba(255, 255, 255, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.5);
            backdrop-filter: blur(5px);
        }
        .form-control:focus {
            background: rgba(255, 255, 255, 0.8);
            box-shadow: 0 0 0 0.25rem rgba(0, 51, 102, 0.25);
        }
        .input-group-text {
            background: rgba(255, 255, 255, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-right: none;
        }
    </style>
</head>
<body>

<div class="card login-card">
    <div class="login-header">
        <h3 class="fw-bold mb-0">Staff Login</h3>
        <p class="small mb-0 opacity-75">Topaz International School</p>
    </div>
    <div class="card-body p-4">
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <div class="mb-3">
                <label for="username" class="form-label">School Email</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                    <input type="email" class="form-control" id="username" name="username" placeholder="ID@topazschoolminna.com" required>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label text-muted small">Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="fas fa-lock text-muted"></i></span>
                    <input type="password" name="password" class="form-control bg-light border-start-0 ps-0" placeholder="Enter your password" required>
                </div>
                <div class="text-end mt-1">
                    <a href="forgot_password.php" class="small text-muted text-decoration-none">Forgot Password?</a>
                </div>
            </div>
            <div class="d-grid mb-3">
                <button type="submit" class="btn btn-primary py-2 fw-bold">Login</button>
            </div>
            
            <div class="text-center mb-3">
                 <p class="mb-0 text-muted small">Don't have an account? <a href="register.php" class="text-primary text-decoration-none fw-semibold">Register Staff</a></p>
            </div>

            <div class="text-center">
                <a href="index.php" class="text-decoration-none small text-muted"><i class="fas fa-arrow-left me-1"></i> Back to Homepage</a>
            </div>
        </form>
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
