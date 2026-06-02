<?php

require_once __DIR__ . '/../../../config/database.php';

$db = new Database();
$conn = $db->connect();

$occupations = [];
$provinces   = [];

/*
|--------------------------------------------------------------------------
| CATÁLOGO DE OCUPACIONES
|--------------------------------------------------------------------------
*/
$sqlOccupations = "SELECT id, name
                   FROM occupations
                   WHERE status = 'activo'
                   ORDER BY (name = 'OTRO') ASC, name ASC";

$resultOccupations = $conn->query($sqlOccupations);

while ($row = $resultOccupations->fetch_assoc()) {
    $occupations[] = $row;
}

/*
|--------------------------------------------------------------------------
| CATÁLOGO DE PROVINCIAS
|--------------------------------------------------------------------------
*/
$sqlProvinces = "SELECT id, name
                 FROM provinces
                 WHERE status = 'activo'
                 ORDER BY name ASC";

$resultProvinces = $conn->query($sqlProvinces);

while ($row = $resultProvinces->fetch_assoc()) {
    $provinces[] = $row;
}

/*
|--------------------------------------------------------------------------
| RECUPERAR FORMULARIO ANTERIOR SI HUBO ERROR
|--------------------------------------------------------------------------
*/
$old = $_SESSION['old_student_form'] ?? [];
unset($_SESSION['old_student_form']);

$oldBirthProvinceId       = $old['birth_province_id'] ?? '';
$oldBirthCantonId         = $old['birth_canton_id'] ?? '';
$oldResidenceProvinceId   = $old['residence_province_id'] ?? '';
$oldResidenceCantonId     = $old['residence_canton_id'] ?? '';
$oldGender                = $old['gender'] ?? '';

$oldBirthCountry          = $old['birth_country'] ?? 'ECUADOR';
$oldResidenceCountry      = $old['residence_country'] ?? 'ECUADOR';

$oldBirthProvinceText     = $old['birth_province_text'] ?? '';
$oldBirthCantonText       = $old['birth_canton_text'] ?? '';
$oldResidenceProvinceText = $old['residence_province_text'] ?? '';
$oldResidenceCantonText   = $old['residence_canton_text'] ?? '';

$oldMoodleUsername        = $old['moodle_username'] ?? '';

$oldIsEcuadorian = $old['is_ecuadorian'] ?? '';

if ($oldIsEcuadorian === '') {
    $countryKey = mb_strtoupper(trim((string)$oldBirthCountry), 'UTF-8');
    $oldIsEcuadorian = ($countryKey === 'ECUADOR') ? '1' : '0';
}

/*
|--------------------------------------------------------------------------
| CONTEXTO ACTIVO
|--------------------------------------------------------------------------
*/
$activeGroupName  = $_SESSION['study_group_name'] ?? 'Grupo activo';
$activePeriodName = $_SESSION['period_name'] ?? 'Período activo';
