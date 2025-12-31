<?php
include_once 'includes/db.php';
include_once 'includes/security.php';
include_once 'includes/mailer.php';
include_once 'includes/validator.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    verify_csrf_token($_POST['csrf_token'] ?? '');
    
    $email = $_POST['email'];
    $validator = new Validator($_POST);
    $validator->required('email')->email('email');

    if ($validator->isValid()) {
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
        $found = false;

        // Check Students
        $stmt = $conn->prepare("SELECT id FROM students WHERE parent_email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $update = $conn->prepare("UPDATE students SET reset_token = ?, reset_token_expiry = ? WHERE parent_email = ?");
            $update->bind_param("sss", $token, $expiry, $email);
            $update->execute();
            $found = true;
        }

        // Check Users (Teachers/Admin)
        if (!$found) {
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $update = $conn->prepare("UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE username = ?");
                $update->bind_param("sss", $token, $expiry, $email);
                $update->execute();
                $found = true;
            }
        }

        if ($found) {
            $mailer = new Mailer();
            $reset_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset_password.php?token=$token";
            $subject = "Password Reset Request";
            $body = "
                <h2>Password Reset</h2>
                <p>Click the link below to reset your password:</p>
                <p><a href='$reset_link'>$reset_link</a></p>
                <p>This link will expire in 1 hour.</p>
                <p>If you did not request this, please ignore this email.</p>
            ";
            
            if ($mailer->send($email, $subject, $body)) {
                $message = "A password reset link has been sent to your email.";
            } else {
                $error = "Failed to send email. Please try again later.";
            }
        } else {
            // For security, don't reveal if email exists
            $message = "If this email is registered, a password reset link has been sent.";
        }
    } else {
        $error = "Please enter a valid email address.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | Topaz International School</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }
        .brand-logo {
            width: 80px;
            height: 80px;
            object-fit: contain;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>

<div class="login-card text-center">
    <img src="assets/images/logo.jpg" alt="Logo" class="brand-logo">
    <h4 class="mb-4">Forgot Password</h4>
    
    <?php if($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>
    <?php if($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
        
        <div class="mb-3 text-start">
            <label class="form-label">Email Address</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                <input type="email" name="email" class="form-control" placeholder="Enter your registered email" required>
            </div>
        </div>

        <button type="submit" class="btn btn-primary w-100 mb-3">
            Send Reset Link
        </button>
        
        <div class="d-flex justify-content-between mb-3">
            <a href="login.php" class="text-decoration-none small">Admin Login</a>
            <a href="student_login.php" class="text-decoration-none small">Student Login</a>
        </div>
        <a href="index.php" class="text-decoration-none small text-muted"><i class="fas fa-arrow-left me-1"></i> Back to Homepage</a>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
