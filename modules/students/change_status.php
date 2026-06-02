<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';

// VALIDAR SESIÓN
if (!isset($_SESSION['user_id'])) {
    header('Location: /nova1/login.php');
    exit;
}

// VALIDAR CONTEXTO ACTIVO
if (
    !isset($_SESSION['study_group_id']) ||
    !isset($_SESSION['period_id']) ||
    !isset($_SESSION['context_locked']) ||
    $_SESSION['context_locked'] !== 'Y'
) {
    $_SESSION['error'] = '⚠ Debe activar un grupo y período antes de realizar esta acción.';
    header('Location: /nova1/dashboard.php');
    exit;
}

$db = new Database();
$conn = $db->connect();

$studentId    = (int) ($_GET['id'] ?? 0);
$newStatus    = trim($_GET['status'] ?? '');
$studyGroupId = (int) $_SESSION['study_group_id'];
$periodId     = (int) $_SESSION['period_id'];

// VALIDAR ID
if ($studentId <= 0) {
    $_SESSION['error'] = '⚠ Estudiante no válido.';
    header('Location: /nova1/modules/students/index.php');
    exit;
}

// VALIDAR STATUS PERMITIDOS
$allowedStatuses = ['active', 'retired', 'graduated', 'suspended', 'migrated'];

if (!in_array($newStatus, $allowedStatuses, true)) {
    $_SESSION['error'] = '⚠ Estado no permitido.';
    header('Location: /nova1/modules/students/index.php');
    exit;
}

// VALIDAR QUE EL ESTUDIANTE PERTENEZCA AL CONTEXTO ACTIVO
$sqlValidate = "
    SELECT se.id
    FROM student_enrollments se
    WHERE se.student_id = ?
      AND se.study_group_id = ?
      AND se.period_id = ?
    LIMIT 1
";

$stmtValidate = $conn->prepare($sqlValidate);
$stmtValidate->bind_param('iii', $studentId, $studyGroupId, $periodId);
$stmtValidate->execute();
$resultValidate = $stmtValidate->get_result();

if ($resultValidate->num_rows === 0) {
    $stmtValidate->close();
    $_SESSION['error'] = '⚠ El estudiante no pertenece al grupo y período activos.';
    header('Location: /nova1/modules/students/index.php');
    exit;
}

$enrollment = $resultValidate->fetch_assoc();
$enrollmentId = (int) $enrollment['id'];
$stmtValidate->close();

// ACTUALIZAR ESTADO (EN MATRÍCULA, NO EN STUDENTS)
$sqlUpdate = "
    UPDATE student_enrollments
    SET status = ?, updated_at = NOW()
    WHERE id = ?
    LIMIT 1
";

$stmtUpdate = $conn->prepare($sqlUpdate);
$stmtUpdate->bind_param('si', $newStatus, $enrollmentId);

if (!$stmtUpdate->execute()) {
    $stmtUpdate->close();
    $_SESSION['error'] = '⚠ No se pudo actualizar el estado del estudiante.';
    header('Location: /nova1/modules/students/index.php');
    exit;
}

$stmtUpdate->close();

// MENSAJE SEGÚN ESTADO
$statusMessages = [
    'active'    => '✅ Estudiante activado correctamente.',
    'retired'   => '⚠ Estudiante marcado como retirado.',
    'graduated' => '🎓 Estudiante marcado como graduado.',
    'suspended' => '⛔ Estudiante suspendido.',
    'migrated'  => '🔄 Estudiante migrado correctamente.'
];

$_SESSION['success'] = $statusMessages[$newStatus] ?? '✅ Estado actualizado correctamente.';

// REDIRECCIÓN
header('Location: /nova1/modules/students/index.php');
exit;