<?php
$c = file_get_contents('sidebar.php');
$c = str_replace('<li class="nav-item">
                <a class="nav-link text-white py-3 px-4" data-bs-toggle="collapse" href="#staffMenu"', 
'<?php if(in_array($role, [\'admin\'])): ?>
            <li class="nav-item">
                <a class="nav-link text-white py-3 px-4" data-bs-toggle="collapse" href="#staffMenu"', $c); 

$c = str_replace('<li class="nav-item">
                <a class="nav-link text-white py-3 px-4" data-bs-toggle="collapse" href="#attendanceMenu"', 
'<?php endif; ?>
            <?php if(in_array($role, [\'admin\', \'teacher\', \'parent\'])): ?>
            <li class="nav-item">
                <a class="nav-link text-white py-3 px-4" data-bs-toggle="collapse" href="#attendanceMenu"', $c);

$c = str_replace('<li class="nav-item">
                <a class="nav-link text-white py-3 px-4" data-bs-toggle="collapse" href="#feesMenu"', 
'<?php endif; ?>
            <?php if(in_array($role, [\'admin\', \'parent\'])): ?>
            <li class="nav-item">
                <a class="nav-link text-white py-3 px-4" data-bs-toggle="collapse" href="#feesMenu"', $c);

$c = str_replace('<li class="nav-item">
                <a class="nav-link text-white py-3 px-4" data-bs-toggle="collapse" href="#examsMenu"', 
'<?php endif; ?>
            <?php if(in_array($role, [\'admin\', \'teacher\'])): ?>
            <li class="nav-item">
                <a class="nav-link text-white py-3 px-4" data-bs-toggle="collapse" href="#examsMenu"', $c);

$c = str_replace('<li class="nav-item">
                <a class="nav-link text-white py-3 px-4" data-bs-toggle="collapse" href="#resultsMenu"', 
'<?php endif; ?>
            <?php if(in_array($role, [\'admin\', \'teacher\', \'parent\'])): ?>
            <li class="nav-item">
                <a class="nav-link text-white py-3 px-4" data-bs-toggle="collapse" href="#resultsMenu"', $c);

$c = str_replace('<li class="nav-item">
                <a class="nav-link text-white py-3 px-4" data-bs-toggle="collapse" href="#transportMenu"', 
'<?php endif; ?>
            <?php if(in_array($role, [\'admin\', \'staff\'])): ?>
            <li class="nav-item">
                <a class="nav-link text-white py-3 px-4" data-bs-toggle="collapse" href="#transportMenu"', $c);

$c = str_replace('<li class="nav-item">
                <a class="nav-link text-white py-3 px-4" data-bs-toggle="collapse" href="#hostelMenu"', 
'<?php endif; ?>
            <?php if(in_array($role, [\'admin\', \'staff\'])): ?>
            <li class="nav-item">
                <a class="nav-link text-white py-3 px-4" data-bs-toggle="collapse" href="#hostelMenu"', $c);

$c = str_replace('</ul>
        </ul>', '</ul>
            <?php endif; ?>
        </ul>', $c);

file_put_contents('sidebar.php', $c);
?>