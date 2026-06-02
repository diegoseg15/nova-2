<?php

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';

$db = new Database();
$conn = $db->connect();

$currentRoleId = (int)($_SESSION['role_id'] ?? 0);
$hasManageAccess = false;

if ($currentRoleId > 0) {
    $permissionSql = "SELECT 1
                      FROM role_permissions rp
                      INNER JOIN permissions p ON p.id = rp.permission_id
                      WHERE rp.role_id = ?
                        AND p.code = 'roles.manage'
                      LIMIT 1";

    if ($stmtPermission = $conn->prepare($permissionSql)) {
        $stmtPermission->bind_param('i', $currentRoleId);
        $stmtPermission->execute();
        $permissionResult = $stmtPermission->get_result();
        $hasManageAccess = ($permissionResult && $permissionResult->num_rows > 0);
        $stmtPermission->close();
    }
}

if (!$hasManageAccess) {
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
                No tienes permisos para gestionar roles en NOVA 1.0.
            </p>
        </div>
    </div>
    <?php
    require_once __DIR__ . '/../../app/views/partials/footer.php';
    exit;
}

$roleId = (int)($_GET['role_id'] ?? $_POST['role_id'] ?? 0);
if ($roleId <= 0) {
    header('Location: /nova1/modules/users/roles.php');
    exit;
}

$roleSql = "SELECT id, name, description, status
            FROM roles
            WHERE id = ?
            LIMIT 1";

$role = null;

if ($stmtRole = $conn->prepare($roleSql)) {
    $stmtRole->bind_param('i', $roleId);
    $stmtRole->execute();
    $roleResult = $stmtRole->get_result();
    $role = $roleResult ? $roleResult->fetch_assoc() : null;
    $stmtRole->close();
}

if (!$role) {
    header('Location: /nova1/modules/users/roles.php');
    exit;
}

$roleName = strtoupper(trim((string)$role['name']));
$isProtectedAdmin = ($roleName === 'ADMIN');

function getModuleLabel(string $module): string
{
    $labels = [
        'dashboard'     => 'Tablero',
        'students'      => 'Estudiantes',
        'academic'      => 'Académico',
        'payments'      => 'Pagos',
        'school_regime' => 'Régimen Escolar',
        'users'         => 'Usuarios',
    ];

    return $labels[$module] ?? $module;
}

function getModuleOrder(string $module): int
{
    $order = [
        'dashboard'     => 1,
        'students'      => 2,
        'academic'      => 3,
        'payments'      => 4,
        'school_regime' => 5,
        'users'         => 6,
    ];

    return $order[$module] ?? 99;
}

$errors = [];
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($isProtectedAdmin) {
        $errors[] = 'El rol ADMIN está protegido y no puede modificarse desde esta pantalla.';
    } else {
        $selectedPermissions = $_POST['permissions'] ?? [];

        if (!is_array($selectedPermissions)) {
            $selectedPermissions = [];
        }

        $permissionIds = [];
        foreach ($selectedPermissions as $permissionId) {
            $permissionIds[] = (int)$permissionId;
        }

        $permissionIds = array_values(array_unique(array_filter($permissionIds, function ($value) {
            return $value > 0;
        })));

        $conn->begin_transaction();

        try {
            $deleteSql = "DELETE FROM role_permissions WHERE role_id = ?";
            if (!$stmtDelete = $conn->prepare($deleteSql)) {
                throw new Exception('No fue posible preparar la limpieza de permisos.');
            }

            $stmtDelete->bind_param('i', $roleId);
            if (!$stmtDelete->execute()) {
                $stmtDelete->close();
                throw new Exception('No fue posible limpiar los permisos actuales del rol.');
            }
            $stmtDelete->close();

            if (!empty($permissionIds)) {
                $insertSql = "INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)";
                if (!$stmtInsert = $conn->prepare($insertSql)) {
                    throw new Exception('No fue posible preparar la asignación de permisos.');
                }

                foreach ($permissionIds as $permissionId) {
                    $stmtInsert->bind_param('ii', $roleId, $permissionId);
                    if (!$stmtInsert->execute()) {
                        $stmtInsert->close();
                        throw new Exception('No fue posible guardar uno de los permisos seleccionados.');
                    }
                }

                $stmtInsert->close();
            }

            $conn->commit();
            $successMessage = 'Los permisos del rol fueron actualizados correctamente.';
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = $e->getMessage();
        }
    }
}

$assignedPermissionIds = [];

$assignedSql = "SELECT permission_id
                FROM role_permissions
                WHERE role_id = ?";

if ($stmtAssigned = $conn->prepare($assignedSql)) {
    $stmtAssigned->bind_param('i', $roleId);
    $stmtAssigned->execute();
    $assignedResult = $stmtAssigned->get_result();

    if ($assignedResult) {
        while ($row = $assignedResult->fetch_assoc()) {
            $assignedPermissionIds[] = (int)$row['permission_id'];
        }
    }

    $stmtAssigned->close();
}

$permissionsSql = "SELECT id, module, code, name, description, status
                   FROM permissions
                   ORDER BY
                       FIELD(module,
                           'dashboard',
                           'students',
                           'academic',
                           'payments',
                           'school_regime',
                           'users'
                       ),
                       code ASC";

$permissionsResult = $conn->query($permissionsSql);

$permissionsByModule = [];

if ($permissionsResult && $permissionsResult->num_rows > 0) {
    while ($permission = $permissionsResult->fetch_assoc()) {
        $module = (string)$permission['module'];

        if (!isset($permissionsByModule[$module])) {
            $permissionsByModule[$module] = [];
        }

        $permissionsByModule[$module][] = $permission;
    }
}

