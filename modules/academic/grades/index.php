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

$subjectId = (int)($_GET['subject_id'] ?? 0);

if ($subjectId <= 0) {
    header('Location: /nova1/modules/school_regime/periods/index.php');
    exit;
}

$sqlSubject = "SELECT 
                s.id,
                s.course_id,
                s.code,
                s.name,
                s.cycle,
                c.name AS course_name,
                p.name AS period_name,
                p.study_group_id,
                p.institution_id,
                i.name AS institution_name,
                g.name AS group_name
              FROM subjects s
              INNER JOIN courses c ON c.id = s.course_id
              INNER JOIN periods p ON p.id = c.period_id
              INNER JOIN institutions i ON i.id = p.institution_id
              INNER JOIN study_groups g ON g.id = p.study_group_id
              WHERE s.id = ?
              LIMIT 1";

$stmt = $conn->prepare($sqlSubject);
$stmt->bind_param('i', $subjectId);
$stmt->execute();
$result = $stmt->get_result();
$subject = $result ? $result->fetch_assoc() : null;
$stmt->close();

if (!$subject) {
    header('Location: /nova1/modules/school_regime/periods/index.php');
    exit;
}

$gradesData = MoodleService::getGradesBySubject(
    $conn,
    (int)$subject['institution_id'],
    (int)$subject['study_group_id'],
    $subject['period_name'],
    $subject['course_name'],
    $subject['code']
);

$headers = $gradesData['headers'] ?? [];
$students = $gradesData['students'] ?? [];
$teachers = $gradesData['teachers'] ?? [];

$totalStudents = count($students);
$totalEvaluations = count($headers);

$finalSum = 0;
$finalCount = 0;

foreach ($students as $student) {
    $finalRaw = $student['final'] ?? null;
    $finalNumber = is_numeric($finalRaw) ? (float)$finalRaw : null;

    if ($finalNumber !== null) {
        $finalSum += $finalNumber;
        $finalCount++;
    }
}

$generalAverage = $finalCount > 0 ? number_format($finalSum / $finalCount, 2) : '—';

require_once __DIR__ . '/../../../app/views/partials/header.php';
?>

