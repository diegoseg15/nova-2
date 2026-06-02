<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';

// VALIDAR SESIÓN
if (!isset($_SESSION['user_id'])) {
    header('Location: /nova1/login.php');
    exit;
}

// VALIDAR CONTEXTO ACTIVO
if (
    !isset($_SESSION['study_group_id']) ||
    !isset($_SESSION['period_id']) ||
    !isset($_SESSION['context_locked']) ||
    $_SESSION['context_locked'] !== 'Y'
) {
    $_SESSION['error'] = '⚠ Debe activar un grupo y período antes de editar estudiantes.';
    header('Location: /nova1/dashboard.php');
    exit;
}

$db = new Database();
$conn = $db->connect();

$studentId    = (int) ($_GET['id'] ?? 0);
$studyGroupId = (int) $_SESSION['study_group_id'];
$periodId     = (int) $_SESSION['period_id'];

if ($studentId <= 0) {
    $_SESSION['error'] = '⚠ Estudiante no válido.';
    header('Location: /nova1/modules/students/index.php');
    exit;
}

// LIMPIAR MENSAJE VIEJO DE CONTEXTO
if (
    isset($_SESSION['error']) &&
    $_SESSION['error'] === '⚠ Debe activar un grupo y período antes de editar estudiantes.'
) {
    unset($_SESSION['error']);
}

// MENSAJES
$error   = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';
unset($_SESSION['error'], $_SESSION['success']);

// OBTENER NOMBRE DEL GRUPO
$groupName = 'Grupo activo';
$sqlGroup = "SELECT name FROM study_groups WHERE id = ? LIMIT 1";
$stmtGroup = $conn->prepare($sqlGroup);
$stmtGroup->bind_param('i', $studyGroupId);
$stmtGroup->execute();
$resGroup = $stmtGroup->get_result();
if ($row = $resGroup->fetch_assoc()) {
    $groupName = $row['name'];
}
$stmtGroup->close();

// OBTENER NOMBRE DEL PERÍODO
$periodName = 'Período activo';
$sqlPeriod = "SELECT name FROM periods WHERE id = ? LIMIT 1";
$stmtPeriod = $conn->prepare($sqlPeriod);
$stmtPeriod->bind_param('i', $periodId);
$stmtPeriod->execute();
$resPeriod = $stmtPeriod->get_result();
if ($row = $resPeriod->fetch_assoc()) {
    $periodName = $row['name'];
}
$stmtPeriod->close();

// VALIDAR QUE EL ESTUDIANTE PERTENEZCA AL CONTEXTO ACTIVO
$sqlStudent = "
    SELECT
        s.id,
        s.student_code,
        s.national_id,
        s.moodle_username,
        s.first_name,
        s.last_name,
        s.gender,
        s.birth_date,
        s.birth_country,
        s.birth_province,
        s.birth_canton,
        s.birth_city,
        s.phone_personal,
        s.phone_family,
        s.email,
        s.occupation,
        s.residence_country,
        s.residence_province,
        s.residence_canton,
        s.residence_city,
        s.address,
        s.status,
        se.study_group_id,
        se.period_id
    FROM students s
    INNER JOIN student_enrollments se ON se.student_id = s.id
    WHERE s.id = ?
      AND se.study_group_id = ?
      AND se.period_id = ?
    LIMIT 1
";
$stmtStudent = $conn->prepare($sqlStudent);
$stmtStudent->bind_param('iii', $studentId, $studyGroupId, $periodId);
$stmtStudent->execute();
$resStudent = $stmtStudent->get_result();

if ($resStudent->num_rows === 0) {
    $stmtStudent->close();
    $_SESSION['error'] = '⚠ El estudiante no pertenece al grupo y período activos.';
    header('Location: /nova1/modules/students/index.php');
    exit;
}

$student = $resStudent->fetch_assoc();
$stmtStudent->close();

// BLOQUEO DE CÉDULA SI YA TIENE MATRÍCULA
$lockNationalId = false;

