<?php
require_once 'header.php';

// Get total students
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM students WHERE status = 'active'");
$stmt->execute();
$studentResult = $stmt->get_result();
$studentRow = $studentResult->fetch_assoc();
$totalStudents = $studentRow['total'];
$stmt->close();

// Get total staff
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM staff WHERE status = 'active'");
$stmt->execute();
$staffResult = $stmt->get_result();
$staffRow = $staffResult->fetch_assoc();
$totalStaff = $staffRow['total'];
$stmt->close();

// Get pending fees
$stmt = $conn->prepare("SELECT SUM(amount) as total FROM fees WHERE payment_status IN ('Pending', 'Partial')");
$stmt->execute();
$feeResult = $stmt->get_result();
$feeRow = $feeResult->fetch_assoc();
$pendingFees = $feeRow['total'] ? number_format($feeRow['total'], 2) : '0.00';
$stmt->close();

// Get today's attendance percentage
$today = date('Y-m-d');
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_marked,
        SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present
    FROM attendance 
    WHERE date = ?
");
$stmt->bind_param("s", $today);
$stmt->execute();
$attendResult = $stmt->get_result();
$attendRow = $attendResult->fetch_assoc();
$totalMarked = $attendRow['total_marked'];
$presentCount = $attendRow['present'];
$attendancePercent = $totalMarked > 0 ? round(($presentCount / $totalMarked) * 100, 1) : 0;
$stmt->close();

// Get total classes
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM classes WHERE status = 'active'");
$stmt->execute();
$classResult = $stmt->get_result();
$classRow = $classResult->fetch_assoc();
$totalClasses = $classRow['total'];
$stmt->close();

// Get upcoming exams
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM exams WHERE exam_date >= CURDATE() AND status = 'scheduled'");
$stmt->execute();
$examResult = $stmt->get_result();
$examRow = $examResult->fetch_assoc();
$upcomingExams = $examRow['total'];
$stmt->close();

// Get transports
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM transport WHERE status = 'active'");
$stmt->execute();
$transportResult = $stmt->get_result();
$transportRow = $transportResult->fetch_assoc();
$totalTransports = $transportRow['total'];
$stmt->close();

// Get hostel occupancy
$stmt = $conn->prepare("SELECT COUNT(*) as total_rooms FROM hostel_rooms WHERE status = 'active'");
$stmt->execute();
$roomResult = $stmt->get_result();
$roomRow = $roomResult->fetch_assoc();
$totalRooms = $roomRow['total_rooms'];

$stmt = $conn->prepare("SELECT COUNT(*) as occupied FROM hostel_assignments WHERE status = 'active'");
$stmt->execute();
$occupiedResult = $stmt->get_result();
$occupiedRow = $occupiedResult->fetch_assoc();
$occupiedRooms = $occupiedRow['occupied'];
$stmt->close();
$hostelOccupancy = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100, 1) : 0;
?>

<h1 class="page-title"><i class="fas fa-chart-line"></i> Dashboard</h1>

<div class="row mb-4">
    <!-- Total Students Card -->
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="card border-0 shadow-sm h-100 bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title mb-2">Total Students</h6>
                        <h2 class="mb-0"><?php echo $totalStudents; ?></h2>
                    </div>
                    <i class="fas fa-users fa-3x opacity-50"></i>
                </div>
            </div>
            <a href="students/list.php" class="card-footer bg-white text-primary text-decoration-none text-center py-2">View Details</a>
        </div>
    </div>

    <!-- Total Staff Card -->
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="card border-0 shadow-sm h-100 bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title mb-2">Total Staff</h6>
                        <h2 class="mb-0"><?php echo $totalStaff; ?></h2>
                    </div>
                    <i class="fas fa-briefcase fa-3x opacity-50"></i>
                </div>
            </div>
            <a href="staff/list.php" class="card-footer bg-white text-success text-decoration-none text-center py-2">View Details</a>
        </div>
    </div>

    <!-- Pending Fees Card -->
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="card border-0 shadow-sm h-100 bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title mb-2">Pending Fees</h6>
                        <h2 class="mb-0">₹<?php echo $pendingFees; ?></h2>
                    </div>
                    <i class="fas fa-money-bill-wave fa-3x opacity-50"></i>
                </div>
            </div>
            <a href="fees/list.php" class="card-footer bg-white text-warning text-decoration-none text-center py-2">View Details</a>
        </div>
    </div>

    <!-- Today's Attendance Card -->
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="card border-0 shadow-sm h-100 bg-danger text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title mb-2">Today's Attendance %</h6>
                        <h2 class="mb-0"><?php echo $attendancePercent; ?>%</h2>
                    </div>
                    <i class="fas fa-clipboard-list fa-3x opacity-50"></i>
                </div>
            </div>
            <a href="attendance/report.php" class="card-footer bg-white text-danger text-decoration-none text-center py-2">View Details</a>
        </div>
    </div>
</div>

<div class="row mb-4">
    <!-- Total Classes Card -->
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="card border-0 shadow-sm h-100 bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title mb-2">Total Classes</h6>
                        <h2 class="mb-0"><?php echo $totalClasses; ?></h2>
                    </div>
                    <i class="fas fa-book fa-3x opacity-50"></i>
                </div>
            </div>
            <a href="students/list.php" class="card-footer bg-white text-info text-decoration-none text-center py-2">View Details</a>
        </div>
    </div>

    <!-- Upcoming Exams Card -->
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="card border-0 shadow-sm h-100 bg-secondary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title mb-2">Upcoming Exams</h6>
                        <h2 class="mb-0"><?php echo $upcomingExams; ?></h2>
                    </div>
                    <i class="fas fa-pen-square fa-3x opacity-50"></i>
                </div>
            </div>
            <a href="exams/list.php" class="card-footer bg-white text-secondary text-decoration-none text-center py-2">View Details</a>
        </div>
    </div>

    <!-- Transport Routes Card -->
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="card border-0 shadow-sm h-100 bg-dark text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title mb-2">Transport Routes</h6>
                        <h2 class="mb-0"><?php echo $totalTransports; ?></h2>
                    </div>
                    <i class="fas fa-bus fa-3x opacity-50"></i>
                </div>
            </div>
            <a href="transport/list.php" class="card-footer bg-white text-dark text-decoration-none text-center py-2">View Details</a>
        </div>
    </div>

    <!-- Hostel Occupancy Card -->
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title mb-2">Hostel Occupancy</h6>
                        <h2 class="mb-0"><?php echo $hostelOccupancy; ?>%</h2>
                        <small>(<?php echo $occupiedRooms; ?>/<?php echo $totalRooms; ?> rooms)</small>
                    </div>
                    <i class="fas fa-home fa-3x opacity-50"></i>
                </div>
            </div>
            <a href="hostel/list.php" class="card-footer bg-white text-decoration-none text-center py-2" style="color: #667eea !important;">View Details</a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-light border-bottom">
                <h5 class="mb-0"><i class="fas fa-info-circle"></i> System Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>School Name:</strong> ABC School</p>
                        <p><strong>System Version:</strong> 1.0</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Current Date:</strong> <?php echo date('d-m-Y'); ?></p>
                        <p><strong>Last Login:</strong> Available soon</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
