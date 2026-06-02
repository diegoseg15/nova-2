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

$groupName  = $_SESSION['study_group_name'] ?? 'Grupo activo';
$periodName = $_SESSION['period_name'] ?? 'Período activo';

$old = $_SESSION['old_student_form'] ?? [];
unset($_SESSION['old_student_form']);

$oldBirthProvinceId      = $old['birth_province_id'] ?? '';
$oldBirthCantonId        = $old['birth_canton_id'] ?? '';
$oldResidenceProvinceId  = $old['residence_province_id'] ?? '';
$oldResidenceCantonId    = $old['residence_canton_id'] ?? '';
?>

<style>
.student-form-card{
    background:#fff;
    border:1px solid #e5e7eb;
    border-radius:14px;
    padding:18px;
    box-shadow:0 4px 12px rgba(0,0,0,0.05);
}

.student-form-header{
    display:flex;
    justify-content:space-between;
    flex-wrap:wrap;
    gap:12px;
    margin-bottom:12px;
}

.student-form-title h2{
    margin:0;
    font-size:24px;
    font-weight:800;
    color:#0f172a;
}

.student-form-title p{
    margin:0;
    font-size:14px;
    color:#64748b;
}

.student-context-wrap{
    display:flex;
    gap:8px;
    flex-wrap:wrap;
}

.student-context-box{
    background:#f8fafc;
    border:1px solid #dbe3ef;
    border-radius:10px;
    padding:8px 10px;
    min-width:170px;
}

.student-section{
    margin-top:14px;
    padding-top:14px;
    border-top:1px solid #e5e7eb;
}

.student-section:first-of-type{
    margin-top:0;
    padding-top:0;
    border-top:none;
}

.student-section-title{
    font-size:18px;
    font-weight:800;
    color:#1d4ed8;
    margin-bottom:8px;
}

.student-grid{
    display:grid;
    grid-template-columns:repeat(4, 1fr);
    gap:10px;
}

.student-grid-5{
    display:grid;
    grid-template-columns:1fr 1fr 1fr 1fr 1fr;
    gap:10px;
}

.student-field{
    display:flex;
    flex-direction:column;
}

.student-field-full{
    grid-column:1 / -1;
}

.student-label{
    font-size:12px;
    font-weight:700;
    margin-bottom:4px;
    color:#1e293b;
}

.student-required{
    color:#dc2626;
}

.student-input,
.student-select,
.student-textarea{
    border:1px solid #cfd8e3;
    border-radius:6px;
    padding:8px;
    font-size:13px;
    width:100%;
    box-sizing:border-box;
    background:#fff;
}

.student-input:focus,
.student-select:focus,
.student-textarea:focus{
    outline:none;
    border-color:#2563eb;
    box-shadow:0 0 0 3px rgba(37, 99, 235, 0.12);
}

.student-input-readonly{
    background:#f1f5f9;
    color:#94a3b8;
}

.student-textarea{
    min-height:50px;
    resize:vertical;
}

.student-actions{
    margin-top:14px;
    display:flex;
    justify-content:flex-end;
    gap:8px;
}

.student-btn{
    padding:8px 14px;
    border-radius:6px;
    font-size:13px;
    font-weight:700;
    border:none;
    cursor:pointer;
    text-decoration:none;
    display:inline-flex;
    align-items:center;
    justify-content:center;
}

.student-btn-primary{
    background:#2563eb;
    color:#fff;
}

.student-btn-secondary{
    background:#fff;
    border:1px solid #cbd5e1;
    color:#334155;
}

@media (max-width: 768px){
    .student-grid,
    .student-grid-5{
        grid-template-columns:1fr;
    }

    .student-actions{
        flex-direction:column;
    }

    .student-btn{
        width:100%;
    }
}
</style>

