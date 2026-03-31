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

// Fetch Global Settings
$settings_query = $conn->query("SELECT setting_key, setting_value FROM system_settings");
$sys_settings = [];
if ($settings_query) {
    while($row = $settings_query->fetch_assoc()){
        $sys_settings[$row['setting_key']] = $row['setting_value'];
    }
}
$school_name = isset($sys_settings['school_name']) && !empty($sys_settings['school_name']) ? $sys_settings['school_name'] : 'School Management System';
$school_logo = isset($sys_settings['logo_path']) && !empty($sys_settings['logo_path']) ? BASE_URL . $sys_settings['logo_path'] : '';

// Role-Based Access Control (RBAC) Logic
$permissions = [
    'admin' => ['students', 'staff', 'classes', 'attendance', 'fees', 'exams', 'results', 'transport', 'hostel', 'settings', 'notices', 'SchoolMS', 'profile.php', 'dashboard.php', 'logout.php'],
    'teacher' => ['students', 'classes', 'attendance', 'exams', 'results', 'SchoolMS', 'profile.php', 'dashboard.php', 'logout.php'],
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
        <title><?php echo htmlspecialchars($school_name); ?></title>
    <!-- Google Fonts: Nunito -->
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
    <style>
        body {
            font-family: 'Nunito', Tahoma, Geneva, Verdana, sans-serif;       
            background-color: #f8f9fc;
            color: #5a5c69;
        }
        /* Fade-in animation for smooth page loads */
        .main-content {
            flex: 1;
            padding: 2rem;
            animation: fadeIn 0.5s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        /* Modern Cards */
        .card {
            border: none;
            border-radius: 0.75rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }
        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 0.5rem 2rem 0 rgba(58, 59, 69, 0.2);
        }
        .card-header {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
            border-radius: 0.75rem 0.75rem 0 0 !important;
            font-weight: 700;
            color: #4e73df;
        }
        /* Dashboard Stats Cards Overrides */
        .card.bg-primary { background-color: #4e73df !important; }
        .card.bg-success { background-color: #1cc88a !important; }
        .card.bg-warning { background-color: #f6c23e !important; }
        .card.bg-danger { background-color: #e74a3b !important; }
        .card.bg-info { background-color: #36b9cc !important; }
        
        .main-wrapper {
            display: flex;
        }
        .sidebar-wrapper {
            width: 250px;
            background: linear-gradient(180deg, #4e73df 10%, #224abe 100%);
            min-height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            color: white;
            overflow-y: auto;
            z-index: 1000;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        /* Sidebar Link Enhancements */
        .sidebar-wrapper .nav-item .nav-link {
            color: rgba(255, 255, 255, 0.8);
            font-weight: 700;
            padding: 1rem;
            transition: all 0.2s;
        }
        .sidebar-wrapper .nav-item .nav-link:hover, 
        .sidebar-wrapper .nav-item .nav-link:focus {
            color: #fff;
            background: rgba(255,255,255,0.1);
            border-radius: 5px;
            margin: 0 10px;
        }
        .sidebar-wrapper .nav-item .collapse .nav-link {
            font-weight: 400;
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
            margin: 0;
        }
        .content-wrapper {
            margin-left: 250px;
            flex: 1;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .navbar-wrapper {
            background: white !important;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            padding: 1rem 2rem;
        }
        .navbar-brand {
            font-weight: 800;
            color: #4e73df !important;
        }
        /* Buttons */
        .btn {
            border-radius: 0.35rem;
            font-weight: 600;
            transition: all 0.2s;
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
            border-radius: 0.5rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        h1.page-title {
            color: #5a5c69;
            margin-bottom: 2rem;
            font-weight: 800;
            font-size: 1.75rem;
        }
    </style>
</head>
<body>
    <div class="main-wrapper">
        <?php include dirname(__FILE__) . '/sidebar.php'; ?>
        <div class="content-wrapper">
            <nav class="navbar navbar-expand-lg navbar-light navbar-wrapper">
                <div class="container-fluid">
                    <span class="navbar-brand mb-0 h1 d-flex align-items-center">
                        <?php if($school_logo): ?>
                            <img src="<?php echo htmlspecialchars($school_logo); ?>" alt="Logo" style="height: 30px; margin-right: 10px;">
                        <?php else: ?>
                            <i class="fas fa-graduation-cap me-2"></i>
                        <?php endif; ?>
                        <?php echo htmlspecialchars($school_name); ?>
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
