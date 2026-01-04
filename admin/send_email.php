<?php
include __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/mailer.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$message = '';
$error = '';
$warning = '';

// Check if SMTP Password is configured
if (defined('SMTP_PASS') && (SMTP_PASS === 'your_smtp_key' || SMTP_PASS === 'YOUR_BREVO_SMTP_KEY_HERE')) {
    $warning = "Warning: SMTP Password is not configured in includes/config.php. Emails will likely fail to send.";
}

// Handle Send Email
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_email'])) {
    $subject = $_POST['subject'];
    $body = $_POST['message'];
    $audience = $_POST['audience'];
    $specific_email = $_POST['specific_email'] ?? '';

    if (empty($subject) || empty($body)) {
        $error = "Subject and Message are required.";
    } else {
        $mailer = new Mailer();
        $recipients = [];

        if ($audience == 'teachers') {
            $stmt = $conn->query("SELECT username as email FROM users WHERE role = 'teacher' AND username LIKE '%@%'");
            while ($row = $stmt->fetch_assoc()) {
                $recipients[] = $row['email'];
            }
        } elseif ($audience == 'students') {
             $stmt = $conn->query("SELECT parent_email as email FROM students WHERE parent_email IS NOT NULL AND parent_email != '' AND parent_email LIKE '%@%'");
             while ($row = $stmt->fetch_assoc()) {
                $recipients[] = $row['email'];
             }
        } elseif ($audience == 'all') {
            // Teachers
            $stmt = $conn->query("SELECT username as email FROM users WHERE role = 'teacher' AND username LIKE '%@%'");
            while ($row = $stmt->fetch_assoc()) {
                $recipients[] = $row['email'];
            }
            // Students
            $stmt = $conn->query("SELECT parent_email as email FROM students WHERE parent_email IS NOT NULL AND parent_email != '' AND parent_email LIKE '%@%'");
             while ($row = $stmt->fetch_assoc()) {
                $recipients[] = $row['email'];
             }
        } elseif ($audience == 'specific') {
            if (!empty($specific_email)) {
                $recipients[] = $specific_email;
            } else {
                $error = "Please enter an email address.";
            }
        }

        if (empty($recipients) && empty($error)) {
            $error = "No recipients found for the selected audience.";
        } elseif (empty($error)) {
            $success_count = 0;
            $fail_count = 0;

            foreach ($recipients as $to) {
                if ($mailer->send($to, $subject, $body)) {
                    $success_count++;
                } else {
                    $fail_count++;
                }
            }

            $message = "Email sending completed. Success: $success_count, Failed: $fail_count.";
            if ($fail_count > 0) {
                 $error = "Some emails failed to send. Check SMTP configuration.";
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
    <title>Send Email | TISM Admin</title>
    <link rel="icon" href="../assets/images/logo.jpg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css?v=<?php echo time(); ?>">
    <!-- TinyMCE for Rich Text Editor -->
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
      tinymce.init({
        selector: '#message',
        plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount',
        toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table | align lineheight | numlist bullist indent outdent | emoticons charmap | removeformat',
      });
    </script>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="#" class="sidebar-brand">
            <i class="fas fa-graduation-cap me-2"></i>TISM ADMIN
        </a>
        <button class="sidebar-close" id="sidebarClose"><i class="fas fa-times"></i></button>
    </div>
    <div class="sidebar-menu">
        <a href="index.php" class="menu-item">
            <i class="fas fa-th-large"></i> Dashboard
        </a>
        <a href="manage_students.php" class="menu-item">
            <i class="fas fa-user-graduate"></i> Manage Students
        </a>
        <a href="manage_teachers.php" class="menu-item">
            <i class="fas fa-chalkboard-teacher"></i> Teachers
        </a>
        <a href="manage_fees.php" class="menu-item">
            <i class="fas fa-money-bill-wave"></i> Fees & Payments
        </a>
        <a href="manage_results.php" class="menu-item">
            <i class="fas fa-chart-bar"></i> Results
        </a>
        <a href="manage_timetable.php" class="menu-item">
            <i class="fas fa-calendar-alt"></i> Timetables
        </a>
        <a href="manage_notifications.php" class="menu-item">
            <i class="fas fa-bell"></i> Notifications
        </a>
        <a href="send_email.php" class="menu-item active">
            <i class="fas fa-envelope"></i> Send Email
        </a>
        <a href="manage_pins.php" class="menu-item">
            <i class="fas fa-key"></i> Scratch Cards
        </a>
        <a href="settings.php" class="menu-item">
            <i class="fas fa-cog"></i> Settings
        </a>
        <a href="../includes/logout.php" class="menu-item mt-5 text-danger">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>

<!-- Main Content -->
<div class="main-content" id="mainContent">
    <!-- Top Bar -->
    <div class="top-bar d-flex justify-content-between align-items-center mb-4">
        <button class="sidebar-toggle btn btn-link" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </button>
        <div class="d-flex align-items-center">
            <span class="me-3">Admin</span>
            <img src="../assets/images/logo.jpg" alt="Profile" class="rounded-circle" width="40" height="40">
        </div>
    </div>

    <div class="container-fluid">
        <h2 class="mb-4">Send Email</h2>

        <?php if ($warning): ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo $warning; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-body">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="audience" class="form-label">Target Audience</label>
                        <select class="form-select" id="audience" name="audience" required onchange="toggleSpecificEmail()">
                            <option value="">Select Audience</option>
                            <option value="teachers">All Teachers</option>
                            <option value="students">All Students (Parents)</option>
                            <option value="all">Everyone (Teachers & Students)</option>
                            <option value="specific">Specific Email</option>
                        </select>
                    </div>

                    <div class="mb-3" id="specific_email_div" style="display: none;">
                        <label for="specific_email" class="form-label">Recipient Email</label>
                        <input type="email" class="form-control" id="specific_email" name="specific_email" placeholder="Enter email address">
                    </div>

                    <div class="mb-3">
                        <label for="subject" class="form-label">Subject</label>
                        <input type="text" class="form-control" id="subject" name="subject" required>
                    </div>

                    <div class="mb-3">
                        <label for="message" class="form-label">Message</label>
                        <textarea class="form-control" id="message" name="message" rows="10"></textarea>
                    </div>

                    <button type="submit" name="send_email" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-2"></i>Send Email
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarClose = document.getElementById('sidebarClose');

    sidebarToggle.addEventListener('click', () => {
        sidebar.classList.add('active');
    });

    sidebarClose.addEventListener('click', () => {
        sidebar.classList.remove('active');
    });

    function toggleSpecificEmail() {
        var audience = document.getElementById('audience').value;
        var specificEmailDiv = document.getElementById('specific_email_div');
        if (audience === 'specific') {
            specificEmailDiv.style.display = 'block';
        } else {
            specificEmailDiv.style.display = 'none';
        }
    }
</script>
</body>
</html>
