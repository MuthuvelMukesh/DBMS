<?php
require_once '../header.php';
if (!in_array($role, ['admin', 'teacher'])) {
    header('Location: ' . BASE_URL . 'dashboard.php?error=Access Denied');
    exit();
}

$selected_month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
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

$attendance_data = [];

if ($selected_class > 0) {
    list($year, $month) = explode('-', $selected_month);
    $start_date = "$year-$month-01";
    $end_date = date('Y-m-t', strtotime($start_date));

    // Get all students in class
    $stmt = $conn->prepare("
        SELECT s.id, s.admission_no, s.full_name
        FROM students s
        WHERE s.class_id = ? AND s.status = 'active'
        ORDER BY s.full_name
    ");
    $stmt->bind_param("i", $selected_class);
    $stmt->execute();
    $students_result = $stmt->get_result();
    $students = $students_result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Get attendance for each student
    foreach ($students as $student) {
        $stmt = $conn->prepare("
            SELECT 
                SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present,
                SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absent,
                SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) as late,
                COUNT(*) as total
            FROM attendance
            WHERE student_id = ? AND class_id = ? AND date BETWEEN ? AND ?
        ");
        $stmt->bind_param("iiss", $student['id'], $selected_class, $start_date, $end_date);
        $stmt->execute();
        $att_result = $stmt->get_result();
        $att_row = $att_result->fetch_assoc();
        $stmt->close();

        $total = $att_row['total'] ? $att_row['total'] : 0;
        $present = $att_row['present'] ? $att_row['present'] : 0;
        $percentage = $total > 0 ? round(($present / $total) * 100, 2) : 0;

        $attendance_data[] = [
            'student_id' => $student['id'],
            'admission_no' => $student['admission_no'],
            'full_name' => $student['full_name'],
            'present' => $present,
            'absent' => $att_row['absent'] ? $att_row['absent'] : 0,
            'late' => $att_row['late'] ? $att_row['late'] : 0,
            'total' => $total,
            'percentage' => $percentage
        ];
    }
}
?>

<h1 class="page-title"><i class="fas fa-calendar-alt"></i> Monthly Attendance Report</h1>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-light border-bottom">
        <h5 class="mb-0">Attendance Summary</h5>
    </div>
    <div class="card-body">
        <form method="GET" action="" class="row mb-3">
            <div class="col-md-6">
                <label for="month" class="form-label">Month</label>
                <input type="month" class="form-control" id="month" name="month" value="<?php echo htmlspecialchars($selected_month); ?>" onchange="this.form.submit();">
            </div>
            <div class="col-md-6">
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
        </form>

        <?php if (!empty($attendance_data)): ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Admission No</th>
                        <th>Student Name</th>
                        <th>Present</th>
                        <th>Absent</th>
                        <th>Late</th>
                        <th>Total Days</th>
                        <th>Attendance %</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total_present = 0;
                    $total_absent = 0;
                    $total_late = 0;
                    $total_days = 0;
                    
                    foreach ($attendance_data as $record): 
                        $total_present += $record['present'];
                        $total_absent += $record['absent'];
                        $total_late += $record['late'];
                        $total_days += $record['total'];
                    ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($record['admission_no']); ?></strong></td>
                            <td><?php echo htmlspecialchars($record['full_name']); ?></td>
                            <td><span class="badge bg-success"><?php echo $record['present']; ?></span></td>
                            <td><span class="badge bg-danger"><?php echo $record['absent']; ?></span></td>
                            <td><span class="badge bg-warning"><?php echo $record['late']; ?></span></td>
                            <td><?php echo $record['total']; ?></td>
                            <td>
                                <div class="progress" style="height: 1.5rem;">
                                    <div class="progress-bar bg-<?php echo ($record['percentage'] >= 75) ? 'success' : (($record['percentage'] >= 50) ? 'warning' : 'danger'); ?>" 
                                         style="width: <?php echo $record['percentage']; ?>%" role="progressbar">
                                        <?php echo $record['percentage']; ?>%
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="table-light">
                        <th colspan="2">Class Total</th>
                        <th><?php echo $total_present; ?></th>
                        <th><?php echo $total_absent; ?></th>
                        <th><?php echo $total_late; ?></th>
                        <th><?php echo $total_days; ?></th>
                        <th>
                            <?php 
                            $class_percentage = ($total_days > 0) ? round(($total_present / $total_days) * 100, 2) : 0;
                            echo $class_percentage . '%';
                            ?>
                        </th>
                    </tr>
                </tbody>
            </table>
        </div>
        <?php elseif ($selected_class > 0): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> No attendance records found for the selected month and class.
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../footer.php'; ?>
