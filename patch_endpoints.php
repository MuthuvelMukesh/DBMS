<?php
$patches = [
    'hostel/assign.php' => "['admin', 'staff']",
    'hostel/rooms.php'  => "['admin', 'staff']",
    'hostel/vacate.php' => "['admin', 'staff']",
    'transport/assign.php' => "['admin', 'staff']",
    'transport/routes.php' => "['admin', 'staff']",
    'transport/get_stops.php' => "['admin', 'staff']",
    'settings/index.php' => "['admin']",
    'notices/index.php'  => "['admin']",
    'students/add.php'   => "['admin', 'teacher']",
    'students/edit.php'  => "['admin', 'teacher']",
    'students/list.php'  => "['admin', 'teacher']",
    'students/view.php'  => "['admin', 'teacher']",
    'classes/list.php'   => "['admin', 'teacher']",
];

foreach ($patches as $file => $roles) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        $content = file_get_contents($path);
        
        // Skip if already patched
        if (strpos($content, "in_array(\$role") !== false || strpos($content, "in_array( \$role") !== false) {
            echo "Skipping $file (already has role check)\n";
            continue;
        }

        $patchBlock = "
if (!in_array(\$role, $roles)) {
    header('Location: ' . BASE_URL . 'dashboard.php?error=Access Denied');
    exit();
}
";
        // Insert after require_once '../header.php';
        $content = preg_replace("/(require_once\s+['\"].*?header\.php['\"];)/i", "$1\n" . $patchBlock, $content, 1, $count);
        
        if ($count > 0) {
            file_put_contents($path, $content);
            echo "Patched $file\n";
        } else {
            echo "Failed to patch $file (header.php not found)\n";
        }
    } else {
        echo "File not found: $file\n";
    }
}
