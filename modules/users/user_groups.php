<?php

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';

$db = new Database();
$conn = $db->connect();

$currentRoleId = (int)($_SESSION['role_id'] ?? 0);
$canManageUserGroups = false;

if ($currentRoleId > 0) {
    $permissionSql = "SELECT 1
                      FROM role_permissions rp
                      INNER JOIN permissions p ON p.id = rp.permission_id
                      WHERE rp.role_id = ?
                        AND p.code = 'users.groups.manage'
                      LIMIT 1";

    if ($stmtPermission = $conn->prepare($permissionSql)) {
        $stmtPermission->bind_param('i', $currentRoleId);
        $stmtPermission->execute();
        $permissionResult = $stmtPermission->get_result();
        $canManageUserGroups = ($permissionResult && $permissionResult->num_rows > 0);
        $stmtPermission->close();
    }
}

if (!$canManageUserGroups) {
    http_response_code(403);
    require_once __DIR__ . '/../../app/views/partials/header.php';
    ?>
    <div style="max-width:900px;margin:0 auto;">
        <div style="
            background:#ffffff;
            border:1px solid #fecaca;
            border-radius:12px;
            padding:18px;
        ">
            <h2 style="margin:0 0 8px 0;font-size:18px;color:#991b1b;">Acceso denegado</h2>
            <p style="margin:0;color:#6b7280;font-size:14px;">
                No tienes permisos para gestionar grupos de usuarios en NOVA 1.0.
            </p>
        </div>
    </div>
    <?php
    require_once __DIR__ . '/../../app/views/partials/footer.php';
    exit;
}

$userId = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

if ($userId <= 0) {
    header('Location: /nova1/modules/users/index.php');
    exit;
}

$userSql = "SELECT u.id, u.username, u.first_name, u.last_name, u.status, u.role_id,
                   r.name AS role_name
            FROM users u
            INNER JOIN roles r ON r.id = u.role_id
            WHERE u.id = ?
            LIMIT 1";

$user = null;

if ($stmtUser = $conn->prepare($userSql)) {
    $stmtUser->bind_param('i', $userId);
    $stmtUser->execute();
    $userResult = $stmtUser->get_result();
    $user = $userResult ? $userResult->fetch_assoc() : null;
    $stmtUser->close();
}

if (!$user) {
    header('Location: /nova1/modules/users/index.php');
    exit;
}

$roleName = strtoupper(trim((string)$user['role_name']));
$isTeacherRole = ($roleName === 'DOCENTE');
$isGroupLeaderRole = ($roleName === 'JEFE_GRUPO');
$isSupervisorRole = ($roleName === 'SUPERVISOR');

$errors = [];
$successMessage = '';

$groupsSql = "SELECT id, name, description, status
              FROM study_groups
              WHERE status = 'active'
              ORDER BY name ASC";

$groupsResult = $conn->query($groupsSql);
$groups = [];

if ($groupsResult && $groupsResult->num_rows > 0) {
    while ($groupRow = $groupsResult->fetch_assoc()) {
        $groups[] = $groupRow;
    }
}

$existingAssignments = [];
$existingPrimaryGroupId = null;

$assignmentSql = "SELECT uga.id, uga.study_group_id, uga.assignment_type, sg.name
                  FROM user_group_assignments uga
                  INNER JOIN study_groups sg ON sg.id = uga.study_group_id
                  WHERE uga.user_id = ?
                  ORDER BY sg.name ASC";

if ($stmtAssignments = $conn->prepare($assignmentSql)) {
    $stmtAssignments->bind_param('i', $userId);
    $stmtAssignments->execute();
    $assignmentResult = $stmtAssignments->get_result();

    if ($assignmentResult) {
        while ($assignmentRow = $assignmentResult->fetch_assoc()) {
            $existingAssignments[] = $assignmentRow;
            if ((string)$assignmentRow['assignment_type'] === 'primary') {
                $existingPrimaryGroupId = (int)$assignmentRow['study_group_id'];
            }
        }
    }

    $stmtAssignments->close();
}

$selectedGroupIds = [];
foreach ($existingAssignments as $assignment) {
    $selectedGroupIds[] = (int)$assignment['study_group_id'];
}

