<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';

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

$db = new Database();
$conn = $db->connect();
$conn->set_charset("utf8mb4");

$institutionId = (int) ($_SESSION['institution_id'] ?? 1);
$studyGroupId  = (int) $_SESSION['study_group_id'];
$periodId      = (int) $_SESSION['period_id'];

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/
function nova_clean(?string $value): string
{
    return trim((string) $value);
}

function nova_upper(?string $value): string
{
    $value = trim((string) $value);
    if ($value === '') {
        return '';
    }

    return mb_strtoupper($value, 'UTF-8');
}

function nova_redirect_back(): void
{
    header('Location: /nova1/modules/students/create.php');
    exit;
}

function nova_valid_ecuador_id(string $cedula): bool
{
    if (!preg_match('/^\d{10}$/', $cedula)) {
        return false;
    }

    $province = (int) substr($cedula, 0, 2);
    $third    = (int) substr($cedula, 2, 1);

    if ($province < 1 || $province > 24) {
        return false;
    }

    if ($third >= 6) {
        return false;
    }

    $digits = array_map('intval', str_split($cedula));
    $sum = 0;

    for ($i = 0; $i < 9; $i++) {
        $value = $digits[$i];

        if ($i % 2 === 0) {
            $value *= 2;
            if ($value > 9) {
                $value -= 9;
            }
        }

        $sum += $value;
    }

    $verifier = (10 - ($sum % 10)) % 10;

    return $verifier === $digits[9];
}

function nova_get_name_by_id(mysqli $conn, string $table, int $id): string
{
    if ($id <= 0) {
        return '';
    }

    $allowed = ['occupations', 'provinces', 'cantons'];
    if (!in_array($table, $allowed, true)) {
        return '';
    }

    $sql = "SELECT name FROM {$table} WHERE id = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return '';
    }

    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $name = '';

    if ($row = $result->fetch_assoc()) {
        $name = (string) $row['name'];
    }

    $stmt->close();
    return $name;
}

/*
|--------------------------------------------------------------------------
| Captura de datos
|--------------------------------------------------------------------------
*/
$lastName            = nova_upper($_POST['last_name'] ?? '');
$firstName           = nova_upper($_POST['first_name'] ?? '');
$nationalId          = nova_clean($_POST['national_id'] ?? '');
$phonePersonal       = nova_clean($_POST['phone_personal'] ?? '');
$gender = $_POST['gender'] ?? '';
$phoneFamily         = nova_clean($_POST['phone_family'] ?? '');
$email               = strtolower(nova_clean($_POST['email'] ?? ''));
$birthDate           = nova_clean($_POST['birth_date'] ?? '');
$birthCountry        = nova_upper($_POST['birth_country'] ?? 'ECUADOR');
$birthCity           = nova_upper($_POST['birth_city'] ?? '');
$residenceCountry    = nova_upper($_POST['residence_country'] ?? 'ECUADOR');
$residenceCity       = nova_upper($_POST['residence_city'] ?? '');
$address             = nova_upper($_POST['address'] ?? '');

$occupationId        = (int) ($_POST['occupation_id'] ?? 0);
$birthProvinceId     = (int) ($_POST['birth_province_id'] ?? 0);
$birthCantonId       = (int) ($_POST['birth_canton_id'] ?? 0);
$residenceProvinceId = (int) ($_POST['residence_province_id'] ?? 0);
$residenceCantonId   = (int) ($_POST['residence_canton_id'] ?? 0);


/*
|--------------------------------------------------------------------------
| Guardar formulario para repoblar si hay error
|--------------------------------------------------------------------------
*/
$_SESSION['old_student_form'] = [
    'last_name'              => $lastName,
    'first_name'             => $firstName,
    'national_id'            => $nationalId,
    'phone_personal'         => $phonePersonal,
    'phone_family'           => $phoneFamily,
    'email'                  => $email,
    'birth_date'             => $birthDate,
    'birth_country'          => $birthCountry,
    'birth_city'             => $birthCity,
    'residence_country'      => $residenceCountry,
    'residence_city'         => $residenceCity,
    'address'                => $address,
    'occupation_id'          => $occupationId,
    'birth_province_id'      => $birthProvinceId,
    'birth_canton_id'        => $birthCantonId,
    'residence_province_id'  => $residenceProvinceId,
    'residence_canton_id'    => $residenceCantonId,
];

/*
|--------------------------------------------------------------------------
| Validaciones
|--------------------------------------------------------------------------
*/
if ($lastName === '' || $firstName === '' || $nationalId === '' || $phonePersonal === '') {
    $_SESSION['error'] = '⚠ Debe completar los campos obligatorios.';
    nova_redirect_back();
}

if ($birthCountry === 'ECUADOR' && !nova_valid_ecuador_id($nationalId)) {
    $_SESSION['error'] = '⚠ La cédula ingresada no es válida.';
    nova_redirect_back();
}

// Duplicado de cédula / identificación
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

/*
|--------------------------------------------------------------------------
| Resolver nombres de catálogos
|--------------------------------------------------------------------------
*/
$occupationName        = nova_upper(nova_get_name_by_id($conn, 'occupations', $occupationId));
$birthProvinceName     = nova_upper(nova_get_name_by_id($conn, 'provinces', $birthProvinceId));
$birthCantonName       = nova_upper(nova_get_name_by_id($conn, 'cantons', $birthCantonId));
$residenceProvinceName = nova_upper(nova_get_name_by_id($conn, 'provinces', $residenceProvinceId));
$residenceCantonName   = nova_upper(nova_get_name_by_id($conn, 'cantons', $residenceCantonId));

/*
|--------------------------------------------------------------------------
| Guardado
|--------------------------------------------------------------------------
*/
$conn->begin_transaction();

try {
    // Insert inicial con código temporal
    $tempCode = 'TEMP';

    $sqlStudent = "INSERT INTO students (
        institution_id,
        student_code,
        national_id,
        first_name,
        last_name,
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
        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'activo', NOW(), NOW()
    )";

    $stmtStudent = $conn->prepare($sqlStudent);
    if (!$stmtStudent) {
        throw new Exception('No se pudo preparar el registro del estudiante.');
    }

    $stmtStudent->bind_param(
        'issssssssssssssssss',
        $institutionId,
        $tempCode,
        $nationalId,
        $firstName,
        $lastName,
        $birthDate,
        $birthCountry,
        $birthProvinceName,
        $birthCantonName,
        $birthCity,
        $phonePersonal,
        $phoneFamily,
        $email,
        $occupationName,
        $residenceCountry,
        $residenceProvinceName,
        $residenceCantonName,
        $residenceCity,
        $address
    );

    $stmtStudent->execute();
    $studentId = (int) $stmtStudent->insert_id;
    $stmtStudent->close();

    if ($studentId <= 0) {
        throw new Exception('No se pudo crear el estudiante.');
    }

    // Generar código interno
    $studentCode = 'STU-' . str_pad((string) $studentId, 6, '0', STR_PAD_LEFT);

    $sqlUpdateCode = "UPDATE students SET student_code = ?, updated_at = NOW() WHERE id = ?";
    $stmtUpdateCode = $conn->prepare($sqlUpdateCode);
    $stmtUpdateCode->bind_param('si', $studentCode, $studentId);
    $stmtUpdateCode->execute();
    $stmtUpdateCode->close();

    // Insert matrícula
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
        ?, ?, ?, ?, CURDATE(), 'activo', NULL, NOW(), NOW()
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