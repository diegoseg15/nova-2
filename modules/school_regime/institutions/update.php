<?php

require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /nova1/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /nova1/modules/school_regime/institutions/index.php');
    exit;
}

$db = new Database();
$conn = $db->connect();

$roleId = (int)($_SESSION['role_id'] ?? 0);
$canManage = false;

$sqlPermission = "SELECT 1
                  FROM role_permissions rp
                  INNER JOIN permissions p ON p.id = rp.permission_id
                  WHERE rp.role_id = ?
                    AND p.code = 'school_regime.institutions.manage'
                    AND p.status = 'active'
                  LIMIT 1";

$stmtPermission = $conn->prepare($sqlPermission);
$stmtPermission->bind_param('i', $roleId);
$stmtPermission->execute();
$resultPermission = $stmtPermission->get_result();
$canManage = ($resultPermission && $resultPermission->num_rows > 0);
$stmtPermission->close();

if (!$canManage) {
    header('Location: /nova1/dashboard.php');
    exit;
}

$id        = (int)($_POST['id'] ?? 0);
$code      = trim($_POST['code'] ?? '');
$name      = trim($_POST['name'] ?? '');
$legalName = trim($_POST['legal_name'] ?? '');
$ruc       = trim($_POST['ruc'] ?? '');
$phone     = trim($_POST['phone'] ?? '');
$email     = trim($_POST['email'] ?? '');
$address   = trim($_POST['address'] ?? '');
$status    = ($_POST['status'] ?? 'inactive') === 'active' ? 'active' : 'inactive';

if ($id <= 0 || $code === '' || $name === '') {
    header('Location: /nova1/modules/school_regime/institutions/index.php?error=required');
    exit;
}

$sqlCheck = "SELECT id FROM institutions WHERE code = ? AND id <> ? LIMIT 1";
$stmtCheck = $conn->prepare($sqlCheck);
$stmtCheck->bind_param('si', $code, $id);
$stmtCheck->execute();
$resultCheck = $stmtCheck->get_result();

if ($resultCheck && $resultCheck->num_rows > 0) {
    $stmtCheck->close();
    header('Location: /nova1/modules/school_regime/institutions/edit.php?id=' . $id . '&error=duplicate');
    exit;
}

$stmtCheck->close();

$conn->begin_transaction();

try {
    if ($status === 'active') {
        $stmtInactive = $conn->prepare("UPDATE institutions SET status = 'inactive' WHERE id <> ?");
        $stmtInactive->bind_param('i', $id);
        $stmtInactive->execute();
        $stmtInactive->close();
    }

    $sqlUpdate = "UPDATE institutions
                  SET code = ?,
                      name = ?,
                      legal_name = ?,
                      ruc = ?,
                      phone = ?,
                      email = ?,
                      address = ?,
                      status = ?,
                      updated_at = NOW()
                  WHERE id = ?
                  LIMIT 1";

    $stmtUpdate = $conn->prepare($sqlUpdate);

    if (!$stmtUpdate) {
        throw new Exception('Error al preparar actualizaci¨®n.');
    }

    $stmtUpdate->bind_param(
        'ssssssssi',
        $code,
        $name,
        $legalName,
        $ruc,
        $phone,
        $email,
        $address,
        $status,
        $id
    );

    $stmtUpdate->execute();
    $stmtUpdate->close();

    $conn->commit();

    header('Location: /nova1/modules/school_regime/institutions/index.php?msg=updated');
    exit;

} catch (Exception $e) {
    $conn->rollback();

    header('Location: /nova1/modules/school_regime/institutions/edit.php?id=' . $id . '&error=save');
    exit;
}