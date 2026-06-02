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
    echo '<p>No tiene permiso para crear materias.</p>';
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
                p.status AS period_status,
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

if (!$course || $course['period_status'] !== 'open') {
    header('Location: /nova1/modules/school_regime/periods/index.php?error=closed');
    exit;
}

require_once __DIR__ . '/../../../app/views/partials/header.php';
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
    <div>
        <h1 style="margin:0;font-size:24px;color:#0f172a;">Nueva materia</h1>
        <p style="margin:4px 0 0;color:#64748b;font-size:14px;">
            Registro de materia dentro del curso seleccionado
        </p>
    </div>

    <a href="/nova1/modules/school_regime/subjects/index.php?course_id=<?php echo (int)$courseId; ?>"
       style="background:#0f172a;color:#fff;text-decoration:none;padding:10px 14px;border-radius:10px;font-weight:700;font-size:14px;">
        Volver
    </a>
</div>

<div style="background:#ffffff;border:1px solid #e5e7eb;border-radius:14px;padding:14px;margin-bottom:16px;">
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;">
        <div>
            <div style="font-size:12px;color:#64748b;font-weight:500;line-height:1.1;">Institución</div>
            <div style="font-size:14px;color:#0f172a;font-weight:700;line-height:1.2;">
                <?php echo htmlspecialchars($course['institution_name']); ?>
            </div>
        </div>

        <div>
            <div style="font-size:12px;color:#64748b;font-weight:500;line-height:1.1;">Grupo</div>
            <div style="font-size:14px;color:#0f172a;font-weight:700;line-height:1.2;">
                <?php echo htmlspecialchars($course['group_name']); ?>
            </div>
        </div>

        <div>
            <div style="font-size:12px;color:#64748b;font-weight:500;line-height:1.1;">Período</div>
            <div style="font-size:14px;color:#0f172a;font-weight:700;line-height:1.2;">
                <?php echo htmlspecialchars($course['period_code']); ?>
            </div>
        </div>

        <div>
            <div style="font-size:12px;color:#64748b;font-weight:500;line-height:1.1;">Curso</div>
            <div style="font-size:14px;color:#0f172a;font-weight:700;line-height:1.2;">
                <?php echo htmlspecialchars($course['code'] . ' - ' . $course['name']); ?>
            </div>
        </div>
    </div>
</div>

<div style="background:#ffffff;border:1px solid #e5e7eb;border-radius:14px;padding:18px;max-width:900px;">
    <form method="POST" action="/nova1/modules/school_regime/subjects/store.php">

        <input type="hidden" name="course_id" value="<?php echo (int)$courseId; ?>">

        <div style="display:grid;grid-template-columns:1fr 2fr;gap:14px;margin-bottom:14px;">
            <div>
                <label style="display:block;font-size:13px;font-weight:700;margin-bottom:6px;">Código *</label>
                <input type="text" name="code" required maxlength="50"
                       style="width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:10px;">
            </div>

            <div>
                <label style="display:block;font-size:13px;font-weight:700;margin-bottom:6px;">Nombre de la materia *</label>
                <input type="text" name="name" required maxlength="150"
                       style="width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:10px;">
            </div>
        </div>

        <div style="margin-bottom:14px;">
            <label style="display:block;font-size:13px;font-weight:700;margin-bottom:6px;">Descripción</label>
            <input type="text" name="description" maxlength="255"
                   style="width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:10px;">
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:18px;">
            <div>
                <label style="display:block;font-size:13px;font-weight:700;margin-bottom:6px;">Ciclo</label>
                <select name="cycle"
                        style="width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:10px;">
                    <option value="C1">Ciclo 1</option>
                    <option value="C2">Ciclo 2</option>
                </select>
            </div>

            <div>
                <label style="display:block;font-size:13px;font-weight:700;margin-bottom:6px;">Estado</label>
                <select name="status"
                        style="width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:10px;">
                    <option value="active">Activo</option>
                    <option value="inactive">Inactivo</option>
                </select>
            </div>
        </div>

        <div style="display:flex;gap:10px;justify-content:flex-end;">
            <a href="/nova1/modules/school_regime/subjects/index.php?course_id=<?php echo (int)$courseId; ?>"
               style="background:#e5e7eb;color:#0f172a;text-decoration:none;padding:10px 14px;border-radius:10px;font-weight:700;">
                Cancelar
            </a>

            <button type="submit"
                    style="background:#2563eb;color:#fff;border:none;padding:10px 16px;border-radius:10px;font-weight:700;cursor:pointer;">
                Guardar materia
            </button>
        </div>

    </form>
</div>

<?php require_once __DIR__ . '/../../../app/views/partials/footer.php'; ?>