<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$role = $_SESSION['role'];

$current_script = basename($_SERVER['PHP_SELF'] ?? '');
$current_dir = basename(dirname($_SERVER['PHP_SELF'] ?? ''));
$is_root_file = ($current_dir === 'SchoolMS' || $current_dir === 'htdocs' || $current_dir === '');
$active_section = $is_root_file ? $current_script : $current_dir;

$students_open = ($active_section === 'students');
$staff_open = ($active_section === 'staff');
$attendance_open = ($active_section === 'attendance');
$fees_open = ($active_section === 'fees');
$results_open = ($active_section === 'results');
$transport_open = ($active_section === 'transport');
$hostel_open = ($active_section === 'hostel');
$settings_open = ($active_section === 'settings');
?>
<div class="sidebar-wrapper" id="appSidebar">
    <a href="<?php echo BASE_URL; ?>dashboard.php" class="sidebar-brand text-decoration-none">
        <i class="fas fa-graduation-cap"></i>
        <span class="sidebar-text">School<span>MS</span></span>
    </a>
    <nav class="navbar navbar-expand-lg w-100 p-0 flex-column" aria-label="Main navigation">
        <ul class="navbar-nav flex-column w-100">
            <li class="nav-item">
                <a class="nav-link<?php echo $current_script === 'dashboard.php' ? ' active' : ''; ?>" href="<?php echo BASE_URL; ?>dashboard.php">
                    <i class="fas fa-chart-line"></i>
                    <span class="sidebar-text">Dashboard</span>
                </a>
            </li>

            <!-- Student Management -->
            <?php if (in_array($role, ['admin', 'teacher'])): ?>
            <li class="nav-item">
                <a class="nav-link<?php echo $students_open ? ' active' : ''; ?>" data-bs-toggle="collapse" href="#studentMenu" role="button" aria-expanded="<?php echo $students_open ? 'true' : 'false'; ?>" aria-controls="studentMenu">
                    <i class="fas fa-users"></i>
                    <span class="sidebar-text">Students</span>
                    <i class="fas fa-chevron-down ms-auto" style="font-size: 0.8rem; margin-left: auto; opacity: 0.5;"></i>
                </a>
                <div class="collapse<?php echo $students_open ? ' show' : ''; ?>" id="studentMenu">
                    <ul class="navbar-nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link<?php echo ($active_section === 'students' && $current_script === 'list.php') ? ' active' : ''; ?>" href="<?php echo BASE_URL; ?>students/list.php">Student Directory</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link<?php echo ($active_section === 'students' && $current_script === 'add.php') ? ' active' : ''; ?>" href="<?php echo BASE_URL; ?>students/add.php">Add Student</a>
                        </li>
                    </ul>
                </div>
            </li>
            <?php endif; ?>

            <!-- Staff Management -->
            <?php if (in_array($role, ['admin'])): ?>
            <li class="nav-item">
                <a class="nav-link<?php echo $staff_open ? ' active' : ''; ?>" data-bs-toggle="collapse" href="#staffMenu" role="button" aria-expanded="<?php echo $staff_open ? 'true' : 'false'; ?>" aria-controls="staffMenu">
                    <i class="fas fa-briefcase"></i>
                    <span class="sidebar-text">Staff</span>
                    <i class="fas fa-chevron-down ms-auto" style="font-size: 0.8rem; margin-left: auto; opacity: 0.5;"></i>
                </a>
                <div class="collapse<?php echo $staff_open ? ' show' : ''; ?>" id="staffMenu">
                    <ul class="navbar-nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link<?php echo ($active_section === 'staff' && $current_script === 'list.php') ? ' active' : ''; ?>" href="<?php echo BASE_URL; ?>staff/list.php">Staff Directory</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link<?php echo ($active_section === 'staff' && $current_script === 'add.php') ? ' active' : ''; ?>" href="<?php echo BASE_URL; ?>staff/add.php">Add Staff</a>
                        </li>
                    </ul>
                </div>
            </li>
            <?php endif; ?>

            <!-- Classes Management -->
            <?php if (in_array($role, ['admin', 'teacher'])): ?>
            <li class="nav-item">
                <a class="nav-link<?php echo $active_section === 'classes' ? ' active' : ''; ?>" href="<?php echo BASE_URL; ?>classes/list.php">
                    <i class="fas fa-chalkboard"></i>
                    <span class="sidebar-text">Classes</span>
                </a>
            </li>
            <?php endif; ?>

            <!-- Attendance Management -->
            <?php if (in_array($role, ['admin', 'teacher', 'parent', 'student'])): ?>
            <li class="nav-item">
                <a class="nav-link<?php echo $attendance_open ? ' active' : ''; ?>" data-bs-toggle="collapse" href="#attendanceMenu" role="button" aria-expanded="<?php echo $attendance_open ? 'true' : 'false'; ?>" aria-controls="attendanceMenu">
                    <i class="fas fa-clipboard-list"></i>
                    <span class="sidebar-text">Attendance</span>
                    <i class="fas fa-chevron-down ms-auto" style="font-size: 0.8rem; margin-left: auto; opacity: 0.5;"></i>
                </a>
                <div class="collapse<?php echo $attendance_open ? ' show' : ''; ?>" id="attendanceMenu">
                    <ul class="navbar-nav flex-column">
                        <?php if (in_array($role, ['admin', 'teacher'])): ?>
                        <li class="nav-item">
                            <a class="nav-link<?php echo ($active_section === 'attendance' && $current_script === 'mark.php') ? ' active' : ''; ?>" href="<?php echo BASE_URL; ?>attendance/mark.php">Mark Attendance</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link<?php echo ($active_section === 'attendance' && $current_script === 'report.php') ? ' active' : ''; ?>" href="<?php echo BASE_URL; ?>attendance/report.php">Monthly Report</a>
                        </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link<?php echo ($active_section === 'attendance' && $current_script === 'student_report.php') ? ' active' : ''; ?>" href="<?php echo BASE_URL; ?>attendance/student_report.php">Student History</a>
                        </li>
                    </ul>
                </div>
            </li>
            <?php endif; ?>

            <!-- Fees Management -->
            <?php if (in_array($role, ['admin', 'parent', 'student'], true)): ?>
            <li class="nav-item">
                <a class="nav-link<?php echo $fees_open ? ' active' : ''; ?>" data-bs-toggle="collapse" href="#feesMenu" role="button" aria-expanded="<?php echo $fees_open ? 'true' : 'false'; ?>" aria-controls="feesMenu">
                    <i class="fas fa-money-bill-wave"></i>
                    <span class="sidebar-text">Fees</span>
                    <i class="fas fa-chevron-down ms-auto" style="font-size: 0.8rem; margin-left: auto; opacity: 0.5;"></i>
                </a>
                <div class="collapse<?php echo $fees_open ? ' show' : ''; ?>" id="feesMenu">
                    <ul class="navbar-nav flex-column">
                        <?php if ($role === 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link<?php echo ($active_section === 'fees' && $current_script === 'add.php') ? ' active' : ''; ?>" href="<?php echo BASE_URL; ?>fees/add.php">Add Fees</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link<?php echo ($active_section === 'fees' && $current_script === 'list.php') ? ' active' : ''; ?>" href="<?php echo BASE_URL; ?>fees/list.php?collect=1">Collect Payment</a>
                        </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link<?php echo ($active_section === 'fees' && $current_script === 'list.php') ? ' active' : ''; ?>" href="<?php echo BASE_URL; ?>fees/list.php">Fee Ledger</a>
                        </li>
                    </ul>
                </div>
            </li>
            <?php endif; ?>

            <!-- Exams Management -->
            <?php if (in_array($role, ['admin', 'teacher'])): ?>
            <li class="nav-item">
                <a class="nav-link<?php echo $active_section === 'exams' ? ' active' : ''; ?>" href="<?php echo BASE_URL; ?>exams/list.php">
                    <i class="fas fa-pen-fancy"></i>
                    <span class="sidebar-text">Exams</span>
                </a>
            </li>
            <?php endif; ?>

            <!-- Results Management -->
            <?php if (in_array($role, ['admin', 'teacher', 'parent', 'student'])): ?>
            <li class="nav-item">
                <a class="nav-link<?php echo $results_open ? ' active' : ''; ?>" data-bs-toggle="collapse" href="#resultsMenu" role="button" aria-expanded="<?php echo $results_open ? 'true' : 'false'; ?>" aria-controls="resultsMenu">
                    <i class="fas fa-chart-bar"></i>
                    <span class="sidebar-text">Results</span>
                    <i class="fas fa-chevron-down ms-auto" style="font-size: 0.8rem; margin-left: auto; opacity: 0.5;"></i>
                </a>
                <div class="collapse<?php echo $results_open ? ' show' : ''; ?>" id="resultsMenu">
                    <ul class="navbar-nav flex-column">
                        <?php if (in_array($role, ['admin', 'teacher'])): ?>
                        <li class="nav-item">
                            <a class="nav-link<?php echo ($active_section === 'results' && $current_script === 'add.php') ? ' active' : ''; ?>" href="<?php echo BASE_URL; ?>results/add.php">Add Results</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link<?php echo ($active_section === 'results' && $current_script === 'marksheet.php') ? ' active' : ''; ?>" href="<?php echo BASE_URL; ?>results/marksheet.php">Marksheet</a>
                        </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link<?php echo ($active_section === 'results' && $current_script === 'report.php') ? ' active' : ''; ?>" href="<?php echo BASE_URL; ?>results/report.php">View Report</a>
                        </li>
                    </ul>
                </div>
            </li>
            <?php endif; ?>

            <!-- Transport Management -->
            <?php if (in_array($role, ['admin', 'staff', 'parent', 'student'])): ?>
            <li class="nav-item">
                <a class="nav-link<?php echo $transport_open ? ' active' : ''; ?>" data-bs-toggle="collapse" href="#transportMenu" role="button" aria-expanded="<?php echo $transport_open ? 'true' : 'false'; ?>" aria-controls="transportMenu">
                    <i class="fas fa-bus"></i>
                    <span class="sidebar-text">Transport</span>
                    <i class="fas fa-chevron-down ms-auto" style="font-size: 0.8rem; margin-left: auto; opacity: 0.5;"></i>
                </a>
                <div class="collapse<?php echo $transport_open ? ' show' : ''; ?>" id="transportMenu">
                    <ul class="navbar-nav flex-column">
                        <?php if (in_array($role, ['admin', 'staff'])): ?>
                        <li class="nav-item">
                            <a class="nav-link<?php echo ($active_section === 'transport' && $current_script === 'routes.php') ? ' active' : ''; ?>" href="<?php echo BASE_URL; ?>transport/routes.php">Routes</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link<?php echo ($active_section === 'transport' && $current_script === 'assign.php') ? ' active' : ''; ?>" href="<?php echo BASE_URL; ?>transport/assign.php">Assign Vehicle</a>
                        </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link<?php echo ($active_section === 'transport' && $current_script === 'list.php') ? ' active' : ''; ?>" href="<?php echo BASE_URL; ?>transport/list.php">Route Assignments</a>
                        </li>
                    </ul>
                </div>
            </li>
            <?php endif; ?>

            <!-- Hostel Management -->
            <?php if (in_array($role, ['admin', 'staff', 'parent', 'student'])): ?>
            <li class="nav-item">
                <a class="nav-link<?php echo $hostel_open ? ' active' : ''; ?>" data-bs-toggle="collapse" href="#hostelMenu" role="button" aria-expanded="<?php echo $hostel_open ? 'true' : 'false'; ?>" aria-controls="hostelMenu">
                    <i class="fas fa-bed"></i>
                    <span class="sidebar-text">Hostel</span>
                    <i class="fas fa-chevron-down ms-auto" style="font-size: 0.8rem; margin-left: auto; opacity: 0.5;"></i>
                </a>
                <div class="collapse<?php echo $hostel_open ? ' show' : ''; ?>" id="hostelMenu">
                    <ul class="navbar-nav flex-column">
                        <?php if (in_array($role, ['admin', 'staff'])): ?>
                        <li class="nav-item">
                            <a class="nav-link<?php echo ($active_section === 'hostel' && $current_script === 'rooms.php') ? ' active' : ''; ?>" href="<?php echo BASE_URL; ?>hostel/rooms.php">Rooms</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link<?php echo ($active_section === 'hostel' && $current_script === 'assign.php') ? ' active' : ''; ?>" href="<?php echo BASE_URL; ?>hostel/assign.php">Assign Room</a>
                        </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link<?php echo ($active_section === 'hostel' && $current_script === 'list.php') ? ' active' : ''; ?>" href="<?php echo BASE_URL; ?>hostel/list.php">Hostel Allocations</a>
                        </li>
                    </ul>
                </div>
            </li>
            <?php endif; ?>

            <!-- System Settings (Admin Only) -->
            <?php if ($role === 'admin'): ?>
            <li class="nav-item">
                <a class="nav-link<?php echo $settings_open ? ' active' : ''; ?>" data-bs-toggle="collapse" href="#settingsMenu" role="button" aria-expanded="<?php echo $settings_open ? 'true' : 'false'; ?>" aria-controls="settingsMenu">
                    <i class="fas fa-cog"></i>
                    <span class="sidebar-text">Settings</span>
                    <i class="fas fa-chevron-down ms-auto" style="font-size: 0.8rem; margin-left: auto; opacity: 0.5;"></i>
                </a>
                <div class="collapse<?php echo $settings_open ? ' show' : ''; ?>" id="settingsMenu">
                    <ul class="navbar-nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link<?php echo ($active_section === 'settings' && $current_script === 'index.php') ? ' active' : ''; ?>" href="<?php echo BASE_URL; ?>settings/index.php">System Settings</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link<?php echo ($active_section === 'settings' && $current_script === 'account_requests.php') ? ' active' : ''; ?>" href="<?php echo BASE_URL; ?>settings/account_requests.php">Account Requests</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link<?php echo ($active_section === 'settings' && $current_script === 'parent_links.php') ? ' active' : ''; ?>" href="<?php echo BASE_URL; ?>settings/parent_links.php">Parent Links</a>
                        </li>
                    </ul>
                </div>
            </li>
            <li class="nav-item">
                <a class="nav-link<?php echo $active_section === 'notices' ? ' active' : ''; ?>" href="<?php echo BASE_URL; ?>notices/index.php">
                    <i class="fas fa-bullhorn"></i>
                    <span class="sidebar-text">Notices</span>
                </a>
            </li>
            <?php endif; ?>

            <!-- User Profile -->
            <li class="nav-item border-top">
                <a class="nav-link<?php echo $current_script === 'profile.php' ? ' active' : ''; ?>" href="<?php echo BASE_URL; ?>profile.php">
                    <i class="fas fa-user-circle"></i>
                    <span class="sidebar-text">Profile</span>
                </a>
            </li>

            <!-- Logout -->
            <li class="nav-item">
                <a class="nav-link<?php echo $current_script === 'logout.php' ? ' active' : ''; ?>" href="<?php echo BASE_URL; ?>logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    <span class="sidebar-text">Logout</span>
                </a>
            </li>
        </ul>
    </nav>
</div>