uksort($permissionsByModule, function ($a, $b) {
    return getModuleOrder($a) <=> getModuleOrder($b);
});

require_once __DIR__ . '/../../app/views/partials/header.php';
?>

<div style="max-width:1200px;margin:0 auto;">

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
            <h2 style="margin:0;font-size:18px;">Gestión de Permisos del Rol</h2>
            <p style="margin:4px 0 0 0;color:#6b7280;font-size:13px;">
                Rol seleccionado: <strong><?php echo htmlspecialchars((string)$role['name']); ?></strong>
            </p>
            <?php if (!empty($role['description'])): ?>
                <p style="margin:6px 0 0 0;color:#6b7280;font-size:13px;">
                    <?php echo htmlspecialchars((string)$role['description']); ?>
                </p>
            <?php endif; ?>
        </div>

        <a href="/nova1/modules/users/roles.php"
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
            Volver a roles
        </a>
    </div>

    <?php if ($isProtectedAdmin): ?>
        <div style="
            background:#fff7ed;
            border:1px solid #fdba74;
            color:#9a3412;
            border-radius:12px;
            padding:14px 16px;
            margin-bottom:16px;
        ">
            El rol <strong>ADMIN</strong> está protegido y no puede modificarse desde esta pantalla.
        </div>
    <?php endif; ?>

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
        <input type="hidden" name="role_id" value="<?php echo $roleId; ?>">

        <?php if (!empty($permissionsByModule)): ?>
            <?php foreach ($permissionsByModule as $module => $modulePermissions): ?>
                <div style="
                    background:#ffffff;
                    border:1px solid #e5e7eb;
                    border-radius:12px;
                    margin-bottom:16px;
                    overflow:hidden;
                ">
                    <div style="
                        background:#f8fafc;
                        border-bottom:1px solid #e5e7eb;
                        padding:14px 16px;
                    ">
                        <h3 style="margin:0;font-size:15px;">
                            <?php echo htmlspecialchars(getModuleLabel((string)$module)); ?>
                        </h3>
                    </div>

                    <div style="padding:8px 16px 16px 16px;">
                        <?php foreach ($modulePermissions as $permission): ?>
                            <?php
                            $permissionId = (int)$permission['id'];
                            $isChecked = in_array($permissionId, $assignedPermissionIds, true);
                            $isPermissionActive = ((string)$permission['status'] === 'active');
                            ?>
                            <label style="
                                display:flex;
                                align-items:flex-start;
                                gap:12px;
                                padding:12px 0;
                                border-bottom:1px solid #f1f5f9;
                                cursor:<?php echo ($isProtectedAdmin || !$isPermissionActive) ? 'not-allowed' : 'pointer'; ?>;
                                opacity:<?php echo $isPermissionActive ? '1' : '0.65'; ?>;
                            ">
                                <input type="checkbox"
                                       name="permissions[]"
                                       value="<?php echo $permissionId; ?>"
                                       <?php echo $isChecked ? 'checked' : ''; ?>
                                       <?php echo ($isProtectedAdmin || !$isPermissionActive) ? 'disabled' : ''; ?>
                                       style="margin-top:2px;">

                                <span style="display:block;">
                                    <span style="
                                        display:block;
                                        font-size:14px;
                                        font-weight:600;
                                        color:#111827;
                                        margin-bottom:4px;
                                    ">
                                        <?php echo htmlspecialchars((string)$permission['name']); ?>
                                    </span>

                                    <span style="
                                        display:block;
                                        font-size:12px;
                                        color:#6b7280;
                                        margin-bottom:4px;
                                    ">
                                        <?php echo htmlspecialchars((string)($permission['description'] ?? '')); ?>
                                    </span>

                                    <span style="
                                        display:inline-block;
                                        font-size:11px;
                                        color:#2563eb;
                                        background:#eff6ff;
                                        border-radius:6px;
                                        padding:3px 7px;
                                    ">
                                        <?php echo htmlspecialchars((string)$permission['code']); ?>
                                    </span>

                                    <?php if (!$isPermissionActive): ?>
                                        <span style="
                                            display:inline-block;
                                            font-size:11px;
                                            color:#991b1b;
                                            background:#fee2e2;
                                            border-radius:6px;
                                            padding:3px 7px;
                                            margin-left:6px;
                                        ">
                                            INACTIVO
                                        </span>
                                    <?php endif; ?>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="
                background:#ffffff;
                border:1px solid #e5e7eb;
                border-radius:12px;
                padding:18px;
                color:#6b7280;
            ">
                No existen permisos registrados para mostrar.
            </div>
        <?php endif; ?>

        <div style="
            display:flex;
            justify-content:flex-end;
            gap:10px;
            margin-top:16px;
            margin-bottom:16px;
            flex-wrap:wrap;
        ">
            <a href="/nova1/modules/users/roles.php"
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

            <?php if (!$isProtectedAdmin): ?>
                <button type="submit"
                        style="
                            display:inline-flex;
                            align-items:center;
                            justify-content:center;
                            padding:10px 14px;
                            border:none;
                            border-radius:8px;
                            background:#2563eb;
                            color:#ffffff;
                            font-weight:600;
                            font-size:13px;
                            cursor:pointer;
                        ">
                    Guardar permisos
                </button>
            <?php endif; ?>
        </div>
    </form>

</div>

<?php require_once __DIR__ . '/../../app/views/partials/footer.php'; ?>