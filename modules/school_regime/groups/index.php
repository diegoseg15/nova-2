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
                    AND p.code = 'school_regime.groups.manage'
                    AND p.status = 'active'
                  LIMIT 1";

$stmt = $conn->prepare($sqlPermission);
$stmt->bind_param('i', $roleId);
$stmt->execute();
$resultPermission = $stmt->get_result();
$canManage = ($resultPermission && $resultPermission->num_rows > 0);
$stmt->close();

if (!$canManage) {
    require_once __DIR__ . '/../../../app/views/partials/header.php';
    echo '<div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:20px;">';
    echo '<h2>Acceso restringido</h2>';
    echo '<p>No tiene permiso para administrar grupos.</p>';
    echo '</div>';
    require_once __DIR__ . '/../../../app/views/partials/footer.php';
    exit;
}

$message = '';
$error = '';

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'created') {
        $message = 'Grupo creado correctamente.';
    } elseif ($_GET['msg'] === 'updated') {
        $message = 'Grupo actualizado correctamente.';
    }
}

if (isset($_GET['error'])) {
    if ($_GET['error'] === 'duplicate') {
        $error = 'El código del grupo ya existe.';
    } elseif ($_GET['error'] === 'save') {
        $error = 'Error al guardar el grupo.';
    }
}

$sql = "SELECT g.id, g.code, g.name, g.description, g.status,
               i.name AS institution_name
        FROM study_groups g
        INNER JOIN institutions i ON i.id = g.institution_id
        ORDER BY g.name ASC";

$result = $conn->query($sql);

require_once __DIR__ . '/../../../app/views/partials/header.php';
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
    <div>
        <h1 style="margin:0;font-size:24px;color:#0f172a;">Grupos</h1>
        <p style="margin:4px 0 0;color:#64748b;font-size:14px;">
            Administración de grupos académicos
        </p>
    </div>

    <a href="/nova1/modules/school_regime/groups/create.php"
       style="background:#2563eb;color:#fff;text-decoration:none;padding:10px 14px;border-radius:10px;font-weight:700;font-size:14px;">
        + Nuevo grupo
    </a>
</div>

<?php if ($message !== ''): ?>
    <div style="background:#dcfce7;border:1px solid #86efac;color:#166534;padding:12px;border-radius:10px;margin-bottom:14px;">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<?php if ($error !== ''): ?>
    <div style="background:#fee2e2;border:1px solid #fecaca;color:#991b1b;padding:12px;border-radius:10px;margin-bottom:14px;">
        <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<div style="background:#ffffff;border:1px solid #e5e7eb;border-radius:14px;overflow:hidden;">
    <table style="width:100%;border-collapse:collapse;font-size:14px;">
        <thead>
            <tr style="background:#f1f5f9;text-align:left;">
                <th style="padding:12px;">Código</th>
                <th style="padding:12px;">Nombre</th>
                <th style="padding:12px;">Institución</th>
                <th style="padding:12px;">Descripción</th>
                <th style="padding:12px;">Estado</th>
                <th style="padding:12px;text-align:center;">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td style="padding:12px;font-weight:700;"><?php echo htmlspecialchars($row['code']); ?></td>
                        <td style="padding:12px;"><?php echo htmlspecialchars($row['name']); ?></td>
                        <td style="padding:12px;"><?php echo htmlspecialchars($row['institution_name']); ?></td>
                        <td style="padding:12px;"><?php echo htmlspecialchars($row['description'] ?? ''); ?></td>

                        <td style="padding:12px;">
                            <?php if ($row['status'] === 'active'): ?>
                                <span style="background:#dcfce7;color:#166534;padding:5px 9px;border-radius:999px;font-size:12px;font-weight:700;">ACTIVO</span>
                            <?php else: ?>
                                <span style="background:#f1f5f9;color:#475569;padding:5px 9px;border-radius:999px;font-size:12px;font-weight:700;">CERRADO</span>
                            <?php endif; ?>
                        </td>

                        <td style="padding:12px;text-align:center;">
                            <a href="/nova1/modules/school_regime/groups/edit.php?id=<?php echo (int)$row['id']; ?>"
                               style="background:#0f172a;color:#fff;padding:6px 10px;border-radius:8px;font-size:12px;text-decoration:none;">
                                Editar
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" style="padding:16px;text-align:center;color:#64748b;">
                        No existen grupos registrados.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../../../app/views/partials/footer.php'; ?>

