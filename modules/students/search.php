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
$course = trim($_GET['course'] ?? '');

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

// BÚSQUEDA
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
    $params[] = $searchLike;
    $params[] = $searchLike;
    $params[] = $searchLike;
    $params[] = $searchLike;
    $params[] = $searchLike;
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

<style>
.nova-search-card{
    background:#ffffff;
    border:1px solid #e2e8f0;
    border-radius:14px;
    padding:18px 20px 20px;
    box-shadow:0 2px 10px rgba(15,23,42,0.04);
}

.nova-search-header{
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    gap:16px;
    flex-wrap:wrap;
    margin-bottom:14px;
    padding-bottom:14px;
    border-bottom:1px solid #e8eef5;
}

.nova-search-header-left{
    display:flex;
    align-items:flex-start;
    gap:14px;
}

.nova-search-icon{
    width:48px;
    height:48px;
    border-radius:10px;
    background:#2563eb;
    display:flex;
    align-items:center;
    justify-content:center;
    flex-shrink:0;
}

.nova-search-icon i{
    color:#ffffff;
    font-size:22px;
}

.nova-search-title{
    margin:0;
    font-size:20px;
    font-weight:700;
    color:#0f172a;
    line-height:1.2;
}

.nova-search-subtitle{
    margin:4px 0 0 0;
    font-size:13px;
    color:#64748b;
}

.nova-search-info{
    display:flex;
    gap:12px;
    flex-wrap:wrap;
}

.nova-search-box{
    min-width:170px;
    border:1px solid #d7e4f4;
    border-radius:10px;
    padding:12px 14px;
    background:#ffffff;
}

.nova-search-box small{
    display:block;
    margin-bottom:3px;
    font-size:12px;
    color:#64748b;
}

.nova-search-box strong{
    display:block;
    font-size:14px;
    color:#0f172a;
    font-weight:700;
}

.nova-search-filters{
    display:flex;
    flex-direction:column;
    gap:14px;
    margin-bottom:16px;
}

.nova-search-grid{
    display:grid;
    grid-template-columns:1.8fr 1fr 1fr;
    gap:14px;
}

.nova-field{
    display:flex;
    flex-direction:column;
    gap:6px;
}

.nova-label{
    font-size:13px;
    font-weight:600;
    color:#1e293b;
    line-height:1.2;
}

.nova-input,
.nova-select{
    width:100%;
    box-sizing:border-box;
    border:1px solid #cfd8e3;
    border-radius:8px;
    background:#ffffff;
    color:#0f172a;
    font-size:14px;
    padding:10px 12px;
    height:40px;
    transition:border-color .18s ease, box-shadow .18s ease;
}

.nova-input:focus,
.nova-select:focus{
    outline:none;
    border-color:#2563eb;
    box-shadow:0 0 0 3px rgba(37,99,235,0.10);
}

.nova-select:disabled{
    background:#f8fafc;
    color:#94a3b8;
    cursor:not-allowed;
}

.nova-search-actions{
    display:flex;
    justify-content:flex-end;
    gap:10px;
    flex-wrap:wrap;
}

.nova-btn{
    min-width:138px;
    height:40px;
    border-radius:8px;
    font-size:14px;
    font-weight:700;
    border:none;
    cursor:pointer;
    text-decoration:none;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:8px;
}

.nova-btn i{
    font-size:14px;
}

.nova-btn-secondary{
    background:#ffffff;
    color:#334155;
    border:1px solid #cbd5e1;
}

.nova-btn-primary{
    background:#2563eb;
    color:#ffffff;
}

.nova-summary{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:12px;
    flex-wrap:wrap;
    margin-bottom:12px;
}

.nova-summary-text{
    font-size:13px;
    color:#475569;
    font-weight:600;
}

.nova-table-wrap{
    width:100%;
    overflow-x:auto;
    border:1px solid #e2e8f0;
    border-radius:12px;
    background:#ffffff;
}

.nova-table{
    width:100%;
    border-collapse:collapse;
    min-width:980px;
}

.nova-table thead th{
    background:#f8fafc;
    color:#334155;
    font-size:12px;
    font-weight:700;
    text-align:left;
    padding:12px 14px;
    border-bottom:1px solid #e2e8f0;
    white-space:nowrap;
}

.nova-table tbody td{
    padding:12px 14px;
    border-bottom:1px solid #edf2f7;
    font-size:13px;
    color:#0f172a;
    vertical-align:middle;
}

.nova-table tbody tr:hover{
    background:#f8fbff;
}

.nova-student-name{
    font-weight:700;
    color:#0f172a;
}

.nova-muted{
    color:#64748b;
}

.nova-badge{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    min-height:26px;
    padding:0 10px;
    border-radius:999px;
    font-size:12px;
    font-weight:700;
    white-space:nowrap;
}

.nova-badge-active{
    background:#e8f7ed;
    color:#15803d;
}

.nova-badge-retired{
    background:#fff7ed;
    color:#c2410c;
}

.nova-badge-other{
    background:#eef2ff;
    color:#4338ca;
}

.nova-empty{
    padding:36px 20px;
    text-align:center;
    color:#64748b;
    font-size:14px;
}

.nova-table-actions{
    display:flex;
    gap:8px;
    flex-wrap:wrap;
}

