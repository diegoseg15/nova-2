<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';

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
    $_SESSION['error'] = '⚠ Debe activar un grupo y período antes de editar estudiantes.';
    header('Location: /nova1/dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /nova1/modules/students/index.php');
    exit;
}

$db = new Database();
$conn = $db->connect();
$conn->set_charset("utf8mb4");

$studentId    = (int) ($_GET['id'] ?? 0);
$studyGroupId = (int) ($_SESSION['study_group_id'] ?? 0);
$periodId     = (int) ($_SESSION['period_id'] ?? 0);

if ($studentId <= 0) {
    $_SESSION['error'] = '⚠ Estudiante no válido.';
    header('Location: /nova1/modules/students/index.php');
    exit;
}

function toUpperNullable($value)
{
    $value = trim((string) $value);
    return $value === '' ? null : mb_strtoupper($value, 'UTF-8');
}

function cleanEmailNullable($value)
{
    $value = trim((string) $value);
    return $value === '' ? null : strtolower($value);
}

function countryKey($value)
{
    $value = trim((string) $value);
    $value = mb_strtoupper($value, 'UTF-8');
    $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
    return strtoupper((string) $value);
}

function cleanIdentifier($value)
{
    $value = trim((string) $value);
    $value = preg_replace('/\s+/', '', $value);
    return strtolower((string) $value);
}

function validateEcuadorianId($cedula)
{
    $cedula = trim((string) $cedula);

    if (!preg_match('/^\d{10}$/', $cedula)) {
        return false;
    }

    $provinceCode = (int) substr($cedula, 0, 2);
    $thirdDigit   = (int) $cedula[2];

    if ($provinceCode < 1 || $provinceCode > 24) {
        return false;
    }

    if ($thirdDigit < 0 || $thirdDigit > 5) {
        return false;
    }

    $coefficients = [2, 1, 2, 1, 2, 1, 2, 1, 2];
    $sum = 0;

    for ($i = 0; $i < 9; $i++) {
        $digit = (int) $cedula[$i];
        $value = $digit * $coefficients[$i];

        if ($value >= 10) {
            $value -= 9;
        }

        $sum += $value;
    }

    $verifier = (10 - ($sum % 10)) % 10;

    return $verifier === (int) $cedula[9];
}

/*
|--------------------------------------------------------------------------
| Validar que el estudiante pertenezca al contexto activo
|--------------------------------------------------------------------------
*/
$sqlValidate = "
    SELECT s.id
    FROM students s
    INNER JOIN student_enrollments se ON se.student_id = s.id
    WHERE s.id = ?
      AND se.study_group_id = ?
      AND se.period_id = ?
    LIMIT 1
";
$stmtValidate = $conn->prepare($sqlValidate);
$stmtValidate->bind_param('iii', $studentId, $studyGroupId, $periodId);
$stmtValidate->execute();
$resValidate = $stmtValidate->get_result();

if ($resValidate->num_rows === 0) {
    $stmtValidate->close();
    $_SESSION['error'] = '⚠ El estudiante no pertenece al grupo y período activos.';
    header('Location: /nova1/modules/students/index.php');
    exit;
}
$stmtValidate->close();

/*
|--------------------------------------------------------------------------
| Captura POST
|--------------------------------------------------------------------------
*/
$lastName                 = trim($_POST['last_name'] ?? '');
$firstName                = trim($_POST['first_name'] ?? '');
$nationalId               = cleanIdentifier($_POST['national_id'] ?? '');
$isEcuadorian             = ($_POST['is_ecuadorian'] ?? '1') === '1' ? '1' : '0';
$moodleUsername           = ($isEcuadorian === '1' ? 'ec' : 'ex') . $nationalId;

$gender                   = trim($_POST['gender'] ?? '');
$birthDate                = trim($_POST['birth_date'] ?? '');
$birthCountry             = trim($_POST['birth_country'] ?? 'ECUADOR');
$birthProvinceId          = (int) ($_POST['birth_province_id'] ?? 0);
$birthCantonId            = (int) ($_POST['birth_canton_id'] ?? 0);
$birthProvinceText        = trim($_POST['birth_province_text'] ?? '');
$birthCantonText          = trim($_POST['birth_canton_text'] ?? '');
$birthCity                = trim($_POST['birth_city'] ?? '');