$selectedPrimaryGroupId = $existingPrimaryGroupId;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedGroupIds = $_POST['group_ids'] ?? [];
    $postedPrimaryGroupId = (int)($_POST['primary_group_id'] ?? 0);

    if (!is_array($postedGroupIds)) {
        $postedGroupIds = [];
    }

    $selectedGroupIds = [];
    foreach ($postedGroupIds as $groupId) {
        $groupId = (int)$groupId;
        if ($groupId > 0) {
            $selectedGroupIds[] = $groupId;
        }
    }

    $selectedGroupIds = array_values(array_unique($selectedGroupIds));
    $selectedPrimaryGroupId = $postedPrimaryGroupId > 0 ? $postedPrimaryGroupId : null;

    if ($isTeacherRole && count($selectedGroupIds) > 1) {
        $errors[] = 'El rol DOCENTE solo puede pertenecer a un grupo.';
    }

    if ($isGroupLeaderRole && count($selectedGroupIds) > 1) {
        $errors[] = 'El rol JEFE_GRUPO solo puede tener un grupo principal.';
    }

    if (($isTeacherRole || $isGroupLeaderRole) && count($selectedGroupIds) === 0) {
        $errors[] = 'Debe seleccionar un grupo para este usuario.';
    }

    if (!empty($selectedGroupIds) && empty($selectedPrimaryGroupId)) {
        $errors[] = 'Debe seleccionar un grupo principal.';
    }

    if (!empty($selectedPrimaryGroupId) && !in_array($selectedPrimaryGroupId, $selectedGroupIds, true)) {
        $errors[] = 'El grupo principal debe estar dentro de los grupos seleccionados.';
    }

    if (empty($errors)) {
        $conn->begin_transaction();

        try {
            $deleteSql = "DELETE FROM user_group_assignments WHERE user_id = ?";
            if (!$stmtDelete = $conn->prepare($deleteSql)) {
                throw new Exception('No fue posible preparar la limpieza de grupos anteriores.');
            }

            $stmtDelete->bind_param('i', $userId);
            if (!$stmtDelete->execute()) {
                $stmtDelete->close();
                throw new Exception('No fue posible limpiar los grupos anteriores del usuario.');
            }
            $stmtDelete->close();

            if (!empty($selectedGroupIds)) {
                $insertSql = "INSERT INTO user_group_assignments (user_id, study_group_id, assignment_type)
                              VALUES (?, ?, ?)";
                if (!$stmtInsert = $conn->prepare($insertSql)) {
                    throw new Exception('No fue posible preparar la asignación de grupos.');
                }

                foreach ($selectedGroupIds as $groupId) {
                    if ($groupId === $selectedPrimaryGroupId) {
                        $assignmentType = 'primary';
                    } elseif ($isSupervisorRole) {
                        $assignmentType = 'supervision';
                    } else {
                        $assignmentType = 'secondary';
                    }

                    $stmtInsert->bind_param('iis', $userId, $groupId, $assignmentType);

                    if (!$stmtInsert->execute()) {
                        $stmtInsert->close();
                        throw new Exception('No fue posible guardar uno de los grupos seleccionados.');
                    }
                }

                $stmtInsert->close();
            }

            $conn->commit();
            $successMessage = 'Los grupos del usuario fueron actualizados correctamente.';
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = $e->getMessage();
        }
    }
}

$selectedGroupsForPrimary = [];
foreach ($groups as $group) {
    if (in_array((int)$group['id'], $selectedGroupIds, true)) {
        $selectedGroupsForPrimary[] = $group;
    }
}

require_once __DIR__ . '/../../app/views/partials/header.php';
?>

