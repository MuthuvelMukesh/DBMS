<?php
require_once '../header.php';

if ($role != 'admin') {
    echo '<div class="alert alert-danger">You do not have permission to access this page.</div>';
    require_once '../footer.php';
    exit();
}

$staff_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($staff_id == 0) {
    header("Location: list.php");
    exit();
}

// Fetch staff details
$stmt = $conn->prepare("SELECT user_id, staff_id, full_name FROM staff WHERE id = ?");
$stmt->bind_param("i", $staff_id);
$stmt->execute();
$result = $stmt->get_result();
$staff = $result->fetch_assoc();
$stmt->close();

if (!$staff) {
    header("Location: list.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    // Delete staff
    $stmt = $conn->prepare("UPDATE staff SET status = 'deleted' WHERE id = ?");
    $stmt->bind_param("i", $staff_id);

    if ($stmt->execute()) {
        // Also deactivate the user account
        if (!empty($staff['user_id'])) {
            $stmt_user = $conn->prepare("UPDATE users SET status = 'inactive' WHERE id = ?");
            $stmt_user->bind_param("i", $staff['user_id']);
            $stmt_user->execute();
            $stmt_user->close();
        }
        $stmt->close();
        header("Location: list.php?success=Staff member deleted successfully");
        exit();
    } else {
        $error = 'Error deleting staff: ' . $stmt->error;
    }
    $stmt->close();
}
?>

<h1 class="page-title"><i class="fas fa-trash"></i> Delete Staff Member</h1>

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
            <p><strong>Staff ID:</strong> <?php echo htmlspecialchars($staff['staff_id']); ?></p>
            <p><strong>Name:</strong> <?php echo htmlspecialchars($staff['full_name']); ?></p>
            <p class="text-muted mb-0">Are you sure you want to delete this staff record?</p>
        </div>

        <form method="POST">
            <input type="hidden" name="confirm_delete" value="1">
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-danger"><i class="fas fa-trash"></i> Yes, Delete Staff Member</button>
                <a href="list.php" class="btn btn-secondary"><i class="fas fa-times"></i> Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once '../footer.php'; ?>
