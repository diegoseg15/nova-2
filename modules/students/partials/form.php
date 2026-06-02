<?php
require_once __DIR__ . '/../../../config/database.php';

$db = new Database();
$conn = $db->connect();

$occupations = [];
$provinces   = [];

$sqlOccupations = "SELECT id, name
                   FROM occupations
                   WHERE status = 'activo'
                   ORDER BY (name = 'OTRO') ASC, name ASC";
$resultOccupations = $conn->query($sqlOccupations);

while ($row = $resultOccupations->fetch_assoc()) {
    $occupations[] = $row;
}

$sqlProvinces = "SELECT id, name
                 FROM provinces
                 WHERE status = 'activo'
                 ORDER BY name ASC";
$resultProvinces = $conn->query($sqlProvinces);

while ($row = $resultProvinces->fetch_assoc()) {
    $provinces[] = $row;
}

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

$activeGroupName = $_SESSION['study_group_name'] ?? 'Grupo activo';
$activePeriodName = $_SESSION['period_name'] ?? 'Período activo';
?>

<style>
.nova-admission-page{
    display:flex;
    flex-direction:column;
    gap:14px;
}

.nova-admission-topbar{
    background:#0f172a;
    color:#ffffff;
    border-radius:8px;
    padding:18px 20px;
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    gap:18px;
    box-shadow:0 2px 8px rgba(15,23,42,.10);
}

.nova-admission-topbar h2{
    margin:0;
    font-size:23px;
    font-weight:800;
    letter-spacing:-.01em;
}

.nova-admission-topbar p{
    margin:5px 0 0;
    color:#cbd5e1;
    font-size:13px;
}

.nova-context-badge{
    background:rgba(255,255,255,.08);
    border:1px solid rgba(255,255,255,.14);
    border-radius:6px;
    padding:10px 12px;
    min-width:260px;
    text-align:left;
}

.nova-context-badge span{
    display:block;
    font-size:10px;
    font-weight:800;
    color:#93c5fd;
    text-transform:uppercase;
    letter-spacing:.05em;
    margin-bottom:4px;
}

.nova-context-badge strong{
    display:block;
    font-size:13px;
    color:#ffffff;
    line-height:1.35;
}

.nova-required-note{
    background:#f8fafc;
    border:1px solid #e2e8f0;
    border-radius:6px;
    padding:10px 12px;
    color:#475569;
    font-size:13px;
    font-weight:700;
    display:flex;
    align-items:center;
    gap:8px;
}

.nova-required-note i{
    color:#2563eb;
}

.nova-student-card{
    background:#ffffff;
    border:1px solid #e5e7eb;
    border-radius:8px;
    overflow:hidden;
}

.nova-section{
    background:#ffffff;
    border-bottom:1px solid #e5e7eb;
    padding:18px 20px;
}

.nova-section:last-child{
    border-bottom:0;
}

.nova-section-header{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    margin-bottom:16px;
}

.nova-section-title{
    display:flex;
    align-items:center;
    gap:9px;
    margin:0;
    color:#0f172a;
    font-size:15px;
    font-weight:800;
}

.nova-step-badge{
    width:24px;
    height:24px;
    border-radius:4px;
    background:#2563eb;
    color:#ffffff;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    font-size:12px;
    font-weight:900;
    flex-shrink:0;
}

.nova-section-caption{
    color:#64748b;
    font-size:12px;
    font-weight:600;
}

.nova-grid-2{
    display:grid;
    grid-template-columns:repeat(2,minmax(0,1fr));
    gap:16px 20px;
}

.nova-grid-3{
    display:grid;
    grid-template-columns:repeat(3,minmax(0,1fr));
    gap:16px 20px;
}

.nova-grid-4{
    display:grid;
    grid-template-columns:repeat(4,minmax(0,1fr));
    gap:16px 20px;
}

.nova-location-grid{
    display:grid;
    grid-template-columns:repeat(2,minmax(0,1fr));
    gap:16px 20px;
}

.nova-two-column-sections{
    display:grid;
    grid-template-columns:1fr 1fr;
}

.nova-two-column-sections .nova-section{
    border-bottom:0;
}

