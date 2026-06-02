<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../app/helpers/auth.php';

if (!isLoggedIn()) {
    header('Location: /nova1/login.php');
    exit;
}

if (
    !isset($_SESSION['context_locked']) ||
    $_SESSION['context_locked'] !== 'Y' ||
    empty($_SESSION['study_group_id']) ||
    empty($_SESSION['period_id'])
) {
    header('Location: /nova1/dashboard.php?error=context_required');
    exit;
}

$db = new Database();
$conn = $db->connect();

$institutionId = (int) ($_SESSION['institution_id'] ?? 1);
$studyGroupId  = (int) ($_SESSION['study_group_id'] ?? 0);
$periodId      = (int) ($_SESSION['period_id'] ?? 0);

$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');

$groupName  = '';
$periodName = '';

// GRUPO
$sqlGroup = "SELECT name FROM study_groups WHERE id = ? LIMIT 1";
$stmtGroup = $conn->prepare($sqlGroup);
$stmtGroup->bind_param('i', $studyGroupId);
$stmtGroup->execute();
$resultGroup = $stmtGroup->get_result();
if ($row = $resultGroup->fetch_assoc()) {
    $groupName = $row['name'];
}
$stmtGroup->close();

// PERÍODO
$sqlPeriod = "SELECT name FROM periods WHERE id = ? LIMIT 1";
$stmtPeriod = $conn->prepare($sqlPeriod);
$stmtPeriod->bind_param('i', $periodId);
$stmtPeriod->execute();
$resultPeriod = $stmtPeriod->get_result();
if ($row = $resultPeriod->fetch_assoc()) {
    $periodName = $row['name'];
}
$stmtPeriod->close();

// QUERY
$sql = "
    SELECT
        se.id AS enrollment_id,
        se.status AS enrollment_status,
        se.enrollment_date,
        s.id AS student_id,
        s.student_code,
        s.national_id,
        s.first_name,
        s.last_name,
        s.phone_personal,
        s.email
    FROM student_enrollments se
    INNER JOIN students s ON s.id = se.student_id
    WHERE se.institution_id = ?
      AND se.study_group_id = ?
      AND se.period_id = ?
";

$params = [$institutionId, $studyGroupId, $periodId];
$types  = 'iii';

if ($search !== '') {
    $sql .= "
      AND (
            s.student_code LIKE ?
         OR s.national_id LIKE ?
         OR s.first_name LIKE ?
         OR s.last_name LIKE ?
         OR CONCAT(s.last_name, ' ', s.first_name) LIKE ?
      )
    ";
    $searchLike = '%' . $search . '%';
    array_push($params, $searchLike, $searchLike, $searchLike, $searchLike, $searchLike);
    $types .= 'sssss';
}

if ($status !== '') {
    $sql .= " AND se.status = ? ";
    $params[] = $status;
    $types .= 's';
}

$sql .= " ORDER BY s.last_name ASC, s.first_name ASC ";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$students = [];
while ($row = $result->fetch_assoc()) {
    $students[] = $row;
}
$stmt->close();

$totalStudents = count($students);

require_once __DIR__ . '/../../app/views/partials/header.php';
?>

<div class="nova-list-card">

    <div class="nova-list-header">
        <div class="nova-list-header-left">
            <div class="nova-list-icon">
                <i class="fa-solid fa-user-graduate"></i>
            </div>
            <div>
                <h2 class="nova-list-title">Listado de estudiantes</h2>
                <p class="nova-list-subtitle">Consulta y gestión de estudiantes</p>
            </div>
        </div>

        <div class="nova-list-info">
            <div class="nova-list-box">
                <small>Grupo</small>
                <strong><?php echo htmlspecialchars($groupName); ?></strong>
            </div>
            <div class="nova-list-box">
                <small>Período</small>
                <strong><?php echo htmlspecialchars($periodName); ?></strong>
            </div>
        </div>
    </div>

    <form method="GET" class="nova-list-filters">
        <div class="nova-list-filters-left">
            <div class="nova-field">
                <label class="nova-label">Buscar</label>
                <input type="text" name="search" class="nova-input"
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>

            <div class="nova-field">
                <label class="nova-label">Estado</label>
                <select name="status" class="nova-select">
                    <option value="">Todos</option>
                    <option value="active">Activo</option>
                    <option value="retired">Retirado</option>
                    <option value="graduated">Graduado</option>
                    <option value="suspended">Suspendido</option>
                    <option value="migrated">Migrado</option>
                </select>
            </div>
        </div>

        <div class="nova-list-actions">
            <button class="nova-btn nova-btn-secondary">Buscar</button>
            <a href="index.php" class="nova-btn nova-btn-secondary">Limpiar</a>
            <a href="create.php" class="nova-btn nova-btn-primary">Nuevo</a>
        </div>
    </form>

    <div class="nova-table-wrap">
        <table class="nova-table">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Estudiante</th>
                    <th>Cédula</th>
                    <th>Celular</th>
                    <th>Email</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($students as $student): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($student['student_code']); ?></td>

                        <td class="nova-student-name">
                            <?php echo htmlspecialchars($student['last_name'] . ' ' . $student['first_name']); ?>
                        </td>

                        <td><?php echo htmlspecialchars($student['national_id']); ?></td>
                        <td><?php echo htmlspecialchars($student['phone_personal']); ?></td>
                        <td><?php echo htmlspecialchars($student['email']); ?></td>

                        <td>
                            <span class="nova-badge">
                                <?php echo strtoupper($student['enrollment_status']); ?>
                            </span>
                        </td>

                        <td>
                            <div class="nova-table-actions">

                                <!-- EDITAR -->
                                <a class="nova-link-action"
                                   href="edit.php?id=<?php echo (int)$student['student_id']; ?>">
                                   Editar
                                </a>

                                <!-- ACCIONES SEGÚN ESTADO -->
                                <?php if ($student['enrollment_status'] === 'active'): ?>

                                    <a class="nova-link-action"
                                       href="change_status.php?id=<?php echo (int)$student['student_id']; ?>&status=retired">
                                       Retirar
                                    </a>

                                    <a class="nova-link-action"
                                       href="change_status.php?id=<?php echo (int)$student['student_id']; ?>&status=suspended">
                                       Suspender
                                    </a>

                                    <a class="nova-link-action"
                                       href="change_status.php?id=<?php echo (int)$student['student_id']; ?>&status=graduated">
                                       Graduar
                                    </a>

                                <?php else: ?>

                                    <a class="nova-link-action"
                                       href="change_status.php?id=<?php echo (int)$student['student_id']; ?>&status=active">
                                       Activar
                                    </a>

                                <?php endif; ?>

                            </div>
                        </td>

                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</div>

<?php require_once __DIR__ . '/../../app/views/partials/footer.php'; ?>