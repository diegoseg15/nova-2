<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/nova1/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/nova1/app/helpers/crypto.php';

$db = new Database();
$conn = $db->connect();

$id             = (int) $_POST['id'];
$institution_id = (int) $_POST['institution_id'];
$group_id       = (int) $_POST['group_id'];
$moodle_url     = trim($_POST['moodle_url']);
$api_token      = trim($_POST['api_token']);
$status         = $_POST['status'];

if ($api_token !== '') {

    $encrypted_token = encryptValue($api_token);

    $stmt = $conn->prepare("
        UPDATE moodle_configurations
        SET institution_id = ?, group_id = ?, moodle_url = ?, api_token = ?, status = ?
        WHERE id = ?
    ");

    $stmt->bind_param("iisssi",
        $institution_id,
        $group_id,
        $moodle_url,
        $encrypted_token,
        $status,
        $id
    );

} else {

    $stmt = $conn->prepare("
        UPDATE moodle_configurations
        SET institution_id = ?, group_id = ?, moodle_url = ?, status = ?
        WHERE id = ?
    ");

    $stmt->bind_param("iissi",
        $institution_id,
        $group_id,
        $moodle_url,
        $status,
        $id
    );
}

$stmt->execute();

header("Location: index.php");
exit;
?>