.nova-two-column-sections .nova-section:first-child{
    border-right:1px solid #e5e7eb;
}

.nova-field{
    display:flex;
    flex-direction:column;
    gap:6px;
}

.nova-field-full{
    grid-column:1 / -1;
}

.nova-label{
    font-size:11px;
    font-weight:800;
    color:#475569;
    text-transform:uppercase;
    letter-spacing:.035em;
    line-height:1.25;
}

.nova-required{
    color:#dc2626;
}

.nova-input,
.nova-select,
.nova-textarea{
    width:100%;
    box-sizing:border-box;
    border:1px solid #cbd5e1;
    border-radius:5px;
    background:#ffffff;
    color:#0f172a;
    font-size:13px;
    padding:10px 12px;
    transition:border-color .16s ease, box-shadow .16s ease, background .16s ease;
}

.nova-input,
.nova-select{
    height:40px;
}

.nova-textarea{
    min-height:74px;
    resize:vertical;
}

.nova-input::placeholder,
.nova-textarea::placeholder{
    color:#94a3b8;
}

.nova-input:focus,
.nova-select:focus,
.nova-textarea:focus{
    outline:none;
    border-color:#2563eb;
    box-shadow:0 0 0 2px rgba(37,99,235,.08);
}

.nova-readonly{
    background:#f8fafc;
    color:#64748b;
}

.nova-hidden{
    display:none !important;
}

.nova-inline-control{
    display:flex;
    align-items:center;
    gap:12px;
    height:40px;
    padding:0 12px;
    border:1px solid #cbd5e1;
    border-radius:5px;
    background:#ffffff;
}

.nova-check-option{
    display:inline-flex;
    align-items:center;
    gap:7px;
    font-size:12px;
    font-weight:800;
    color:#334155;
    cursor:pointer;
}

.nova-check-option input{
    width:16px;
    height:16px;
    accent-color:#2563eb;
    cursor:pointer;
}

.nova-age-box{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    border:1px solid #dbe5f0;
    border-radius:5px;
    background:#f8fafc;
    padding:10px 12px;
    min-width:160px;
}

.nova-age-label{
    display:block;
    color:#64748b;
    font-size:11px;
    font-weight:800;
    text-transform:uppercase;
    letter-spacing:.035em;
}

.nova-age-value{
    color:#0f172a;
    font-size:18px;
    font-weight:800;
}

.nova-actions{
    position:sticky;
    bottom:0;
    z-index:20;
    display:flex;
    justify-content:flex-end;
    gap:10px;
    background:#ffffff;
    border-top:1px solid #e5e7eb;
    padding:14px 20px;
    box-shadow:0 -2px 8px rgba(15,23,42,.05);
}

