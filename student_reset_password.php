<?php
include 'includes/db.php';

$message = '';
$error = '';
$token = $_GET['token'] ?? '';

if (!$token) {
    die("Invalid request.");
}

// Verify Token
$stmt = $conn->prepare("SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW()");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Invalid or expired token.");
}

$reset_request = $result->fetch_assoc();
$email = $reset_request['email'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    if ($new_pass === $confirm_pass) {
        if (strlen($new_pass) >= 6) {
            $hashed_pass = password_hash($new_pass, PASSWORD_DEFAULT);
            
            // Update Password
            $upd = $conn->prepare("UPDATE students SET password = ? WHERE parent_email = ?");
            $upd->bind_param("ss", $hashed_pass, $email);
            
            if ($upd->execute()) {
                // Delete token
                $del = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
                $del->bind_param("s", $email);
                $del->execute();
                
                $message = "Password reset successfully! You can now <a href='student_login.php'>Login</a>.";
            } else {
                $error = "Error updating password.";
            }
        } else {
            $error = "Password must be at least 6 characters.";
        }
    } else {
        $error = "Passwords do not match.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password | TISM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f5f7fa; height: 100vh; display: flex; align-items: center; justify-content: center; }
        .card { max-width: 400px; width: 100%; border-radius: 15px; border: none; box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
    </style>
</head>
<body>

<div class="card p-4">
    <div class="card-body">
        <h4 class="fw-bold text-center mb-4">Reset Password</h4>

        <?php if($message): ?>
            <div class="alert alert-success small"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if($error): ?>
            <div class="alert alert-danger small"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if(empty($message)): ?>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label fw-bold small">New Password</label>
                <input type="password" name="new_password" class="form-control" required minlength="6">
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold small">Confirm Password</label>
                <input type="password" name="confirm_password" class="form-control" required minlength="6">
            </div>
            <div class="d-grid">
                <button type="submit" class="btn btn-primary">Reset Password</button>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>

</body>
</html>