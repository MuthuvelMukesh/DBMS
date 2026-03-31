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

// Fetch Active Notices
$noticeResult = $conn->query("SELECT * FROM notices WHERE is_active = 1 ORDER BY created_at DESC LIMIT 3");
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4 mt-2">
    <h1 class="page-title mb-0"><i class="fas fa-chart-line text-primary"></i> Dashboard</h1>
    <a href="#" class="btn btn-sm btn-primary shadow-sm"><i class="fas fa-download fa-sm text-white-50"></i> Generate Report</a>
</div>

<!-- Noticeboard Alerts -->
<?php if($noticeResult && $noticeResult->num_rows > 0): ?>
    <div class="mb-4">
        <?php while($n = $noticeResult->fetch_assoc()): 
            $bs_class = $n['type'] === 'error' ? 'danger' : $n['type'];
            $icon = 'info-circle';
            if ($bs_class == 'warning') $icon = 'exclamation-triangle';
            if ($bs_class == 'danger') $icon = 'exclamation-circle';
            if ($bs_class == 'success') $icon = 'check-circle';
        ?>
            <div class="alert alert-<?php echo $bs_class; ?> alert-dismissible fade show shadow-sm" role="alert" style="border-left: 5px solid;">
                <h5 class="alert-heading font-weight-bold mb-1"><i class="fas fa-<?php echo $icon; ?> me-2"></i> <?php echo htmlspecialchars($n['title']); ?></h5>
                <hr class="mt-1 mb-2">
                <p class="mb-0"><?php echo nl2br(htmlspecialchars($n['message'])); ?></p>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endwhile; ?>
    </div>
<?php endif; ?>

<?php if ($is_admin_or_teacher): ?>
<div class="row">
    <!-- Total Students Card -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card bg-primary text-white shadow-sm h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-uppercase mb-1" style="font-size: 0.8rem; letter-spacing: 0.5px; opacity: 0.8;">Total Students</div>
                        <div class="h5 mb-0 font-weight-bold" style="font-weight: 800; font-size: 1.75rem;"><?php echo $totalStudents; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-user-graduate fa-2x" style="opacity: 0.5;"></i>
                    </div>
                </div>
            </div>
            <a href="students/list.php" class="card-footer bg-transparent border-0 text-white text-center pb-2 pt-0 text-decoration-none" style="font-size:0.85rem; font-weight:600; opacity: 0.9;">View Details <i class="fas fa-arrow-right ms-1"></i></a>
        </div>
    </div>

    <!-- Total Staff Card -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card bg-success text-white shadow-sm h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-uppercase mb-1" style="font-size: 0.8rem; letter-spacing: 0.5px; opacity: 0.8;">Staff Members</div>
                        <div class="h5 mb-0 font-weight-bold" style="font-weight: 800; font-size: 1.75rem;"><?php echo $totalStaff; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-chalkboard-teacher fa-2x" style="opacity: 0.5;"></i>
                    </div>
                </div>
            </div>
            <a href="staff/list.php" class="card-footer bg-transparent border-0 text-white text-center pb-2 pt-0 text-decoration-none" style="font-size:0.85rem; font-weight:600; opacity: 0.9;">View Details <i class="fas fa-arrow-right ms-1"></i></a>
        </div>
    </div>

    <!-- Pending Fees Card -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card bg-warning text-white shadow-sm h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-uppercase mb-1" style="font-size: 0.8rem; letter-spacing: 0.5px; opacity: 0.8;">Pending Fees</div>
                        <div class="h5 mb-0 font-weight-bold" style="font-weight: 800; font-size: 1.5rem;">₹<?php echo $pendingFees; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-rupee-sign fa-2x" style="opacity: 0.5;"></i>
                    </div>
                </div>
            </div>
            <a href="fees/list.php" class="card-footer bg-transparent border-0 text-white text-center pb-2 pt-0 text-decoration-none" style="font-size:0.85rem; font-weight:600; opacity: 0.9;">View Details <i class="fas fa-arrow-right ms-1"></i></a>
        </div>
    </div>

    <!-- Attendance Card -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card bg-info text-white shadow-sm h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-uppercase mb-1" style="font-size: 0.8rem; letter-spacing: 0.5px; opacity: 0.8;">Attendance (Today)</div>
                        <div class="row no-gutters align-items-center">
                            <div class="col-auto">
                                <div class="h5 mb-0 mr-3 font-weight-bold" style="font-weight: 800; font-size: 1.5rem; margin-right: 15px;"><?php echo $attendancePercent; ?>%</div>
                            </div>
                            <div class="col">
                                <div class="progress progress-sm mr-2" style="height: 0.5rem; margin-right:15px; background-color: rgba(255,255,255,0.2);">
                                    <div class="progress-bar bg-white" role="progressbar" style="width: <?php echo $attendancePercent; ?>%" aria-valuenow="<?php echo $attendancePercent; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-clipboard-list fa-2x" style="opacity: 0.5;"></i>
                    </div>
                </div>
            </div>
            <a href="attendance/report.php" class="card-footer bg-transparent border-0 text-white text-center pb-2 pt-0 text-decoration-none" style="font-size:0.85rem; font-weight:600; opacity: 0.9;">View Details <i class="fas fa-arrow-right ms-1"></i></a>
        </div>
    </div>
