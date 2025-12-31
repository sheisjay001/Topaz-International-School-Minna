<?php
include __DIR__ . '/includes/db.php';
include_once __DIR__ . '/includes/logger.php';
require_once __DIR__ . '/vendor/autoload.php';

use RobThree\Auth\TwoFactorAuth;

if (!isset($_SESSION['2fa_pending_user_id'])) {
    header("Location: login.php");
    exit();
}

$error = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $code = $_POST['code'];
    $user_id = $_SESSION['2fa_pending_user_id'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    $tfa = new TwoFactorAuth('Topaz International School');
    if ($tfa->verifyCode($user['two_factor_secret'], $code)) {
        // Code is valid, complete login
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['full_name'] = $user['full_name'];
        
        // Clear pending 2FA session
        unset($_SESSION['2fa_pending_user_id']);
        unset($_SESSION['2fa_role']);

        // Log Login
        log_activity($conn, $user['id'], $user['role'], 'Logged in (2FA)');

        if ($user['role'] == 'admin') {
            header("Location: admin/index.php");
        } else {
            header("Location: teacher/index.php");
        }
        exit();
    } else {
        $error = "Invalid verification code.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Two-Factor Authentication | TISM</title>
    <link rel="icon" href="assets/images/logo.jpg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body { background-color: #f4f6f9; display: flex; align-items: center; justify-content: center; height: 100vh; }
        .auth-card { max-width: 400px; width: 100%; padding: 30px; border-radius: 10px; background: #fff; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <div class="auth-card text-center">
        <h3 class="mb-4">Two-Factor Authentication</h3>
        <p class="mb-4">Please enter the code from your authenticator app.</p>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <input type="text" name="code" class="form-control text-center fs-4" placeholder="000000" maxlength="6" required autofocus>
            </div>
            <button type="submit" class="btn btn-primary w-100">Verify</button>
        </form>
        <div class="mt-3">
            <a href="login.php" class="text-decoration-none">Back to Login</a>
        </div>
    </div>
</body>
</html>
