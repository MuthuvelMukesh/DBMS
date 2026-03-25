<?php
ob_start();
$servername = "localhost";
$username = "root";
$password = "1234";
$dbname = "schoolms";
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
define('BASE_URL', '/SchoolMS/');