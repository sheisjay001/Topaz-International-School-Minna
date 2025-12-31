<?php
include 'includes/db.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];

    // Check if email exists
    $stmt = $conn->prepare("SELECT id, full_name FROM students WHERE parent_email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $student = $result->fetch_assoc();
        $token = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Store token
        // Check if password_resets table exists (handled by setup_security.php)
        // We assume it exists.
        $stmt = $conn->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $email, $token, $expires_at);
        
        if ($stmt->execute()) {
            // Send Email (Simulated)
            $reset_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/student_reset_password.php?token=" . $token;
            
            // In a real app, use mail() or PHPMailer
            // mail($email, "Password Reset", "Click here: $reset_link");
            
            $message = "A password reset link has been sent to your email. <br> <small>(Simulated: <a href='$reset_link'>Click here to reset</a>)</small>";
        } else {
            $error = "Error generating reset token.";
        }
    } else {
        // Security: Don't reveal if email exists or not, but for UX we might say "If email exists..."
        $message = "If an account exists with this email, a reset link has been sent.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password | TISM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f5f7fa; height: 100vh; display: flex; align-items: center; justify-content: center; }
        .card { max-width: 400px; width: 100%; border-radius: 15px; border: none; box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
    </style>
</head>
<body>

<div class="card p-4">
    <div class="card-body">
        <h4 class="fw-bold text-center mb-4">Forgot Password?</h4>
        <p class="text-muted text-center small mb-4">Enter your registered email address to receive a password reset link.</p>

        <?php if($message): ?>
            <div class="alert alert-success small"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if($error): ?>
            <div class="alert alert-danger small"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label fw-bold small">Email Address</label>
                <input type="email" name="email" class="form-control" required placeholder="parent@example.com">
            </div>
            <div class="d-grid">
                <button type="submit" class="btn btn-primary">Send Reset Link</button>
            </div>
        </form>

        <div class="text-center mt-3">
            <a href="student_login.php" class="text-decoration-none small text-muted">Back to Login</a>
        </div>
    </div>
</div>

</body>
</html>