<style>
    .grades-page {
        display: flex;
        flex-direction: column;
        gap: 18px;
    }

    .grades-hero {
        background: #0f172a;
        border-radius: 8px;
        padding: 20px 22px;
        color: #fff;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 18px;
        box-shadow: 0 3px 10px rgba(15, 23, 42, .10);
    }

    .grades-hero h1 {
        margin: 0;
        font-size: 26px;
        font-weight: 800;
        letter-spacing: -.02em;
    }

    .grades-hero p {
        margin: 6px 0 0;
        color: #dbeafe;
        font-size: 14px;
    }

    .btn-back {
        background: rgba(255, 255, 255, .14);
        color: #fff;
        padding: 10px 14px;
        border-radius: 6px;
        text-decoration: none;
        font-weight: 800;
        font-size: 13px;
        white-space: nowrap;
        border: 1px solid rgba(255, 255, 255, .18);
    }

    .btn-back:hover {
        background: rgba(255, 255, 255, .22);
    }

    .context-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        padding: 16px;
        box-shadow: 0 4px 12px rgba(15, 23, 42, .04);
    }

    .context-grid {
        display: grid;
        grid-template-columns: repeat(5, minmax(150px, 1fr));
        gap: 14px;
    }

    .context-item span {
        display: block;
        font-size: 11px;
        color: #64748b;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .04em;
        margin-bottom: 4px;
    }

    .context-item strong {
        color: #0f172a;
        font-size: 14px;
    }

    .kpi-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(160px, 1fr));
        gap: 14px;
    }

    .kpi-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        padding: 16px;
        box-shadow: 0 4px 12px rgba(15, 23, 42, .04);
    }

    .kpi-label {
        color: #64748b;
        font-size: 12px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .04em;
        margin-bottom: 8px;
    }

    .kpi-value {
        color: #0f172a;
        font-size: 24px;
        font-weight: 900;
        line-height: 1;
    }

    .kpi-sub {
        margin-top: 8px;
        color: #64748b;
        font-size: 12px;
        font-weight: 600;
    }

    .teacher-list {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .teacher-pill {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        font-size: 12px;
        font-weight: 800;
        width: fit-content;
    }

    .error-box {
        background: #fee2e2;
        border: 1px solid #fecaca;
        color: #991b1b;
        padding: 16px;
        border-radius: 14px;
        font-weight: 700;
    }

    .table-panel {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(15, 23, 42, .05);
    }

    .table-panel-head {
        padding: 14px 16px;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        background: #f8fafc;
    }

    .table-panel-head h2 {
        margin: 0;
        font-size: 16px;
        color: #0f172a;
    }

    .table-panel-head p {
        margin: 3px 0 0;
        font-size: 12px;
        color: #64748b;
    }

    .legend-chip {
        background: #fff;
        border: 1px solid #e5e7eb;
        color: #475569;
        border-radius: 999px;
        padding: 7px 10px;
        font-size: 12px;
        font-weight: 700;
        white-space: nowrap;
    }

    .grade-table-wrap {
        overflow: auto;
        max-height: 68vh;
    }

    .grade-table {
        width: max-content;
        min-width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        font-size: 13px;
    }

    .grade-table thead th {
        background: #f8fafc;
        color: #334155;
        font-weight: 800;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: .04em;
        padding: 12px 10px;
        border-bottom: 1px solid #e5e7eb;
        position: sticky;
        top: 0;
        z-index: 3;
    }

    .grade-table tbody td {
        padding: 10px;
        border-bottom: 1px solid #f1f5f9;
        text-align: center;
        background: #fff;
    }

    .grade-table tbody tr:hover td {
        background: #f8fafc;
    }

    .student-head,
    .student-col {
        position: sticky;
        left: 0;
        min-width: 260px;
        max-width: 260px;
        text-align: left !important;
        box-shadow: 2px 0 0 #e5e7eb;
    }

    .student-head {
        z-index: 5 !important;
        background: #f8fafc !important;
    }

    .student-col {
        z-index: 2;
        font-weight: 600;
        color: #0f172a;
    }

    .student-name {
        display: flex;
        align-items: center;
        gap: 9px;
    }

    .eval-head {
        min-width: 82px;
        max-width: 82px;
        text-align: center;
        cursor: help;
        line-height: 1.15;
    }

    .grade-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 38px;
        padding: 4px 7px;
        border-radius: 4px;
        background: #eff6ff;
        color: #1d4ed8;
        font-weight: 800;
        font-size: 12px;
    }

    .grade-low {
        background: #fee2e2;
        color: #b91c1c;
    }

    .grade-mid {
        background: #fef3c7;
        color: #92400e;
    }

    .grade-high {
        background: #dcfce7;
        color: #166534;
    }

    .grade-empty {
        color: #94a3b8;
        font-weight: 800;
    }

    .final-head {
        position: sticky !important;
        right: 0;
        z-index: 4 !important;
        background: #ecfeff !important;
        min-width: 92px;
    }

    .final-col {
        position: sticky;
        right: 0;
        background: #ecfeff !important;
        color: #0f766e;
        font-weight: 900;
        box-shadow: -2px 0 0 #ccfbf1;
    }

    .eval-legend {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        padding: 14px;
        box-shadow: 0 4px 12px rgba(15, 23, 42, .04);
    }

    .eval-legend-title {
        font-size: 12px;
        color: #64748b;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: .04em;
        margin-bottom: 10px;
    }

    .eval-list {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 8px;
    }

    .eval-item {
        background: #f8fafc;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 9px 10px;
        font-size: 12px;
        color: #334155;
    }

    .eval-item strong {
        color: #1d4ed8;
        margin-right: 6px;
    }

    @media (max-width: 1000px) {
        .grades-hero {
            flex-direction: column;
        }

        .context-grid,
        .kpi-grid {
            grid-template-columns: 1fr 1fr;
        }
    }

    @media (max-width: 640px) {

        .context-grid,
        .kpi-grid {
            grid-template-columns: 1fr;
        }

        .student-head,
        .student-col {
            min-width: 210px;
            max-width: 210px;
        }
    }
</style>

