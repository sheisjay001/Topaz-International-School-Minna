<?php
include __DIR__ . '/includes/db.php';

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = $_POST['full_name'];
    $staff_id = $_POST['staff_id'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = 'teacher'; // Default role for registration
    
    // Get assigned classes
    $assigned_classes = isset($_POST['assigned_classes']) ? implode(',', $_POST['assigned_classes']) : '';

    // Basic Validation
    if ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Sanitize Staff ID for email generation (remove slashes, spaces)
        $clean_staff_id = preg_replace('/[^a-zA-Z0-9]/', '', $staff_id);
        $email = $clean_staff_id . '@topazschoolminna.com';

        // Check if Staff ID or Email already exists
        $check_sql = "SELECT * FROM users WHERE staff_id = ? OR username = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ss", $staff_id, $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $error = "Staff ID or Email already registered.";
        } else {
            // Hash Password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert Users
            $sql = "INSERT INTO users (username, password, role, full_name, staff_id, assigned_classes) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssss", $email, $hashed_password, $role, $full_name, $staff_id, $assigned_classes);

            if ($stmt->execute()) {
                $success = "Registration successful! Your School Email is: <strong>$email</strong>. <a href='login.php'>Login here</a>";
            } else {
                $error = "Error: " . $conn->error;
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
    <title>Staff Registration | Topaz International School</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            background-color: var(--light-bg);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .register-card {
            max-width: 500px;
            width: 100%;
            border: none;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            border-radius: 15px;
        }
        .register-header {
            background: var(--gradient-red);
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 15px 15px 0 0;
        }
    </style>
</head>
<body>

<div class="card register-card my-5">
    <div class="register-header">
        <h3 class="fw-bold mb-0">Staff Registration</h3>
        <p class="small mb-0 opacity-75">Create your School Account</p>
    </div>
    <div class="card-body p-4">
        <?php if($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php else: ?>
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="full_name" class="form-label">Full Name</label>
                    <input type="text" class="form-control" id="full_name" name="full_name" required>
                </div>
                <div class="mb-3">
                    <label for="staff_id" class="form-label">Staff / Teacher ID</label>
                    <input type="text" class="form-control" id="staff_id" name="staff_id" placeholder="e.g. TISM/ST/001" required>
                    <div class="form-text">Your School Email will be generated from this ID.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Classes Taught (Select all that apply)</label>
                    <div class="row g-2" style="max-height: 200px; overflow-y: auto; border: 1px solid #ced4da; padding: 10px; border-radius: 5px;">
                        <?php
                        $classes = [
                            'Playgroup', 'Nursery 1', 'Nursery 2', 
                            'Primary 1', 'Primary 2', 'Primary 3', 'Primary 4', 'Primary 5', 'Primary 6',
                            'JSS 1', 'JSS 2', 'JSS 3', 
                            'SSS 1', 'SSS 2', 'SSS 3'
                        ];
                        foreach ($classes as $class) {
                            echo '
                            <div class="col-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="assigned_classes[]" value="'.$class.'" id="class_'.str_replace(' ', '_', $class).'">
                                    <label class="form-check-label" for="class_'.str_replace(' ', '_', $class).'">
                                        '.$class.'
                                    </label>
                                </div>
                            </div>';
                        }
                        ?>
                    </div>
                    <div class="form-text">Students from selected classes will be visible in your dashboard.</div>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Create Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>
                <div class="d-grid mb-3">
                    <button type="submit" class="btn btn-primary btn-lg fw-bold">Create Account</button>
                </div>

                <div class="text-center">
                    <p class="mb-2">Already have an account? <a href="login.php" class="fw-bold text-decoration-none">Login Here</a></p>
                    <a href="index.php" class="text-decoration-none small text-muted"><i class="fas fa-arrow-left me-1"></i> Back to Homepage</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