.nova-btn{
    min-width:140px;
    height:40px;
    border-radius:5px;
    font-size:13px;
    font-weight:800;
    border:none;
    cursor:pointer;
    text-decoration:none;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:8px;
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

.nova-btn-primary:hover{
    background:#1d4ed8;
}

.nova-btn-secondary:hover{
    background:#f8fafc;
}

@media (max-width: 1200px){
    .nova-grid-4{
        grid-template-columns:repeat(2,minmax(0,1fr));
    }

    .nova-two-column-sections{
        grid-template-columns:1fr;
    }

    .nova-two-column-sections .nova-section:first-child{
        border-right:0;
        border-bottom:1px solid #e5e7eb;
    }
}

@media (max-width: 900px){
    .nova-admission-topbar{
        flex-direction:column;
    }

    .nova-context-badge{
        width:100%;
        min-width:unset;
    }

    .nova-grid-2,
    .nova-grid-3,
    .nova-grid-4,
    .nova-location-grid{
        grid-template-columns:1fr;
    }
}

@media (max-width: 640px){
    .nova-actions{
        flex-direction:column;
    }

    .nova-btn{
        width:100%;
    }
}
</style>

<div class="nova-admission-page">

    <div class="nova-admission-topbar">
        <div>
            <h2>Registro de estudiante</h2>
            <p>Creación de ficha académica, datos personales y matrícula inicial.</p>
        </div>

        <div class="nova-context-badge">
            <span>Contexto activo</span>
            <strong><?php echo htmlspecialchars($activeGroupName); ?></strong>
            <strong><?php echo htmlspecialchars($activePeriodName); ?></strong>
        </div>
    </div>

    <div class="nova-required-note">
        <i class="fa-solid fa-circle-info"></i>
        <span>Los campos marcados con <span class="nova-required">*</span> son obligatorios.</span>
    </div>

    <div class="nova-student-card">

        <div class="nova-section">
            <div class="nova-section-header">
                <h3 class="nova-section-title">
                    <span class="nova-step-badge">1</span>
                    Identificación del estudiante
                </h3>
                <span class="nova-section-caption">Datos principales de identidad</span>
            </div>

            <div class="nova-grid-2" style="margin-bottom:16px;">
                <div class="nova-field">
                    <label class="nova-label">Apellidos <span class="nova-required">*</span></label>
                    <input type="text" name="last_name" class="nova-input" placeholder="Ingrese apellidos" value="<?php echo htmlspecialchars($old['last_name'] ?? ''); ?>" required>
                </div>

                <div class="nova-field">
                    <label class="nova-label">Nombres <span class="nova-required">*</span></label>
                    <input type="text" name="first_name" class="nova-input" placeholder="Ingrese nombres" value="<?php echo htmlspecialchars($old['first_name'] ?? ''); ?>" required>
                </div>
            </div>

            <div class="nova-grid-3" style="margin-bottom:16px;">
                <div class="nova-field">
                    <label class="nova-label">Cédula ecuatoriana</label>
                    <input type="hidden" name="is_ecuadorian" id="is_ecuadorian" value="<?php echo htmlspecialchars($oldIsEcuadorian); ?>">

                    <div class="nova-inline-control">
                        <label class="nova-check-option">
                            <input type="checkbox" id="ecuadorian_yes" <?php echo $oldIsEcuadorian === '1' ? 'checked' : ''; ?>>
                            <span>Sí</span>
                        </label>

                        <label class="nova-check-option">
                            <input type="checkbox" id="ecuadorian_no" <?php echo $oldIsEcuadorian === '0' ? 'checked' : ''; ?>>
                            <span>No</span>
                        </label>
                    </div>
                </div>

                <div class="nova-field">
                    <label class="nova-label">Cédula / Identificación <span class="nova-required">*</span></label>
                    <input
                        type="text"
                        name="national_id"
                        id="national_id"
                        class="nova-input"
                        placeholder="Ingrese número de identificación"
                        value="<?php echo htmlspecialchars($old['national_id'] ?? ''); ?>"
                        <?php echo !empty($old['lock_national_id']) ? 'readonly style="background:#f8fafc; cursor:not-allowed;"' : ''; ?>
                        required
                    >
                </div>

                <div class="nova-field">
                    <label class="nova-label">Moodle usuario</label>
                    <input type="text" name="moodle_username" id="moodle_username" class="nova-input nova-readonly" placeholder="Se generará automáticamente" value="<?php echo htmlspecialchars($oldMoodleUsername); ?>" readonly>
                </div>
            </div>

            <div class="nova-grid-3">
                <div class="nova-field">
                    <label class="nova-label">Género <span class="nova-required">*</span></label>
                    <select name="gender" class="nova-select" required>
                        <option value="">Seleccione género</option>
                        <option value="M" <?php echo $oldGender === 'M' ? 'selected' : ''; ?>>Masculino</option>
                        <option value="F" <?php echo $oldGender === 'F' ? 'selected' : ''; ?>>Femenino</option>
                    </select>
                </div>

                <div class="nova-field">
                    <label class="nova-label">Estado <span class="nova-required">*</span></label>
                    <select name="status" class="nova-select" required>
                        <option value="">Seleccione estado</option>
                        <option value="activo" <?php echo ($old['status'] ?? '') === 'activo' ? 'selected' : ''; ?>>Activo</option>
                        <option value="retirado" <?php echo ($old['status'] ?? '') === 'retirado' ? 'selected' : ''; ?>>Retirado</option>
                        <option value="graduado" <?php echo ($old['status'] ?? '') === 'graduado' ? 'selected' : ''; ?>>Graduado</option>
                        <option value="suspendido" <?php echo ($old['status'] ?? '') === 'suspendido' ? 'selected' : ''; ?>>Suspendido</option>
                        <option value="migrado" <?php echo ($old['status'] ?? '') === 'migrado' ? 'selected' : ''; ?>>Migrado</option>
                    </select>
                </div>

                <div class="nova-field">
                    <label class="nova-label">Código interno</label>
                    <input type="text" class="nova-input nova-readonly" value="Se generará automáticamente" readonly>
                </div>
            </div>
        </div>

        <div class="nova-section">
            <div class="nova-section-header">
                <h3 class="nova-section-title">
                    <span class="nova-step-badge">2</span>
                    Contacto y ocupación
                </h3>
                <span class="nova-section-caption">Canales de comunicación</span>
            </div>

            <div class="nova-grid-4">
                <div class="nova-field">
                    <label class="nova-label">Celular personal <span class="nova-required">*</span></label>
                    <input type="text" name="phone_personal" class="nova-input" placeholder="09XXXXXXXX" value="<?php echo htmlspecialchars($old['phone_personal'] ?? ''); ?>" required>
                </div>

                <div class="nova-field">
                    <label class="nova-label">Celular familiar</label>
                    <input type="text" name="phone_family" class="nova-input" placeholder="09XXXXXXXX" value="<?php echo htmlspecialchars($old['phone_family'] ?? ''); ?>">
                </div>

                <div class="nova-field">
                    <label class="nova-label">Correo</label>
                    <input type="email" name="email" class="nova-input" placeholder="correo@ejemplo.com" value="<?php echo htmlspecialchars($old['email'] ?? ''); ?>">
                </div>

                <div class="nova-field">
                    <label class="nova-label">Ocupación</label>
                    <select name="occupation_id" class="nova-select">
                        <option value="">Seleccione ocupación</option>
                        <?php foreach ($occupations as $occ): ?>
                            <option value="<?php echo $occ['id']; ?>" <?php echo (($old['occupation_id'] ?? '') == $occ['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($occ['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="nova-two-column-sections">
            <div class="nova-section">
                <div class="nova-section-header">
                    <h3 class="nova-section-title">
                        <span class="nova-step-badge">3</span>
                        Residencia
                    </h3>
                    <span class="nova-section-caption">Ubicación actual</span>
                </div>

                <div class="nova-location-grid">
                    <div class="nova-field">
                        <label class="nova-label">País <span class="nova-required">*</span></label>
                        <input type="text" id="residence_country" name="residence_country" class="nova-input" value="<?php echo htmlspecialchars($oldResidenceCountry); ?>">
                    </div>

                    <div class="nova-field">
                        <label class="nova-label" id="residence_province_label">Provincia <span class="nova-required">*</span></label>

                        <select id="residence_province_id" name="residence_province_id" class="nova-select">
                            <option value="">Seleccione provincia</option>
                            <?php foreach ($provinces as $province): ?>
                                <option value="<?php echo $province['id']; ?>" <?php echo ($oldResidenceProvinceId == $province['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($province['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <input type="text" id="residence_province_text" name="residence_province_text" class="nova-input nova-hidden" placeholder="Ingrese estado / provincia / región" value="<?php echo htmlspecialchars($oldResidenceProvinceText); ?>">
                    </div>

                    <div class="nova-field">
                        <label class="nova-label" id="residence_canton_label">Cantón <span class="nova-required">*</span></label>

                        <select id="residence_canton_id" name="residence_canton_id" class="nova-select">
                            <option value="">Seleccione cantón</option>
                        </select>

                        <input type="text" id="residence_canton_text" name="residence_canton_text" class="nova-input nova-hidden" placeholder="Ingrese ciudad" value="<?php echo htmlspecialchars($oldResidenceCantonText); ?>">
                    </div>

                    <div class="nova-field">
                        <label class="nova-label" id="residence_city_label">Ciudad</label>
                        <input type="text" name="residence_city" id="residence_city" class="nova-input" placeholder="Ingrese ciudad" value="<?php echo htmlspecialchars($old['residence_city'] ?? ''); ?>">
                    </div>

                    <div class="nova-field nova-field-full">
                        <label class="nova-label">Dirección <span class="nova-required">*</span></label>
                        <textarea name="address" class="nova-textarea" placeholder="Ingrese dirección completa de residencia"><?php echo htmlspecialchars($old['address'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>

            <div class="nova-section">
                <div class="nova-section-header">
                    <h3 class="nova-section-title">
                        <span class="nova-step-badge">4</span>
                        Nacimiento
                    </h3>

                    <div class="nova-age-box">
                        <span class="nova-age-label">Edad</span>
                        <strong class="nova-age-value" id="calculated_age">-- años</strong>
                    </div>
                </div>

                <div class="nova-location-grid">
                    <div class="nova-field">
                        <label class="nova-label">Fecha de nacimiento <span class="nova-required">*</span></label>
                        <input type="date" name="birth_date" id="birth_date" class="nova-input" value="<?php echo htmlspecialchars($old['birth_date'] ?? ''); ?>">
                    </div>

                    <div class="nova-field">
                        <label class="nova-label">País <span class="nova-required">*</span></label>
                        <input type="text" id="birth_country" name="birth_country" class="nova-input" value="<?php echo htmlspecialchars($oldBirthCountry); ?>">
                    </div>

                    <div class="nova-field">
                        <label class="nova-label" id="birth_province_label">Provincia <span class="nova-required">*</span></label>

                        <select id="birth_province_id" name="birth_province_id" class="nova-select">
                            <option value="">Seleccione provincia</option>
                            <?php foreach ($provinces as $province): ?>
                                <option value="<?php echo $province['id']; ?>" <?php echo ($oldBirthProvinceId == $province['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($province['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <input type="text" id="birth_province_text" name="birth_province_text" class="nova-input nova-hidden" placeholder="Ingrese estado / provincia / región" value="<?php echo htmlspecialchars($oldBirthProvinceText); ?>">
                    </div>

                    <div class="nova-field">
                        <label class="nova-label" id="birth_canton_label">Cantón <span class="nova-required">*</span></label>

                        <select id="birth_canton_id" name="birth_canton_id" class="nova-select">
                            <option value="">Seleccione cantón</option>
                        </select>

                        <input type="text" id="birth_canton_text" name="birth_canton_text" class="nova-input nova-hidden" placeholder="Ingrese ciudad" value="<?php echo htmlspecialchars($oldBirthCantonText); ?>">
                    </div>

                    <div class="nova-field nova-field-full">
                        <label class="nova-label" id="birth_city_label">Ciudad</label>
                        <input type="text" name="birth_city" id="birth_city" class="nova-input" placeholder="Ingrese ciudad" value="<?php echo htmlspecialchars($old['birth_city'] ?? ''); ?>">
                    </div>
                </div>
            </div>
        </div>

        <div class="nova-actions">
            <a href="/nova1/dashboard.php" class="nova-btn nova-btn-secondary">
                <i class="fa-solid fa-xmark"></i>
                Cancelar
            </a>

            <button type="submit" class="nova-btn nova-btn-primary">
                <i class="fa-solid fa-floppy-disk"></i>
                Guardar estudiante
            </button>
        </div>

    </div>
</div>

<script>
function normalizeCountry(countryValue) {
    return String(countryValue || '')
        .trim()
        .toUpperCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '');
}

function onlyCleanId(value) {
    return String(value || '')
        .trim()
        .replace(/\s+/g, '')
        .toLowerCase();
}

function generateMoodleUsername() {
    const isEcuadorian = document.getElementById('is_ecuadorian');
    const nationalId = document.getElementById('national_id');
    const moodleUsername = document.getElementById('moodle_username');

    if (!isEcuadorian || !nationalId || !moodleUsername) {
        return;
    }

    const cleanId = onlyCleanId(nationalId.value);

    if (cleanId === '') {
        moodleUsername.value = '';
        moodleUsername.placeholder = 'Se generará automáticamente';
        return;
    }

    const prefix = isEcuadorian.value === '1' ? 'ec' : 'ex';
    moodleUsername.value = prefix + cleanId;
}

function setEcuadorian(value) {
    const hidden = document.getElementById('is_ecuadorian');
    const yes = document.getElementById('ecuadorian_yes');
    const no = document.getElementById('ecuadorian_no');
    const birthCountry = document.getElementById('birth_country');

    if (!hidden || !yes || !no) {
        return;
    }

    hidden.value = value === '1' ? '1' : '0';
    yes.checked = hidden.value === '1';
    no.checked = hidden.value === '0';

    if (birthCountry) {
        birthCountry.value = hidden.value === '1' ? 'ECUADOR' : birthCountry.value;
        toggleLocationFields('birth');
    }

    generateMoodleUsername();
}

function calculateAge() {
    const birthDateInput = document.getElementById('birth_date');
    const ageOutput = document.getElementById('calculated_age');

    if (!birthDateInput || !ageOutput) {
        return;
    }

    const value = birthDateInput.value;

    if (!value) {
        ageOutput.textContent = '-- años';
        return;
    }

    const birthDate = new Date(value + 'T00:00:00');
    const today = new Date();

    if (isNaN(birthDate.getTime()) || birthDate > today) {
        ageOutput.textContent = '-- años';
        return;
    }

    let age = today.getFullYear() - birthDate.getFullYear();
    const monthDiff = today.getMonth() - birthDate.getMonth();

    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
        age--;
    }

    ageOutput.textContent = age + ' años';
}

function loadCantons(provinceSelectId, cantonSelectId, selectedCantonId = '') {
    const provinceSelect = document.getElementById(provinceSelectId);
    const cantonSelect = document.getElementById(cantonSelectId);

    if (!provinceSelect || !cantonSelect) {
        return;
    }

    const provinceId = provinceSelect.value;

    if (!provinceId) {
        cantonSelect.innerHTML = '<option value="">Seleccione cantón</option>';
        return;
    }

    cantonSelect.innerHTML = '<option value="">Cargando...</option>';

    fetch('/nova1/modules/students/get_cantons.php?province_id=' + provinceId)
        .then(response => response.json())
        .then(data => {
            cantonSelect.innerHTML = '<option value="">Seleccione cantón</option>';

            data.forEach(canton => {
                const option = document.createElement('option');
                option.value = canton.id;
                option.textContent = canton.name;

                if (selectedCantonId && String(selectedCantonId) === String(canton.id)) {
                    option.selected = true;
                }

                cantonSelect.appendChild(option);
            });
        })
        .catch(() => {
            cantonSelect.innerHTML = '<option value="">Error al cargar</option>';
        });
}

function toggleLocationFields(type) {
    const countryInput      = document.getElementById(type + '_country');
    const provinceSelect    = document.getElementById(type + '_province_id');
    const cantonSelect      = document.getElementById(type + '_canton_id');
    const provinceText      = document.getElementById(type + '_province_text');
    const cantonText        = document.getElementById(type + '_canton_text');
    const provinceLabel     = document.getElementById(type + '_province_label');
    const cantonLabel       = document.getElementById(type + '_canton_label');
    const cityLabel         = document.getElementById(type + '_city_label');
    const cityInput         = document.getElementById(type + '_city');

    if (!countryInput || !provinceSelect || !cantonSelect || !provinceText || !cantonText || !provinceLabel || !cantonLabel || !cityLabel || !cityInput) {
        return;
    }

    const normalizedCountry = normalizeCountry(countryInput.value);
    const isEcuador = normalizedCountry === 'ECUADOR';

    if (isEcuador) {
        provinceLabel.innerHTML = 'Provincia <span class="nova-required">*</span>';
        cantonLabel.innerHTML   = 'Cantón <span class="nova-required">*</span>';
        cityLabel.textContent   = 'Ciudad';

        provinceSelect.classList.remove('nova-hidden');
        cantonSelect.classList.remove('nova-hidden');

        provinceText.classList.add('nova-hidden');
        cantonText.classList.add('nova-hidden');

        provinceSelect.disabled = false;
        cantonSelect.disabled   = false;
        provinceText.disabled   = true;
        cantonText.disabled     = true;

        provinceText.value = '';
        cantonText.value = '';

        if (provinceSelect.value) {
            const currentSelected = cantonSelect.dataset.selectedValue || '';
            loadCantons(type + '_province_id', type + '_canton_id', currentSelected);
        } else {
            cantonSelect.innerHTML = '<option value="">Seleccione cantón</option>';
        }
    } else {
        provinceLabel.textContent = 'Estado / Provincia / Región';
        cantonLabel.textContent   = 'Ciudad';
        cityLabel.textContent     = 'Localidad / Referencia';

        provinceSelect.classList.add('nova-hidden');
        cantonSelect.classList.add('nova-hidden');

        provinceText.classList.remove('nova-hidden');
        cantonText.classList.remove('nova-hidden');

        provinceSelect.disabled = true;
        cantonSelect.disabled   = true;
        provinceText.disabled   = false;
        cantonText.disabled     = false;

        provinceSelect.value = '';
        cantonSelect.innerHTML = '<option value="">Seleccione cantón</option>';
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const oldBirthCantonId = '<?php echo htmlspecialchars($oldBirthCantonId); ?>';
    const oldResidenceCantonId = '<?php echo htmlspecialchars($oldResidenceCantonId); ?>';

    const birthCantonSelect = document.getElementById('birth_canton_id');
    const residenceCantonSelect = document.getElementById('residence_canton_id');

    if (birthCantonSelect) {
        birthCantonSelect.dataset.selectedValue = oldBirthCantonId;
    }

    if (residenceCantonSelect) {
        residenceCantonSelect.dataset.selectedValue = oldResidenceCantonId;
    }

    toggleLocationFields('birth');
    toggleLocationFields('residence');

    const birthCountryInput = document.getElementById('birth_country');
    const residenceCountryInput = document.getElementById('residence_country');
    const birthProvince = document.getElementById('birth_province_id');
    const residenceProvince = document.getElementById('residence_province_id');
    const nationalIdInput = document.getElementById('national_id');
    const birthDateInput = document.getElementById('birth_date');
    const yes = document.getElementById('ecuadorian_yes');
    const no = document.getElementById('ecuadorian_no');

    if (yes) {
        yes.addEventListener('change', function () {
            if (yes.checked) {
                setEcuadorian('1');
            } else {
                setEcuadorian('0');
            }
        });
    }

    if (no) {
        no.addEventListener('change', function () {
            if (no.checked) {
                setEcuadorian('0');
            } else {
                setEcuadorian('1');
            }
        });
    }

    if (nationalIdInput) {
        nationalIdInput.addEventListener('input', generateMoodleUsername);
        nationalIdInput.addEventListener('change', generateMoodleUsername);
    }

    if (birthDateInput) {
        birthDateInput.addEventListener('input', calculateAge);
        birthDateInput.addEventListener('change', calculateAge);
    }

    if (birthCountryInput) {
        birthCountryInput.addEventListener('input', function () {
            toggleLocationFields('birth');

            if (normalizeCountry(birthCountryInput.value) === 'ECUADOR') {
                setEcuadorian('1');
            } else if (birthCountryInput.value.trim() !== '') {
                setEcuadorian('0');
            }
        });

        birthCountryInput.addEventListener('change', function () {
            toggleLocationFields('birth');

            if (normalizeCountry(birthCountryInput.value) === 'ECUADOR') {
                setEcuadorian('1');
            } else if (birthCountryInput.value.trim() !== '') {
                setEcuadorian('0');
            }
        });
    }

    if (residenceCountryInput) {
        residenceCountryInput.addEventListener('input', function () {
            toggleLocationFields('residence');
        });

        residenceCountryInput.addEventListener('change', function () {
            toggleLocationFields('residence');
        });
    }

    if (birthProvince) {
        birthProvince.addEventListener('change', function () {
            if (normalizeCountry(document.getElementById('birth_country').value) === 'ECUADOR') {
                loadCantons('birth_province_id', 'birth_canton_id');
            }
        });
    }

    if (residenceProvince) {
        residenceProvince.addEventListener('change', function () {
            if (normalizeCountry(document.getElementById('residence_country').value) === 'ECUADOR') {
                loadCantons('residence_province_id', 'residence_canton_id');
            }
        });
    }

    setEcuadorian('<?php echo htmlspecialchars($oldIsEcuadorian); ?>');
    generateMoodleUsername();
    calculateAge();
});
</script>
