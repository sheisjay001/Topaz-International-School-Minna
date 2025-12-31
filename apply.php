<?php
include 'includes/db.php';
include 'includes/header.php';

$message = '';
$message_type = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitization and Validation would go here
    $child_name = $conn->real_escape_string($_POST['child_name']);
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];
    $parent_name = $conn->real_escape_string($_POST['parent_name']);
    $parent_email = $conn->real_escape_string($_POST['parent_email']);
    $parent_phone = $conn->real_escape_string($_POST['parent_phone']);
    $address = $conn->real_escape_string($_POST['address']);
    $previous_school = $conn->real_escape_string($_POST['previous_school']);
    $class_applied = $_POST['class_applied'];

    $sql = "INSERT INTO applications (child_name, dob, gender, parent_name, parent_email, parent_phone, address, previous_school, class_applied) 
            VALUES ('$child_name', '$dob', '$gender', '$parent_name', '$parent_email', '$parent_phone', '$address', '$previous_school', '$class_applied')";

    if ($conn->query($sql) === TRUE) {
        $message = "Application submitted successfully! The school administration will contact you shortly.";
        $message_type = "success";
    } else {
        $message = "Error: " . $sql . "<br>" . $conn->error;
        $message_type = "danger";
    }
}
?>

<!-- Header Section -->
<header class="bg-primary text-white text-center py-5" style="background: linear-gradient(rgba(0,51,102,0.9), rgba(0,51,102,0.9)), url('assets/img/hero-bg.jpg') center/cover;">
    <div class="container">
        <h1 class="display-4 fw-bold" data-aos="fade-down">Online Admission</h1>
        <p class="lead" data-aos="fade-up">Join the Topaz Family today</p>
    </div>
</header>

<section class="py-5 bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="card shadow-lg border-0 rounded-3">
                    <div class="card-header bg-white p-4 border-bottom">
                        <h4 class="mb-0 fw-bold text-primary"><i class="fas fa-file-alt me-2"></i>Admission Form</h4>
                    </div>
                    <div class="card-body p-4 p-md-5">
                        <form method="POST" action="">
                            
                            <h5 class="text-secondary mb-3 border-bottom pb-2">Student Information</h5>
                            <div class="row g-3 mb-4">
                                <div class="col-md-12">
                                    <label class="form-label fw-bold">Child's Full Name</label>
                                    <input type="text" name="child_name" class="form-control" required placeholder="Surname Firstname Othername">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Date of Birth</label>
                                    <input type="date" name="dob" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Gender</label>
                                    <select name="gender" class="form-select" required>
                                        <option value="">Select...</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                    </select>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label fw-bold">Previous School (if any)</label>
                                    <input type="text" name="previous_school" class="form-control">
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label fw-bold">Class Applying For</label>
                                    <select name="class_applied" class="form-select" required>
                                        <option value="">Select Class...</option>
                                        <option value="Playgroup">Playgroup</option>
                                        <option value="Nursery 1">Nursery 1</option>
                                        <option value="Nursery 2">Nursery 2</option>
                                        <option value="Primary 1">Primary 1</option>
                                        <option value="Primary 2">Primary 2</option>
                                        <option value="Primary 3">Primary 3</option>
                                        <option value="Primary 4">Primary 4</option>
                                        <option value="Primary 5">Primary 5</option>
                                        <option value="Primary 6">Primary 6</option>
                                        <option value="JSS 1">JSS 1</option>
                                        <option value="JSS 2">JSS 2</option>
                                        <option value="JSS 3">JSS 3</option>
                                        <option value="SSS 1">SSS 1</option>
                                        <option value="SSS 2">SSS 2</option>
                                        <option value="SSS 3">SSS 3</option>
                                    </select>
                                </div>
                            </div>

                            <h5 class="text-secondary mb-3 border-bottom pb-2 pt-3">Parent/Guardian Information</h5>
                            <div class="row g-3 mb-4">
                                <div class="col-md-12">
                                    <label class="form-label fw-bold">Parent/Guardian Name</label>
                                    <input type="text" name="parent_name" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Email Address</label>
                                    <input type="email" name="parent_email" class="form-control" placeholder="optional">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Phone Number</label>
                                    <input type="tel" name="parent_phone" class="form-control" required>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label fw-bold">Residential Address</label>
                                    <textarea name="address" class="form-control" rows="3" required></textarea>
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg fw-bold">Submit Application</button>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
