<?php
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('.'));
$missing = [];
foreach ($files as $file) {
    if ($file->getExtension() === 'php') {
        $path = $file->getPathname();
        if (strpos($path, 'vendor') !== false || dirname($path) == '.') continue;
        
        $content = file_get_contents($path);
        // Only checking inside module directories
        if (!preg_match('/(\$role[^=]+=)|(in_array\(\$role)|(\$role\s*!==?)/', substr($content, 0, 400))) {
            $missing[] = $path;
        }
    }
}
echo implode("\n", $missing);
