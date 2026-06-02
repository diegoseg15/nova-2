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
                    AND p.code = 'school_regime.periods.manage'
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

$id           = (int)($_POST['id'] ?? 0);
$studyGroupId = (int)($_POST['study_group_id'] ?? 0);
$code         = trim($_POST['code'] ?? '');
$name         = trim($_POST['name'] ?? '');
$startDate    = trim($_POST['start_date'] ?? '');
$endDate      = trim($_POST['end_date'] ?? '');
$status       = ($_POST['status'] ?? 'open') === 'closed' ? 'closed' : 'open';

if ($id <= 0 || $studyGroupId <= 0 || $code === '' || $name === '' || $startDate === '' || $endDate === '') {
    header('Location: /nova1/modules/school_regime/periods/index.php?error=required');
    exit;
}

if ($endDate < $startDate) {
    header('Location: /nova1/modules/school_regime/periods/edit.php?id=' . $id . '&error=dates');
    exit;
}

$sqlCurrent = "SELECT id, status FROM periods WHERE id = ? LIMIT 1";
$stmtCurrent = $conn->prepare($sqlCurrent);
$stmtCurrent->bind_param('i', $id);
$stmtCurrent->execute();
$resultCurrent = $stmtCurrent->get_result();
$current = $resultCurrent ? $resultCurrent->fetch_assoc() : null;
$stmtCurrent->close();

if (!$current || $current['status'] !== 'open') {
    header('Location: /nova1/modules/school_regime/periods/index.php?error=closed');
    exit;
}

$sqlGroup = "SELECT id, institution_id FROM study_groups WHERE id = ? LIMIT 1";
$stmtGroup = $conn->prepare($sqlGroup);
$stmtGroup->bind_param('i', $studyGroupId);
$stmtGroup->execute();
$resultGroup = $stmtGroup->get_result();
$group = $resultGroup ? $resultGroup->fetch_assoc() : null;
$stmtGroup->close();

if (!$group) {
    header('Location: /nova1/modules/school_regime/periods/edit.php?id=' . $id . '&error=group');
    exit;
}

$institutionId = (int)$group['institution_id'];

$sqlCheck = "SELECT id
             FROM periods
             WHERE study_group_id = ?
               AND code = ?
               AND id <> ?
             LIMIT 1";

$stmtCheck = $conn->prepare($sqlCheck);
$stmtCheck->bind_param('isi', $studyGroupId, $code, $id);
$stmtCheck->execute();
$resultCheck = $stmtCheck->get_result();

if ($resultCheck && $resultCheck->num_rows > 0) {
    $stmtCheck->close();
    header('Location: /nova1/modules/school_regime/periods/edit.php?id=' . $id . '&error=duplicate');
    exit;
}

$stmtCheck->close();

$conn->begin_transaction();

try {
    if ($status === 'open') {
        $stmtClose = $conn->prepare("UPDATE periods SET status = 'closed' WHERE study_group_id = ? AND status = 'open' AND id <> ?");
        $stmtClose->bind_param('ii', $studyGroupId, $id);
        $stmtClose->execute();
        $stmtClose->close();
    }

    $sqlUpdate = "UPDATE periods
                  SET institution_id = ?,
                      study_group_id = ?,
                      code = ?,
                      name = ?,
                      start_date = ?,
                      end_date = ?,
                      status = ?,
                      updated_at = NOW()
                  WHERE id = ?
                  LIMIT 1";

    $stmtUpdate = $conn->prepare($sqlUpdate);

    if (!$stmtUpdate) {
        throw new Exception('Error al preparar actualizaci¨®n.');
    }

    $stmtUpdate->bind_param(
        'iisssssi',
        $institutionId,
        $studyGroupId,
        $code,
        $name,
        $startDate,
        $endDate,
        $status,
        $id
    );

    $stmtUpdate->execute();
    $stmtUpdate->close();

    $conn->commit();

    header('Location: /nova1/modules/school_regime/periods/index.php?msg=updated');
    exit;

} catch (Exception $e) {
    $conn->rollback();

    header('Location: /nova1/modules/school_regime/periods/edit.php?id=' . $id . '&error=save');
    exit;
}