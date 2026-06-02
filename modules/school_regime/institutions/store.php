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

$code       = trim($_POST['code'] ?? '');
$name       = trim($_POST['name'] ?? '');
$legalName  = trim($_POST['legal_name'] ?? '');
$ruc        = trim($_POST['ruc'] ?? '');
$phone      = trim($_POST['phone'] ?? '');
$email      = trim($_POST['email'] ?? '');
$address    = trim($_POST['address'] ?? '');
$status     = ($_POST['status'] ?? 'inactive') === 'active' ? 'active' : 'inactive';

if ($code === '' || $name === '') {
    header('Location: /nova1/modules/school_regime/institutions/create.php?error=required');
    exit;
}

$sqlCheck = "SELECT id FROM institutions WHERE code = ? LIMIT 1";
$stmtCheck = $conn->prepare($sqlCheck);
$stmtCheck->bind_param('s', $code);
$stmtCheck->execute();
$resultCheck = $stmtCheck->get_result();

if ($resultCheck && $resultCheck->num_rows > 0) {
    $stmtCheck->close();
    header('Location: /nova1/modules/school_regime/institutions/create.php?error=duplicate');
    exit;
}

$stmtCheck->close();

$conn->begin_transaction();

try {
    if ($status === 'active') {
        $conn->query("UPDATE institutions SET status = 'inactive' WHERE status = 'active'");
    }

    $sqlInsert = "INSERT INTO institutions
                    (code, name, legal_name, ruc, phone, email, address, status, created_at, updated_at)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

    $stmtInsert = $conn->prepare($sqlInsert);

    if (!$stmtInsert) {
        throw new Exception('Error al preparar inserci¨®n.');
    }

    $stmtInsert->bind_param(
        'ssssssss',
        $code,
        $name,
        $legalName,
        $ruc,
        $phone,
        $email,
        $address,
        $status
    );

    $stmtInsert->execute();
    $stmtInsert->close();

    $conn->commit();

    header('Location: /nova1/modules/school_regime/institutions/index.php?msg=created');
    exit;

} catch (Exception $e) {
    $conn->rollback();

    header('Location: /nova1/modules/school_regime/institutions/create.php?error=save');
    exit;
}