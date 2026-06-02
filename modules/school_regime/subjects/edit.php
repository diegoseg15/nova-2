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
    echo '<p>No tiene permiso para editar materias.</p>';
    echo '</div>';
    require_once __DIR__ . '/../../../app/views/partials/footer.php';
    exit;
}

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: /nova1/modules/school_regime/periods/index.php');
    exit;
}

$sql = "SELECT 
            s.id,
            s.course_id,
            s.code,
            s.name,
            s.description,
            s.cycle,
            s.status,
            c.code AS course_code,
            c.name AS course_name,
            p.id AS period_id,
            p.code AS period_code,
            p.name AS period_name,
            p.status AS period_status,
            g.name AS group_name,
            i.name AS institution_name
        FROM subjects s
        INNER JOIN courses c ON c.id = s.course_id
        INNER JOIN periods p ON p.id = c.period_id
        INNER JOIN study_groups g ON g.id = p.study_group_id
        INNER JOIN institutions i ON i.id = p.institution_id
        WHERE s.id = ?
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$subject = $result ? $result->fetch_assoc() : null;
$stmt->close();

if (!$subject) {
    header('Location: /nova1/modules/school_regime/periods/index.php');
    exit;
}

if ($subject['period_status'] !== 'open') {
    header('Location: /nova1/modules/school_regime/subjects/index.php?course_id=' . $subject['course_id'] . '&error=closed');
    exit;
}

require_once __DIR__ . '/../../../app/views/partials/header.php';
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
    <div>
        <h1 style="margin:0;font-size:24px;">Editar materia</h1>
        <p style="color:#64748b;">Actualización de materia académica</p>
    </div>

    <a href="/nova1/modules/school_regime/subjects/index.php?course_id=<?php echo (int)$subject['course_id']; ?>"
       style="background:#0f172a;color:#fff;padding:10px;border-radius:10px;text-decoration:none;">
        Volver
    </a>
</div>

<div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:16px;margin-bottom:16px;">
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;">
        <div><b>Institución:</b><br><?php echo htmlspecialchars($subject['institution_name']); ?></div>
        <div><b>Grupo:</b><br><?php echo htmlspecialchars($subject['group_name']); ?></div>
        <div><b>Período:</b><br><?php echo htmlspecialchars($subject['period_code']); ?></div>
        <div><b>Curso:</b><br><?php echo htmlspecialchars($subject['course_code'] . ' - ' . $subject['course_name']); ?></div>
    </div>
</div>

<div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:18px;max-width:900px;">
<form method="POST" action="/nova1/modules/school_regime/subjects/update.php">

<input type="hidden" name="id" value="<?php echo (int)$subject['id']; ?>">
<input type="hidden" name="course_id" value="<?php echo (int)$subject['course_id']; ?>">

<div style="display:grid;grid-template-columns:1fr 2fr;gap:14px;margin-bottom:14px;">
    <div>
        <label>Código *</label>
        <input type="text" name="code" required value="<?php echo htmlspecialchars($subject['code']); ?>" style="width:100%;padding:10px;border-radius:10px;">
    </div>

    <div>
        <label>Nombre *</label>
        <input type="text" name="name" required value="<?php echo htmlspecialchars($subject['name']); ?>" style="width:100%;padding:10px;border-radius:10px;">
    </div>
</div>

<div style="margin-bottom:14px;">
    <label>Descripción</label>
    <input type="text" name="description" value="<?php echo htmlspecialchars($subject['description']); ?>" style="width:100%;padding:10px;border-radius:10px;">
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:18px;">
    <div>
        <label>Ciclo</label>
        <select name="cycle" style="width:100%;padding:10px;border-radius:10px;">
            <option value="C1" <?php echo $subject['cycle'] === 'C1' ? 'selected' : ''; ?>>Ciclo 1</option>
            <option value="C2" <?php echo $subject['cycle'] === 'C2' ? 'selected' : ''; ?>>Ciclo 2</option>
        </select>
    </div>

    <div>
        <label>Estado</label>
        <select name="status" style="width:100%;padding:10px;border-radius:10px;">
            <option value="active" <?php echo $subject['status'] === 'active' ? 'selected' : ''; ?>>Activo</option>
            <option value="inactive" <?php echo $subject['status'] === 'inactive' ? 'selected' : ''; ?>>Inactivo</option>
        </select>
    </div>
</div>

<div style="display:flex;justify-content:flex-end;gap:10px;">
    <a href="/nova1/modules/school_regime/subjects/index.php?course_id=<?php echo (int)$subject['course_id']; ?>"
       style="padding:10px;background:#e5e7eb;border-radius:10px;text-decoration:none;">
        Cancelar
    </a>

    <button type="submit" style="background:#2563eb;color:#fff;padding:10px 16px;border-radius:10px;border:none;">
        Guardar cambios
    </button>
</div>

</form>
</div>

<?php require_once __DIR__ . '/../../../app/views/partials/footer.php'; ?>