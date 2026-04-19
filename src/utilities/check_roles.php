<?php
/**
 * Role Verification Utility (CLI)
 * 
 * Scans PHP files to verify that all endpoint files have proper role checks
 * Usage: php src/utilities/check_roles.php
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

$root_dir = dirname(__DIR__, 2);
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root_dir));
$missing = [];

foreach ($files as $file) {
    if ($file->getExtension() !== 'php') {
        continue;
    }

    $path = $file->getPathname();
    
    // Skip vendor, config, includes, and root files
    if (strpos($path, 'vendor') !== false || 
        strpos($path, 'config') !== false ||
        strpos($path, 'includes') !== false ||
        strpos($path, 'src/utilities') !== false ||
        dirname($path) === $root_dir) {
        continue;
    }

    $content = file_get_contents($path);
    // Check for role assignment or role verification in first 400 chars
    if (!preg_match('/(\$role[^=]+=)|(in_array\(\$role)|(\$role\s*!==?)/', substr($content, 0, 400))) {
        $missing[] = str_replace($root_dir . '\\', '', $path);
    }
}

if (empty($missing)) {
    echo "✓ All endpoint files have proper role checks.\n";
} else {
    echo "✗ Files missing role checks:\n";
    echo implode("\n", $missing) . "\n";
}
