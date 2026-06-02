<?php
$activeGroupName      = $activeGroupName ?? 'Grupo activo';
$activePeriodName     = $activePeriodName ?? 'Período activo';

$old                  = $old ?? [];
$oldGender            = $oldGender ?? '';
$oldIsEcuadorian      = $oldIsEcuadorian ?? '1';
$oldMoodleUsername    = $oldMoodleUsername ?? '';

$oldBirthCountry      = $oldBirthCountry ?? 'ECUADOR';
$oldResidenceCountry  = $oldResidenceCountry ?? 'ECUADOR';

$oldBirthProvinceId       = $oldBirthProvinceId ?? '';
$oldBirthCantonId         = $oldBirthCantonId ?? '';
$oldResidenceProvinceId   = $oldResidenceProvinceId ?? '';
$oldResidenceCantonId     = $oldResidenceCantonId ?? '';

$oldBirthProvinceText     = $oldBirthProvinceText ?? '';
$oldBirthCantonText       = $oldBirthCantonText ?? '';
$oldResidenceProvinceText = $oldResidenceProvinceText ?? '';
$oldResidenceCantonText   = $oldResidenceCantonText ?? '';

$occupations = $occupations ?? [];
$provinces   = $provinces ?? [];
?>

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

        <!-- ===================== IDENTIFICACIÓN ===================== -->
        <div class="nova-section">
            <div class="nova-section-header">
                <h3 class="nova-section-title">
                    <span class="nova-step-badge">1</span>
                    Identificación del estudiante
                </h3>
                <span class="nova-section-caption">Datos principales de identidad</span>
            </div>

            <div class="nova-grid-2 nova-mb-16">
                <div class="nova-field">
                    <label class="nova-label">Apellidos <span class="nova-required">*</span></label>
                    <input type="text" name="last_name" class="nova-input" placeholder="Ingrese apellidos"
                        value="<?php echo htmlspecialchars($old['last_name'] ?? ''); ?>" required>
                </div>

                <div class="nova-field">
                    <label class="nova-label">Nombres <span class="nova-required">*</span></label>
                    <input type="text" name="first_name" class="nova-input" placeholder="Ingrese nombres"
                        value="<?php echo htmlspecialchars($old['first_name'] ?? ''); ?>" required>
                </div>
            </div>

            <div class="nova-grid-3 nova-mb-16">
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
                        required>
                </div>

                <div class="nova-field">
                    <label class="nova-label">Usuario Moodle</label>
                    <input type="text" name="moodle_username" id="moodle_username"
                        class="nova-input nova-readonly"
                        placeholder="Se generará automáticamente"
                        value="<?php echo htmlspecialchars($oldMoodleUsername); ?>"
                        readonly>
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

        <!-- ===================== CONTACTO ===================== -->
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
                    <input type="text" name="phone_personal" class="nova-input" placeholder="09XXXXXXXX"
                        value="<?php echo htmlspecialchars($old['phone_personal'] ?? ''); ?>" required>
                </div>

                <div class="nova-field">
                    <label class="nova-label">Celular familiar</label>
                    <input type="text" name="phone_family" class="nova-input" placeholder="09XXXXXXXX"
                        value="<?php echo htmlspecialchars($old['phone_family'] ?? ''); ?>">
                </div>

                <div class="nova-field">
                    <label class="nova-label">Correo</label>
                    <input type="email" name="email" class="nova-input" placeholder="correo@ejemplo.com"
                        value="<?php echo htmlspecialchars($old['email'] ?? ''); ?>">
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