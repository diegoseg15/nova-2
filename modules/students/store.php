<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/services/StudentHelper.php';

/*
|--------------------------------------------------------------------------
| VALIDACIONES BASE
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /nova1/modules/students/create.php');
    exit;
}

if (!isset($_SESSION['user_id'])) {
    header('Location: /nova1/login.php');
    exit;
}

if (
    !isset($_SESSION['study_group_id']) ||
    !isset($_SESSION['period_id']) ||
    !isset($_SESSION['context_locked']) ||
    $_SESSION['context_locked'] !== 'Y'
) {
    $_SESSION['error'] = '⚠ Debe activar un grupo y período antes de registrar estudiantes.';
    header('Location: /nova1/dashboard.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| CONEXIÓN
|--------------------------------------------------------------------------
*/
$db = new Database();
$conn = $db->connect();
$conn->set_charset('utf8mb4');

$institutionId = (int)($_SESSION['institution_id'] ?? 1);
$studyGroupId  = (int)$_SESSION['study_group_id'];
$periodId      = (int)$_SESSION['period_id'];

/*
|--------------------------------------------------------------------------
| CAPTURA Y NORMALIZACIÓN
|--------------------------------------------------------------------------
*/
$lastName       = nova_upper($_POST['last_name'] ?? '');
$firstName      = nova_upper($_POST['first_name'] ?? '');
$nationalId     = nova_clean_identifier($_POST['national_id'] ?? '');
$isEcuadorian   = ($_POST['is_ecuadorian'] ?? '1') === '1' ? '1' : '0';
$moodleUsername = ($isEcuadorian === '1' ? 'ec' : 'ex') . $nationalId;

$phonePersonal = nova_clean($_POST['phone_personal'] ?? '');
$phoneFamily   = nova_clean($_POST['phone_family'] ?? '');
$email         = strtolower(nova_clean($_POST['email'] ?? ''));
$gender        = nova_clean($_POST['gender'] ?? '');
$status        = nova_clean($_POST['status'] ?? 'activo');

$birthDate    = nova_clean($_POST['birth_date'] ?? '');
$birthCountry = nova_country($_POST['birth_country'] ?? 'ECUADOR');
$birthCity    = nova_upper($_POST['birth_city'] ?? '');

$residenceCountry = nova_country($_POST['residence_country'] ?? 'ECUADOR');
$residenceCity    = nova_upper($_POST['residence_city'] ?? '');
$address          = nova_upper($_POST['address'] ?? '');

$occupationId        = (int)($_POST['occupation_id'] ?? 0);
$birthProvinceId     = (int)($_POST['birth_province_id'] ?? 0);
$birthCantonId       = (int)($_POST['birth_canton_id'] ?? 0);
$residenceProvinceId = (int)($_POST['residence_province_id'] ?? 0);
$residenceCantonId   = (int)($_POST['residence_canton_id'] ?? 0);

$birthProvinceText     = nova_upper($_POST['birth_province_text'] ?? '');
$birthCantonText       = nova_upper($_POST['birth_canton_text'] ?? '');
$residenceProvinceText = nova_upper($_POST['residence_province_text'] ?? '');
$residenceCantonText   = nova_upper($_POST['residence_canton_text'] ?? '');

$allowedStatuses = ['activo', 'retirado', 'graduado', 'suspendido', 'migrado'];

if (!in_array($status, $allowedStatuses, true)) {
    $status = 'activo';
}

if ($gender !== '' && !in_array($gender, ['M', 'F'], true)) {
    $gender = '';
}

