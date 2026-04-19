<?php
/**
 * Password Hashing Utility
 * 
 * CLI utility for generating secure password hashes
 * Usage: php src/utilities/hash.php <password>
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

$password = $argv[1] ?? '';
if ($password === '') {
    fwrite(STDERR, "Usage: php src/utilities/hash.php <password>\n");
    exit(1);
}

echo password_hash($password, PASSWORD_DEFAULT) . PHP_EOL;
