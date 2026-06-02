<?php

require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /nova1/login.php');
    exit;
}

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: /nova1/modules/school_regime/courses/index.php');
    exit;
}

$db = new Database();
$conn = $db->connect();

/**
 * Obtener curso + contexto
 */
$sqlCourse = "SELECT 
                c.id,
                c.period_id,
                c.code,
                c.name,
                c.description,
                c.status,
                p.name AS period_name,
                g.name AS group_name,
                i.name AS institution_name
              FROM courses c
              INNER JOIN periods p ON p.id = c.period_id
              INNER JOIN study_groups g ON g.id = p.study_group_id
              INNER JOIN institutions i ON i.id = p.institution_id
              WHERE c.id = ?
              LIMIT 1";

$stmtCourse = $conn->prepare($sqlCourse);
$stmtCourse->bind_param('i', $id);
$stmtCourse->execute();
$resultCourse = $stmtCourse->get_result();
$course = $resultCourse ? $resultCourse->fetch_assoc() : null;
$stmtCourse->close();

if (!$course) {
    header('Location: /nova1/modules/school_regime/courses/index.php');
    exit;
}

/**
 * Validar que el período esté abierto
 */
$sqlPeriod = "SELECT status FROM periods WHERE id = ? LIMIT 1";
$stmtPeriod = $conn->prepare($sqlPeriod);
$stmtPeriod->bind_param('i', $course['period_id']);
$stmtPeriod->execute();
$resPeriod = $stmtPeriod->get_result();
$period = $resPeriod ? $resPeriod->fetch_assoc() : null;
$stmtPeriod->close();

if (!$period || $period['status'] === 'closed') {
    header('Location: /nova1/modules/school_regime/courses/index.php?error=closed');
    exit;
}

require_once __DIR__ . '/../../../app/views/partials/header.php';
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
    <div>
        <h1 style="margin:0;font-size:24px;color:#0f172a;">Editar curso</h1>
    </div>

    <a href="/nova1/modules/school_regime/courses/index.php"
       style="background:#0f172a;color:#fff;padding:10px 14px;border-radius:10px;text-decoration:none;">
        Volver
    </a>
</div>

<div style="background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:18px;max-width:900px;">

<form method="POST" action="/nova1/modules/school_regime/courses/update.php">

<input type="hidden" name="id" value="<?php echo (int)$course['id']; ?>">
<input type="hidden" name="period_id" value="<?php echo (int)$course['period_id']; ?>">

<div style="margin-bottom:14px;">
    <label style="font-weight:700;">Período</label>
    <div style="padding:10px;background:#f8fafc;border-radius:10px;">
        <?php echo htmlspecialchars($course['group_name'] . ' - ' . $course['period_name']); ?>
        (<?php echo htmlspecialchars($course['institution_name']); ?>)
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 2fr;gap:14px;margin-bottom:14px;">
    <div>
        <label style="font-weight:700;">Código *</label>
        <input type="text" name="code" required value="<?php echo htmlspecialchars($course['code']); ?>" style="width:100%;padding:10px;border-radius:10px;">
    </div>

    <div>
        <label style="font-weight:700;">Nombre *</label>
        <input type="text" name="name" required value="<?php echo htmlspecialchars($course['name']); ?>" style="width:100%;padding:10px;border-radius:10px;">
    </div>
</div>

<div style="margin-bottom:14px;">
    <label style="font-weight:700;">Descripción</label>
    <input type="text" name="description" value="<?php echo htmlspecialchars($course['description']); ?>" style="width:100%;padding:10px;border-radius:10px;">
</div>

<div style="margin-bottom:18px;">
    <label style="font-weight:700;">Estado</label>
    <select name="status" style="width:100%;padding:10px;border-radius:10px;">
        <option value="active" <?php echo $course['status'] === 'active' ? 'selected' : ''; ?>>Activo</option>
        <option value="inactive" <?php echo $course['status'] === 'inactive' ? 'selected' : ''; ?>>Inactivo</option>
    </select>
</div>

<div style="display:flex;justify-content:flex-end;gap:10px;">
    <a href="/nova1/modules/school_regime/courses/index.php" style="padding:10px;background:#e5e7eb;border-radius:10px;text-decoration:none;">
        Cancelar
    </a>

    <button type="submit" style="background:#2563eb;color:#fff;padding:10px 16px;border-radius:10px;border:none;">
        Guardar cambios
    </button>
</div>

</form>

</div>

<?php require_once __DIR__ . '/../../../app/views/partials/footer.php'; ?>