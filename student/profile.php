<?php
include __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['student_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../student_login.php");
    exit();
}

$student_id = $_SESSION['student_id'];

// CSRF Protection
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = '';
$error = '';

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate CSRF Token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Security Check Failed: Invalid CSRF Token.");
    }

    if (isset($_POST['update_profile'])) {
        $gender = $_POST['gender'];
        $dob = $_POST['dob'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];

        $update_stmt = $conn->prepare("UPDATE students SET gender = ?, dob = ?, parent_email = ?, parent_phone = ? WHERE id = ?");
        $update_stmt->bind_param("ssssi", $gender, $dob, $email, $phone, $student_id);

        if ($update_stmt->execute()) {
            $message = "Profile updated successfully!";
        } else {
            $error = "Error updating profile: " . $conn->error;
        }
    }
    elseif (isset($_POST['change_password'])) {
        $current_pass = $_POST['current_password'];
        $new_pass = $_POST['new_password'];
        $confirm_pass = $_POST['confirm_password'];

        // Verify current password
        $stmt = $conn->prepare("SELECT password FROM students WHERE id = ?");
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        
        if (password_verify($current_pass, $res['password'])) {
            if ($new_pass === $confirm_pass) {
                if (strlen($new_pass) >= 6) {
                    $hashed_pass = password_hash($new_pass, PASSWORD_DEFAULT);
                    $upd = $conn->prepare("UPDATE students SET password = ? WHERE id = ?");
                    $upd->bind_param("si", $hashed_pass, $student_id);
                    if ($upd->execute()) {
                        $message = "Password changed successfully!";
                    } else {
                        $error = "Error changing password.";
                    }
                } else {
                    $error = "New password must be at least 6 characters long.";
                }
            } else {
                $error = "New passwords do not match.";
            }
        } else {
            $error = "Current password is incorrect.";
        }
    }
    
}

// Fetch Student Details
$stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if (!$student) {
    die("Student record not found.");
}
?>

<?php
$page_title = 'My Profile';
$extra_css = '<style>.profile-img-large { width: 150px; height: 150px; object-fit: cover; border: 5px solid #fff; box-shadow: 0 0 15px rgba(0,0,0,0.1); }</style>';
include 'includes/header.php';
?>

<!-- Sidebar -->
<?php include 'includes/sidebar.php'; ?>

