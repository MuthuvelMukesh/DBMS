<?php
/**
 * Header Include
 * 
 * This file is included at the top of all authenticated pages.
 * - Initializes session
 * - Loads database connection
 * - Sets up CSRF token protection
 * - Includes role-based access control
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load configuration
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$role = $_SESSION['role'];

// Initialize CSRF token
if (empty($_SESSION['csrf_token'])) {
    try {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    } catch (Throwable $e) {
        $_SESSION['csrf_token'] = hash('sha256', session_id() . microtime(true));
    }
}

// CSRF Token Validation for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submitted_token = $_POST['_csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!is_string($submitted_token) || $submitted_token === '' || !hash_equals($_SESSION['csrf_token'], $submitted_token)) {
        http_response_code(419);
        echo 'Invalid request token. Please refresh the page and try again.';
        exit();
    }
}

// CSRF Helper Functions
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

// Parent-Student Links Table Setup
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
$body_role_class = 'role-' . preg_replace('/[^a-z0-9_-]/i', '-', strtolower((string) $role));

// Role-Based Access Control
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
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>public/css/app.css">
</head>
<body class="app-shell <?php echo htmlspecialchars($body_role_class, ENT_QUOTES, 'UTF-8'); ?>">
    <a class="skip-link" href="#main-content">Skip to main content</a>
    <button
        class="btn btn-primary app-menu-toggle d-lg-none"
        type="button"
        id="appMenuToggle"
        aria-controls="appSidebar"
        aria-expanded="false"
        aria-label="Toggle navigation menu"
    >
        <i class="fas fa-bars" aria-hidden="true"></i>
    </button>
    <div class="app-layout d-flex">
        <?php require_once dirname(__FILE__) . '/sidebar.php'; ?>
        <div class="app-sidebar-overlay" id="appSidebarOverlay" hidden></div>
        <main class="flex-grow-1 app-main" id="main-content" tabindex="-1">
