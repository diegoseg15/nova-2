<?php

require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/nova1/app/services/MoodleService.php';

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
    echo '<p>No tiene permiso para administrar cursos.</p>';
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
                  p.institution_id,
                  p.study_group_id,
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

if (!$period) {
    header('Location: /nova1/modules/school_regime/periods/index.php');
    exit;
}

$message = '';
$error = '';

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'created') {
        $message = 'Curso creado correctamente.';
    } elseif ($_GET['msg'] === 'updated') {
        $message = 'Curso actualizado correctamente.';
    }
}

$sqlCourses = "SELECT id, code, name, description, status
               FROM courses
               WHERE period_id = ?
               ORDER BY name ASC";

$stmtCourses = $conn->prepare($sqlCourses);
$stmtCourses->bind_param('i', $periodId);
$stmtCourses->execute();
$courses = $stmtCourses->get_result();

require_once __DIR__ . '/../../../app/views/partials/header.php';
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
    <div>
        <h1 style="margin:0;font-size:24px;color:#0f172a;">Cursos del período</h1>
        <p style="margin:4px 0 0;color:#64748b;font-size:14px;">
            Administración de cursos asociados al período seleccionado
        </p>
    </div>

    <div style="display:flex;gap:10px;">
        <a href="/nova1/modules/school_regime/periods/index.php"
            style="background:#0f172a;color:#fff;text-decoration:none;padding:10px 14px;border-radius:10px;font-weight:700;font-size:14px;">
            Volver a períodos
        </a>

        <?php if ($period['status'] === 'open'): ?>
            <a href="/nova1/modules/school_regime/courses/create.php?period_id=<?php echo (int)$periodId; ?>"
                style="background:#2563eb;color:#fff;text-decoration:none;padding:10px 14px;border-radius:10px;font-weight:700;font-size:14px;">
                + Nuevo curso
            </a>
        <?php endif; ?>
    </div>
</div>

<div style="background:#ffffff;border:1px solid #e5e7eb;border-radius:14px;padding:16px;margin-bottom:16px;">
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
        <div>
            <div style="font-size:12px;color:#64748b;font-weight:700;">Institución</div>
            <div style="font-size:14px;color:#0f172a;font-weight:700;"><?php echo htmlspecialchars($period['institution_name']); ?></div>
        </div>

        <div>
            <div style="font-size:12px;color:#64748b;font-weight:700;">Grupo</div>
            <div style="font-size:14px;color:#0f172a;font-weight:700;"><?php echo htmlspecialchars($period['group_name']); ?></div>
        </div>

        <div>
            <div style="font-size:12px;color:#64748b;font-weight:700;">Período</div>
            <div style="font-size:14px;color:#0f172a;font-weight:700;">
                <?php echo htmlspecialchars($period['code'] . ' - ' . $period['name']); ?>
            </div>
        </div>
    </div>
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
                <th style="padding:12px;">Curso</th>
                <th style="padding:12px;">Descripción</th>
                <th style="padding:12px;">Estado</th>
                <th style="padding:12px;">Moodle</th>
                <th style="padding:12px;text-align:center;">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($courses && $courses->num_rows > 0): ?>
                <?php while ($row = $courses->fetch_assoc()): ?>
                    <tr>
                        <td style="padding:12px;font-weight:700;"><?php echo htmlspecialchars($row['code']); ?></td>
                        <td style="padding:12px;"><?php echo htmlspecialchars($row['name']); ?></td>
                        <td style="padding:12px;"><?php echo htmlspecialchars($row['description'] ?? ''); ?></td>

                        <td style="padding:12px;">
                            <?php if ($row['status'] === 'active'): ?>
                                <span style="background:#dcfce7;color:#166534;padding:5px 9px;border-radius:999px;font-size:12px;font-weight:700;">ACTIVO</span>
                            <?php else: ?>
                                <span style="background:#f1f5f9;color:#475569;padding:5px 9px;border-radius:999px;font-size:12px;font-weight:700;">INACTIVO</span>
                            <?php endif; ?>
                        </td>

                        <?php
                        $moodleCourse = MoodleService::courseExistsInMoodle(
                            $conn,
                            (int)$period['institution_id'],
                            (int)$period['study_group_id'],
                            $period['name'],
                            $row['name']
                        );
                        ?>
                        <td style="padding:12px;">
                            <?php if ($moodleCourse['exists']): ?>
                                <span style="background:#dcfce7;color:#166534;padding:5px 9px;border-radius:999px;font-size:12px;font-weight:700;">
                                    <?php echo htmlspecialchars($moodleCourse['message']); ?>
                                </span>
                            <?php else: ?>
                                <span style="background:#fee2e2;color:#991b1b;padding:5px 9px;border-radius:999px;font-size:12px;font-weight:700;">
                                    <?php echo htmlspecialchars($moodleCourse['message']); ?>
                                </span>
                            <?php endif; ?>
                        </td>

                        <td style="padding:12px;text-align:center;white-space:nowrap;">
                            <a href="/nova1/modules/school_regime/subjects/index.php?course_id=<?php echo (int)$row['id']; ?>"
                                style="background:#2563eb;color:#fff;padding:6px 10px;border-radius:8px;font-size:12px;text-decoration:none;margin-right:6px;">
                                Materias
                            </a>

                            <?php if ($period['status'] === 'open'): ?>
                                <a href="/nova1/modules/school_regime/courses/edit.php?id=<?php echo (int)$row['id']; ?>&period_id=<?php echo (int)$periodId; ?>"
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
                    <td colspan="6" style="padding:16px;text-align:center;color:#64748b;">
                        No existen cursos registrados para este período.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
$stmtCourses->close();
require_once __DIR__ . '/../../../app/views/partials/footer.php';
?>