$phonePersonal            = trim($_POST['phone_personal'] ?? '');
$phoneFamily              = trim($_POST['phone_family'] ?? '');
$email                    = trim($_POST['email'] ?? '');
$occupationId             = (int) ($_POST['occupation_id'] ?? 0);

$residenceCountry         = trim($_POST['residence_country'] ?? 'ECUADOR');
$residenceProvinceId      = (int) ($_POST['residence_province_id'] ?? 0);
$residenceCantonId        = (int) ($_POST['residence_canton_id'] ?? 0);
$residenceProvinceText    = trim($_POST['residence_province_text'] ?? '');
$residenceCantonText      = trim($_POST['residence_canton_text'] ?? '');
$residenceCity            = trim($_POST['residence_city'] ?? '');
$address                  = trim($_POST['address'] ?? '');

if ($isEcuadorian === '1') {
    $birthCountry = 'ECUADOR';
}

/*
|--------------------------------------------------------------------------
| Guardar old form si hay error
|--------------------------------------------------------------------------
*/
$_SESSION['old_student_form'] = [
    'last_name'               => $lastName,
    'first_name'              => $firstName,
    'national_id'             => $nationalId,
    'is_ecuadorian'           => $isEcuadorian,
    'moodle_username'         => $moodleUsername,
    'gender'                  => $gender,
    'birth_date'              => $birthDate,
    'birth_country'           => $birthCountry,
    'birth_province_id'       => $birthProvinceId ?: '',
    'birth_canton_id'         => $birthCantonId ?: '',
    'birth_province_text'     => $birthProvinceText,
    'birth_canton_text'       => $birthCantonText,
    'birth_city'              => $birthCity,
    'phone_personal'          => $phonePersonal,
    'phone_family'            => $phoneFamily,
    'email'                   => $email,
    'occupation_id'           => $occupationId ?: '',
    'residence_country'       => $residenceCountry,
    'residence_province_id'   => $residenceProvinceId ?: '',
    'residence_canton_id'     => $residenceCantonId ?: '',
    'residence_province_text' => $residenceProvinceText,
    'residence_canton_text'   => $residenceCantonText,
    'residence_city'          => $residenceCity,
    'address'                 => $address,
];

/*
|--------------------------------------------------------------------------
| Validaciones
|--------------------------------------------------------------------------
*/
if ($lastName === '' || $firstName === '' || $nationalId === '' || $phonePersonal === '') {
    $_SESSION['error'] = '⚠ Debe completar apellidos, nombres, cédula / identificación y celular personal.';
    header('Location: /nova1/modules/students/edit.php?id=' . $studentId);
    exit;
}

if ($gender !== '' && !in_array($gender, ['M', 'F'], true)) {
    $gender = null;
}

if ($isEcuadorian === '1' && !validateEcuadorianId($nationalId)) {
    $_SESSION['error'] = '⚠ La cédula ecuatoriana ingresada no es válida.';
    header('Location: /nova1/modules/students/edit.php?id=' . $studentId);
    exit;
}

/*
|--------------------------------------------------------------------------
| Validar duplicados
|--------------------------------------------------------------------------
*/
$sqlDup = "SELECT id FROM students WHERE national_id = ? AND id <> ? LIMIT 1";
$stmtDup = $conn->prepare($sqlDup);
$stmtDup->bind_param('si', $nationalId, $studentId);
$stmtDup->execute();
$resDup = $stmtDup->get_result();

if ($resDup->num_rows > 0) {
    $stmtDup->close();
    $_SESSION['error'] = '⚠ Ya existe otro estudiante registrado con esa cédula / identificación.';
    header('Location: /nova1/modules/students/edit.php?id=' . $studentId);
    exit;
}
$stmtDup->close();

