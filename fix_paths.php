<?php
$c = file_get_contents('sidebar.php');
$c = str_replace('href="dashboard.php"', 'href="<?php echo BASE_URL; ?>dashboard.php"', $c);
$c = preg_replace('/href="(students|staff|attendance|fees|exams|results|transport|hostel)\/([a-z_]+)\.php"/', 'href="<?php echo BASE_URL; ?>$1/$2.php"', $c);
file_put_contents('sidebar.php', $c);

$h = file_get_contents('header.php');
$h = str_replace('href="profile.php"', 'href="<?php echo BASE_URL; ?>profile.php"', $h);
$h = str_replace('href="logout.php"', 'href="<?php echo BASE_URL; ?>logout.php"', $h);
$h = str_replace('header("Location: login.php");', 'header("Location: " . BASE_URL . "login.php");', $h);
file_put_contents('header.php', $h);

$login = file_get_contents('login.php');
$login = str_replace('header("Location: dashboard.php");', 'header("Location: " . BASE_URL . "dashboard.php");', $login);
file_put_contents('login.php', $login);

echo "Fixed paths!\n";
