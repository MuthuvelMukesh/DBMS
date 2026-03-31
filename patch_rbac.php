<?php
$map = [
    'attendance/mark.php' => ['admin','teacher'],
    'fees/add.php' => ['admin'],
    'fees/collect.php' => ['admin'],
    'exams/add.php' => ['admin'],
    'results/add.php' => ['admin','teacher'],
    'results/marksheet.php' => ['admin','teacher']
];

foreach ($map as $file => $roles) {
    $path = 'c:/xampp/htdocs/SchoolMS/' . $file;
    if (file_exists($path)) {
        $content = file_get_contents($path);
        
        // Skip if already patched
        if (strpos($content, 'Access Denied') !== false) {
            echo "Already patched $file\n";
            continue;
        }

        $roles_str = "'" . implode("', '", $roles) . "'";
        $insert = "\nif (!in_array(\$role, [$roles_str])) {\n    header('Location: ' . BASE_URL . 'dashboard.php?error=Access Denied');\n    exit();\n}\n";
        
        $content = preg_replace("/require_once '..\/header.php';/", "require_once '../header.php';" . $insert, $content, 1);
        file_put_contents($path, $content);
        echo "Patched $file\n";
    }
}
echo "Done";