$sqlMoodleDup = "SELECT id FROM students WHERE moodle_username = ? AND id <> ? LIMIT 1";
$stmtMoodleDup = $conn->prepare($sqlMoodleDup);
$stmtMoodleDup->bind_param('si', $moodleUsername, $studentId);
$stmtMoodleDup->execute();
$resMoodleDup = $stmtMoodleDup->get_result();

if ($resMoodleDup->num_rows > 0) {
    $stmtMoodleDup->close();
    $_SESSION['error'] = '⚠ El usuario Moodle generado ya pertenece a otro estudiante.';
    header('Location: /nova1/modules/students/edit.php?id=' . $studentId);
    exit;
}
$stmtMoodleDup->close();

/*
|--------------------------------------------------------------------------
| Resolver ocupación
|--------------------------------------------------------------------------
*/
$occupationName = null;

if ($occupationId > 0) {
    $sqlOccupation = "SELECT name FROM occupations WHERE id = ? LIMIT 1";
    $stmtOccupation = $conn->prepare($sqlOccupation);
    $stmtOccupation->bind_param('i', $occupationId);
    $stmtOccupation->execute();
    $resOccupation = $stmtOccupation->get_result();

    if ($row = $resOccupation->fetch_assoc()) {
        $occupationName = $row['name'];
    }

    $stmtOccupation->close();
}

/*
|--------------------------------------------------------------------------
| Resolver nacimiento
|--------------------------------------------------------------------------
*/
$birthProvinceName = null;
$birthCantonName   = null;

if (countryKey($birthCountry) === 'ECUADOR') {
    if ($birthProvinceId > 0) {
        $sqlBirthProvince = "SELECT name FROM provinces WHERE id = ? LIMIT 1";
        $stmtBirthProvince = $conn->prepare($sqlBirthProvince);
        $stmtBirthProvince->bind_param('i', $birthProvinceId);
        $stmtBirthProvince->execute();
        $resBirthProvince = $stmtBirthProvince->get_result();

        if ($row = $resBirthProvince->fetch_assoc()) {
            $birthProvinceName = $row['name'];
        }

        $stmtBirthProvince->close();
    }

    if ($birthCantonId > 0) {
        $sqlBirthCanton = "SELECT name FROM cantons WHERE id = ? LIMIT 1";
        $stmtBirthCanton = $conn->prepare($sqlBirthCanton);
        $stmtBirthCanton->bind_param('i', $birthCantonId);
        $stmtBirthCanton->execute();
        $resBirthCanton = $stmtBirthCanton->get_result();

        if ($row = $resBirthCanton->fetch_assoc()) {
            $birthCantonName = $row['name'];
        }

        $stmtBirthCanton->close();
    }
} else {
    $birthProvinceName = $birthProvinceText !== '' ? $birthProvinceText : null;
    $birthCantonName   = $birthCantonText !== '' ? $birthCantonText : null;
}

/*
|--------------------------------------------------------------------------
| Resolver residencia
|--------------------------------------------------------------------------
*/
$residenceProvinceName = null;
$residenceCantonName   = null;

if (countryKey($residenceCountry) === 'ECUADOR') {
    if ($residenceProvinceId > 0) {
        $sqlResidenceProvince = "SELECT name FROM provinces WHERE id = ? LIMIT 1";
        $stmtResidenceProvince = $conn->prepare($sqlResidenceProvince);
        $stmtResidenceProvince->bind_param('i', $residenceProvinceId);
        $stmtResidenceProvince->execute();
        $resResidenceProvince = $stmtResidenceProvince->get_result();

        if ($row = $resResidenceProvince->fetch_assoc()) {
            $residenceProvinceName = $row['name'];
        }

        $stmtResidenceProvince->close();
    }

    if ($residenceCantonId > 0) {
        $sqlResidenceCanton = "SELECT name FROM cantons WHERE id = ? LIMIT 1";
        $stmtResidenceCanton = $conn->prepare($sqlResidenceCanton);
        $stmtResidenceCanton->bind_param('i', $residenceCantonId);
        $stmtResidenceCanton->execute();
        $resResidenceCanton = $stmtResidenceCanton->get_result();

        if ($row = $resResidenceCanton->fetch_assoc()) {
            $residenceCantonName = $row['name'];
        }

        $stmtResidenceCanton->close();
    }
} else {
    $residenceProvinceName = $residenceProvinceText !== '' ? $residenceProvinceText : null;
    $residenceCantonName   = $residenceCantonText !== '' ? $residenceCantonText : null;
}

