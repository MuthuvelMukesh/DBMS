<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once dirname(__FILE__) . '/dbconfig.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$role = $_SESSION['role'];

// Role-Based Access Control (RBAC) Logic
$permissions = [
    'admin' => ['students', 'staff', 'attendance', 'fees', 'exams', 'results', 'transport', 'hostel', 'SchoolMS', 'profile.php', 'dashboard.php', 'logout.php'],
    'teacher' => ['students', 'attendance', 'exams', 'results', 'SchoolMS', 'profile.php', 'dashboard.php', 'logout.php'],
    'staff' => ['transport', 'hostel', 'SchoolMS', 'profile.php', 'dashboard.php', 'logout.php'],
    'parent' => ['fees', 'attendance', 'results', 'SchoolMS', 'profile.php', 'dashboard.php', 'logout.php']
];

$current_script = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
$is_root_file = ($current_dir === 'SchoolMS' || $current_dir === 'htdocs' || $current_dir === ''); // Approximation for root

if (!$is_root_file) {
    if (!isset($permissions[$role]) || !in_array($current_dir, $permissions[$role])) {
        // If they are not authorized for this directory module
        header("Location: " . BASE_URL . "dashboard.php?error=Access Denied");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        .main-wrapper {
            display: flex;
        }
        .sidebar-wrapper {
            width: 250px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            color: white;
            overflow-y: auto;
            z-index: 1000;
        }
        .content-wrapper {
            margin-left: 250px;
            flex: 1;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .navbar-wrapper {
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 1rem;
        }
        .navbar-brand:hover {
            color: #667eea !important;
        }
        .main-content {
            flex: 1;
            padding: 2rem;
        }
        @media (max-width: 768px) {
            .sidebar-wrapper {
                width: 70px;
            }
            .content-wrapper {
                margin-left: 70px;
            }
            .sidebar-text {
                display: none;
            }
        }
        .alert-dismissible {
            margin-bottom: 1rem;
        }
        h1.page-title {
            color: #333;
            margin-bottom: 1.5rem;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="main-wrapper">
        <?php include dirname(__FILE__) . '/sidebar.php'; ?>
        <div class="content-wrapper">
            <nav class="navbar navbar-expand-lg navbar-light navbar-wrapper">
                <div class="container-fluid">
                    <span class="navbar-brand mb-0 h1">
                        <i class="fas fa-graduation-cap"></i> School Management System
                    </span>
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <div class="collapse navbar-collapse" id="navbarNav">
                        <ul class="navbar-nav ms-auto">
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($username); ?> (<?php echo ucfirst($role); ?>)
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>profile.php"><i class="fas fa-user"></i> My Profile</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>
            <div class="main-content">