<!-- Main Content -->
<div class="main-content" id="main-content">
    <!-- Topbar -->
    <?php include 'includes/topbar.php'; ?>

        <?php if($message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i> <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-4 mb-4">
                <div class="card border-0 shadow-sm text-center p-4 h-100">
                    <div class="mb-3 position-relative d-inline-block">
                        <?php 
                        $photoPath = $student['photo'];
                        if ($photoPath && !strpos($photoPath, '/')) {
                            // Legacy path support
                            $photoPath = 'uploads/photos/' . $photoPath;
                        }
                        ?>
                        <?php if($photoPath): ?>
                            <img src="../<?php echo $photoPath; ?>" class="rounded-circle profile-img-large" alt="Student Photo">
                        <?php else: ?>
                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($student['full_name']); ?>&background=0D8ABC&color=fff&size=150" class="rounded-circle profile-img-large" alt="Student Photo">
                        <?php endif; ?>
                    </div>
                    
                    <h4 class="fw-bold mt-2"><?php echo htmlspecialchars($student['full_name']); ?></h4>
                    <p class="text-muted mb-1"><?php echo htmlspecialchars($student['admission_no']); ?></p>
                    <span class="badge bg-primary px-3 py-2"><?php echo htmlspecialchars($student['class']); ?></span>
                    
                    <hr class="my-4">
                    
                    <button class="btn btn-outline-primary w-100 mb-2" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                        <i class="fas fa-user-edit me-2"></i> Edit Details
                    </button>
                    <button class="btn btn-outline-danger w-100 mb-2" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                        <i class="fas fa-key me-2"></i> Change Password
                    </button>
                    <a href="download_admission_letter.php" target="_blank" class="btn btn-success w-100">
                        <i class="fas fa-file-download me-2"></i> Download Admission Letter
                    </a>
                </div>
            </div>

            <div class="col-lg-8 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white py-3">
                        <h5 class="fw-bold mb-0 text-primary">Personal Information</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="text-muted small text-uppercase fw-bold">Full Name</label>
                                <p class="fw-bold fs-5"><?php echo htmlspecialchars($student['full_name']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small text-uppercase fw-bold">Admission Number</label>
                                <p class="fw-bold fs-5"><?php echo htmlspecialchars($student['admission_no']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small text-uppercase fw-bold">Gender</label>
                                <p class="fw-bold fs-5"><?php echo ucfirst(htmlspecialchars($student['gender'] ?? 'N/A')); ?></p>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small text-uppercase fw-bold">Date of Birth</label>
                                <p class="fw-bold fs-5"><?php echo $student['dob'] ? date('F d, Y', strtotime($student['dob'])) : 'N/A'; ?></p>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small text-uppercase fw-bold">Email Address</label>
                                <p class="fw-bold fs-5"><?php echo htmlspecialchars($student['parent_email'] ?? 'N/A'); ?></p>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small text-uppercase fw-bold">Phone Number</label>
                                <p class="fw-bold fs-5"><?php echo htmlspecialchars($student['parent_phone'] ?? 'N/A'); ?></p>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small text-uppercase fw-bold">Current Class</label>
                                <p class="fw-bold fs-5"><?php echo htmlspecialchars($student['class']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small text-uppercase fw-bold">Account Status</label>
                                <p><span class="badge bg-success">Active</span></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<!-- Edit Profile Modal -->
<div class="modal fade" id="editProfileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Edit Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="update_profile" value="1">
                    
                    <div class="mb-3">
                        <label class="form-label">Gender</label>
                        <select name="gender" class="form-select" required>
                            <option value="">Select Gender</option>
                            <option value="Male" <?php echo ($student['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo ($student['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Date of Birth</label>
                        <input type="date" name="dob" class="form-control" value="<?php echo $student['dob']; ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($student['parent_email'] ?? ''); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Phone Number</label>
                        <input type="tel" name="phone" class="form-control" value="<?php echo htmlspecialchars($student['parent_phone'] ?? ''); ?>" required>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Change Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="change_password" value="1">
                    
                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" id="new_password" class="form-control" minlength="6" required>
                        <div class="progress mt-2" style="height: 5px;">
                            <div id="password-strength-bar" class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <small id="password-strength-text" class="form-text text-muted">Enter a strong password.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control" minlength="6" required>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-danger">Update Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const passwordInput = document.getElementById('new_password');
    const strengthBar = document.getElementById('password-strength-bar');
    const strengthText = document.getElementById('password-strength-text');

    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            const password = passwordInput.value;
            let strength = 0;
            let status = '';
            let colorClass = '';
            let width = '0%';

            if (password.length === 0) {
                strengthBar.style.width = '0%';
                strengthText.textContent = 'Enter a strong password.';
                return;
            }

            // Strength Logic
            if (password.length >= 6) strength += 1;
            if (password.length >= 10) strength += 1;
            if (/[A-Z]/.test(password)) strength += 1;
            if (/[0-9]/.test(password)) strength += 1;
            if (/[^A-Za-z0-9]/.test(password)) strength += 1;

            switch (strength) {
                case 0:
                case 1:
                case 2:
                    status = 'Weak';
                    colorClass = 'bg-danger';
                    width = '33%';
                    break;
                case 3:
                case 4:
                    status = 'Medium';
                    colorClass = 'bg-warning';
                    width = '66%';
                    break;
                case 5:
                    status = 'Strong';
                    colorClass = 'bg-success';
                    width = '100%';
                    break;
            }

            strengthBar.style.width = width;
            strengthBar.className = 'progress-bar ' + colorClass;
            strengthText.textContent = 'Strength: ' + status;
        });
    }
});
</script>
</body>
</html>
