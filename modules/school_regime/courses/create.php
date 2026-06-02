<?php

require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /nova1/login.php');
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
    require_once __DIR__ . '/../../../app/views/partials/header.php';
    echo '<div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:20px;">';
    echo '<h2>Acceso restringido</h2>';
    echo '<p>No tiene permiso para crear cursos.</p>';
    echo '</div>';
    require_once __DIR__ . '/../../../app/views/partials/footer.php';
    exit;
}

$periodId = (int)($_GET['period_id'] ?? 0);

if ($periodId <= 0) {
    header('Location: /nova1/modules/school_regime/periods/index.php');
    exit;
}

$sqlPeriod = "SELECT 
                  p.id,
                  p.code,
                  p.name,
                  p.status,
                  g.name AS group_name,
                  i.name AS institution_name
              FROM periods p
              INNER JOIN study_groups g ON g.id = p.study_group_id
              INNER JOIN institutions i ON i.id = p.institution_id
              WHERE p.id = ?
              LIMIT 1";

$stmtPeriod = $conn->prepare($sqlPeriod);
$stmtPeriod->bind_param('i', $periodId);
$stmtPeriod->execute();
$resultPeriod = $stmtPeriod->get_result();
$period = $resultPeriod ? $resultPeriod->fetch_assoc() : null;
$stmtPeriod->close();

if (!$period || $period['status'] !== 'open') {
    header('Location: /nova1/modules/school_regime/periods/index.php?error=closed');
    exit;
}

require_once __DIR__ . '/../../../app/views/partials/header.php';
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
    <div>
        <h1 style="margin:0;font-size:24px;color:#0f172a;">Nuevo curso</h1>
        <p style="margin:4px 0 0;color:#64748b;font-size:14px;">
            Registro de curso dentro del período seleccionado
        </p>
    </div>

    <a href="/nova1/modules/school_regime/courses/index.php?period_id=<?php echo (int)$periodId; ?>"
       style="background:#0f172a;color:#fff;text-decoration:none;padding:10px 14px;border-radius:10px;font-weight:700;font-size:14px;">
        Volver
    </a>
</div>

<div style="background:#ffffff;border:1px solid #e5e7eb;border-radius:14px;padding:16px;margin-bottom:16px;">
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
        <div>
            <div style="font-size:12px;color:#64748b;font-weight:700;">Institución</div>
            <div style="font-size:14px;color:#0f172a;font-weight:700;">
                <?php echo htmlspecialchars($period['institution_name']); ?>
            </div>
        </div>

        <div>
            <div style="font-size:12px;color:#64748b;font-weight:700;">Grupo</div>
            <div style="font-size:14px;color:#0f172a;font-weight:700;">
                <?php echo htmlspecialchars($period['group_name']); ?>
            </div>
        </div>

        <div>
            <div style="font-size:12px;color:#64748b;font-weight:700;">Período</div>
            <div style="font-size:14px;color:#0f172a;font-weight:700;">
                <?php echo htmlspecialchars($period['code'] . ' - ' . $period['name']); ?>
            </div>
        </div>
    </div>
</div>

<div style="background:#ffffff;border:1px solid #e5e7eb;border-radius:14px;padding:18px;max-width:900px;">

<form method="POST" action="/nova1/modules/school_regime/courses/store.php">

<input type="hidden" name="period_id" value="<?php echo (int)$periodId; ?>">

<div style="display:grid;grid-template-columns:1fr 2fr;gap:14px;margin-bottom:14px;">
    <div>
        <label style="display:block;font-size:13px;font-weight:700;margin-bottom:6px;">Código *</label>
        <input type="text" name="code" required maxlength="50"
               style="width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:10px;">
    </div>

    <div>
        <label style="display:block;font-size:13px;font-weight:700;margin-bottom:6px;">Nombre del curso *</label>
        <input type="text" name="name" required maxlength="150"
               style="width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:10px;">
    </div>
</div>

<div style="margin-bottom:14px;">
    <label style="display:block;font-size:13px;font-weight:700;margin-bottom:6px;">Descripción</label>
    <input type="text" name="description" maxlength="255"
           style="width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:10px;">
</div>

<div style="margin-bottom:18px;">
    <label style="display:block;font-size:13px;font-weight:700;margin-bottom:6px;">Estado</label>
    <select name="status"
            style="width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:10px;">
        <option value="active">Activo</option>
        <option value="inactive">Inactivo</option>
    </select>
</div>

<div style="display:flex;gap:10px;justify-content:flex-end;">
    <a href="/nova1/modules/school_regime/courses/index.php?period_id=<?php echo (int)$periodId; ?>"
       style="background:#e5e7eb;color:#0f172a;text-decoration:none;padding:10px 14px;border-radius:10px;font-weight:700;">
        Cancelar
    </a>

    <button type="submit"
            style="background:#2563eb;color:#fff;border:none;padding:10px 16px;border-radius:10px;font-weight:700;cursor:pointer;">
        Guardar curso
    </button>
</div>

</form>

</div>

<?php require_once __DIR__ . '/../../../app/views/partials/footer.php'; ?>

