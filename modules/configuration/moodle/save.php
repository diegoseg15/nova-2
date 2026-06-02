<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/nova1/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/nova1/app/helpers/crypto.php';

$db = new Database();
$conn = $db->connect();

$institution_id = (int) $_POST['institution_id'];
$group_id       = (int) $_POST['group_id'];
$moodle_url     = trim($_POST['moodle_url']);
$api_token      = trim($_POST['api_token']);
$status         = $_POST['status'];

$encrypted_token = encryptValue($api_token);

$stmt = $conn->prepare("
    INSERT INTO moodle_configurations 
    (institution_id, group_id, moodle_url, api_token, status) 
    VALUES (?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "iisss",
    $institution_id,
    $group_id,
    $moodle_url,
    $encrypted_token,
    $status
);

$stmt->execute();

header("Location: index.php");
exit;