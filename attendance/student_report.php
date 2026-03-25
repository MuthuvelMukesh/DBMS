<?php
require_once '../header.php';

$selected_student = isset($_GET['student']) ? (int)$_GET['student'] : 0;
$selected_month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');

// Get all students
$students_result = $conn->query("SELECT id, admission_no, CONCAT(full_name, ' (', admission_no, ')') as display_name FROM students WHERE status = 'active' ORDER BY full_name");
$students_list = $students_result->fetch_all(MYSQLI_ASSOC);

$attendance_records = [];
$student_info = null;

if ($selected_student > 0) {
    // Get student info
    $stmt = $conn->prepare("SELECT id, admission_no, full_name, class_id FROM students WHERE id = ? AND status = 'active'");
    $stmt->bind_param("i", $selected_student);
    $stmt->execute();
    $info_result = $stmt->get_result();
    $student_info = $info_result->fetch_assoc();
    $stmt->close();

    if ($student_info) {
        list($year, $month) = explode('-', $selected_month);
        $start_date = "$year-$month-01";
        $end_date = date('Y-m-t', strtotime($start_date));

        // Get attendance records
        $stmt = $conn->prepare("
            SELECT date, status, remarks
            FROM attendance
            WHERE student_id = ? AND date BETWEEN ? AND ?
            ORDER BY date ASC
        ");
        $stmt->bind_param("iss", $selected_student, $start_date, $end_date);
        $stmt->execute();
        $records_result = $stmt->get_result();
        $attendance_records = $records_result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Get summary
        $stmt = $conn->prepare("
            SELECT 
                SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present,
                SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absent,
                SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) as late,
                COUNT(*) as total
            FROM attendance
            WHERE student_id = ? AND date BETWEEN ? AND ?
        ");
        $stmt->bind_param("iss", $selected_student, $start_date, $end_date);
        $stmt->execute();
        $summary_result = $stmt->get_result();
        $summary = $summary_result->fetch_assoc();
        $stmt->close();
    }
}
?>

<h1 class="page-title"><i class="fas fa-user"></i> Student Attendance History</h1>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-light border-bottom">
        <h5 class="mb-0">Student Attendance Details</h5>
    </div>
    <div class="card-body">
        <form method="GET" action="" class="row mb-3">
            <div class="col-md-8">
                <label for="student" class="form-label">Select Student</label>
                <select class="form-select" id="student" name="student" onchange="this.form.submit();">
                    <option value="">Select a Student</option>
                    <?php foreach ($students_list as $student): ?>
                        <option value="<?php echo $student['id']; ?>" <?php echo ($student['id'] == $selected_student) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($student['display_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label for="month" class="form-label">Month</label>
                <input type="month" class="form-control" id="month" name="month" value="<?php echo htmlspecialchars($selected_month); ?>" onchange="this.form.submit();">
            </div>
        </form>

        <?php if ($student_info): ?>
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="alert alert-info">
                    <strong><?php echo htmlspecialchars($student_info['full_name']); ?></strong> 
                    (Admission No: <?php echo htmlspecialchars($student_info['admission_no']); ?>)
                </div>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-3">
                <div class="card bg-light border-0">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Present</h6>
                        <h4 class="text-success mb-0"><?php echo $summary['present'] ? $summary['present'] : 0; ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-light border-0">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Absent</h6>
                        <h4 class="text-danger mb-0"><?php echo $summary['absent'] ? $summary['absent'] : 0; ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-light border-0">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Late</h6>
                        <h4 class="text-warning mb-0"><?php echo $summary['late'] ? $summary['late'] : 0; ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-light border-0">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Total Days</h6>
                        <h4 class="text-primary mb-0"><?php echo $summary['total'] ? $summary['total'] : 0; ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="mb-3">
            <h6>Attendance Percentage</h6>
            <?php 
            $percentage = ($summary['total'] > 0) ? round(($summary['present'] / $summary['total']) * 100, 2) : 0;
            $bg_color = ($percentage >= 75) ? 'success' : (($percentage >= 50) ? 'warning' : 'danger');
            ?>
            <div class="progress" style="height: 2rem;">
                <div class="progress-bar bg-<?php echo $bg_color; ?>" style="width: <?php echo $percentage; ?>%; font-size: 1rem;" role="progressbar">
                    <?php echo $percentage; ?>%
                </div>
            </div>
        </div>

        <hr>

        <?php if (!empty($attendance_records)): ?>
        <div class="table-responsive">
            <table class="table table-sm table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($attendance_records as $record): ?>
                        <tr>
                            <td><?php echo date('d-m-Y', strtotime($record['date'])); ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    if ($record['status'] == 'Present') echo 'success';
                                    elseif ($record['status'] == 'Absent') echo 'danger';
                                    else echo 'warning';
                                ?>">
                                    <?php echo htmlspecialchars($record['status']); ?>
                                </span>
                            </td>
                            <td><?php echo !empty($record['remarks']) ? htmlspecialchars($record['remarks']) : '-'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> No attendance records found for the selected month.
        </div>
        <?php endif; ?>

        <?php endif; ?>
    </div>
</div>

<?php require_once '../footer.php'; ?>
