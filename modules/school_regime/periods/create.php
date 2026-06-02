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
    echo '<p>No tiene permiso para crear períodos.</p>';
    echo '</div>';
    require_once __DIR__ . '/../../../app/views/partials/footer.php';
    exit;
}

/**
 * Cargar grupos activos
 */
$sqlGroups = "SELECT id, name FROM study_groups WHERE status = 'active' ORDER BY name ASC";
$resultGroups = $conn->query($sqlGroups);

require_once __DIR__ . '/../../../app/views/partials/header.php';
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
    <div>
        <h1 style="margin:0;font-size:24px;color:#0f172a;">Nuevo período</h1>
        <p style="margin:4px 0 0;color:#64748b;font-size:14px;">
            Registro de período académico
        </p>
    </div>

    <a href="/nova1/modules/school_regime/periods/index.php"
       style="background:#0f172a;color:#fff;text-decoration:none;padding:10px 14px;border-radius:10px;font-weight:700;">
        Volver
    </a>
</div>

<div style="background:#ffffff;border:1px solid #e5e7eb;border-radius:14px;padding:18px;max-width:900px;">

<form method="POST" action="/nova1/modules/school_regime/periods/store.php">

    <!-- GRUPO -->
    <div style="margin-bottom:14px;">
        <label style="display:block;font-size:13px;font-weight:700;margin-bottom:6px;">
            Grupo *
        </label>

        <select name="study_group_id" required
                style="width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:10px;">
            <option value="">Seleccione grupo</option>

            <?php if ($resultGroups && $resultGroups->num_rows > 0): ?>
                <?php while ($g = $resultGroups->fetch_assoc()): ?>
                    <option value="<?php echo (int)$g['id']; ?>">
                        <?php echo htmlspecialchars($g['name']); ?>
                    </option>
                <?php endwhile; ?>
            <?php endif; ?>
        </select>
    </div>

    <!-- CÓDIGO Y NOMBRE -->
    <div style="display:grid;grid-template-columns:1fr 2fr;gap:14px;margin-bottom:14px;">
        <div>
            <label style="display:block;font-size:13px;font-weight:700;margin-bottom:6px;">
                Código *
            </label>
            <input type="text" name="code" required maxlength="50"
                   placeholder="Ej: G1C FEB26 AG26"
                   style="width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:10px;">
        </div>

        <div>
            <label style="display:block;font-size:13px;font-weight:700;margin-bottom:6px;">
                Nombre *
            </label>
            <input type="text" name="name" required maxlength="150"
                   placeholder="Ej: Periodo 2026 A"
                   style="width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:10px;">
        </div>
    </div>

    <!-- FECHAS -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;">
        <div>
            <label style="display:block;font-size:13px;font-weight:700;margin-bottom:6px;">
                Fecha inicio *
            </label>
            <input type="date" name="start_date" required
                   style="width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:10px;">
        </div>

        <div>
            <label style="display:block;font-size:13px;font-weight:700;margin-bottom:6px;">
                Fecha fin *
            </label>
            <input type="date" name="end_date" required
                   style="width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:10px;">
        </div>
    </div>

    <!-- ESTADO -->
    <div style="margin-bottom:18px;">
        <label style="display:block;font-size:13px;font-weight:700;margin-bottom:6px;">
            Estado
        </label>

        <select name="status"
                style="width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:10px;">
            <option value="open">Abierto</option>
            <option value="closed">Cerrado</option>
        </select>
    </div>

    <!-- BOTONES -->
    <div style="display:flex;gap:10px;justify-content:flex-end;">
        <a href="/nova1/modules/school_regime/periods/index.php"
           style="background:#e5e7eb;color:#0f172a;text-decoration:none;padding:10px 14px;border-radius:10px;font-weight:700;">
            Cancelar
        </a>

        <button type="submit"
                style="background:#2563eb;color:#fff;border:none;padding:10px 16px;border-radius:10px;font-weight:700;cursor:pointer;">
            Guardar período
        </button>
    </div>

</form>

</div>

<?php require_once __DIR__ . '/../../../app/views/partials/footer.php'; ?>