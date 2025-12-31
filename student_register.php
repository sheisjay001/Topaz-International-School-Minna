<?php
include 'includes/db.php';

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $admission_no = $_POST['admission_no'];
    $full_name = $_POST['full_name'];
    $class = $_POST['class'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Basic Validation
    if ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Check if Admission Number already exists
        $stmt = $conn->prepare("SELECT id, full_name FROM students WHERE admission_no = ?");
        $stmt->bind_param("s", $admission_no);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Student exists (Uploaded by Admin)
            $student = $result->fetch_assoc();
            
            // Optional: Verify name matches roughly to prevent hijacking? 
            // For now, we assume if they know the Admission No, they can claim it. 
            // Better: Verify exact name match or ask for DOB if available.
            // Let's implement Name Verification (Case Insensitive)
            if (strcasecmp($student['full_name'], $full_name) == 0) {
                // Name matches, Update Password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $update = $conn->prepare("UPDATE students SET password = ? WHERE id = ?");
                $update->bind_param("si", $hashed_password, $student['id']);
                
                if ($update->execute()) {
                    $success = "Account activated successfully! You can now <a href='student_login.php'>login</a>.";
                } else {
                    $error = "Error updating account.";
                }
            } else {
                $error = "Admission Number exists, but the Name provided does not match our records. Please contact Admin.";
            }
        } else {
            // Student does NOT exist. Create new record?
            // User requirement: "Students should be able to register first"
            // We assume this enables self-registration for new/missing students.
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $insert = $conn->prepare("INSERT INTO students (admission_no, full_name, class, password) VALUES (?, ?, ?, ?)");
            $insert->bind_param("ssss", $admission_no, $full_name, $class, $hashed_password);
            
            if ($insert->execute()) {
                $success = "Registration successful! You can now <a href='student_login.php'>login</a>.";
            } else {
                $error = "Error registering student: " . $conn->error;
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
    <title>Student Registration | TISM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .register-card {
            max-width: 500px;
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

<div class="card register-card p-4 my-5">
    <div class="brand-logo">
        <i class="fas fa-user-plus"></i>
    </div>
    <div class="card-body">
        <h3 class="text-center fw-bold mb-4">Student Registration</h3>
        
        <?php if($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php else: ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Admission Number</label>
                <input type="text" name="admission_no" class="form-control" required placeholder="Enter your Admission No">
                <div class="form-text">This will be your Login ID.</div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Full Name</label>
                <input type="text" name="full_name" class="form-control" required placeholder="Surname Firstname">
            </div>

            <div class="mb-3">
                <label class="form-label">Class</label>
                <select name="class" class="form-select" required>
                    <option value="">Select Class...</option>
                    <option value="JSS 1">JSS 1</option>
                    <option value="JSS 2">JSS 2</option>
                    <option value="JSS 3">JSS 3</option>
                    <option value="SSS 1">SSS 1</option>
                    <option value="SSS 2">SSS 2</option>
                    <option value="SSS 3">SSS 3</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Create Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Confirm Password</label>
                <input type="password" name="confirm_password" class="form-control" required>
            </div>

            <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">Register / Activate</button>
                            <a href="student_login.php" class="btn btn-outline-secondary">Back to Login</a>
                            <a href="index.php" class="btn btn-link text-decoration-none text-muted">Back to Homepage</a>
                        </div>
        </form>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>