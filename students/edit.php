<?php
require_once '../header.php';

$error = '';
$success = '';
$student_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($student_id == 0) {
    header("Location: list.php");
    exit();
}

// Fetch student details
$stmt = $conn->prepare("SELECT * FROM students WHERE id = ? AND status != 'deleted'");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
$stmt->close();

if (!$student) {
    header("Location: list.php");
    exit();
}

// Get classes
$classes_result = $conn->query("SELECT id, CONCAT(class_name, ' - ', section) as class_name FROM classes WHERE status = 'active' ORDER BY class_name");
$classes = $classes_result->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
    $dob = isset($_POST['dob']) ? $_POST['dob'] : '';
    $gender = isset($_POST['gender']) ? $_POST['gender'] : '';
    $class_id = isset($_POST['class_id']) ? (int)$_POST['class_id'] : 0;
    $section = isset($_POST['section']) ? trim($_POST['section']) : '';
    $parent_name = isset($_POST['parent_name']) ? trim($_POST['parent_name']) : '';
    $contact = isset($_POST['contact']) ? trim($_POST['contact']) : '';
    $address = isset($_POST['address']) ? trim($_POST['address']) : '';
    $status = isset($_POST['status']) ? $_POST['status'] : 'active';

    if (empty($full_name) || empty($dob) || empty($gender) || empty($class_id) || empty($section) || empty($parent_name) || empty($contact)) {
        $error = 'All required fields must be filled!';
    } else {
        $photo = $student['photo'];
        if (isset($_FILES['photo']) && $_FILES['photo']['size'] > 0) {
            $file_name = $_FILES['photo']['name'];
            $file_tmp = $_FILES['photo']['tmp_name'];
            $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);

            if (in_array(strtolower($file_ext), ['jpg', 'jpeg', 'png', 'gif'])) {
                if ($_FILES['photo']['size'] <= 5 * 1024 * 1024) {
                    if (!empty($student['photo'])) {
                        $old_file = '../uploads/' . $student['photo'];
                        if (file_exists($old_file)) {
                            unlink($old_file);
                        }
                    }
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
            $stmt = $conn->prepare("
                UPDATE students 
                SET full_name = ?, dob = ?, gender = ?, class_id = ?, section = ?, parent_name = ?, contact = ?, address = ?, photo = ?, status = ?
                WHERE id = ?
            ");
            $stmt->bind_param("sssissssssi", $full_name, $dob, $gender, $class_id, $section, $parent_name, $contact, $address, $photo, $status, $student_id);

            if ($stmt->execute()) {
                $success = 'Student updated successfully!';
                $student = [
                    'id' => $student_id,
                    'admission_no' => $student['admission_no'],
                    'full_name' => $full_name,
                    'dob' => $dob,
                    'gender' => $gender,
                    'class_id' => $class_id,
                    'section' => $section,
                    'parent_name' => $parent_name,
                    'contact' => $contact,
                    'address' => $address,
                    'photo' => $photo,
                    'status' => $status
                ];
            } else {
                $error = 'Error updating student: ' . $stmt->error;
            }
            $stmt->close();
        }
    }
}
?>

<h1 class="page-title"><i class="fas fa-edit"></i> Edit Student</h1>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-light border-bottom">
        <h5 class="mb-0">Student Details - Admission No: <?php echo htmlspecialchars($student['admission_no']); ?></h5>
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
                    <input type="text" class="form-control" id="full_name" name="full_name" required value="<?php echo htmlspecialchars($student['full_name']); ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="dob" class="form-label">Date of Birth *</label>
                    <input type="date" class="form-control" id="dob" name="dob" required value="<?php echo htmlspecialchars($student['dob']); ?>">
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="gender" class="form-label">Gender *</label>
                    <select class="form-select" id="gender" name="gender" required>
                        <option value="Male" <?php echo ($student['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                        <option value="Female" <?php echo ($student['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                        <option value="Other" <?php echo ($student['gender'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="class_id" class="form-label">Class *</label>
                    <select class="form-select" id="class_id" name="class_id" required>
                        <option value="">Select Class</option>
                        <?php foreach ($classes as $class): ?>
                            <option value="<?php echo $class['id']; ?>" <?php echo ($class['id'] == $student['class_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($class['class_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="section" class="form-label">Section *</label>
                    <input type="text" class="form-control" id="section" name="section" required value="<?php echo htmlspecialchars($student['section']); ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="parent_name" class="form-label">Parent Name *</label>
                    <input type="text" class="form-control" id="parent_name" name="parent_name" required value="<?php echo htmlspecialchars($student['parent_name']); ?>">
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="contact" class="form-label">Contact Number *</label>
                    <input type="tel" class="form-control" id="contact" name="contact" pattern="[0-9]{10}" required value="<?php echo htmlspecialchars($student['contact']); ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="active" <?php echo ($student['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo ($student['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                        <option value="passed_out" <?php echo ($student['status'] == 'passed_out') ? 'selected' : ''; ?>>Passed Out</option>
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label for="address" class="form-label">Address</label>
                <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($student['address'] ?? ''); ?></textarea>
            </div>

            <div class="mb-3">
                <label for="photo" class="form-label">Photo</label>
                <div class="row">
                    <div class="col-md-6">
                        <input type="file" class="form-control" id="photo" name="photo" accept="image/*">
                        <small class="text-muted">Leave blank to keep current photo</small>
                    </div>
                    <div class="col-md-6">
                        <?php if (!empty($student['photo'])): ?>
                            <img src="../uploads/<?php echo htmlspecialchars($student['photo']); ?>" alt="Student Photo" style="max-width: 150px; height: auto; border-radius: 5px;">
                        <?php else: ?>
                            <p class="text-muted">No photo uploaded</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Student</button>
                <a href="list.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
            </div>
        </form>
    </div>
</div>

<?php require_once '../footer.php'; ?>
