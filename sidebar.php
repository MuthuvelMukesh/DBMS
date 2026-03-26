<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$role = $_SESSION['role'];
?>
<div class="sidebar-wrapper">
    <div class="p-4 border-bottom border-secondary">
        <h5 class="mb-0">
            <i class="fas fa-graduation-cap"></i>
            <span class="sidebar-text">SchoolMS</span>
        </h5>
    </div>
    <nav class="navbar navbar-expand-lg navbar-dark w-100 p-0 flex-column">     
        <ul class="navbar-nav flex-column w-100">
            <li class="nav-item">
                <a class="nav-link text-white py-3 px-4" href="<?php echo BASE_URL; ?>dashboard.php">
                    <i class="fas fa-chart-line"></i>
                    <span class="sidebar-text ms-2">Dashboard</span>
                </a>
            </li>

            <!-- Student Management -->
            <?php if (in_array($role, ['admin', 'teacher'])): ?>
            <li class="nav-item">
                <a class="nav-link text-white py-3 px-4" data-bs-toggle="collapse" href="#studentMenu" role="button">
                    <i class="fas fa-users"></i>
                    <span class="sidebar-text ms-2">Students</span>
                </a>
                <div class="collapse" id="studentMenu">
                    <ul class="navbar-nav flex-column ms-3">
                        <li class="nav-item">
                            <a class="nav-link text-white py-2 px-4" href="<?php echo BASE_URL; ?>students/list.php">View All</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white py-2 px-4" href="<?php echo BASE_URL; ?>students/add.php">Add Student</a>
                        </li>
                    </ul>
                </div>
            </li>
            <?php endif; ?>

            <!-- Staff Management -->
            <?php if (in_array($role, ['admin'])): ?>
            <li class="nav-item">
                <a class="nav-link text-white py-3 px-4" data-bs-toggle="collapse" href="#staffMenu" role="button">
                    <i class="fas fa-briefcase"></i>
                    <span class="sidebar-text ms-2">Staff</span>
                </a>
                <div class="collapse" id="staffMenu">
                    <ul class="navbar-nav flex-column ms-3">
                        <li class="nav-item">
                            <a class="nav-link text-white py-2 px-4" href="<?php echo BASE_URL; ?>staff/list.php">View All</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white py-2 px-4" href="<?php echo BASE_URL; ?>staff/add.php">Add Staff</a>
                        </li>
                    </ul>
                </div>
            </li>
            <?php endif; ?>

            <!-- Attendance Management -->
            <?php if (in_array($role, ['admin', 'teacher', 'parent'])): ?>
            <li class="nav-item">
                <a class="nav-link text-white py-3 px-4" data-bs-toggle="collapse" href="#attendanceMenu" role="button">
                    <i class="fas fa-clipboard-list"></i>
                    <span class="sidebar-text ms-2">Attendance</span>
                </a>
                <div class="collapse" id="attendanceMenu">
                    <ul class="navbar-nav flex-column ms-3">
                        <?php if (in_array($role, ['admin', 'teacher'])): ?>
                        <li class="nav-item">
                            <a class="nav-link text-white py-2 px-4" href="<?php echo BASE_URL; ?>attendance/mark.php">Mark Attendance</a>
                        </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link text-white py-2 px-4" href="<?php echo BASE_URL; ?>attendance/report.php">Monthly Report</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white py-2 px-4" href="<?php echo BASE_URL; ?>attendance/student_report.php">Student History</a>
                        </li>
                    </ul>
                </div>
            </li>
            <?php endif; ?>

            <!-- Fees Management -->
            <?php if (in_array($role, ['admin', 'parent'])): ?>
            <li class="nav-item">
                <a class="nav-link text-white py-3 px-4" data-bs-toggle="collapse" href="#feesMenu" role="button">
                    <i class="fas fa-money-bill-wave"></i>
                    <span class="sidebar-text ms-2">Fees</span>
                </a>
                <div class="collapse" id="feesMenu">
                    <ul class="navbar-nav flex-column ms-3">
                        <li class="nav-item">
                            <a class="nav-link text-white py-2 px-4" href="<?php echo BASE_URL; ?>fees/list.php">View All</a>
                        </li>
                        <?php if (in_array($role, ['admin'])): ?>
                        <li class="nav-item">
                            <a class="nav-link text-white py-2 px-4" href="<?php echo BASE_URL; ?>fees/add.php">Add Fee</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white py-2 px-4" href="<?php echo BASE_URL; ?>fees/collect.php">Collect Payment</a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </li>
            <?php endif; ?>

            <!-- Exams Management -->
            <?php if (in_array($role, ['admin', 'teacher'])): ?>
            <li class="nav-item">
                <a class="nav-link text-white py-3 px-4" data-bs-toggle="collapse" href="#examsMenu" role="button">
                    <i class="fas fa-pen-square"></i>
                    <span class="sidebar-text ms-2">Exams</span>
                </a>
                <div class="collapse" id="examsMenu">
                    <ul class="navbar-nav flex-column ms-3">
                        <li class="nav-item">
                            <a class="nav-link text-white py-2 px-4" href="<?php echo BASE_URL; ?>exams/list.php">View All</a>
                        </li>
                        <?php if (in_array($role, ['admin'])): ?>
                        <li class="nav-item">
                            <a class="nav-link text-white py-2 px-4" href="<?php echo BASE_URL; ?>exams/add.php">Add Exam</a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </li>
            <?php endif; ?>

            <!-- Results Management -->
            <?php if (in_array($role, ['admin', 'teacher', 'parent'])): ?>
            <li class="nav-item">
                <a class="nav-link text-white py-3 px-4" data-bs-toggle="collapse" href="#resultsMenu" role="button">
                    <i class="fas fa-chart-bar"></i>
                    <span class="sidebar-text ms-2">Results</span>
                </a>
                <div class="collapse" id="resultsMenu">
                    <ul class="navbar-nav flex-column ms-3">
                        <li class="nav-item">
                            <a class="nav-link text-white py-2 px-4" href="<?php echo BASE_URL; ?>results/report.php">View Results</a>
                        </li>
                        <?php if (in_array($role, ['admin', 'teacher'])): ?>
                        <li class="nav-item">
                            <a class="nav-link text-white py-2 px-4" href="<?php echo BASE_URL; ?>results/add.php">Add Results</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white py-2 px-4" href="<?php echo BASE_URL; ?>results/marksheet.php">Marksheet</a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </li>
            <?php endif; ?>

            <!-- Transport Management -->
            <?php if (in_array($role, ['admin', 'staff'])): ?>
            <li class="nav-item">
                <a class="nav-link text-white py-3 px-4" data-bs-toggle="collapse" href="#transportMenu" role="button">
                    <i class="fas fa-bus"></i>
                    <span class="sidebar-text ms-2">Transport</span>
                </a>
                <div class="collapse" id="transportMenu">
                    <ul class="navbar-nav flex-column ms-3">
                        <li class="nav-item">
                            <a class="nav-link text-white py-2 px-4" href="<?php echo BASE_URL; ?>transport/routes.php">Routes</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white py-2 px-4" href="<?php echo BASE_URL; ?>transport/assign.php">Assign</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white py-2 px-4" href="<?php echo BASE_URL; ?>transport/list.php">Student Assignments</a>
                        </li>
                    </ul>
                </div>
            </li>
            <?php endif; ?>

            <!-- Hostel Management -->
            <?php if (in_array($role, ['admin', 'staff'])): ?>
            <li class="nav-item">
                <a class="nav-link text-white py-3 px-4" data-bs-toggle="collapse" href="#hostelMenu" role="button">
                    <i class="fas fa-home"></i>
                    <span class="sidebar-text ms-2">Hostel</span>
                </a>
                <div class="collapse" id="hostelMenu">
                    <ul class="navbar-nav flex-column ms-3">
                        <li class="nav-item">
                            <a class="nav-link text-white py-2 px-4" href="<?php echo BASE_URL; ?>hostel/rooms.php">Rooms</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white py-2 px-4" href="<?php echo BASE_URL; ?>hostel/assign.php">Assign Room</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white py-2 px-4" href="<?php echo BASE_URL; ?>hostel/list.php">Assignments</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white py-2 px-4" href="<?php echo BASE_URL; ?>hostel/vacate.php">Vacate</a>
                        </li>
                    </ul>
                </div>
            </li>
            <?php endif; ?>
        </ul>
    </nav>
</div>