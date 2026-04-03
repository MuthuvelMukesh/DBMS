<?php
require_once '../header.php';

$error = '';
$success = '';

// Get next admission number
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM students");
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$next_admission = 'ADM' . str_pad($row['total'] + 1, 5, '0', STR_PAD_LEFT);
$stmt->close();

// Get classes
$classes_result = $conn->query("SELECT id, CONCAT(class_name, ' - ', section) as class_name FROM classes WHERE status = 'active' ORDER BY class_name");
$classes = $classes_result->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $admission_no = $next_admission;
    $full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
    $dob = isset($_POST['dob']) ? $_POST['dob'] : '';
    $gender = isset($_POST['gender']) ? $_POST['gender'] : '';
    $class_id = isset($_POST['class_id']) ? (int)$_POST['class_id'] : 0;
    $section = isset($_POST['section']) ? trim($_POST['section']) : '';
    $parent_name = isset($_POST['parent_name']) ? trim($_POST['parent_name']) : '';
    $contact = isset($_POST['contact']) ? trim($_POST['contact']) : '';
    $address = isset($_POST['address']) ? trim($_POST['address']) : '';

    if (empty($full_name) || empty($dob) || empty($gender) || empty($class_id) || empty($section) || empty($parent_name) || empty($contact)) {
        $error = 'All required fields must be filled!';
    } else {
        $photo = NULL;
        if (isset($_FILES['photo']) && $_FILES['photo']['size'] > 0) {
            $file_name = $_FILES['photo']['name'];
            $file_tmp = $_FILES['photo']['tmp_name'];
            $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);

            if (in_array(strtolower($file_ext), ['jpg', 'jpeg', 'png', 'gif'])) {
                if ($_FILES['photo']['size'] <= 5 * 1024 * 1024) {
                    $new_filename = 'student_' . time() . '.' . $file_ext;
                    $upload_path = '../uploads/' . $new_filename;
                    if (move_uploaded_file($file_tmp, $upload_path)) {
                        $photo = $new_filename;
                    } else {
                        $error = 'Failed to upload photo. Please try again.';
                    }
                } else {
                    $error = 'Photo size must be less than 5MB.';
                }
            } else {
                $error = 'Only JPG, PNG, and GIF files are allowed.';
            }
        }

        if (empty($error)) {
            $conn->begin_transaction();
            try {
                // 1. Create User Account
                $username = $admission_no;
                // Default password is the admission number
                $default_password = password_hash($username, PASSWORD_DEFAULT);
                $role = 'student';
                
                $stmt_user = $conn->prepare("INSERT INTO users (username, password, role, status) VALUES (?, ?, ?, 'active')");
                $stmt_user->bind_param("sss", $username, $default_password, $role);
                $stmt_user->execute();
                $user_id = $conn->insert_id;
                $stmt_user->close();

                // 2. Create Student Profile
                $stmt = $conn->prepare("
                    INSERT INTO students (admission_no, user_id, full_name, dob, gender, class_id, section, parent_name, contact, address, photo, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')
                ");
                $stmt->bind_param("sisssisssss", $admission_no, $user_id, $full_name, $dob, $gender, $class_id, $section, $parent_name, $contact, $address, $photo);

                if ($stmt->execute()) {
                    $conn->commit();
                    $success = 'Student added successfully! Admission No: ' . $admission_no . ' (Login: ' . $username . ' / Pass: ' . $username . ')';
                    $_POST = [];
                } else {
                    throw new Exception($stmt->error);
                }
                $stmt->close();
            } catch (Exception $e) {
                $conn->rollback();
                $error = 'Error adding student: ' . $e->getMessage();
            }
        }
    }
}
?>

<h1 class="page-title"><i class="fas fa-user-plus"></i> Add New Student</h1>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-light border-bottom">
        <h5 class="mb-0">Student Registration Form</h5>
    </div>
    <div class="card-body">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="full_name" class="form-label">Full Name *</label>
                    <input type="text" class="form-control" id="full_name" name="full_name" required value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="dob" class="form-label">Date of Birth *</label>
                    <input type="date" class="form-control" id="dob" name="dob" required value="<?php echo isset($_POST['dob']) ? htmlspecialchars($_POST['dob']) : ''; ?>">
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="gender" class="form-label">Gender *</label>
                    <select class="form-select" id="gender" name="gender" required>
                        <option value="">Select Gender</option>
                        <option value="Male" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                        <option value="Female" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                        <option value="Other" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="class_id" class="form-label">Class *</label>
                    <select class="form-select" id="class_id" name="class_id" required>
                        <option value="">Select Class</option>
                        <?php foreach ($classes as $class): ?>
                            <option value="<?php echo $class['id']; ?>" <?php echo (isset($_POST['class_id']) && $_POST['class_id'] == $class['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($class['class_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="section" class="form-label">Section *</label>
                    <input type="text" class="form-control" id="section" name="section" placeholder="e.g., A, B, C" required value="<?php echo isset($_POST['section']) ? htmlspecialchars($_POST['section']) : ''; ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="parent_name" class="form-label">Parent Name *</label>
                    <input type="text" class="form-control" id="parent_name" name="parent_name" required value="<?php echo isset($_POST['parent_name']) ? htmlspecialchars($_POST['parent_name']) : ''; ?>">
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="contact" class="form-label">Contact Number *</label>
                    <input type="tel" class="form-control" id="contact" name="contact" pattern="[0-9]{10}" placeholder="10-digit mobile number" required value="<?php echo isset($_POST['contact']) ? htmlspecialchars($_POST['contact']) : ''; ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="photo" class="form-label">Photo (Optional)</label>
                    <input type="file" class="form-control" id="photo" name="photo" accept="image/*">
                    <small class="text-muted">JPG, PNG, GIF - Max 5MB</small>
                </div>
            </div>

            <div class="mb-3">
                <label for="address" class="form-label">Address</label>
                <textarea class="form-control" id="address" name="address" rows="3"><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Add Student</button>
                <a href="list.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
            </div>
        </form>
    </div>
</div>

<?php require_once '../footer.php'; ?>
