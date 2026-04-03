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
    'parent' => ['fees', 'attendance', 'results', 'SchoolMS', 'profile.php', 'dashboard.php', 'logout.php'],
    'student' => ['fees', 'attendance', 'results', 'exams', 'transport', 'hostel', 'SchoolMS', 'profile.php', 'dashboard.php', 'logout.php']
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
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: #f1f5f9;
            color: #334155;
            -webkit-font-smoothing: antialiased;
        }
        /* Fade-in animation for smooth page loads */
        .main-content {
            flex: 1;
            padding: 2.5rem;
            animation: fadeIn 0.4s ease-out forwards;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }
        /* Modern Cards */
        .card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: #ffffff;
        }
        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -2px rgba(0, 0, 0, 0.04);
        }
        .card-header {
            background-color: transparent;
            border-bottom: 1px solid #e2e8f0;
            border-radius: 1rem 1rem 0 0 !important;
            font-weight: 700;
            color: #0f172a;
            padding: 1.25rem 1.5rem;
        }
        /* Dashboard Stats Cards Overrides */
        .card.bg-primary { background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%) !important; border: none; }
        .card.bg-success { background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important; border: none; }
        .card.bg-warning { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important; border: none; }
        .card.bg-danger { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important; border: none; }
        .card.bg-info { background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%) !important; border: none; }
        
        .main-wrapper {
            display: flex;
        }
        .sidebar-wrapper {
            width: 260px;
            background: #0f172a;
            min-height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            color: #e2e8f0;
            overflow-y: auto;
            z-index: 1000;
            box-shadow: 4px 0 15px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        .sidebar-wrapper::-webkit-scrollbar { width: 5px; }
        .sidebar-wrapper::-webkit-scrollbar-thumb { background: #334155; border-radius: 5px; }
        
        /* Sidebar Link Enhancements */
        .sidebar-wrapper .nav-item .nav-link { justify-content: flex-start;
            color: #cbd5e1;
            font-weight: 600;
            padding: 0.85rem 1.25rem;
            border-radius: 0.5rem;
            margin: 0.25rem 1rem;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
        }
        .sidebar-wrapper .nav-item .nav-link i {
            width: 24px;
            font-size: 1.1rem;
            margin-right: 10px;
            color: #94a3b8;
            transition: all 0.2s ease;
        }
        .sidebar-wrapper .nav-item .nav-link:hover, 
        .sidebar-wrapper .nav-item .nav-link:focus,
        .sidebar-wrapper .nav-item .nav-link[aria-expanded="true"] {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.08);
            transform: translateX(3px);
        }
        .sidebar-wrapper .nav-item .nav-link:hover i,
        .sidebar-wrapper .nav-item .nav-link[aria-expanded="true"] i {
            color: #38bdf8;
        }
        .sidebar-wrapper .nav-item .collapse .nav-link {
            font-weight: 500;
            font-size: 0.9rem;
            padding: 0.6rem 1rem 0.6rem 2.8rem;
            margin: 0;
            border-radius: 0;
        }
        .sidebar-wrapper .nav-item .collapse .nav-link:hover {
            background: transparent;
            color: #38bdf8;
        }
        
        .content-wrapper {
            margin-left: 260px;
            flex: 1;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background-color: #f8fafc;
            transition: all 0.3s ease;
        }
        .navbar-wrapper {
            background: rgba(255, 255, 255, 0.9) !important;
            backdrop-filter: blur(10px);
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            padding: 0.75rem 2rem;
            position: sticky;
            top: 0;
            z-index: 999;
        }
        .navbar-brand {
            font-weight: 800;
            color: #0f172a !important;
            font-size: 1.25rem;
        }
        /* Buttons */
        .btn {
            border-radius: 0.5rem;
            font-weight: 600;
            padding: 0.5rem 1rem;
            transition: all 0.2s;
            letter-spacing: 0.3px;
        }
        .btn-primary { background-color: #3b82f6; border-color: #3b82f6; box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.4); }
        .btn-primary:hover { background-color: #2563eb; border-color: #2563eb; transform: translateY(-1px); box-shadow: 0 6px 8px -1px rgba(59, 130, 246, 0.5); }
        .btn-success { background-color: #10b981; border-color: #10b981; }
        @media (max-width: 768px) {
            .sidebar-wrapper {
                width: 76px;
            }
            .sidebar-wrapper:hover {
                width: 260px;
            }
            .content-wrapper {
                margin-left: 76px;
            }
            .sidebar-text {
                display: none;
            }
            .sidebar-wrapper:hover .sidebar-text {
                display: inline;
            }
        }
        
        .alert-dismissible {
            margin-bottom: 1.5rem;
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            border: none;
        }
        h1.page-title {
            color: #1e293b;
            margin-bottom: 2rem;
            font-weight: 800;
            font-size: 1.85rem;
            letter-spacing: -0.5px;
        }
        
        /* DataTables Customization */
        div.dataTables_wrapper div.dataTables_filter input {
            border-radius: 0.5rem;
            border: 1px solid #cbd5e1;
            padding: 0.4rem 0.8rem;
            margin-left: 0.5rem;
        }
        .table {
            color: #334155;
        }
        .table > :not(caption) > * > * {
            padding: 1rem 0.75rem;
            border-bottom-color: #e2e8f0;
        }
        .table thead th {
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            color: #64748b;
            border-bottom-width: 2px;
        }
        .badge {
            padding: 0.4em 0.8em;
            border-radius: 6px;
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
