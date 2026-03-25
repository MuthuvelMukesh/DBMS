<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once dirname(__FILE__) . '/dbconfig.php';
session_destroy();
header("Location: " . BASE_URL . "login.php");
exit();
?>
