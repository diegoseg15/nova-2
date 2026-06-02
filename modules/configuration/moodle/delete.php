<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/nova1/config/database.php';

$db = new Database();
$conn = $db->connect();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if($id > 0){
    $stmt = $conn->prepare("DELETE FROM moodle_configurations WHERE id = ?");
    $stmt->bind_param("i",$id);
    $stmt->execute();
}

header("Location: index.php");
exit;
?>