<div style="max-width:1100px;margin:0 auto;">

    <div style="
        background:#ffffff;
        border:1px solid #e5e7eb;
        border-radius:12px;
        padding:18px;
        margin-bottom:16px;
        display:flex;
        justify-content:space-between;
        align-items:flex-start;
        gap:12px;
        flex-wrap:wrap;
    ">
        <div>
            <h2 style="margin:0;font-size:18px;">Asignación de Grupos</h2>
            <p style="margin:4px 0 0 0;color:#6b7280;font-size:13px;">
                Usuario: <strong><?php echo htmlspecialchars((string)$user['last_name'] . ' ' . (string)$user['first_name']); ?></strong>
            </p>
            <p style="margin:4px 0 0 0;color:#6b7280;font-size:13px;">
                Usuario de acceso: <strong><?php echo htmlspecialchars((string)$user['username']); ?></strong> |
                Rol: <strong><?php echo htmlspecialchars((string)$user['role_name']); ?></strong>
            </p>
        </div>

        <a href="/nova1/modules/users/index.php"
           style="
                display:inline-flex;
                align-items:center;
                justify-content:center;
                gap:8px;
                background:#e5e7eb;
                color:#374151;
                text-decoration:none;
                padding:10px 14px;
                border-radius:8px;
                font-weight:600;
                font-size:13px;
                white-space:nowrap;
           ">
            <i class="fa-solid fa-arrow-left"></i>
            Volver a usuarios
        </a>
    </div>

    <div style="
        background:#eff6ff;
        border:1px solid #bfdbfe;
        border-radius:12px;
        padding:14px 16px;
        margin-bottom:16px;
        color:#1e3a8a;
        font-size:13px;
    ">
        <?php if ($isTeacherRole): ?>
            Regla del rol <strong>DOCENTE</strong>: solo puede pertenecer a un grupo. Si cambia, reemplaza el grupo anterior.
        <?php elseif ($isGroupLeaderRole): ?>
            Regla del rol <strong>JEFE_GRUPO</strong>: debe tener un único grupo principal.
        <?php elseif ($isSupervisorRole): ?>
            Regla del rol <strong>SUPERVISOR</strong>: puede tener varios grupos y uno de ellos debe quedar marcado como principal.
        <?php else: ?>
            Este usuario puede operar sin necesidad obligatoria de grupo, pero puedes asignarle grupos si tu operación lo requiere.
        <?php endif; ?>
    </div>

    <?php if (!empty($errors)): ?>
        <div style="
            background:#fef2f2;
            border:1px solid #fecaca;
            color:#991b1b;
            border-radius:12px;
            padding:14px 16px;
            margin-bottom:16px;
        ">
            <div style="font-weight:700;margin-bottom:6px;">Revisa lo siguiente:</div>
            <ul style="margin:0;padding-left:18px;">
                <?php foreach ($errors as $error): ?>
                    <li style="margin-bottom:4px;"><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($successMessage !== ''): ?>
        <div style="
            background:#ecfdf5;
            border:1px solid #86efac;
            color:#166534;
            border-radius:12px;
            padding:14px 16px;
            margin-bottom:16px;
        ">
            <?php echo htmlspecialchars($successMessage); ?>
        </div>
    <?php endif; ?>

    <form method="post" action="">
        <input type="hidden" name="id" value="<?php echo $userId; ?>">

        <div style="
            background:#ffffff;
            border:1px solid #e5e7eb;
            border-radius:12px;
            overflow:hidden;
            margin-bottom:16px;
        ">
            <div style="
                background:#f8fafc;
                border-bottom:1px solid #e5e7eb;
                padding:14px 16px;
            ">
                <h3 style="margin:0;font-size:15px;">Grupos disponibles</h3>
            </div>

            <div style="padding:8px 16px 16px 16px;">
                <?php if (!empty($groups)): ?>
                    <?php foreach ($groups as $group): ?>
                        <?php
                        $groupId = (int)$group['id'];
                        $isChecked = in_array($groupId, $selectedGroupIds, true);
                        ?>
                        <label style="
                            display:flex;
                            align-items:flex-start;
                            gap:12px;
                            padding:12px 0;
                            border-bottom:1px solid #f1f5f9;
                            cursor:pointer;
                        ">
                            <input type="checkbox"
                                   class="group-checkbox"
                                   name="group_ids[]"
                                   value="<?php echo $groupId; ?>"
                                   data-name="<?php echo htmlspecialchars((string)$group['name'], ENT_QUOTES, 'UTF-8'); ?>"
                                   <?php echo $isChecked ? 'checked' : ''; ?>
                                   style="margin-top:2px;">

                            <span style="display:block;width:100%;">
                                <span style="
                                    display:block;
                                    font-size:14px;
                                    font-weight:600;
                                    color:#111827;
                                    margin-bottom:4px;
                                ">
                                    <?php echo htmlspecialchars((string)$group['name']); ?>
                                </span>

                                <?php if (!empty($group['description'])): ?>
                                    <span style="
                                        display:block;
                                        font-size:12px;
                                        color:#6b7280;
                                    ">
                                        <?php echo htmlspecialchars((string)$group['description']); ?>
                                    </span>
                                <?php endif; ?>
                            </span>
                        </label>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="padding:12px 0;color:#6b7280;font-size:13px;">
                        No existen grupos activos registrados.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div style="
            background:#ffffff;
            border:1px solid #e5e7eb;
            border-radius:12px;
            padding:20px;
            margin-bottom:16px;
        ">
            <label style="display:block;font-weight:600;margin-bottom:6px;font-size:13px;color:#111827;">
                Grupo principal
            </label>

            <select id="primary_group_id"
                    name="primary_group_id"
                    data-selected="<?php echo (int)$selectedPrimaryGroupId; ?>"
                    style="
                        width:100%;
                        height:42px;
                        border:1px solid #d1d5db;
                        border-radius:8px;
                        padding:0 12px;
                        font-size:14px;
                    ">
                <option value="">Seleccione...</option>
                <?php foreach ($selectedGroupsForPrimary as $group): ?>
                    <option value="<?php echo (int)$group['id']; ?>" <?php echo ((int)$selectedPrimaryGroupId === (int)$group['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars((string)$group['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <p style="margin:8px 0 0 0;color:#6b7280;font-size:12px;">
                El grupo principal se alimenta únicamente de los grupos que estén marcados.
            </p>
        </div>

        <?php if (!empty($existingAssignments)): ?>
            <div style="
                background:#ffffff;
                border:1px solid #e5e7eb;
                border-radius:12px;
                overflow:hidden;
                margin-bottom:16px;
            ">
                <div style="
                    background:#f8fafc;
                    border-bottom:1px solid #e5e7eb;
                    padding:14px 16px;
                ">
                    <h3 style="margin:0;font-size:15px;">Asignaciones actuales</h3>
                </div>

                <table style="width:100%;border-collapse:collapse;font-size:13px;">
                    <thead style="background:#f8fafc;">
                        <tr>
                            <th style="padding:10px;text-align:left;">Grupo</th>
                            <th style="padding:10px;text-align:center;">Tipo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($existingAssignments as $assignment): ?>
                            <tr style="border-top:1px solid #e5e7eb;">
                                <td style="padding:10px;">
                                    <?php echo htmlspecialchars((string)$assignment['name']); ?>
                                </td>
                                <td style="padding:10px;text-align:center;">
                                    <?php echo htmlspecialchars(strtoupper((string)$assignment['assignment_type'])); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <div style="
            display:flex;
            justify-content:flex-end;
            gap:10px;
            margin-bottom:16px;
            flex-wrap:wrap;
        ">
            <a href="/nova1/modules/users/index.php"
               style="
                    display:inline-flex;
                    align-items:center;
                    justify-content:center;
                    padding:10px 14px;
                    border-radius:8px;
                    text-decoration:none;
                    background:#e5e7eb;
                    color:#374151;
                    font-weight:600;
                    font-size:13px;
               ">
                Cancelar
            </a>

            <button type="submit"
                    style="
                        display:inline-flex;
                        align-items:center;
                        justify-content:center;
                        padding:10px 14px;
                        border:none;
                        border-radius:8px;
                        background:#0f766e;
                        color:#ffffff;
                        font-weight:600;
                        font-size:13px;
                        cursor:pointer;
                    ">
                Guardar grupos
            </button>
        </div>
    </form>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const checkboxes = document.querySelectorAll('.group-checkbox');
    const primarySelect = document.getElementById('primary_group_id');

    function updatePrimaryGroupOptions() {
        const selectedValue = primarySelect.value || primarySelect.getAttribute('data-selected') || '';
        const selectedGroups = [];

        checkboxes.forEach(function (checkbox) {
            if (checkbox.checked) {
                selectedGroups.push({
                    id: checkbox.value,
                    name: checkbox.getAttribute('data-name') || ''
                });
            }
        });

        primarySelect.innerHTML = '';

        const defaultOption = document.createElement('option');
        defaultOption.value = '';
        defaultOption.textContent = 'Seleccione...';
        primarySelect.appendChild(defaultOption);

        selectedGroups.forEach(function (group) {
            const option = document.createElement('option');
            option.value = group.id;
            option.textContent = group.name;

            if (String(group.id) === String(selectedValue)) {
                option.selected = true;
            }

            primarySelect.appendChild(option);
        });

        const stillExists = selectedGroups.some(function (group) {
            return String(group.id) === String(primarySelect.value);
        });

        if (!stillExists) {
            if (selectedGroups.length === 1) {
                primarySelect.value = selectedGroups[0].id;
            } else {
                primarySelect.value = '';
            }
        }

        primarySelect.setAttribute('data-selected', primarySelect.value);
    }

    checkboxes.forEach(function (checkbox) {
        checkbox.addEventListener('change', updatePrimaryGroupOptions);
    });

    primarySelect.addEventListener('change', function () {
        primarySelect.setAttribute('data-selected', primarySelect.value);
    });

    updatePrimaryGroupOptions();
});
</script>

<?php require_once __DIR__ . '/../../app/views/partials/footer.php'; ?>