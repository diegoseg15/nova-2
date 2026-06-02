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
    require_once __DIR__ . '/../../../app/views/partials/header.php';
    echo '<div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:20px;">';
    echo '<h2>Acceso restringido</h2>';
    echo '<p>No tiene permiso para administrar materias.</p>';
    echo '</div>';
    require_once __DIR__ . '/../../../app/views/partials/footer.php';
    exit;
}

$courseId = (int)($_GET['course_id'] ?? 0);

if ($courseId <= 0) {
    header('Location: /nova1/modules/school_regime/periods/index.php');
    exit;
}

$sqlCourse = "SELECT 
                c.id,
                c.code,
                c.name,
                p.id AS period_id,
                p.code AS period_code,
                p.name AS period_name,
                p.study_group_id,
                p.institution_id,
                g.name AS group_name,
                i.name AS institution_name
              FROM courses c
              INNER JOIN periods p ON p.id = c.period_id
              INNER JOIN study_groups g ON g.id = p.study_group_id
              INNER JOIN institutions i ON i.id = p.institution_id
              WHERE c.id = ?
              LIMIT 1";

$stmt = $conn->prepare($sqlCourse);
$stmt->bind_param('i', $courseId);
$stmt->execute();
$result = $stmt->get_result();
$course = $result ? $result->fetch_assoc() : null;
$stmt->close();

if (!$course) {
    header('Location: /nova1/modules/school_regime/periods/index.php');
    exit;
}

$sqlSubjects = "SELECT id, code, name, cycle, status
                FROM subjects
                WHERE course_id = ?
                ORDER BY name ASC";

$stmtSubjects = $conn->prepare($sqlSubjects);
$stmtSubjects->bind_param('i', $courseId);
$stmtSubjects->execute();
$subjects = $stmtSubjects->get_result();

require_once __DIR__ . '/../../../app/views/partials/header.php';
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
    <div>
        <h1 style="margin:0;font-size:24px;color:#0f172a;">Materias del curso</h1>
        <p style="margin:4px 0 0;color:#64748b;font-size:14px;">Gestión de materias académicas</p>
    </div>

    <div style="display:flex;gap:10px;">
        <a href="/nova1/modules/school_regime/courses/index.php?period_id=<?php echo (int)$course['period_id']; ?>"
            style="background:#0f172a;color:#fff;padding:10px 14px;border-radius:10px;text-decoration:none;font-weight:700;">
            Volver a cursos
        </a>

        <a href="/nova1/modules/school_regime/subjects/create.php?course_id=<?php echo (int)$courseId; ?>"
            style="background:#2563eb;color:#fff;padding:10px 14px;border-radius:10px;text-decoration:none;font-weight:700;">
            + Nueva materia
        </a>
    </div>
</div>

<div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:14px;margin-bottom:16px;">
    <div style="display:flex;align-items:center;gap:28px;flex-wrap:wrap;font-size:14px;">
        <div>
            <span style="font-size:12px;color:#64748b;font-weight:700;">Institución: </span>
            <strong><?php echo htmlspecialchars($course['institution_name']); ?></strong>
        </div>

        <div>
            <span style="font-size:12px;color:#64748b;font-weight:700;">Grupo: </span>
            <strong><?php echo htmlspecialchars($course['group_name']); ?></strong>
        </div>

        <div>
            <span style="font-size:12px;color:#64748b;font-weight:700;">Período: </span>
            <strong><?php echo htmlspecialchars($course['period_code']); ?></strong>
        </div>

        <div>
            <span style="font-size:12px;color:#64748b;font-weight:700;">Curso: </span>
            <strong><?php echo htmlspecialchars($course['code'] . ' - ' . $course['name']); ?></strong>
        </div>
    </div>
</div>

<div style="background:#fff;border-radius:12px;border:1px solid #e5e7eb;overflow:hidden;">
    <table style="width:100%;border-collapse:collapse;font-size:14px;">
        <thead style="background:#f1f5f9;">
            <tr>
                <th style="padding:12px;text-align:left;">Código</th>
                <th style="padding:12px;text-align:left;">Materia</th>
                <th style="padding:12px;text-align:left;">Ciclo</th>
                <th style="padding:12px;text-align:left;">Estado</th>
                <th style="padding:12px;text-align:left;">Moodle</th>
                <th style="padding:12px;text-align:center;">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($subjects && $subjects->num_rows > 0): ?>
                <?php while ($row = $subjects->fetch_assoc()): ?>
                    <tr>
                        <td style="padding:12px;font-weight:700;"><?php echo htmlspecialchars($row['code']); ?></td>
                        <td style="padding:12px;"><?php echo htmlspecialchars($row['name']); ?></td>
                        <td style="padding:12px;"><?php echo htmlspecialchars($row['cycle']); ?></td>
                        <td style="padding:12px;"><?php echo htmlspecialchars(strtoupper($row['status'])); ?></td>
                        <?php
                        $moodleSubject = MoodleService::subjectExistsInMoodle(
                            $conn,
                            (int)$course['institution_id'],
                            (int)$course['study_group_id'],
                            $course['period_name'],
                            $course['name'],
                            $row['code']
                        );
                        ?>
                        <td style="padding:12px;">
                            <?php if ($moodleSubject['exists']): ?>
                                <span style="background:#dcfce7;color:#166534;padding:5px 9px;border-radius:999px;font-size:12px;font-weight:700;">
                                    En Moodle
                                </span>
                            <?php else: ?>
                                <span style="background:#fee2e2;color:#991b1b;padding:5px 9px;border-radius:999px;font-size:12px;font-weight:700;">
                                    <?php echo htmlspecialchars($moodleSubject['message']); ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td style="padding:12px;text-align:center;">
                            <a href="/nova1/modules/school_regime/subjects/edit.php?id=<?php echo (int)$row['id']; ?>&course_id=<?php echo (int)$courseId; ?>"
                                style="background:#0f172a;color:#fff;padding:6px 10px;border-radius:8px;font-size:12px;text-decoration:none;">
                                Editar
                            </a>
                            <a href="/nova1/modules/academic/grades/index.php?subject_id=<?php echo (int)$row['id']; ?>"
                                style="background:#2563eb;color:#fff;padding:6px 10px;border-radius:8px;font-size:12px;text-decoration:none;margin-right:5px;">
                                Ver notas
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" style="text-align:center;padding:16px;color:#64748b;">
                        No existen materias registradas para este curso.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
$stmtSubjects->close();
require_once __DIR__ . '/../../../app/views/partials/footer.php';
?>