.nova-link-action{
    color:#2563eb;
    text-decoration:none;
    font-weight:700;
    font-size:13px;
}

.nova-link-action:hover{
    text-decoration:underline;
}

@media (max-width: 1100px){
    .nova-search-grid{
        grid-template-columns:1fr;
    }
}

@media (max-width: 768px){
    .nova-search-card{
        padding:14px;
    }

    .nova-search-actions{
        justify-content:stretch;
    }

    .nova-btn{
        width:100%;
    }
}
</style>

<div class="nova-search-card">

    <div class="nova-search-header">
        <div class="nova-search-header-left">
            <div class="nova-search-icon">
                <i class="fa-solid fa-magnifying-glass"></i>
            </div>

            <div>
                <h2 class="nova-search-title">Buscar estudiantes</h2>
                <p class="nova-search-subtitle">Consulta y selección de estudiantes dentro del contexto activo</p>
            </div>
        </div>

        <div class="nova-search-info">
            <div class="nova-search-box">
                <small>Grupo</small>
                <strong><?php echo htmlspecialchars($groupName); ?></strong>
            </div>

            <div class="nova-search-box">
                <small>Período</small>
                <strong><?php echo htmlspecialchars($periodName); ?></strong>
            </div>
        </div>
    </div>

    <form method="GET" class="nova-search-filters">
        <div class="nova-search-grid">
            <div class="nova-field">
                <label class="nova-label">Buscar por apellidos, nombres o cédula</label>
                <input
                    type="text"
                    name="search"
                    class="nova-input"
                    placeholder="Ejemplo: SEGOVIA, 1802..., DENYS"
                    value="<?php echo htmlspecialchars($search); ?>"
                >
            </div>

            <div class="nova-field">
                <label class="nova-label">Estado</label>
                <select name="status" class="nova-select">
                    <option value="">Todos</option>
                    <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Activo</option>
                    <option value="retired" <?php echo $status === 'retired' ? 'selected' : ''; ?>>Retirado</option>
                    <option value="graduated" <?php echo $status === 'graduated' ? 'selected' : ''; ?>>Graduado</option>
                    <option value="suspended" <?php echo $status === 'suspended' ? 'selected' : ''; ?>>Suspendido</option>
                    <option value="migrated" <?php echo $status === 'migrated' ? 'selected' : ''; ?>>Migrado</option>
                </select>
            </div>

            <div class="nova-field">
                <label class="nova-label">Curso</label>
                <select name="course" class="nova-select" disabled>
                    <option value="">Visible, sin lógica aún</option>
                </select>
            </div>
        </div>

        <div class="nova-search-actions">
            <button type="submit" class="nova-btn nova-btn-primary">
                <i class="fa-solid fa-magnifying-glass"></i>
                <span>Buscar</span>
            </button>

            <a href="/nova1/modules/students/search.php" class="nova-btn nova-btn-secondary">
                <i class="fa-solid fa-rotate-left"></i>
                <span>Limpiar</span>
            </a>
        </div>
    </form>

    <div class="nova-summary">
        <div class="nova-summary-text">
            Total de registros encontrados: <?php echo $totalStudents; ?>
        </div>
    </div>

    <div class="nova-table-wrap">
        <?php if (!empty($students)): ?>
            <table class="nova-table">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Estudiante</th>
                        <th>Cédula</th>
                        <th>Curso</th>
                        <th>Estado</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                        <?php
                        $badgeClass = 'nova-badge-other';
                        $statusLabel = strtoupper((string) $student['enrollment_status']);

                        if ($student['enrollment_status'] === 'active') {
                            $badgeClass = 'nova-badge-active';
                            $statusLabel = 'ACTIVO';
                        } elseif ($student['enrollment_status'] === 'retired') {
                            $badgeClass = 'nova-badge-retired';
                            $statusLabel = 'RETIRADO';
                        } elseif ($student['enrollment_status'] === 'graduated') {
                            $statusLabel = 'GRADUADO';
                        } elseif ($student['enrollment_status'] === 'suspended') {
                            $statusLabel = 'SUSPENDIDO';
                        } elseif ($student['enrollment_status'] === 'migrated') {
                            $statusLabel = 'MIGRADO';
                        }
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($student['student_code'] ?? ''); ?></td>
                            <td>
                                <div class="nova-student-name">
                                    <?php echo htmlspecialchars(trim(($student['last_name'] ?? '') . ' ' . ($student['first_name'] ?? ''))); ?>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($student['national_id'] ?? ''); ?></td>
                            <td class="nova-muted">Sin lógica aún</td>
                            <td>
                                <span class="nova-badge <?php echo $badgeClass; ?>">
                                    <?php echo htmlspecialchars($statusLabel); ?>
                                </span>
                            </td>
                            <td>
                                <div class="nova-table-actions">
                                    <a class="nova-link-action" href="/nova1/modules/students/edit.php?id=<?php echo (int) $student['student_id']; ?>">
                                        Seleccionar
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="nova-empty">
                No existen estudiantes que coincidan con el criterio de búsqueda.
            </div>
        <?php endif; ?>
    </div>

</div>

<?php require_once __DIR__ . '/../../app/views/partials/footer.php'; ?>