/*
|--------------------------------------------------------------------------
| Normalizar datos
|--------------------------------------------------------------------------
*/
$lastNameDb            = toUpperNullable($lastName);
$firstNameDb           = toUpperNullable($firstName);
$nationalIdDb          = $nationalId;
$moodleUsernameDb      = $moodleUsername;
$genderDb              = ($gender === 'M' || $gender === 'F') ? $gender : null;
$birthDateDb           = $birthDate !== '' ? $birthDate : null;
$birthCountryDb        = toUpperNullable($birthCountry);
$birthProvinceDb       = $birthProvinceName !== null ? mb_strtoupper($birthProvinceName, 'UTF-8') : null;
$birthCantonDb         = $birthCantonName !== null ? mb_strtoupper($birthCantonName, 'UTF-8') : null;
$birthCityDb           = toUpperNullable($birthCity);
$phonePersonalDb       = trim($phonePersonal);
$phoneFamilyDb         = trim($phoneFamily) !== '' ? trim($phoneFamily) : null;
$emailDb               = cleanEmailNullable($email);
$occupationDb          = $occupationName !== null ? mb_strtoupper($occupationName, 'UTF-8') : null;
$residenceCountryDb    = toUpperNullable($residenceCountry);
$residenceProvinceDb   = $residenceProvinceName !== null ? mb_strtoupper($residenceProvinceName, 'UTF-8') : null;
$residenceCantonDb     = $residenceCantonName !== null ? mb_strtoupper($residenceCantonName, 'UTF-8') : null;
$residenceCityDb       = toUpperNullable($residenceCity);
$addressDb             = toUpperNullable($address);

/*
|--------------------------------------------------------------------------
| Actualizar estudiante
|--------------------------------------------------------------------------
*/
$sqlUpdate = "
    UPDATE students SET
        national_id = ?,
        moodle_username = ?,
        first_name = ?,
        last_name = ?,
        gender = ?,
        birth_date = ?,
        birth_country = ?,
        birth_province = ?,
        birth_canton = ?,
        birth_city = ?,
        phone_personal = ?,
        phone_family = ?,
        email = ?,
        occupation = ?,
        residence_country = ?,
        residence_province = ?,
        residence_canton = ?,
        residence_city = ?,
        address = ?,
        updated_at = NOW()
    WHERE id = ?
    LIMIT 1
";

$stmtUpdate = $conn->prepare($sqlUpdate);

if (!$stmtUpdate) {
    $_SESSION['error'] = '⚠ No se pudo preparar la actualización del estudiante.';
    header('Location: /nova1/modules/students/edit.php?id=' . $studentId);
    exit;
}

$stmtUpdate->bind_param(
    'sssssssssssssssssssi',
    $nationalIdDb,
    $moodleUsernameDb,
    $firstNameDb,
    $lastNameDb,
    $genderDb,
    $birthDateDb,
    $birthCountryDb,
    $birthProvinceDb,
    $birthCantonDb,
    $birthCityDb,
    $phonePersonalDb,
    $phoneFamilyDb,
    $emailDb,
    $occupationDb,
    $residenceCountryDb,
    $residenceProvinceDb,
    $residenceCantonDb,
    $residenceCityDb,
    $addressDb,
    $studentId
);

if (!$stmtUpdate->execute()) {
    $stmtUpdate->close();
    $_SESSION['error'] = '⚠ No se pudo actualizar el estudiante.';
    header('Location: /nova1/modules/students/edit.php?id=' . $studentId);
    exit;
}

$stmtUpdate->close();

unset($_SESSION['old_student_form']);

$_SESSION['success'] = '✅ Estudiante actualizado correctamente.';
header('Location: /nova1/modules/students/edit.php?id=' . $studentId);
exit;

