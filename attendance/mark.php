<?php
require_once '../header.php';
if (!in_array($role, ['admin', 'teacher'])) {
    header('Location: ' . BASE_URL . 'dashboard.php?error=Access Denied');
    exit();
}


$error = '';
$success = '';
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$selected_class = isset($_GET['class']) ? (int)$_GET['class'] : 0;

// Get classes based on role
if ($role === 'teacher') {
    $stmt_classes = $conn->prepare("SELECT id, CONCAT(class_name, ' - ', section) as class_name FROM classes WHERE status = 'active' AND class_teacher_id = ? ORDER BY class_name");
    $stmt_classes->bind_param("i", $_SESSION['user_id']);
    $stmt_classes->execute();
    $classes_result = $stmt_classes->get_result();
    $classes = $classes_result->fetch_all(MYSQLI_ASSOC);
    $stmt_classes->close();
} else {
    $classes_result = $conn->query("SELECT id, CONCAT(class_name, ' - ', section) as class_name FROM classes WHERE status = 'active' ORDER BY class_name");
    $classes = $classes_result->fetch_all(MYSQLI_ASSOC);
}

$students = [];
if ($selected_class > 0) {
    // Get students for selected class
    $stmt = $conn->prepare("SELECT id, admission_no, full_name FROM students WHERE class_id = ? AND status = 'active' ORDER BY full_name");
    $stmt->bind_param("i", $selected_class);
    $stmt->execute();
    $students_result = $stmt->get_result();
    $students = $students_result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Get existing attendance for this date
    $stmt = $conn->prepare("SELECT student_id, status FROM attendance WHERE class_id = ? AND date = ?");
    $stmt->bind_param("is", $selected_class, $selected_date);
    $stmt->execute();
    $attendance_result = $stmt->get_result();
    $existing_attendance = [];
    while ($row = $attendance_result->fetch_assoc()) {
        $existing_attendance[$row['student_id']] = $row['status'];
    }
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mark_date = isset($_POST['mark_date']) ? $_POST['mark_date'] : '';
    $class_id = isset($_POST['class_id']) ? (int)$_POST['class_id'] : 0;

    if (empty($mark_date) || $class_id == 0) {
        $error = 'Please select both date and class!';
    } else {
        // First delete existing attendance for this date and class
        $stmt = $conn->prepare("DELETE FROM attendance WHERE class_id = ? AND date = ?");
        $stmt->bind_param("is", $class_id, $mark_date);
        $stmt->execute();
        $stmt->close();

        // Get students for this class
        $stmt = $conn->prepare("SELECT id FROM students WHERE class_id = ? AND status = 'active'");
        $stmt->bind_param("i", $class_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $class_students = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Insert attendance for each student
        $stmt = $conn->prepare("INSERT INTO attendance (student_id, class_id, date, status) VALUES (?, ?, ?, ?)");
        foreach ($class_students as $student) {
            $student_id = $student['id'];
            $status = isset($_POST['attendance_' . $student_id]) ? $_POST['attendance_' . $student_id] : 'Absent';
            $stmt->bind_param("iiss", $student_id, $class_id, $mark_date, $status);
            $stmt->execute();
        }
        $stmt->close();

        $success = 'Attendance marked successfully for ' . count($class_students) . ' students!';
        $_POST = [];
    }
}
?>

<h1 class="page-title"><i class="fas fa-clipboard-list"></i> Mark Attendance</h1>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-light border-bottom">
        <h5 class="mb-0">Daily Attendance Entry</h5>
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

        <form method="GET" action="">
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="date" class="form-label">Date</label>
                    <input type="date" class="form-control" id="date" name="date" value="<?php echo htmlspecialchars($selected_date); ?>" onchange="this.form.submit();">
                </div>
                <div class="col-md-4">
                    <label for="class" class="form-label">Class</label>
                    <select class="form-select" id="class" name="class" onchange="this.form.submit();">
                        <option value="">Select Class</option>
                        <?php foreach ($classes as $class): ?>
                            <option value="<?php echo $class['id']; ?>" <?php echo ($class['id'] == $selected_class) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($class['class_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </form>

        <?php if (!empty($students)): ?>
        <hr>
        <form method="POST" action="">
            <input type="hidden" name="mark_date" value="<?php echo htmlspecialchars($selected_date); ?>">
            <input type="hidden" name="class_id" value="<?php echo htmlspecialchars($selected_class); ?>">

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Admission No</th>
                            <th>Student Name</th>
                            <th>Attendance Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($student['admission_no']); ?></strong></td>
                                <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                <td>
                                    <select class="form-select" name="attendance_<?php echo $student['id']; ?>">
                                        <option value="Present" <?php echo (isset($existing_attendance[$student['id']]) && $existing_attendance[$student['id']] == 'Present') ? 'selected' : ''; ?>>Present</option>
                                        <option value="Absent" <?php echo (isset($existing_attendance[$student['id']]) && $existing_attendance[$student['id']] == 'Absent') ? 'selected' : ''; ?>>Absent</option>
                                        <option value="Late" <?php echo (isset($existing_attendance[$student['id']]) && $existing_attendance[$student['id']] == 'Late') ? 'selected' : ''; ?>>Late</option>
                                    </select>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Attendance</button>
            </div>
        </form>
        <?php elseif ($selected_class > 0): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> No students found in the selected class.
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../footer.php'; ?>
