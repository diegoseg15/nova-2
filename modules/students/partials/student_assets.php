<?php
$oldBirthCantonId      = $oldBirthCantonId ?? '';
$oldResidenceCantonId  = $oldResidenceCantonId ?? '';
$oldIsEcuadorian       = $oldIsEcuadorian ?? '1';
?>

<style>
    .nova-mb {
        margin-bottom: 16px;
    }

    .nova-admission-page {
        display: flex;
        flex-direction: column;
        gap: 14px;
    }

    .nova-admission-topbar {
        background: #0f172a;
        color: #ffffff;
        border-radius: 8px;
        padding: 18px 20px;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 18px;
        box-shadow: 0 2px 8px rgba(15, 23, 42, .10);
    }

    .nova-admission-topbar h2 {
        margin: 0;
        font-size: 23px;
        font-weight: 800;
    }

    .nova-admission-topbar p {
        margin: 5px 0 0;
        color: #cbd5e1;
        font-size: 13px;
    }

    .nova-context-badge {
        background: rgba(255, 255, 255, .08);
        border: 1px solid rgba(255, 255, 255, .14);
        border-radius: 6px;
        padding: 10px 12px;
        min-width: 260px;
    }

    .nova-context-badge span {
        display: block;
        font-size: 10px;
        font-weight: 800;
        color: #93c5fd;
        text-transform: uppercase;
        margin-bottom: 4px;
    }

    .nova-context-badge strong {
        display: block;
        font-size: 13px;
    }

    .nova-required-note {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        padding: 10px 12px;
        color: #475569;
        font-size: 13px;
        font-weight: 700;
    }

    .nova-student-card {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        overflow: hidden;
    }

    .nova-section {
        background: #ffffff;
        border-bottom: 1px solid #e5e7eb;
        padding: 18px 20px;
    }

    .nova-section:last-child {
        border-bottom: 0;
    }

    .nova-section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        margin-bottom: 16px;
    }

    .nova-section-title {
        display: flex;
        align-items: center;
        gap: 9px;
        margin: 0;
        color: #0f172a;
        font-size: 15px;
        font-weight: 800;
    }

    .nova-step-badge {
        width: 24px;
        height: 24px;
        border-radius: 4px;
        background: #2563eb;
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 900;
    }

    .nova-section-caption {
        color: #64748b;
        font-size: 12px;
        font-weight: 600;
    }

    .nova-grid-2,
    .nova-grid-3,
    .nova-grid-4,
    .nova-location-grid {
        display: grid;
        gap: 16px 20px;
    }

    .nova-grid-2 {
        grid-template-columns: repeat(2, 1fr);
    }

    .nova-grid-3 {
        grid-template-columns: repeat(3, 1fr);
    }

    .nova-grid-4 {
        grid-template-columns: repeat(4, 1fr);
    }

    .nova-location-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .nova-two-column-sections {
        display: grid;
        grid-template-columns: 1fr 1fr;
    }

    .nova-two-column-sections .nova-section:first-child {
        border-right: 1px solid #e5e7eb;
    }

    .nova-two-column-sections .nova-section {
        border-bottom: 0;
    }

    .nova-field {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .nova-field-full {
        grid-column: 1 / -1;
    }

    .nova-label {
        font-size: 11px;
        font-weight: 800;
        color: #475569;
        text-transform: uppercase;
    }

    .nova-required {
        color: #dc2626;
    }

    .nova-input,
    .nova-select,
    .nova-textarea {
        width: 100%;
        box-sizing: border-box;
        border: 1px solid #cbd5e1;
        border-radius: 5px;
        background: #fff;
        color: #0f172a;
        font-size: 13px;
        padding: 10px 12px;
        transition: .16s;
    }

    .nova-input,
    .nova-select {
        height: 40px;
    }

    .nova-textarea {
        min-height: 74px;
        resize: vertical;
    }

    .nova-input:focus,
    .nova-select:focus,
    .nova-textarea:focus {
        outline: none;
        border-color: #2563eb;
        box-shadow: 0 0 0 2px rgba(37, 99, 235, .08);
    }

    .nova-readonly {
        background: #f8fafc;
        color: #64748b;
    }

    .nova-inline-control {
        display: flex;
        align-items: center;
        gap: 14px;
        height: 40px;
        padding: 0 12px;
        border: 1px solid #cbd5e1;
        border-radius: 5px;
    }

    .nova-check-option {
        display: flex;
        align-items: center;
        gap: 7px;
        font-size: 12px;
        font-weight: 800;
        cursor: pointer;
    }

    .nova-check-option input {
        width: 16px;
        height: 16px;
        accent-color: #2563eb;
    }

    .nova-hidden {
        display: none !important;
    }

    .nova-age-box {
        border: 1px solid #dbe5f0;
        border-radius: 5px;
        padding: 9px 12px;
        background: #f8fafc;
        min-width: 150px;
    }

    .nova-age-label {
        font-size: 11px;
        color: #64748b;
        font-weight: 800;
    }

    .nova-age-value {
        font-size: 18px;
        color: #0f172a;
        font-weight: 800;
    }

    .nova-actions {
        position: sticky;
        bottom: 0;
        z-index: 20;
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        background: #fff;
        border-top: 1px solid #e5e7eb;
        padding: 14px 20px;
    }

    .nova-btn {
        min-width: 140px;
        height: 40px;
        border-radius: 5px;
        font-size: 13px;
        font-weight: 800;
        cursor: pointer;
        border: none;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .nova-btn-secondary {
        background: #fff;
        color: #334155;
        border: 1px solid #cbd5e1;
    }

    .nova-btn-primary {
        background: #2563eb;
        color: #fff;
    }

    @media (max-width:1200px) {
        .nova-grid-4 {
            grid-template-columns: repeat(2, 1fr);
        }

        .nova-two-column-sections {
            grid-template-columns: 1fr;
        }

        .nova-two-column-sections .nova-section:first-child {
            border-right: 0;
            border-bottom: 1px solid #e5e7eb;
        }
    }

    @media (max-width:900px) {
        .nova-admission-topbar {
            flex-direction: column;
        }

        .nova-context-badge {
            width: 100%;
            min-width: unset;
        }

        .nova-grid-2,
        .nova-grid-3,
        .nova-grid-4,
        .nova-location-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width:640px) {
        .nova-actions {
            flex-direction: column;
        }

        .nova-btn {
            width: 100%;
        }
    }
</style>

<script>
    function normalizeCountry(countryValue) {
        return String(countryValue || '').trim().toUpperCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    }

    function onlyCleanId(value) {
        return String(value || '').trim().replace(/\s+/g, '').toLowerCase();
    }

    function generateMoodleUsername() {
        const isEcuadorian = document.getElementById('is_ecuadorian');
        const nationalId = document.getElementById('national_id');
        const moodleUsername = document.getElementById('moodle_username');

        if (!isEcuadorian || !nationalId || !moodleUsername) return;

        const cleanId = onlyCleanId(nationalId.value);

        if (cleanId === '') {
            moodleUsername.value = '';
            return;
        }

        moodleUsername.value = (isEcuadorian.value === '1' ? 'ec' : 'ex') + cleanId;
    }

    function setEcuadorian(value) {
        const hidden = document.getElementById('is_ecuadorian');
        const yes = document.getElementById('ecuadorian_yes');
        const no = document.getElementById('ecuadorian_no');
        const birthCountry = document.getElementById('birth_country');

        hidden.value = value === '1' ? '1' : '0';
        yes.checked = hidden.value === '1';
        no.checked = hidden.value === '0';

        if (hidden.value === '1') {
            birthCountry.value = 'ECUADOR';
        }

        toggleLocationFields('birth');
        generateMoodleUsername();
    }

    function calculateAge() {
        const birthDateInput = document.getElementById('birth_date');
        const ageOutput = document.getElementById('calculated_age');

        if (!birthDateInput || !ageOutput) return;

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

        if (!provinceSelect || !cantonSelect) return;

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
        const countryInput = document.getElementById(type + '_country');
        const provinceSelect = document.getElementById(type + '_province_id');
        const cantonSelect = document.getElementById(type + '_canton_id');
        const provinceText = document.getElementById(type + '_province_text');
        const cantonText = document.getElementById(type + '_canton_text');
        const provinceLabel = document.getElementById(type + '_province_label');
        const cantonLabel = document.getElementById(type + '_canton_label');
        const cityLabel = document.getElementById(type + '_city_label');

        if (!countryInput) return;

        const normalizedCountry = normalizeCountry(countryInput.value);
        const isEcuador = normalizedCountry === 'ECUADOR';

        if (isEcuador) {
            provinceLabel.innerHTML = 'Provincia <span class="nova-required">*</span>';
            cantonLabel.innerHTML = 'Cantón <span class="nova-required">*</span>';
            cityLabel.textContent = 'Ciudad';

            provinceSelect.classList.remove('nova-hidden');
            cantonSelect.classList.remove('nova-hidden');
            provinceText.classList.add('nova-hidden');
            cantonText.classList.add('nova-hidden');

            provinceSelect.disabled = false;
            cantonSelect.disabled = false;
            provinceText.disabled = true;
            cantonText.disabled = true;

            provinceText.value = '';
            cantonText.value = '';

            if (provinceSelect.value) {
                const selectedValue = cantonSelect.dataset.selectedValue || '';
                loadCantons(type + '_province_id', type + '_canton_id', selectedValue);
            } else {
                cantonSelect.innerHTML = '<option value="">Seleccione cantón</option>';
            }

        } else {
            provinceLabel.textContent = 'Estado / Provincia / Región';
            cantonLabel.textContent = 'Ciudad';
            cityLabel.textContent = 'Localidad / Referencia';

            provinceSelect.classList.add('nova-hidden');
            cantonSelect.classList.add('nova-hidden');
            provinceText.classList.remove('nova-hidden');
            cantonText.classList.remove('nova-hidden');

            provinceSelect.disabled = true;
            cantonSelect.disabled = true;
            provinceText.disabled = false;
            cantonText.disabled = false;

            provinceSelect.value = '';
            cantonSelect.innerHTML = '<option value="">Seleccione cantón</option>';
        }
    }

    document.addEventListener('DOMContentLoaded', function() {

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
            yes.addEventListener('change', function() {
                if (yes.checked) {
                    setEcuadorian('1');
                } else {
                    setEcuadorian('0');
                }
            });
        }

        if (no) {
            no.addEventListener('change', function() {
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
            birthCountryInput.addEventListener('input', function() {
                toggleLocationFields('birth');

                if (normalizeCountry(birthCountryInput.value) === 'ECUADOR') {
                    setEcuadorian('1');
                } else if (birthCountryInput.value.trim() !== '') {
                    setEcuadorian('0');
                }
            });

            birthCountryInput.addEventListener('change', function() {
                toggleLocationFields('birth');

                if (normalizeCountry(birthCountryInput.value) === 'ECUADOR') {
                    setEcuadorian('1');
                } else if (birthCountryInput.value.trim() !== '') {
                    setEcuadorian('0');
                }
            });
        }

        if (residenceCountryInput) {
            residenceCountryInput.addEventListener('input', function() {
                toggleLocationFields('residence');
            });

            residenceCountryInput.addEventListener('change', function() {
                toggleLocationFields('residence');
            });
        }

        if (birthProvince) {
            birthProvince.addEventListener('change', function() {
                if (normalizeCountry(document.getElementById('birth_country').value) === 'ECUADOR') {
                    loadCantons('birth_province_id', 'birth_canton_id');
                }
            });
        }

        if (residenceProvince) {
            residenceProvince.addEventListener('change', function() {
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