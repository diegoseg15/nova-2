<?php

require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /nova1/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /nova1/modules/school_regime/periods/index.php');
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
                    AND p.code = 'school_regime.courses.manage'
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

$id          = (int)($_POST['id'] ?? 0);
$periodId    = (int)($_POST['period_id'] ?? 0);
$code        = trim($_POST['code'] ?? '');
$name        = trim($_POST['name'] ?? '');
$description = trim($_POST['description'] ?? '');
$status      = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';

if ($id <= 0 || $periodId <= 0 || $code === '' || $name === '') {
    header('Location: /nova1/modules/school_regime/courses/index.php?period_id=' . $periodId . '&error=required');
    exit;
}

$sqlPeriod = "SELECT status FROM periods WHERE id = ? LIMIT 1";
$stmtPeriod = $conn->prepare($sqlPeriod);
$stmtPeriod->bind_param('i', $periodId);
$stmtPeriod->execute();
$resultPeriod = $stmtPeriod->get_result();
$period = $resultPeriod ? $resultPeriod->fetch_assoc() : null;
$stmtPeriod->close();

if (!$period || $period['status'] !== 'open') {
    header('Location: /nova1/modules/school_regime/courses/index.php?period_id=' . $periodId . '&error=closed');
    exit;
}

$sqlCheck = "SELECT id 
             FROM courses 
             WHERE period_id = ? 
               AND code = ? 
               AND id <> ? 
             LIMIT 1";

$stmtCheck = $conn->prepare($sqlCheck);
$stmtCheck->bind_param('isi', $periodId, $code, $id);
$stmtCheck->execute();
$resultCheck = $stmtCheck->get_result();

if ($resultCheck && $resultCheck->num_rows > 0) {
    $stmtCheck->close();
    header('Location: /nova1/modules/school_regime/courses/edit.php?id=' . $id . '&error=duplicate');
    exit;
}

$stmtCheck->close();

$conn->begin_transaction();

try {
    $sqlUpdate = "UPDATE courses
                  SET code = ?,
                      name = ?,
                      description = ?,
                      status = ?,
                      updated_at = NOW()
                  WHERE id = ?
                  LIMIT 1";

    $stmtUpdate = $conn->prepare($sqlUpdate);

    if (!$stmtUpdate) {
        throw new Exception('Error al preparar actualización.');
    }

    $stmtUpdate->bind_param(
        'ssssi',
        $code,
        $name,
        $description,
        $status,
        $id
    );

    $stmtUpdate->execute();
    $stmtUpdate->close();

    $conn->commit();

    header('Location: /nova1/modules/school_regime/courses/index.php?period_id=' . $periodId . '&msg=updated');
    exit;

} catch (Exception $e) {
    $conn->rollback();

    header('Location: /nova1/modules/school_regime/courses/edit.php?id=' . $id . '&error=save');
    exit;
}