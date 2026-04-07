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

if (empty($_SESSION['csrf_token'])) {
    try {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    } catch (Throwable $e) {
        $_SESSION['csrf_token'] = hash('sha256', session_id() . microtime(true));
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submitted_token = $_POST['_csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!is_string($submitted_token) || $submitted_token === '' || !hash_equals($_SESSION['csrf_token'], $submitted_token)) {
        http_response_code(419);
        echo 'Invalid request token. Please refresh the page and try again.';
        exit();
    }
}

if (!function_exists('csrf_token_value')) {
    function csrf_token_value() {
        return $_SESSION['csrf_token'] ?? '';
    }
}

if (!function_exists('csrf_input')) {
    function csrf_input() {
        return '<input type="hidden" name="_csrf_token" value="' . htmlspecialchars(csrf_token_value(), ENT_QUOTES, 'UTF-8') . '">';
    }
}

if (!function_exists('ensure_parent_student_links_table')) {
    function ensure_parent_student_links_table($conn) {
        static $checked = null;

        if ($checked !== null) {
            return $checked;
        }

        $sql = "CREATE TABLE IF NOT EXISTS parent_student_links (
            id INT PRIMARY KEY AUTO_INCREMENT,
            parent_user_id INT NOT NULL,
            student_id INT NOT NULL,
            relationship VARCHAR(50) DEFAULT NULL,
            status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_parent_student (parent_user_id, student_id),
            INDEX idx_parent_user (parent_user_id),
            INDEX idx_student (student_id),
            FOREIGN KEY (parent_user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $checked = (bool) $conn->query($sql);
        return $checked;
    }
}

if (!function_exists('get_parent_students')) {
    function get_parent_students($conn, $parent_user_id) {
        if (!ensure_parent_student_links_table($conn)) {
            return [];
        }

        $stmt = $conn->prepare(
            "SELECT s.id, s.admission_no, s.full_name, s.class_id, s.section
             FROM parent_student_links psl
             JOIN students s ON s.id = psl.student_id
             WHERE psl.parent_user_id = ?
               AND psl.status = 'active'
               AND s.status = 'active'
             ORDER BY s.full_name"
        );
        $stmt->bind_param('i', $parent_user_id);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $rows;
    }
}

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
    'parent' => ['fees', 'attendance', 'results', 'transport', 'hostel', 'SchoolMS', 'profile.php', 'dashboard.php', 'logout.php'],
    'student' => ['fees', 'attendance', 'results', 'exams', 'transport', 'hostel', 'SchoolMS', 'profile.php', 'dashboard.php', 'logout.php']
];

$current_script = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
$is_root_file = ($current_dir === 'SchoolMS' || $current_dir === 'htdocs' || $current_dir === '');

if (!isset($permissions[$role])) {
    header("Location: " . BASE_URL . "dashboard.php?error=Access Denied");
    exit();
}

if ($is_root_file) {
    if (!in_array($current_script, $permissions[$role], true)) {
        header("Location: " . BASE_URL . "dashboard.php?error=Access Denied");
        exit();
    }
} else {
    if (!in_array($current_dir, $permissions[$role], true)) {
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
        .card.bg-primary { background: #eff6ff !important; color: #1e3a8a !important; border: 1px solid #bfdbfe !important; }
        .card.bg-success { background: #f0fdf4 !important; color: #064e3b !important; border: 1px solid #bbf7d0 !important; }
        .card.bg-warning { background: #fffbeb !important; color: #78350f !important; border: 1px solid #fde68a !important; }
        .card.bg-danger { background: #fef2f2 !important; color: #7f1d1d !important; border: 1px solid #fecaca !important; }
        .card.bg-info { background: #ecfeff !important; color: #164e63 !important; border: 1px solid #a5f3fc !important; }
        .card[class*="bg-"] { color: inherit !important; }
        .card[class*="bg-"] * { color: inherit !important; }
        .card .card-body h5, .card .card-body h2 { color: inherit !important; }
        .card .display-4 { color: inherit !important; }
        
        .main-wrapper {
            display: flex;
        }
        .sidebar-wrapper {
            width: 260px;
            background: #ffffff;
            min-height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            color: #334155;
            overflow-y: auto;
            z-index: 1000;
            border-right: 1px solid #e2e8f0;
            box-shadow: 4px 0 15px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        .sidebar-wrapper::-webkit-scrollbar { width: 5px; }
        .sidebar-wrapper::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 5px; }
        
        /* Sidebar Link Enhancements */
        .sidebar-wrapper .nav-item .nav-link { justify-content: flex-start;
            color: #475569;
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
            color: #64748b;
            transition: all 0.2s ease;
        }
        .sidebar-wrapper .nav-item .nav-link:hover, 
        .sidebar-wrapper .nav-item .nav-link:focus,
        .sidebar-wrapper .nav-item .nav-link[aria-expanded="true"] {
            color: #0f172a;
            background: #f1f5f9;
            transform: translateX(3px);
        }
        .sidebar-wrapper .nav-item .nav-link:hover i,
        .sidebar-wrapper .nav-item .nav-link[aria-expanded="true"] i {
            color: #3b82f6;
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
            color: #3b82f6;
        }
        .sidebar-brand {
            padding: 1.5rem 1.25rem;
            font-size: 1.5rem;
            font-weight: 800;
            color: #0f172a;
            display: flex;
            align-items: center;
        }
        .sidebar-brand i {
            color: #3b82f6;
            margin-right: 10px;
        }
        .sidebar-brand span {
            color: #0f172a;
        }
        .sidebar-brand span span {
            color: #3b82f6;
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
