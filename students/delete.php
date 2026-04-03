<?php
require_once '../header.php';
if ($role !== 'admin') {
    header('Location: ' . BASE_URL . 'dashboard.php?error=Access Denied');
    exit();
}


$student_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($student_id == 0) {
    header("Location: list.php");
    exit();
}

// Fetch student details
$stmt = $conn->prepare("SELECT user_id, admission_no, full_name, photo FROM students WHERE id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
$stmt->close();

if (!$student) {
    header("Location: list.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    // Delete student
    $stmt = $conn->prepare("UPDATE students SET status = 'deleted' WHERE id = ?");
    $stmt->bind_param("i", $student_id);

    if ($stmt->execute()) {
        // Also deactivate the user account
        if (!empty($student['user_id'])) {
            $stmt_user = $conn->prepare("UPDATE users SET status = 'inactive' WHERE id = ?");
            $stmt_user->bind_param("i", $student['user_id']);
            $stmt_user->execute();
            $stmt_user->close();
        }

        // Delete photo if exists
        if (!empty($student['photo'])) {
            $photo_path = '../uploads/' . $student['photo'];
            if (file_exists($photo_path)) {
                unlink($photo_path);
            }
        }
        $stmt->close();
        header("Location: list.php?success=Student deleted successfully");
        exit();
    } else {
        $error = 'Error deleting student: ' . $stmt->error;
    }
    $stmt->close();
}
?>

<h1 class="page-title"><i class="fas fa-trash"></i> Delete Student</h1>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-light border-bottom">
        <h5 class="mb-0">Confirm Deletion</h5>
    </div>
    <div class="card-body">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i> <strong>Warning!</strong> This action cannot be undone.
        </div>

        <div class="card bg-light border-0 p-3 mb-4">
            <p><strong>Admission No:</strong> <?php echo htmlspecialchars($student['admission_no']); ?></p>
            <p><strong>Name:</strong> <?php echo htmlspecialchars($student['full_name']); ?></p>
            <p class="text-muted mb-0">Are you sure you want to delete this student record?</p>
        </div>

        <form method="POST">
            <input type="hidden" name="confirm_delete" value="1">
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-danger"><i class="fas fa-trash"></i> Yes, Delete Student</button>
                <a href="list.php" class="btn btn-secondary"><i class="fas fa-times"></i> Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once '../footer.php'; ?>
