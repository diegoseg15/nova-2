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
    require_once __DIR__ . '/../../../app/views/partials/header.php';
    echo '<div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:20px;">';
    echo '<h2>Acceso restringido</h2>';
    echo '<p>No tiene permiso para editar períodos.</p>';
    echo '</div>';
    require_once __DIR__ . '/../../../app/views/partials/footer.php';
    exit;
}

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: /nova1/modules/school_regime/periods/index.php');
    exit;
}

$sqlPeriod = "SELECT 
                  p.id,
                  p.institution_id,
                  p.study_group_id,
                  p.code,
                  p.name,
                  p.start_date,
                  p.end_date,
                  p.status,
                  g.name AS group_name,
                  i.name AS institution_name
              FROM periods p
              INNER JOIN study_groups g ON g.id = p.study_group_id
              INNER JOIN institutions i ON i.id = p.institution_id
              WHERE p.id = ?
              LIMIT 1";

$stmtPeriod = $conn->prepare($sqlPeriod);
$stmtPeriod->bind_param('i', $id);
$stmtPeriod->execute();
$resultPeriod = $stmtPeriod->get_result();
$period = $resultPeriod ? $resultPeriod->fetch_assoc() : null;
$stmtPeriod->close();

if (!$period) {
    header('Location: /nova1/modules/school_regime/periods/index.php');
    exit;
}

// if ($period['status'] !== 'open') {
//     header('Location: /nova1/modules/school_regime/periods/index.php?error=closed');
//     exit;
// }

require_once __DIR__ . '/../../../app/views/partials/header.php';
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
    <div>
        <h1 style="margin:0;font-size:24px;color:#0f172a;">Editar período</h1>
        <p style="margin:4px 0 0;color:#64748b;font-size:14px;">
            Actualización de período académico
        </p>
    </div>

    <a href="/nova1/modules/school_regime/periods/index.php"
       style="background:#0f172a;color:#fff;text-decoration:none;padding:10px 14px;border-radius:10px;font-weight:700;font-size:14px;">
        Volver
    </a>
</div>

<div style="background:#ffffff;border:1px solid #e5e7eb;border-radius:14px;padding:18px;box-shadow:0 8px 20px rgba(15,23,42,0.04);max-width:900px;">

<form method="POST" action="/nova1/modules/school_regime/periods/update.php">

<input type="hidden" name="id" value="<?php echo (int)$period['id']; ?>">
<input type="hidden" name="study_group_id" value="<?php echo (int)$period['study_group_id']; ?>">

<div style="margin-bottom:14px;">
    <label style="display:block;font-size:13px;font-weight:700;color:#334155;margin-bottom:6px;">
        Grupo
    </label>

    <div style="width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:10px;font-size:14px;background:#f8fafc;color:#0f172a;font-weight:700;">
        <?php echo htmlspecialchars($period['group_name']); ?>
        <span style="font-weight:400;color:#64748b;">
            — <?php echo htmlspecialchars($period['institution_name']); ?>
        </span>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 2fr;gap:14px;margin-bottom:14px;">
    <div>
        <label style="display:block;font-size:13px;font-weight:700;color:#334155;margin-bottom:6px;">
            Código *
        </label>
        <input type="text" name="code" required maxlength="50"
               value="<?php echo htmlspecialchars($period['code']); ?>"
               style="width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:10px;font-size:14px;">
    </div>

    <div>
        <label style="display:block;font-size:13px;font-weight:700;color:#334155;margin-bottom:6px;">
            Nombre *
        </label>
        <input type="text" name="name" required maxlength="150"
               value="<?php echo htmlspecialchars($period['name']); ?>"
               style="width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:10px;font-size:14px;">
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;">
    <div>
        <label style="display:block;font-size:13px;font-weight:700;color:#334155;margin-bottom:6px;">
            Fecha inicio *
        </label>
        <input type="date" name="start_date" required
               value="<?php echo htmlspecialchars($period['start_date']); ?>"
               style="width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:10px;font-size:14px;">
    </div>

    <div>
        <label style="display:block;font-size:13px;font-weight:700;color:#334155;margin-bottom:6px;">
            Fecha fin *
        </label>
        <input type="date" name="end_date" required
               value="<?php echo htmlspecialchars($period['end_date']); ?>"
               style="width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:10px;font-size:14px;">
    </div>
</div>

<div style="margin-bottom:18px;">
    <label style="display:block;font-size:13px;font-weight:700;color:#334155;margin-bottom:6px;">
        Estado
    </label>
    <select name="status"
            style="width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:10px;font-size:14px;">
        <option value="open" <?php echo $period['status'] === 'open' ? 'selected' : ''; ?>>
            Abierto
        </option>
        <option value="closed" <?php echo $period['status'] === 'closed' ? 'selected' : ''; ?>>
            Cerrado
        </option>
    </select>
</div>

<div style="display:flex;gap:10px;justify-content:flex-end;">
    <a href="/nova1/modules/school_regime/periods/index.php"
       style="background:#e5e7eb;color:#0f172a;text-decoration:none;padding:10px 14px;border-radius:10px;font-weight:700;font-size:14px;">
        Cancelar
    </a>

    <button type="submit"
            style="border:none;background:#2563eb;color:#fff;padding:10px 16px;border-radius:10px;font-weight:700;font-size:14px;cursor:pointer;">
        Guardar cambios
    </button>
</div>

</form>
</div>

<?php require_once __DIR__ . '/../../../app/views/partials/footer.php'; ?>