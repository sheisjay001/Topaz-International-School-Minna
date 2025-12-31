<?php
include 'includes/db.php';
include 'includes/security.php';
include_once 'includes/validator.php';

$message = '';
$error = '';
$token = $_GET['token'] ?? '';
$valid_token = false;
$user_type = ''; // 'student' or 'user'
$user_id = 0;

if (empty($token)) {
    die("Invalid request.");
}

// Verify Token
$now = date('Y-m-d H:i:s');

// Check Students
$stmt = $conn->prepare("SELECT id FROM students WHERE reset_token = ? AND reset_token_expiry > ?");
$stmt->bind_param("ss", $token, $now);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows > 0) {
    $valid_token = true;
    $user_type = 'student';
    $user_id = $res->fetch_assoc()['id'];
} else {
    // Check Users
    $stmt = $conn->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_token_expiry > ?");
    $stmt->bind_param("ss", $token, $now);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows > 0) {
        $valid_token = true;
        $user_type = 'user';
        $user_id = $res->fetch_assoc()['id'];
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    verify_csrf_token($_POST['csrf_token'] ?? '');
    
    if (!$valid_token) {
        $error = "Invalid or expired token.";
    } else {
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (strlen($password) < 6) {
            $error = "Password must be at least 6 characters.";
        } elseif ($password !== $confirm_password) {
            $error = "Passwords do not match.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            if ($user_type === 'student') {
                $update = $conn->prepare("UPDATE students SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE id = ?");
            } else {
                $update = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE id = ?");
            }
            
            $update->bind_param("si", $hashed_password, $user_id);
            
            if ($update->execute()) {
                $message = "Password reset successfully. You can now login.";
                $valid_token = false; // Prevent reuse
            } else {
                $error = "Error updating password.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | Topaz International School</title>
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
    </style>
</head>
<body>

<div class="login-card text-center">
    <h4 class="mb-4">Reset Password</h4>
    
    <?php if($message): ?>
        <div class="alert alert-success">
            <?php echo $message; ?>
            <div class="mt-2">
                <a href="login.php" class="btn btn-sm btn-outline-success">Go to Login</a>
            </div>
        </div>
    <?php elseif(!$valid_token && !$error): ?>
        <div class="alert alert-danger">Invalid or expired reset link.</div>
        <a href="forgot_password.php" class="btn btn-primary">Request New Link</a>
    <?php else: ?>
        
        <?php if($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            
            <div class="mb-3 text-start">
                <label class="form-label">New Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" name="password" class="form-control" required minlength="6">
                </div>
            </div>

            <div class="mb-3 text-start">
                <label class="form-label">Confirm Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" name="confirm_password" class="form-control" required minlength="6">
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100">
                Reset Password
            </button>
        </form>
        <div class="mt-3">
             <a href="index.php" class="text-decoration-none small text-muted"><i class="fas fa-arrow-left me-1"></i> Back to Homepage</a>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