$sqlCheck = "SELECT id FROM student_enrollments WHERE student_id = ? LIMIT 1";
$stmtCheck = $conn->prepare($sqlCheck);
$stmtCheck->bind_param('i', $studentId);
$stmtCheck->execute();
$resCheck = $stmtCheck->get_result();

if ($resCheck->num_rows > 0) {
    $lockNationalId = true;
}

$stmtCheck->close();

// DETERMINAR SI ES ECUATORIANO
$isEcuadorian = '1';

if (!empty($student['moodle_username'])) {
    $isEcuadorian = (strpos((string) $student['moodle_username'], 'ec') === 0) ? '1' : '0';
} else {
    $birthCountryKey = trim(mb_strtoupper((string) ($student['birth_country'] ?? 'ECUADOR'), 'UTF-8'));
    $isEcuadorian = ($birthCountryKey === 'ECUADOR') ? '1' : '0';
}

// RESOLVER occupation_id DESDE EL NOMBRE GUARDADO
$occupationId = '';
if (!empty($student['occupation'])) {
    $sqlOccupation = "SELECT id FROM occupations WHERE name = ? LIMIT 1";
    $stmtOccupation = $conn->prepare($sqlOccupation);
    $stmtOccupation->bind_param('s', $student['occupation']);
    $stmtOccupation->execute();
    $resOccupation = $stmtOccupation->get_result();
    if ($rowOcc = $resOccupation->fetch_assoc()) {
        $occupationId = $rowOcc['id'];
    }
    $stmtOccupation->close();
}

function nova_country_key_edit($value)
{
    $value = trim((string) $value);
    $value = mb_strtoupper($value, 'UTF-8');
    $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
    return strtoupper((string) $value);
}

// NACIMIENTO
$birthProvinceId = '';
$birthCantonId   = '';
$birthProvinceText = '';
$birthCantonText   = '';

if (nova_country_key_edit($student['birth_country'] ?? 'ECUADOR') === 'ECUADOR') {
    if (!empty($student['birth_province'])) {
        $sqlBirthProvince = "SELECT id FROM provinces WHERE name = ? LIMIT 1";
        $stmtBirthProvince = $conn->prepare($sqlBirthProvince);
        $stmtBirthProvince->bind_param('s', $student['birth_province']);
        $stmtBirthProvince->execute();
        $resBirthProvince = $stmtBirthProvince->get_result();
        if ($rowBirthProvince = $resBirthProvince->fetch_assoc()) {
            $birthProvinceId = $rowBirthProvince['id'];
        }
        $stmtBirthProvince->close();
    }

    if (!empty($student['birth_canton'])) {
        if ($birthProvinceId !== '') {
            $sqlBirthCanton = "SELECT id FROM cantons WHERE province_id = ? AND name = ? LIMIT 1";
            $stmtBirthCanton = $conn->prepare($sqlBirthCanton);
            $stmtBirthCanton->bind_param('is', $birthProvinceId, $student['birth_canton']);
        } else {
            $sqlBirthCanton = "SELECT id FROM cantons WHERE name = ? LIMIT 1";
            $stmtBirthCanton = $conn->prepare($sqlBirthCanton);
            $stmtBirthCanton->bind_param('s', $student['birth_canton']);
        }

        $stmtBirthCanton->execute();
        $resBirthCanton = $stmtBirthCanton->get_result();
        if ($rowBirthCanton = $resBirthCanton->fetch_assoc()) {
            $birthCantonId = $rowBirthCanton['id'];
        }
        $stmtBirthCanton->close();
    }
} else {
    $birthProvinceText = $student['birth_province'] ?? '';
    $birthCantonText   = $student['birth_canton'] ?? '';
}

// RESIDENCIA
$residenceProvinceId = '';
$residenceCantonId   = '';
$residenceProvinceText = '';
$residenceCantonText   = '';

