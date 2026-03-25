<?php
require_once '../dbconfig.php';

$transport_id = isset($_GET['transport_id']) ? (int)$_GET['transport_id'] : 0;

if ($transport_id <= 0) {
    echo json_encode([]);
    exit();
}

$stmt = $conn->prepare("SELECT stops FROM transport WHERE id = ?");
$stmt->bind_param("i", $transport_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($result) {
    $stops = array_map('trim', explode(',', $result['stops']));
    echo json_encode($stops);
} else {
    echo json_encode([]);
}
?>
