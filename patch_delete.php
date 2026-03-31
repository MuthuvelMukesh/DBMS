<?php
$f = 'c:/xampp/htdocs/SchoolMS/students/delete.php';
if(file_exists($f)){
    $c = file_get_contents($f);
    if (strpos($c, 'Access Denied') === false) {
        $insert = "\nif (\$role !== 'admin') {\n    header('Location: ' . BASE_URL . 'dashboard.php?error=Access Denied');\n    exit();\n}\n";
        $c = preg_replace("/require_once '..\/header.php';/", "require_once '../header.php';" . $insert, $c, 1);
        file_put_contents($f, $c);
        echo "Patched delete.php\n";
    }
}
