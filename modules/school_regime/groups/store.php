<?php

require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /nova1/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /nova1/modules/school_regime/groups/index.php');
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
                    AND p.code = 'school_regime.groups.manage'
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

$institutionId = (int)($_POST['institution_id'] ?? 0);
$code          = trim($_POST['code'] ?? '');
$name          = trim($_POST['name'] ?? '');
$description   = trim($_POST['description'] ?? '');
$status        = ($_POST['status'] ?? 'active') === 'closed' ? 'closed' : 'active';

if ($institutionId <= 0 || $code === '' || $name === '') {
    header('Location: /nova1/modules/school_regime/groups/create.php?error=required');
    exit;
}

$sqlCheck = "SELECT id 
             FROM study_groups 
             WHERE institution_id = ? 
               AND code = ?
             LIMIT 1";

$stmtCheck = $conn->prepare($sqlCheck);
$stmtCheck->bind_param('is', $institutionId, $code);
$stmtCheck->execute();
$resultCheck = $stmtCheck->get_result();

if ($resultCheck && $resultCheck->num_rows > 0) {
    $stmtCheck->close();
    header('Location: /nova1/modules/school_regime/groups/create.php?error=duplicate');
    exit;
}

$stmtCheck->close();

$conn->begin_transaction();

try {
    $sqlInsert = "INSERT INTO study_groups
                    (institution_id, code, name, description, status, created_at, updated_at)
                  VALUES (?, ?, ?, ?, ?, NOW(), NOW())";

    $stmtInsert = $conn->prepare($sqlInsert);

    if (!$stmtInsert) {
        throw new Exception('Error al preparar inserci¨®n.');
    }

    $stmtInsert->bind_param(
        'issss',
        $institutionId,
        $code,
        $name,
        $description,
        $status
    );

    $stmtInsert->execute();
    $stmtInsert->close();

    $conn->commit();

    header('Location: /nova1/modules/school_regime/groups/index.php?msg=created');
    exit;

} catch (Exception $e) {
    $conn->rollback();

    header('Location: /nova1/modules/school_regime/groups/create.php?error=save');
    exit;
}