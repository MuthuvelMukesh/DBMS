<?php
require_once '../header.php';
if (!in_array($role, ['admin'])) {
    header('Location: ' . BASE_URL . 'dashboard.php?error=Access Denied');
    exit();
}


$error = '';
$success = '';

// Get all students
$students_result = $conn->query("SELECT id, admission_no, CONCAT(full_name, ' (', admission_no, ')') as display_name FROM students WHERE status = 'active' ORDER BY full_name");
$students = $students_result->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = isset($_POST['student_id']) ? (int)$_POST['student_id'] : 0;
    $fee_type = isset($_POST['fee_type']) ? trim($_POST['fee_type']) : '';
    $amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0;
    $due_date = isset($_POST['due_date']) ? $_POST['due_date'] : '';

    if ($student_id == 0 || empty($fee_type) || $amount <= 0 || empty($due_date)) {
        $error = 'All fields are required and amount must be greater than 0!';
    } else {
        $stmt = $conn->prepare("
            INSERT INTO fees (student_id, fee_type, amount, due_date, payment_status)
            VALUES (?, ?, ?, ?, 'Pending')
        ");
        $stmt->bind_param("isds", $student_id, $fee_type, $amount, $due_date);

        if ($stmt->execute()) {
            $success = 'Fee added successfully!';
            $_POST = [];
        } else {
            $error = 'Error adding fee: ' . $stmt->error;
        }
        $stmt->close();
    }
}
?>

<h1 class="page-title"><i class="fas fa-plus-circle"></i> Add New Fee</h1>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-light border-bottom">
        <h5 class="mb-0">Add Fee Record</h5>
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
                    <label for="student_id" class="form-label">Student *</label>
                    <select class="form-select" id="student_id" name="student_id" required>
                        <option value="">Select Student</option>
                        <?php foreach ($students as $student): ?>
                            <option value="<?php echo $student['id']; ?>" <?php echo (isset($_POST['student_id']) && $_POST['student_id'] == $student['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($student['display_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="fee_type" class="form-label">Fee Type *</label>
                    <input type="text" class="form-control" id="fee_type" name="fee_type" placeholder="e.g., Tuition, Transport, Hostel" required value="<?php echo isset($_POST['fee_type']) ? htmlspecialchars($_POST['fee_type']) : ''; ?>">
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="amount" class="form-label">Amount *</label>
                    <input type="number" class="form-control" id="amount" name="amount" step="0.01" min="0" placeholder="0.00" required value="<?php echo isset($_POST['amount']) ? htmlspecialchars($_POST['amount']) : ''; ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="due_date" class="form-label">Due Date *</label>
                    <input type="date" class="form-control" id="due_date" name="due_date" required value="<?php echo isset($_POST['due_date']) ? htmlspecialchars($_POST['due_date']) : ''; ?>">
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Add Fee</button>
                <a href="list.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
            </div>
        </form>
    </div>
</div>

<?php require_once '../footer.php'; ?>
