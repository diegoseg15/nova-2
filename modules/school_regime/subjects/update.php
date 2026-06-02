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
                    AND p.code = 'school_regime.subjects.manage'
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
$courseId    = (int)($_POST['course_id'] ?? 0);
$code        = trim($_POST['code'] ?? '');
$name        = trim($_POST['name'] ?? '');
$description = trim($_POST['description'] ?? '');
$cycle       = ($_POST['cycle'] ?? 'C1') === 'C2' ? 'C2' : 'C1';
$status      = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';

if ($id <= 0 || $courseId <= 0 || $code === '' || $name === '') {
    header('Location: /nova1/modules/school_regime/subjects/index.php?course_id=' . $courseId . '&error=required');
    exit;
}

$sqlCourse = "SELECT p.status
              FROM courses c
              INNER JOIN periods p ON p.id = c.period_id
              WHERE c.id = ?
              LIMIT 1";

$stmtCourse = $conn->prepare($sqlCourse);
$stmtCourse->bind_param('i', $courseId);
$stmtCourse->execute();
$resultCourse = $stmtCourse->get_result();
$course = $resultCourse ? $resultCourse->fetch_assoc() : null;
$stmtCourse->close();

if (!$course || $course['status'] !== 'open') {
    header('Location: /nova1/modules/school_regime/subjects/index.php?course_id=' . $courseId . '&error=closed');
    exit;
}

$sqlCheck = "SELECT id
             FROM subjects
             WHERE course_id = ?
               AND code = ?
               AND id <> ?
             LIMIT 1";

$stmtCheck = $conn->prepare($sqlCheck);
$stmtCheck->bind_param('isi', $courseId, $code, $id);
$stmtCheck->execute();
$resultCheck = $stmtCheck->get_result();

if ($resultCheck && $resultCheck->num_rows > 0) {
    $stmtCheck->close();
    header('Location: /nova1/modules/school_regime/subjects/edit.php?id=' . $id . '&error=duplicate');
    exit;
}

$stmtCheck->close();

$conn->begin_transaction();

try {
    $sqlUpdate = "UPDATE subjects
                  SET code = ?,
                      name = ?,
                      description = ?,
                      cycle = ?,
                      status = ?,
                      updated_at = NOW()
                  WHERE id = ?
                  LIMIT 1";

    $stmtUpdate = $conn->prepare($sqlUpdate);

    if (!$stmtUpdate) {
        throw new Exception('Error al preparar actualizaci¨®n.');
    }

    $stmtUpdate->bind_param(
        'sssssi',
        $code,
        $name,
        $description,
        $cycle,
        $status,
        $id
    );

    $stmtUpdate->execute();
    $stmtUpdate->close();

    $conn->commit();

    header('Location: /nova1/modules/school_regime/subjects/index.php?course_id=' . $courseId . '&msg=updated');
    exit;

} catch (Exception $e) {
    $conn->rollback();

    header('Location: /nova1/modules/school_regime/subjects/edit.php?id=' . $id . '&error=save');
    exit;
}