/*
|--------------------------------------------------------------------------
| PRESERVAR FORMULARIO SI HAY ERROR
|--------------------------------------------------------------------------
*/
$_SESSION['old_student_form'] = [
    'last_name'               => $lastName,
    'first_name'              => $firstName,
    'national_id'             => $nationalId,
    'is_ecuadorian'           => $isEcuadorian,
    'moodle_username'         => $moodleUsername,
    'phone_personal'          => $phonePersonal,
    'phone_family'            => $phoneFamily,
    'email'                   => $email,
    'gender'                  => $gender,
    'status'                  => $status,
    'birth_date'              => $birthDate,
    'birth_country'           => $birthCountry,
    'birth_city'              => $birthCity,
    'residence_country'       => $residenceCountry,
    'residence_city'          => $residenceCity,
    'address'                 => $address,
    'occupation_id'           => $occupationId,
    'birth_province_id'       => $birthProvinceId,
    'birth_canton_id'         => $birthCantonId,
    'residence_province_id'   => $residenceProvinceId,
    'residence_canton_id'     => $residenceCantonId,
    'birth_province_text'     => $birthProvinceText,
    'birth_canton_text'       => $birthCantonText,
    'residence_province_text' => $residenceProvinceText,
    'residence_canton_text'   => $residenceCantonText,
];

/*
|--------------------------------------------------------------------------
| VALIDACIONES DE NEGOCIO
|--------------------------------------------------------------------------
*/
if (
    $lastName === '' ||
    $firstName === '' ||
    $nationalId === '' ||
    $phonePersonal === '' ||
    $status === ''
) {
    $_SESSION['error'] = '⚠ Debe completar los campos obligatorios.';
    nova_redirect_back();
}

if ($isEcuadorian === '1' && !nova_valid_ecuador_id($nationalId)) {
    $_SESSION['error'] = '⚠ La cédula ecuatoriana ingresada no es válida.';
    nova_redirect_back();
}

/*
|--------------------------------------------------------------------------
| VALIDAR DUPLICADOS
|--------------------------------------------------------------------------
*/
$sqlExists = "SELECT id FROM students WHERE national_id = ? LIMIT 1";
$stmtExists = $conn->prepare($sqlExists);
$stmtExists->bind_param('s', $nationalId);
$stmtExists->execute();
$resultExists = $stmtExists->get_result();

if ($resultExists->fetch_assoc()) {
    $stmtExists->close();
    $_SESSION['error'] = '⚠ La cédula o identificación ya pertenece a otro estudiante.';
    nova_redirect_back();
}

$stmtExists->close();

$sqlMoodleExists = "SELECT id FROM students WHERE moodle_username = ? LIMIT 1";
$stmtMoodleExists = $conn->prepare($sqlMoodleExists);
$stmtMoodleExists->bind_param('s', $moodleUsername);
$stmtMoodleExists->execute();
$resultMoodleExists = $stmtMoodleExists->get_result();

if ($resultMoodleExists->fetch_assoc()) {
    $stmtMoodleExists->close();
    $_SESSION['error'] = '⚠ El usuario Moodle generado ya existe.';
    nova_redirect_back();
}

$stmtMoodleExists->close();

/*
|--------------------------------------------------------------------------
| RESOLVER CATÁLOGOS
|--------------------------------------------------------------------------
*/
$occupationName = nova_upper(nova_get_name_by_id($conn, 'occupations', $occupationId));

if (nova_country_key($birthCountry) === 'ECUADOR') {
    $birthProvinceName = nova_upper(nova_get_name_by_id($conn, 'provinces', $birthProvinceId));
    $birthCantonName   = nova_upper(nova_get_name_by_id($conn, 'cantons', $birthCantonId));
} else {
    $birthProvinceName = $birthProvinceText;
    $birthCantonName   = $birthCantonText;
}

if (nova_country_key($residenceCountry) === 'ECUADOR') {
    $residenceProvinceName = nova_upper(nova_get_name_by_id($conn, 'provinces', $residenceProvinceId));
    $residenceCantonName   = nova_upper(nova_get_name_by_id($conn, 'cantons', $residenceCantonId));
} else {
    $residenceProvinceName = $residenceProvinceText;
    $residenceCantonName   = $residenceCantonText;
}