if (nova_country_key_edit($student['residence_country'] ?? 'ECUADOR') === 'ECUADOR') {
    if (!empty($student['residence_province'])) {
        $sqlResidenceProvince = "SELECT id FROM provinces WHERE name = ? LIMIT 1";
        $stmtResidenceProvince = $conn->prepare($sqlResidenceProvince);
        $stmtResidenceProvince->bind_param('s', $student['residence_province']);
        $stmtResidenceProvince->execute();
        $resResidenceProvince = $stmtResidenceProvince->get_result();
        if ($rowResidenceProvince = $resResidenceProvince->fetch_assoc()) {
            $residenceProvinceId = $rowResidenceProvince['id'];
        }
        $stmtResidenceProvince->close();
    }

    if (!empty($student['residence_canton'])) {
        if ($residenceProvinceId !== '') {
            $sqlResidenceCanton = "SELECT id FROM cantons WHERE province_id = ? AND name = ? LIMIT 1";
            $stmtResidenceCanton = $conn->prepare($sqlResidenceCanton);
            $stmtResidenceCanton->bind_param('is', $residenceProvinceId, $student['residence_canton']);
        } else {
            $sqlResidenceCanton = "SELECT id FROM cantons WHERE name = ? LIMIT 1";
            $stmtResidenceCanton = $conn->prepare($sqlResidenceCanton);
            $stmtResidenceCanton->bind_param('s', $student['residence_canton']);
        }

        $stmtResidenceCanton->execute();
        $resResidenceCanton = $stmtResidenceCanton->get_result();
        if ($rowResidenceCanton = $resResidenceCanton->fetch_assoc()) {
            $residenceCantonId = $rowResidenceCanton['id'];
        }
        $stmtResidenceCanton->close();
    }
} else {
    $residenceProvinceText = $student['residence_province'] ?? '';
    $residenceCantonText   = $student['residence_canton'] ?? '';
}

// PASAR DATOS A form.php
$_SESSION['study_group_name'] = $groupName;
$_SESSION['period_name']      = $periodName;

$_SESSION['old_student_form'] = [
    'last_name'               => $student['last_name'] ?? '',
    'first_name'              => $student['first_name'] ?? '',
    'national_id'             => $student['national_id'] ?? '',
    'moodle_username'         => $student['moodle_username'] ?? '',
    'is_ecuadorian'           => $isEcuadorian,
    'lock_national_id'        => $lockNationalId,
    'gender'                  => $student['gender'] ?? '',
    'birth_date'              => $student['birth_date'] ?? '',
    'birth_country'           => $student['birth_country'] ?? 'ECUADOR',
    'birth_province_id'       => $birthProvinceId,
    'birth_canton_id'         => $birthCantonId,
    'birth_province_text'     => $birthProvinceText,
    'birth_canton_text'       => $birthCantonText,
    'birth_city'              => $student['birth_city'] ?? '',
    'phone_personal'          => $student['phone_personal'] ?? '',
    'phone_family'            => $student['phone_family'] ?? '',
    'email'                   => $student['email'] ?? '',
    'occupation_id'           => $occupationId,
    'residence_country'       => $student['residence_country'] ?? 'ECUADOR',
    'residence_province_id'   => $residenceProvinceId,
    'residence_canton_id'     => $residenceCantonId,
    'residence_province_text' => $residenceProvinceText,
    'residence_canton_text'   => $residenceCantonText,
    'residence_city'          => $student['residence_city'] ?? '',
    'address'                 => $student['address'] ?? ''
];

require_once __DIR__ . '/../../app/views/partials/header.php';
?>

<div class="container-fluid" style="padding:10px 16px;">

    <?php if ($error): ?>
        <div style="background:#fdecea; color:#b71c1c; padding:10px; border-radius:8px; margin-bottom:12px;">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div style="background:#ecfdf3; color:#027a48; padding:10px; border-radius:8px; margin-bottom:12px;">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <form action="update.php?id=<?php echo (int) $studentId; ?>" method="POST">
        <?php require_once __DIR__ . '/partials/form.php'; ?>
    </form>

</div>

<?php require_once __DIR__ . '/../../app/views/partials/footer.php'; ?>