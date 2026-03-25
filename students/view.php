<?php
require_once '../header.php';

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

// Fetch attendance stats
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_marked,
        SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present,
        SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) as late,
        SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absent
    FROM attendance 
    WHERE student_id = ?
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$attResult = $stmt->get_result();
$attendance = $attResult->fetch_assoc();
$stmt->close();

$attendance_percent = ($attendance['total_marked'] > 0) ? round(($attendance['present'] / $attendance['total_marked']) * 100, 2) : 0;

// Fetch fee details
$stmt = $conn->prepare("
    SELECT 
        SUM(CASE WHEN payment_status = 'Paid' THEN amount ELSE 0 END) as paid,
        SUM(CASE WHEN payment_status = 'Pending' THEN amount ELSE 0 END) as pending,
        SUM(CASE WHEN payment_status = 'Partial' THEN amount ELSE 0 END) as partial
    FROM fees 
    WHERE student_id = ?
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$feeResult = $stmt->get_result();
$fees = $feeResult->fetch_assoc();
$stmt->close();

// Fetch exam results
$stmt = $conn->prepare("
    SELECT e.exam_name, e.subject, r.marks_obtained, r.grade, e.max_marks, e.pass_marks
    FROM results r
    JOIN exams e ON r.exam_id = e.id
    WHERE r.student_id = ?
    ORDER BY e.exam_date DESC
    LIMIT 5
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$resultsList = $stmt->get_result();
$results = $resultsList->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch hostel info
$stmt = $conn->prepare("
    SELECT hr.room_no, hr.room_type, ha.join_date, ha.leave_date, ha.status
    FROM hostel_assignments ha
    JOIN hostel_rooms hr ON ha.room_id = hr.id
    WHERE ha.student_id = ? AND ha.status = 'active'
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$hostelResult = $stmt->get_result();
$hostel = $hostelResult->fetch_assoc();
$stmt->close();

// Fetch transport info
$stmt = $conn->prepare("
    SELECT t.route_name, t.vehicle_no, ta.pickup_stop, ta.monthly_fee
    FROM transport_assignments ta
    JOIN transport t ON ta.transport_id = t.id
    WHERE ta.student_id = ? AND ta.status = 'active'
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$transportResult = $stmt->get_result();
$transport = $transportResult->fetch_assoc();
$stmt->close();
?>

<h1 class="page-title"><i class="fas fa-user"></i> Student Profile</h1>

<div class="row mb-4">
    <div class="col-lg-4 mb-3">
        <!-- Profile Card -->
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <?php if (!empty($student['photo'])): ?>
                    <img src="../uploads/<?php echo htmlspecialchars($student['photo']); ?>" alt="Student Photo" style="max-width: 100%; height: auto; border-radius: 5px; margin-bottom: 1rem;">
                <?php else: ?>
                    <div class="bg-light rounded p-5 mb-3">
                        <i class="fas fa-user fa-5x text-muted"></i>
                    </div>
                <?php endif; ?>
                <h5><?php echo htmlspecialchars($student['full_name']); ?></h5>
                <p class="text-muted mb-3">Admission No: <strong><?php echo htmlspecialchars($student['admission_no']); ?></strong></p>
                <span class="badge bg-<?php echo ($student['status'] == 'active') ? 'success' : 'warning'; ?>" style="font-size: 0.9rem; padding: 0.5rem 1rem;">
                    <?php echo ucfirst($student['status']); ?>
                </span>
                <div class="d-flex gap-2 mt-3">
                    <a href="edit.php?id=<?php echo $student_id; ?>" class="btn btn-sm btn-warning flex-grow-1"><i class="fas fa-edit"></i> Edit</a>
                    <a href="list.php" class="btn btn-sm btn-secondary flex-grow-1"><i class="fas fa-arrow-left"></i> Back</a>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="card border-0 shadow-sm mt-3">
            <div class="card-header bg-light border-bottom">
                <h6 class="mb-0">Quick Stats</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <small class="text-muted">Attendance Rate</small>
                    <div class="progress">
                        <div class="progress-bar bg-success" style="width: <?php echo $attendance_percent; ?>%"></div>
                    </div>
                    <small><strong><?php echo $attendance_percent; ?>%</strong></small>
                </div>
                <div class="text-center">
                    <p class="mb-1"><small class="text-muted">Present: <?php echo $attendance['present'] ?? 0; ?></small></p>
                    <p class="mb-1"><small class="text-muted">Absent: <?php echo $attendance['absent'] ?? 0; ?></small></p>
                    <p class="mb-0"><small class="text-muted">Late: <?php echo $attendance['late'] ?? 0; ?></small></p>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <!-- Personal Information -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-light border-bottom">
                <h5 class="mb-0"><i class="fas fa-id-card"></i> Personal Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Full Name</label>
                        <p class="mb-0"><strong><?php echo htmlspecialchars($student['full_name']); ?></strong></p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Admission Number</label>
                        <p class="mb-0"><strong><?php echo htmlspecialchars($student['admission_no']); ?></strong></p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Date of Birth</label>
                        <p class="mb-0"><strong><?php echo date('d-m-Y', strtotime($student['dob'])); ?></strong></p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Gender</label>
                        <p class="mb-0"><strong><?php echo htmlspecialchars($student['gender']); ?></strong></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Academic Information -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-light border-bottom">
                <h5 class="mb-0"><i class="fas fa-book"></i> Academic Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Class</label>
                        <p class="mb-0"><strong><?php echo htmlspecialchars($student['class_id']); ?></strong></p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Section</label>
                        <p class="mb-0"><strong><?php echo htmlspecialchars($student['section']); ?></strong></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contact Information -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-light border-bottom">
                <h5 class="mb-0"><i class="fas fa-phone"></i> Contact Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Parent Name</label>
                        <p class="mb-0"><strong><?php echo htmlspecialchars($student['parent_name']); ?></strong></p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Contact Number</label>
                        <p class="mb-0"><strong><?php echo htmlspecialchars($student['contact']); ?></strong></p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label class="text-muted small">Address</label>
                        <p class="mb-0"><strong><?php echo htmlspecialchars($student['address'] ?? 'N/A'); ?></strong></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Financial Information -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-light border-bottom">
                <h5 class="mb-0"><i class="fas fa-money-bill-wave"></i> Financial Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 text-center">
                        <p class="text-muted small">Paid</p>
                        <h6 class="text-success">₹<?php echo number_format($fees['paid'] ?? 0, 2); ?></h6>
                    </div>
                    <div class="col-md-4 text-center">
                        <p class="text-muted small">Pending</p>
                        <h6 class="text-danger">₹<?php echo number_format($fees['pending'] ?? 0, 2); ?></h6>
                    </div>
                    <div class="col-md-4 text-center">
                        <p class="text-muted small">Partial</p>
                        <h6 class="text-warning">₹<?php echo number_format($fees['partial'] ?? 0, 2); ?></h6>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($hostel): ?>
        <!-- Hostel Information -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-light border-bottom">
                <h5 class="mb-0"><i class="fas fa-home"></i> Hostel Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Room Number</label>
                        <p class="mb-0"><strong><?php echo htmlspecialchars($hostel['room_no']); ?></strong></p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Room Type</label>
                        <p class="mb-0"><strong><?php echo htmlspecialchars($hostel['room_type']); ?></strong></p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Join Date</label>
                        <p class="mb-0"><strong><?php echo date('d-m-Y', strtotime($hostel['join_date'])); ?></strong></p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Status</label>
                        <p class="mb-0"><span class="badge bg-success"><?php echo ucfirst($hostel['status']); ?></span></p>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($transport): ?>
        <!-- Transport Information -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-light border-bottom">
                <h5 class="mb-0"><i class="fas fa-bus"></i> Transport Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Route Name</label>
                        <p class="mb-0"><strong><?php echo htmlspecialchars($transport['route_name']); ?></strong></p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Vehicle Number</label>
                        <p class="mb-0"><strong><?php echo htmlspecialchars($transport['vehicle_no']); ?></strong></p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Pickup Stop</label>
                        <p class="mb-0"><strong><?php echo htmlspecialchars($transport['pickup_stop']); ?></strong></p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Monthly Fee</label>
                        <p class="mb-0"><strong>₹<?php echo number_format($transport['monthly_fee'], 2); ?></strong></p>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Recent Results -->
        <?php if (!empty($results)): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-light border-bottom">
                <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Recent Results (Last 5 Exams)</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Exam Name</th>
                            <th>Subject</th>
                            <th>Marks</th>
                            <th>Grade</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $result): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($result['exam_name']); ?></td>
                            <td><?php echo htmlspecialchars($result['subject']); ?></td>
                            <td><?php echo $result['marks_obtained']; ?>/<?php echo $result['max_marks']; ?></td>
                            <td><strong><?php echo htmlspecialchars($result['grade'] ?? 'N/A'); ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../footer.php'; ?>
