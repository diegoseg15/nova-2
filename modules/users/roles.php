<?php

// PANTALLA ROLES

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';

$db = new Database();
$conn = $db->connect();

$currentRoleId = (int)($_SESSION['role_id'] ?? 0);
$canViewRoles = false;
$canCreateRole = false;

if ($currentRoleId > 0) {
    $viewPermissionSql = "SELECT 1
                          FROM role_permissions rp
                          INNER JOIN permissions p ON p.id = rp.permission_id
                          WHERE rp.role_id = ?
                            AND p.code = 'roles.view'
                          LIMIT 1";

    if ($stmtViewPermission = $conn->prepare($viewPermissionSql)) {
        $stmtViewPermission->bind_param('i', $currentRoleId);
        $stmtViewPermission->execute();
        $viewPermissionResult = $stmtViewPermission->get_result();
        $canViewRoles = ($viewPermissionResult && $viewPermissionResult->num_rows > 0);
        $stmtViewPermission->close();
    }

    $createPermissionSql = "SELECT 1
                            FROM role_permissions rp
                            INNER JOIN permissions p ON p.id = rp.permission_id
                            WHERE rp.role_id = ?
                              AND p.code = 'roles.create'
                            LIMIT 1";

    if ($stmtCreatePermission = $conn->prepare($createPermissionSql)) {
        $stmtCreatePermission->bind_param('i', $currentRoleId);
        $stmtCreatePermission->execute();
        $createPermissionResult = $stmtCreatePermission->get_result();
        $canCreateRole = ($createPermissionResult && $createPermissionResult->num_rows > 0);
        $stmtCreatePermission->close();
    }
}

if (!$canViewRoles) {
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
                No tienes permisos para visualizar los roles del sistema en NOVA 1.0.
            </p>
        </div>
    </div>
    <?php
    require_once __DIR__ . '/../../app/views/partials/footer.php';
    exit;
}

$sql = "SELECT id, name, description, status, created_at
        FROM roles
        ORDER BY id ASC";

$result = $conn->query($sql);

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
        align-items:center;
        justify-content:space-between;
        gap:12px;
        flex-wrap:wrap;
    ">
        <div>
            <h2 style="margin:0;font-size:18px;">Roles del Sistema</h2>
            <p style="margin:4px 0 0 0;color:#6b7280;font-size:13px;">
                Roles existentes en NOVA 1.0 y acceso a su gesti¨®n de permisos.
            </p>
        </div>

        <?php if ($canCreateRole): ?>
            <a href="/nova1/modules/users/create_role.php"
               style="
                    display:inline-flex;
                    align-items:center;
                    justify-content:center;
                    gap:8px;
                    background:#16a34a;
                    color:#ffffff;
                    text-decoration:none;
                    padding:10px 14px;
                    border-radius:8px;
                    font-weight:600;
                    font-size:13px;
                    white-space:nowrap;
               ">
                <i class="fa-solid fa-plus"></i>
                Nuevo rol
            </a>
        <?php endif; ?>
    </div>

    <div style="
        background:#ffffff;
        border:1px solid #e5e7eb;
        border-radius:12px;
        padding:0;
        overflow:hidden;
    ">

        <table style="width:100%;border-collapse:collapse;font-size:13px;">

            <thead style="background:#f1f5f9;">
                <tr>
                    <th style="padding:10px;text-align:left;">Rol</th>
                    <th style="padding:10px;text-align:left;">Descripci¨®n</th>
                    <th style="padding:10px;text-align:center;">Estado</th>
                    <th style="padding:10px;text-align:center;">Creado</th>
                    <th style="padding:10px;text-align:center;">Acci¨®n</th>
                </tr>
            </thead>

            <tbody>

                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <?php
                        $roleId = (int)$row['id'];
                        $roleName = strtoupper(trim((string)$row['name']));
                        $isProtectedAdmin = ($roleName === 'ADMIN');
                        ?>
                        <tr style="border-top:1px solid #e5e7eb;">
                            <td style="padding:10px;font-weight:600;">
                                <?php echo htmlspecialchars((string)$row['name']); ?>
                            </td>

                            <td style="padding:10px;color:#374151;">
                                <?php echo htmlspecialchars((string)($row['description'] ?? '')); ?>
                            </td>

                            <td style="padding:10px;text-align:center;">
                                <?php if ($row['status'] === 'active'): ?>
                                    <span style="
                                        background:#dcfce7;
                                        color:#166534;
                                        padding:4px 8px;
                                        border-radius:6px;
                                        font-weight:600;
                                    ">
                                        ACTIVO
                                    </span>
                                <?php else: ?>
                                    <span style="
                                        background:#fee2e2;
                                        color:#991b1b;
                                        padding:4px 8px;
                                        border-radius:6px;
                                        font-weight:600;
                                    ">
                                        INACTIVO
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td style="padding:10px;text-align:center;color:#374151;">
                                <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime((string)$row['created_at']))); ?>
                            </td>

                            <td style="padding:10px;text-align:center;">
                                <?php if ($isProtectedAdmin): ?>
                                    <span style="
                                        display:inline-flex;
                                        align-items:center;
                                        justify-content:center;
                                        gap:6px;
                                        background:#e5e7eb;
                                        color:#6b7280;
                                        padding:8px 12px;
                                        border-radius:8px;
                                        font-weight:600;
                                        font-size:12px;
                                        cursor:not-allowed;
                                    ">
                                        <i class="fa-solid fa-lock"></i>
                                        Protegido
                                    </span>
                                <?php else: ?>
                                    <a href="/nova1/modules/users/role_permissions.php?role_id=<?php echo $roleId; ?>"
                                       style="
                                            display:inline-flex;
                                            align-items:center;
                                            justify-content:center;
                                            gap:6px;
                                            background:#2563eb;
                                            color:#ffffff;
                                            text-decoration:none;
                                            padding:8px 12px;
                                            border-radius:8px;
                                            font-weight:600;
                                            font-size:12px;
                                       ">
                                        <i class="fa-solid fa-shield-halved"></i>
                                        Gestionar permisos
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="padding:12px;text-align:center;color:#6b7280;">
                            No existen roles registrados
                        </td>
                    </tr>
                <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>

<?php require_once __DIR__ . '/../../app/views/partials/footer.php'; ?>