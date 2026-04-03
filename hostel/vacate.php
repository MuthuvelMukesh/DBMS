<?php
require_once '../header.php';

if (!in_array($role, ['admin', 'staff'])) {
    header('Location: ' . BASE_URL . 'dashboard.php?error=Access Denied');
    exit();
}


$error = '';
$success = '';
$assignment = null;
$assignment_id = isset($_GET['assignment_id']) ? (int)$_GET['assignment_id'] : 0;

// Fetch assignment details
if ($assignment_id > 0) {
    $stmt = $conn->prepare("
        SELECT ha.id, ha.join_date, ha.status,
               s.admission_no, s.full_name, c.class_name,
               r.room_no, r.floor, r.room_type
        FROM hostel_assignments ha
        JOIN students s ON ha.student_id = s.id
        JOIN classes c ON s.class_id = c.id
        JOIN hostel_rooms r ON ha.room_id = r.id
        WHERE ha.id = ?
    ");
    $stmt->bind_param("i", $assignment_id);
    $stmt->execute();
    $assignment = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$assignment) {
        $error = 'Assignment not found!';
    } elseif ($assignment['status'] !== 'active') {
        $error = 'This assignment is already inactive!';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'vacate') {
    $assgn_id = isset($_POST['assignment_id']) ? (int)$_POST['assignment_id'] : 0;
    $leave_date = isset($_POST['leave_date']) ? trim($_POST['leave_date']) : '';

    if ($assgn_id <= 0 || empty($leave_date)) {
        $error = 'All fields are required!';
    } else {
        // Verify assignment exists and is active
        $stmt = $conn->prepare("SELECT id, status FROM hostel_assignments WHERE id = ?");
        $stmt->bind_param("i", $assgn_id);
        $stmt->execute();
        $check = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$check) {
            $error = 'Assignment not found!';
        } elseif ($check['status'] !== 'active') {
            $error = 'This assignment is already inactive!';
        } else {
            // Update assignment status to inactive
            $stmt = $conn->prepare("UPDATE hostel_assignments SET status = 'inactive', leave_date = ? WHERE id = ?");
            $stmt->bind_param("si", $leave_date, $assgn_id);

            if ($stmt->execute()) {
                $success = 'Student vacated from hostel successfully!';
                header("Location: list.php");
                exit();
            } else {
                $error = 'Error vacating student: ' . $stmt->error;
            }
            $stmt->close();
        }
    }
}
?>

<h1 class="page-title"><i class="fas fa-sign-out-alt"></i> Vacate Student from Hostel</h1>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (!empty($success)): ?>
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($assignment && $assignment['status'] === 'active'): ?>
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-bottom">
                    <h5 class="mb-0">Confirm Vacation</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="text-muted">Student Information</h6>
                            <p class="mb-2">
                                <strong>Name:</strong> <?php echo htmlspecialchars($assignment['full_name']); ?>
                            </p>
                            <p class="mb-2">
                                <strong>Admission No:</strong> <?php echo htmlspecialchars($assignment['admission_no']); ?>
                            </p>
                            <p class="mb-2">
                                <strong>Class:</strong> <?php echo htmlspecialchars($assignment['class_name']); ?>
                            </p>
                            <p class="mb-2">
                                <strong>Join Date:</strong> <?php echo date('d M Y', strtotime($assignment['join_date'])); ?>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Room Information</h6>
                            <p class="mb-2">
                                <strong>Room No:</strong> <?php echo htmlspecialchars($assignment['room_no']); ?>
                            </p>
                            <p class="mb-2">
                                <strong>Floor:</strong> <?php echo $assignment['floor']; ?>
                            </p>
                            <p class="mb-2">
                                <strong>Room Type:</strong> <?php echo htmlspecialchars($assignment['room_type']); ?>
                            </p>
                            <p class="mb-2">
                                <strong>Status:</strong>
                                <span class="badge bg-success">Active</span>
                            </p>
                        </div>
                    </div>

                    <hr>

                    <form method="POST">
                        <input type="hidden" name="action" value="vacate">
                        <input type="hidden" name="assignment_id" value="<?php echo $assignment['id']; ?>">

                        <div class="mb-3">
                            <label for="leave_date" class="form-label">Leave Date *</label>
                            <input type="date" class="form-control" id="leave_date" name="leave_date" required value="<?php echo date('Y-m-d'); ?>">
                        </div>

                        <div class="alert alert-warning mb-4" role="alert">
                            <i class="fas fa-info-circle"></i> 
                            This action will mark the student as vacated from the hostel room and update their status to inactive.
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-check"></i> Confirm Vacation
                            </button>
                            <a href="list.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php elseif ($assignment): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle"></i> This assignment is already inactive or no longer available.
        <a href="list.php" class="btn btn-sm btn-secondary ms-2">Back to List</a>
    </div>
<?php endif; ?>

<?php require_once '../footer.php'; ?>
