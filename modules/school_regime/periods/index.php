<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once $_SERVER['DOCUMENT_ROOT'] . '/nova1/app/services/MoodleService.php';
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
    echo '<p>No tiene permiso para administrar períodos.</p>';
    echo '</div>';
    require_once __DIR__ . '/../../../app/views/partials/footer.php';
    exit;
}

$message = '';
$error = '';

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'created') {
        $message = 'Período creado correctamente.';
    } elseif ($_GET['msg'] === 'updated') {
        $message = 'Período actualizado correctamente.';
    }
}

if (isset($_GET['error'])) {
    if ($_GET['error'] === 'duplicate') {
        $error = 'El código del período ya existe en el grupo.';
    } elseif ($_GET['error'] === 'save') {
        $error = 'Error al guardar el período.';
    } elseif ($_GET['error'] === 'closed') {
        $error = 'El período cerrado no puede ser editado.';
    }
}

$sql = "SELECT p.id,
               p.code,
               p.name,
               p.start_date,
               p.end_date,
               p.status,
               p.institution_id,
               p.study_group_id,
               g.name AS group_name,
               i.name AS institution_name
        FROM periods p
        INNER JOIN study_groups g ON g.id = p.study_group_id
        INNER JOIN institutions i ON i.id = p.institution_id
        ORDER BY g.name ASC, p.start_date DESC";

$result = $conn->query($sql);

require_once __DIR__ . '/../../../app/views/partials/header.php';
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
    <div>
        <h1 style="margin:0;font-size:24px;color:#0f172a;">Períodos</h1>
        <p style="margin:4px 0 0;color:#64748b;font-size:14px;">
            Administración de períodos académicos por grupo
        </p>
    </div>

    <a href="/nova1/modules/school_regime/periods/create.php"
       style="background:#2563eb;color:#fff;text-decoration:none;padding:10px 14px;border-radius:10px;font-weight:700;font-size:14px;">
        + Nuevo período
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
                <th style="padding:12px;">Grupo</th>
                <th style="padding:12px;">Nombre</th>
                <th style="padding:12px;">Institución</th>
                <th style="padding:12px;">Inicio</th>
                <th style="padding:12px;">Fin</th>
                <th style="padding:12px;">Estado</th>
                <th style="padding:12px;">Moodle</th>
                <th style="padding:12px;text-align:center;">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td style="padding:12px;font-weight:700;"><?php echo htmlspecialchars($row['code']); ?></td>
                        <td style="padding:12px;"><?php echo htmlspecialchars($row['group_name']); ?></td>
                        <td style="padding:12px;"><?php echo htmlspecialchars($row['name']); ?></td>
                        <td style="padding:12px;"><?php echo htmlspecialchars($row['institution_name']); ?></td>
                        <td style="padding:12px;"><?php echo htmlspecialchars($row['start_date']); ?></td>
                        <td style="padding:12px;"><?php echo htmlspecialchars($row['end_date']); ?></td>

                        <td style="padding:12px;">
                            <?php if ($row['status'] === 'open'): ?>
                                <span style="background:#dcfce7;color:#166534;padding:5px 9px;border-radius:999px;font-size:12px;font-weight:700;">ABIERTO</span>
                            <?php else: ?>
                                <span style="background:#f1f5f9;color:#475569;padding:5px 9px;border-radius:999px;font-size:12px;font-weight:700;">CERRADO</span>
                            <?php endif; ?>
                        </td>

                        <?php
                        $moodleSync = MoodleService::periodExistsInMoodle(
                            $conn,
                            (int)$row['institution_id'],
                            (int)$row['study_group_id'],
                            $row['name']
                        );
                        ?>

                        <td style="padding:12px;">
                            <?php if ($moodleSync['exists']): ?>
                                <span title="<?php echo htmlspecialchars($moodleSync['moodle_category_name'] ?? ''); ?>"
                                    style="background:#dcfce7;color:#166534;padding:5px 9px;border-radius:999px;font-size:12px;font-weight:700;">
                                    <?php echo htmlspecialchars($moodleSync['message']); ?>
                                </span>
                            <?php else: ?>
                                <span style="background:#fee2e2;color:#991b1b;padding:5px 9px;border-radius:999px;font-size:12px;font-weight:700;">
                                    <?php echo htmlspecialchars($moodleSync['message']); ?>
                                </span>
                            <?php endif; ?>
                        </td>

                        <td style="padding:12px;text-align:center;white-space:nowrap;">
                            <a href="/nova1/modules/school_regime/courses/index.php?period_id=<?php echo (int)$row['id']; ?>"
                               style="background:#2563eb;color:#fff;padding:6px 10px;border-radius:8px;font-size:12px;text-decoration:none;margin-right:6px;">
                                Cursos
                            </a>

                            <?php if ($row['status']): ?>
                                <a href="/nova1/modules/school_regime/periods/edit.php?id=<?php echo (int)$row['id']; ?>"
                                   style="background:#0f172a;color:#fff;padding:6px 10px;border-radius:8px;font-size:12px;text-decoration:none;">
                                    Editar
                                </a>
                            <?php else: ?>
                                <span style="font-size:12px;color:#64748b;">Bloqueado</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="9" style="padding:16px;text-align:center;color:#64748b;">
                        No existen períodos registrados.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../../../app/views/partials/footer.php'; ?>