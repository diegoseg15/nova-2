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
    $_SESSION['error'] = '⚠ Debe activar un grupo y período antes de registrar estudiantes.';
    header('Location: /nova1/dashboard.php');
    exit;
}

$db = new Database();
$conn = $db->connect();

$studyGroupId = (int) $_SESSION['study_group_id'];
$periodId     = (int) $_SESSION['period_id'];

// LIMPIAR MENSAJE VIEJO
if (
    isset($_SESSION['error']) &&
    $_SESSION['error'] === '⚠ Debe activar un grupo y período antes de registrar estudiantes.'
) {
    unset($_SESSION['error']);
}

// MENSAJES
$error   = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';
unset($_SESSION['error'], $_SESSION['success']);

// OBTENER NOMBRE DEL GRUPO
$groupName = 'Grupo activo';
$sqlGroup = "SELECT name FROM study_groups WHERE id = ? LIMIT 1";
$stmtGroup = $conn->prepare($sqlGroup);
$stmtGroup->bind_param('i', $studyGroupId);
$stmtGroup->execute();
$resGroup = $stmtGroup->get_result();
if ($row = $resGroup->fetch_assoc()) {
    $groupName = $row['name'];
}
$stmtGroup->close();

// OBTENER NOMBRE DEL PERÍODO
$periodName = 'Período activo';
$sqlPeriod = "SELECT name FROM periods WHERE id = ? LIMIT 1";
$stmtPeriod = $conn->prepare($sqlPeriod);
$stmtPeriod->bind_param('i', $periodId);
$stmtPeriod->execute();
$resPeriod = $stmtPeriod->get_result();
if ($row = $resPeriod->fetch_assoc()) {
    $periodName = $row['name'];
}
$stmtPeriod->close();

// PASAR A FORM
$_SESSION['study_group_name'] = $groupName;
$_SESSION['period_name']      = $periodName;

// VISTA CONTENEDORA
require_once __DIR__ . '/../../app/views/partials/header.php';
?>

<div class="container-fluid" style="padding:10px 16px;">

    <?php if ($error): ?>
        <div style="background:#fdecea; color:#b71c1c; padding:10px; border-radius:8px; margin-bottom:12px;">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div style="background:#ecfdf3; color:#027a48; padding:10px; border-radius:8px; margin-bottom:12px;">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <form action="store.php" method="POST">
        <?php require_once __DIR__ . '/partials/form.php'; ?>
    </form>

</div>

<?php require_once __DIR__ . '/../../app/views/partials/footer.php'; ?>