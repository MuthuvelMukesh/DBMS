<?php
require_once '../header.php';
if (!in_array($role, ['admin'])) {
    header('Location: ' . BASE_URL . 'dashboard.php?error=Access Denied');
    exit();
}


$error = '';
$success = '';

// Get classes
$classes_result = $conn->query("SELECT id, CONCAT(class_name, ' - ', section) as class_name FROM classes WHERE status = 'active' ORDER BY class_name");
$classes = $classes_result->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $exam_name = isset($_POST['exam_name']) ? trim($_POST['exam_name']) : '';
    $class_id = isset($_POST['class_id']) ? (int)$_POST['class_id'] : 0;
    $subject = isset($_POST['subject']) ? trim($_POST['subject']) : '';
    $exam_date = isset($_POST['exam_date']) ? $_POST['exam_date'] : '';
    $max_marks = isset($_POST['max_marks']) ? (int)$_POST['max_marks'] : 0;
    $pass_marks = isset($_POST['pass_marks']) ? (int)$_POST['pass_marks'] : 0;

    if (empty($exam_name) || $class_id == 0 || empty($subject) || empty($exam_date) || $max_marks <= 0 || $pass_marks <= 0) {
        $error = 'All fields are required and marks must be greater than 0!';
    } else if ($pass_marks > $max_marks) {
        $error = 'Pass marks cannot be greater than maximum marks!';
    } else {
        $stmt = $conn->prepare("
            INSERT INTO exams (exam_name, class_id, subject, exam_date, max_marks, pass_marks, status)
            VALUES (?, ?, ?, ?, ?, ?, 'scheduled')
        ");
        $stmt->bind_param("sisiis", $exam_name, $class_id, $subject, $exam_date, $max_marks, $pass_marks);

        if ($stmt->execute()) {
            $success = 'Exam added successfully!';
            $_POST = [];
        } else {
            $error = 'Error adding exam: ' . $stmt->error;
        }
        $stmt->close();
    }
}
?>

<h1 class="page-title"><i class="fas fa-plus-circle"></i> Add New Exam</h1>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-light border-bottom">
        <h5 class="mb-0">Exam Registration Form</h5>
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

        <form method="POST">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="exam_name" class="form-label">Exam Name *</label>
                    <input type="text" class="form-control" id="exam_name" name="exam_name" placeholder="e.g., Final Exam, Mid-term" required value="<?php echo isset($_POST['exam_name']) ? htmlspecialchars($_POST['exam_name']) : ''; ?>">
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
                    <label for="subject" class="form-label">Subject *</label>
                    <input type="text" class="form-control" id="subject" name="subject" placeholder="e.g., Mathematics, English" required value="<?php echo isset($_POST['subject']) ? htmlspecialchars($_POST['subject']) : ''; ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="exam_date" class="form-label">Exam Date *</label>
                    <input type="date" class="form-control" id="exam_date" name="exam_date" required value="<?php echo isset($_POST['exam_date']) ? htmlspecialchars($_POST['exam_date']) : ''; ?>">
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="max_marks" class="form-label">Maximum Marks *</label>
                    <input type="number" class="form-control" id="max_marks" name="max_marks" min="1" placeholder="e.g., 100" required value="<?php echo isset($_POST['max_marks']) ? htmlspecialchars($_POST['max_marks']) : ''; ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="pass_marks" class="form-label">Pass Marks *</label>
                    <input type="number" class="form-control" id="pass_marks" name="pass_marks" min="1" placeholder="e.g., 40" required value="<?php echo isset($_POST['pass_marks']) ? htmlspecialchars($_POST['pass_marks']) : ''; ?>">
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Add Exam</button>
                <a href="list.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
            </div>
        </form>
    </div>
</div>

<?php require_once '../footer.php'; ?>