<div class="student-form-card">

    <div class="student-form-header">
        <div class="student-form-title">
            <h2>Ficha estudiantil</h2>
            <p>Registro y matrícula</p>
        </div>

        <div class="student-context-wrap">
            <div class="student-context-box">
                <small>Grupo</small><br>
                <strong><?php echo htmlspecialchars($groupName); ?></strong>
            </div>
            <div class="student-context-box">
                <small>Período</small><br>
                <strong><?php echo htmlspecialchars($periodName); ?></strong>
            </div>
        </div>
    </div>

    <div class="student-section">
        <div class="student-section-title">A. Identificación</div>

        <div class="student-grid">
            <div class="student-field">
                <label class="student-label">Apellidos <span class="student-required">*</span></label>
                <input type="text" name="last_name" class="student-input" value="<?php echo htmlspecialchars($old['last_name'] ?? ''); ?>" required>
            </div>

            <div class="student-field">
                <label class="student-label">Nombres <span class="student-required">*</span></label>
                <input type="text" name="first_name" class="student-input" value="<?php echo htmlspecialchars($old['first_name'] ?? ''); ?>" required>
            </div>

         <div class="student-field">
            <label class="student-label">Cédula / Identificación <span class="student-required">*</span></label>
            <input type="text" name="national_id" class="student-input" style="max-width:160px;" required>
        </div>
        
        <div class="student-field">
            <label class="student-label">Género <span class="student-required">*</span></label>
            <select name="gender" class="student-select" required>
                <option value="">Seleccione</option>
                <option value="M">Masculino</option>
                <option value="F">Femenino</option>
                <option value="O">Otro</option>
            </select>
        </div>
        
        <div class="student-field" style="max-width:180px;">
            <label class="student-label">Código interno</label>
            <input type="text" class="student-input student-input-readonly" value="Auto" readonly>
         </div>
    </div>

    <div class="student-section">
        <div class="student-section-title">B. Contacto</div>

        <div class="student-grid-5">
            <div class="student-field">
                <label class="student-label">Celular personal <span class="student-required">*</span></label>
                <input type="text" name="phone_personal" class="student-input" value="<?php echo htmlspecialchars($old['phone_personal'] ?? ''); ?>" required>
            </div>

            <div class="student-field">
                <label class="student-label">Celular familiar</label>
                <input type="text" name="phone_family" class="student-input" value="<?php echo htmlspecialchars($old['phone_family'] ?? ''); ?>">
            </div>

            <div class="student-field">
                <label class="student-label">Correo</label>
                <input type="email" name="email" class="student-input" value="<?php echo htmlspecialchars($old['email'] ?? ''); ?>">
            </div>

            <div class="student-field">
                <label class="student-label">Ocupación</label>
                <select name="occupation_id" class="student-select">
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

    <div class="student-section">
        <div class="student-section-title">C. Lugar de nacimiento</div>

        <div class="student-grid-5">
            <div class="student-field">
                <label class="student-label">Fecha de nacimiento</label>
                <input type="date" name="birth_date" class="student-input" value="<?php echo htmlspecialchars($old['birth_date'] ?? ''); ?>">
            </div>

            <div class="student-field">
                <label class="student-label">País</label>
                <input type="text" name="birth_country" class="student-input" value="<?php echo htmlspecialchars($old['birth_country'] ?? 'ECUADOR'); ?>">
            </div>

            <div class="student-field">
                <label class="student-label">Provincia</label>
                <select id="birth_province_id" name="birth_province_id" class="student-select">
                    <option value="">Seleccione provincia</option>
                    <?php foreach ($provinces as $province): ?>
                        <option value="<?php echo $province['id']; ?>" <?php echo ($oldBirthProvinceId == $province['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($province['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="student-field">
                <label class="student-label">Cantón</label>
                <select id="birth_canton_id" name="birth_canton_id" class="student-select">
                    <option value="">Seleccione cantón</option>
                </select>
            </div>

            <div class="student-field">
                <label class="student-label">Ciudad</label>
                <input type="text" name="birth_city" class="student-input" value="<?php echo htmlspecialchars($old['birth_city'] ?? ''); ?>">
            </div>
        </div>
    </div>

    <div class="student-section">
        <div class="student-section-title">D. Residencia</div>

        <div class="student-grid">
            <div class="student-field">
                <label class="student-label">País</label>
                <input type="text" name="residence_country" class="student-input" value="<?php echo htmlspecialchars($old['residence_country'] ?? 'ECUADOR'); ?>">
            </div>

            <div class="student-field">
                <label class="student-label">Provincia</label>
                <select id="residence_province_id" name="residence_province_id" class="student-select">
                    <option value="">Seleccione provincia</option>
                    <?php foreach ($provinces as $province): ?>
                        <option value="<?php echo $province['id']; ?>" <?php echo ($oldResidenceProvinceId == $province['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($province['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="student-field">
                <label class="student-label">Cantón</label>
                <select id="residence_canton_id" name="residence_canton_id" class="student-select">
                    <option value="">Seleccione cantón</option>
                </select>
            </div>

            <div class="student-field">
                <label class="student-label">Ciudad</label>
                <input type="text" name="residence_city" class="student-input" value="<?php echo htmlspecialchars($old['residence_city'] ?? ''); ?>">
            </div>

            <div class="student-field student-field-full">
                <label class="student-label">Dirección</label>
                <textarea name="address" class="student-textarea"><?php echo htmlspecialchars($old['address'] ?? ''); ?></textarea>
            </div>
        </div>
    </div>

    <div class="student-actions">
        <a href="/nova1/dashboard.php" class="student-btn student-btn-secondary">Cancelar</a>
        <button type="submit" class="student-btn student-btn-primary">Guardar estudiante</button>
    </div>
</div>

<script>
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

document.addEventListener('DOMContentLoaded', function () {
    const oldBirthCantonId = '<?php echo htmlspecialchars($oldBirthCantonId); ?>';
    const oldResidenceCantonId = '<?php echo htmlspecialchars($oldResidenceCantonId); ?>';

    loadCantons('birth_province_id', 'birth_canton_id', oldBirthCantonId);
    loadCantons('residence_province_id', 'residence_canton_id', oldResidenceCantonId);

    document.getElementById('birth_province_id').addEventListener('change', function () {
        loadCantons('birth_province_id', 'birth_canton_id');
    });

    document.getElementById('residence_province_id').addEventListener('change', function () {
        loadCantons('residence_province_id', 'residence_canton_id');
    });
});
</script>