<div class="grades-page">

    <div class="grades-hero">
        <div>
            <h1>Calificaciones de materia</h1>
            <p>Panel académico consolidado con notas sincronizadas desde Moodle.</p>
        </div>

        <a href="/nova1/modules/school_regime/subjects/index.php?course_id=<?php echo (int)$subject['course_id']; ?>"
            class="btn-back">
            <i class="fa-solid fa-arrow-left"></i> Volver a materias
        </a>
    </div>

    <div class="context-card">
        <div class="context-grid">
            <div class="context-item">
                <span>Institución</span>
                <strong><?php echo htmlspecialchars($subject['institution_name']); ?></strong>
            </div>

            <div class="context-item">
                <span>Grupo</span>
                <strong><?php echo htmlspecialchars($subject['group_name']); ?></strong>
            </div>

            <div class="context-item">
                <span>Período</span>
                <strong><?php echo htmlspecialchars($subject['period_name']); ?></strong>
            </div>

            <div class="context-item">
                <span>Curso</span>
                <strong><?php echo htmlspecialchars($subject['course_name']); ?></strong>
            </div>

            <div class="context-item">
                <span>Materia</span>
                <strong><?php echo htmlspecialchars($subject['code'] . ' - ' . $subject['name']); ?></strong>
            </div>
        </div>
    </div>

    <?php if (!$gradesData['ok']): ?>

        <div class="error-box">
            <?php echo htmlspecialchars($gradesData['message']); ?>
        </div>

    <?php else: ?>

        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-label">Estudiantes</div>
                <div class="kpi-value"><?php echo (int)$totalStudents; ?></div>
                <div class="kpi-sub">Matriculados en la materia</div>
            </div>

            <div class="kpi-card">
                <div class="kpi-label">Evaluaciones</div>
                <div class="kpi-value"><?php echo (int)$totalEvaluations; ?></div>
                <div class="kpi-sub">Ítems de calificación detectados</div>
            </div>

            <div class="kpi-card">
                <div class="kpi-label">Promedio final</div>
                <div class="kpi-value"><?php echo htmlspecialchars($generalAverage); ?></div>
                <div class="kpi-sub">Calculado desde notas finales disponibles</div>
            </div>

            <div class="kpi-card">
                <div class="kpi-label">Docente responsable</div>

                <?php if (!empty($teachers)): ?>
                    <div class="teacher-list">
                        <?php foreach ($teachers as $teacher): ?>
                            <div class="teacher-pill">
                                <i class="fa-solid fa-chalkboard-user" style="color: #1d4ed8;"></i>
                                <?php echo htmlspecialchars($teacher); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="kpi-value" style="font-size:18px;color:#94a3b8;">No identificado</div>
                    <div class="kpi-sub">Verificar roles del curso en Moodle</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- <?php if (!empty($headers)): ?>
            <div class="eval-legend">
                <div class="eval-legend-title">Leyenda de evaluaciones</div>

                <div class="eval-list">
                    <?php foreach ($headers as $index => $header): ?>
                        <div class="eval-item">
                            <strong>Eval <?php echo $index + 1; ?></strong>
                            <?php echo htmlspecialchars($header); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?> -->

        <div class="table-panel">
            <div class="table-panel-head">
                <div>
                    <h2>Detalle de calificaciones</h2>
                    <p>La columna de estudiante y la nota final permanecen fijas al desplazarse.</p>
                </div>

                <div class="legend-chip">
                    <?php echo (int)$totalStudents; ?> estudiantes · <?php echo (int)$totalEvaluations; ?> evaluaciones
                </div>
            </div>

            <div class="grade-table-wrap">
                <table class="grade-table">
                    <thead>
                        <tr>
                            <th class="student-head">Estudiante</th>

                            <?php foreach ($headers as $index => $header): ?>
                                <th class="eval-head" title="<?php echo htmlspecialchars($header); ?>">
                                    Eval <?php echo $index + 1; ?>
                                </th>
                            <?php endforeach; ?>

                            <th class="final-head">Final</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (!empty($students)): ?>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td class="student-col">
                                        <div class="student-name">
                                            <span><?php echo htmlspecialchars($student['student_name']); ?></span>
                                        </div>
                                    </td>

                                    <?php foreach ($headers as $header): ?>
                                        <td>
                                            <?php
                                            $grade = $student['grades'][$header] ?? '';
                                            $gradeNumber = is_numeric($grade) ? (float)$grade : null;
                                            $gradeClass = '';

                                            if ($gradeNumber !== null) {
                                                if ($gradeNumber < 7) {
                                                    $gradeClass = 'grade-low';
                                                } elseif ($gradeNumber < 8.5) {
                                                    $gradeClass = 'grade-mid';
                                                } else {
                                                    $gradeClass = 'grade-high';
                                                }
                                            }
                                            ?>

                                            <?php if ($grade !== ''): ?>
                                                <span class="grade-badge <?php echo $gradeClass; ?>">
                                                    <?php echo htmlspecialchars($grade); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="grade-empty">—</span>
                                            <?php endif; ?>
                                        </td>
                                    <?php endforeach; ?>

                                    <?php
                                    $final = $student['final'] ?? '';
                                    $finalNumber = is_numeric($final) ? (float)$final : null;
                                    $finalClass = '';

                                    if ($finalNumber !== null) {
                                        if ($finalNumber < 7) {
                                            $finalClass = 'grade-low';
                                        } elseif ($finalNumber < 8.5) {
                                            $finalClass = 'grade-mid';
                                        } else {
                                            $finalClass = 'grade-high';
                                        }
                                    }
                                    ?>

                                    <td class="final-col">
                                        <?php if ($final !== ''): ?>
                                            <span class="grade-badge <?php echo $finalClass; ?>">
                                                <?php echo htmlspecialchars($final); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="grade-empty">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="<?php echo count($headers) + 2; ?>" style="padding:22px;text-align:center;color:#64748b;">
                                    No existen estudiantes matriculados en esta materia.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../../../app/views/partials/footer.php'; ?>