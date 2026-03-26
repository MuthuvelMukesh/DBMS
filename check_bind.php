<?php
$files = glob('{*,*/*}.php', GLOB_BRACE);
if (!$files) { $files = []; }
$dirs = ['attendance','exams','fees','hostel','results','staff','students','transport'];
foreach($dirs as $d) {
    if(is_dir($d)) {
        $subfiles = glob("$d/*.php");
        if($subfiles) {
            $files = array_merge($files, $subfiles);
        }
    }
}
$files = array_unique($files);

foreach($files as $f) {
    $content = file_get_contents($f);
    if (preg_match_all('/bind_param\s*\(\s*[\'\"]([^\'\"]+)[\'\"]\s*,([^\)]+)\)/', $content, $matches)) {
        foreach($matches[1] as $idx => $types) {
            // Count commas in the variable list to get number of variables
            $vars = check_vars($matches[2][$idx]);
            $types_len = strlen($types);
            if ($types_len != $vars) {
                echo "Mismatch in $f: types '$types' ($types_len chars) vs $vars vars\n";
                echo "Vars section: " . trim($matches[2][$idx]) . "\n\n";
            }
        }
    }
}

function check_vars($str) {
    $str = trim($str);
    if (empty($str)) return 0;
    // poor man's count variables: count commas + 1
    // warning: fails if function calls inside bind_param have commas
    return substr_count($str, ',') + 1;
}
?>