/*
|--------------------------------------------------------------------------
| VALORES NULLABLES
|--------------------------------------------------------------------------
*/
$genderDb             = ($gender === 'M' || $gender === 'F') ? $gender : null;
$birthDateDb          = $birthDate !== '' ? $birthDate : null;
$phoneFamilyDb        = $phoneFamily !== '' ? $phoneFamily : null;
$emailDb              = $email !== '' ? $email : null;
$occupationDb         = $occupationName !== '' ? $occupationName : null;
$birthProvinceDb      = $birthProvinceName !== '' ? $birthProvinceName : null;
$birthCantonDb        = $birthCantonName !== '' ? $birthCantonName : null;
$birthCityDb          = $birthCity !== '' ? $birthCity : null;
$residenceProvinceDb  = $residenceProvinceName !== '' ? $residenceProvinceName : null;
$residenceCantonDb    = $residenceCantonName !== '' ? $residenceCantonName : null;
$residenceCityDb      = $residenceCity !== '' ? $residenceCity : null;
$addressDb            = $address !== '' ? $address : null;

/*
|--------------------------------------------------------------------------
| TRANSACCIÓN
|--------------------------------------------------------------------------
*/
$conn->begin_transaction();

try {
    $tempCode = 'TEMP';

    $sqlStudent = "INSERT INTO students (
        institution_id,
        student_code,
        national_id,
        moodle_username,
        first_name,
        last_name,
        gender,
        birth_date,
        birth_country,
        birth_province,
        birth_canton,
        birth_city,
        phone_personal,
        phone_family,
        email,
        occupation,
        residence_country,
        residence_province,
        residence_canton,
        residence_city,
        address,
        status,
        created_at,
        updated_at
    ) VALUES (
        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW()
    )";

    $stmtStudent = $conn->prepare($sqlStudent);

    if (!$stmtStudent) {
        throw new Exception('No se pudo preparar el registro del estudiante.');
    }

    $stmtStudent->bind_param(
        'isssssssssssssssssssss',
        $institutionId,
        $tempCode,
        $nationalId,
        $moodleUsername,
        $firstName,
        $lastName,
        $genderDb,
        $birthDateDb,
        $birthCountry,
        $birthProvinceDb,
        $birthCantonDb,
        $birthCityDb,
        $phonePersonal,
        $phoneFamilyDb,
        $emailDb,
        $occupationDb,
        $residenceCountry,
        $residenceProvinceDb,
        $residenceCantonDb,
        $residenceCityDb,
        $addressDb,
        $status
    );

    $stmtStudent->execute();
    $studentId = (int)$stmtStudent->insert_id;
    $stmtStudent->close();

    if ($studentId <= 0) {
        throw new Exception('No se pudo crear el estudiante.');
    }

    /*
    |--------------------------------------------------------------------------
    | GENERAR CÓDIGO INTERNO
    |--------------------------------------------------------------------------
    */
    $studentCode = 'STU-' . str_pad((string)$studentId, 6, '0', STR_PAD_LEFT);

    $sqlUpdateCode = "UPDATE students
                      SET student_code = ?, updated_at = NOW()
                      WHERE id = ?";

    $stmtUpdateCode = $conn->prepare($sqlUpdateCode);
    $stmtUpdateCode->bind_param('si', $studentCode, $studentId);
    $stmtUpdateCode->execute();
    $stmtUpdateCode->close();

    /*
    |--------------------------------------------------------------------------
    | CREAR MATRÍCULA
    |--------------------------------------------------------------------------
    */
    $sqlEnrollment = "INSERT INTO student_enrollments (
        institution_id,
        student_id,
        study_group_id,
        period_id,
        enrollment_date,
        status,
        notes,
        created_at,
        updated_at
    ) VALUES (
        ?, ?, ?, ?, CURDATE(), 'active', NULL, NOW(), NOW()
    )";

    $stmtEnrollment = $conn->prepare($sqlEnrollment);

    if (!$stmtEnrollment) {
        throw new Exception('No se pudo preparar la matrícula del estudiante.');
    }

    $stmtEnrollment->bind_param(
        'iiii',
        $institutionId,
        $studentId,
        $studyGroupId,
        $periodId
    );

    $stmtEnrollment->execute();
    $stmtEnrollment->close();

    $conn->commit();

    unset($_SESSION['old_student_form']);

    $_SESSION['success'] = '✔ Estudiante registrado correctamente.';
    header('Location: /nova1/modules/students/create.php');
    exit;

} catch (Throwable $e) {
    $conn->rollback();

    $_SESSION['error'] = '⚠ Error al guardar el estudiante: ' . $e->getMessage();
    nova_redirect_back();
}