</div>

<div class="row">
    <!-- Chart Column -->
    <div class="col-xl-8 col-lg-7 mb-4">
        <div class="card shadow-sm border-0 mb-4 h-100">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between bg-white border-bottom">
                <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-chart-area me-2"></i>School Overview</h6>
            </div>
            <div class="card-body">
                <div class="chart-area" style="height: 300px; width: 100%;">
                    <canvas id="myAreaChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Mini Stats Column -->
    <div class="col-xl-4 col-lg-5 mb-4">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header py-3 bg-white border-bottom">
                <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-building me-2"></i>Infrastructure</h6>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center mb-4">
                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 45px; height: 45px;">
                        <i class="fas fa-bus"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="mb-0 font-weight-bold">Transport Hub</h6>
                        <small class="text-muted"><b class="text-dark"><?php echo $totalTransports; ?></b> Active Routes</small>
                    </div>
                    <a href="transport/list.php" class="btn btn-sm btn-light text-primary">View</a>
                </div>
                
                <div class="d-flex align-items-center mb-4">
                    <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 45px; height: 45px;">
                        <i class="fas fa-home"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="mb-0 font-weight-bold">Hostel Blocks</h6>
                        <small class="text-muted"><b class="text-dark"><?php echo $totalRooms; ?></b> Registered Rooms</small>
                    </div>
                    <a href="hostel/list.php" class="btn btn-sm btn-light text-success">View</a>
                </div>
                
                <div class="d-flex align-items-center">
                    <div class="bg-info text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 45px; height: 45px;">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="mb-0 font-weight-bold">Academics</h6>
                        <small class="text-muted"><b class="text-dark"><?php echo $upcomingExams; ?></b> Upcoming Exams</small>
                    </div>
                    <a href="exams/list.php" class="btn btn-sm btn-light text-info">View</a>
                </div>
            </div>
        </div>
        
        <!-- Hostel Occupancy Progress -->
        <div class="card shadow-sm border-0 border-bottom border-dark border-3">
             <div class="card-body">
                <h6 class="font-weight-bold text-dark mb-1">Hostel Occupancy</h6>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small"><?php echo $occupiedRooms; ?>/<?php echo $totalRooms; ?> Rooms Filled</span>
                    <span class="font-weight-bold text-dark"><?php echo $hostelOccupancy; ?>%</span>
                </div>
                <div class="progress" style="height: 8px;">
                    <div class="progress-bar bg-dark" role="progressbar" style="width: <?php echo $hostelOccupancy; ?>%" aria-valuenow="<?php echo $hostelOccupancy; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
             </div>
        </div>
    </div>
</div>

<?php endif; ?>
<?php if ($is_admin_or_teacher): ?>
<script>
document.addEventListener("DOMContentLoaded", function() {
    var ctx = document.getElementById("myAreaChart");
    if (ctx) {
        var myPieChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
                datasets: [{
                    label: "Fees Collected (₹)",
                    backgroundColor: "rgba(78, 115, 223, 0.8)",
                    hoverBackgroundColor: "rgba(78, 115, 223, 1)",
                    borderColor: "#4e73df",
                    data: [12000, 25000, 18000, 32000, 15000, 40000, 45000, 21000, 19000, 28000, 24000, 31000],
                    borderRadius: 4,
                }],
            },
            options: {
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false,
                            drawBorder: false
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: "rgb(234, 236, 244)",
                            zeroLineColor: "rgb(234, 236, 244)",
                            drawBorder: false,
                            borderDash: [2],
                            zeroLineBorderDash: [2]
                        }
                    }
                }
            }
        });
    }
});
</script>
<?php endif; ?>

<?php require_once 'footer.php'; ?>
