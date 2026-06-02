<?php

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';

$db = new Database();
$conn = $db->connect();

$currentRoleId = (int)($_SESSION['role_id'] ?? 0);

$canViewUsers = false;
$canCreateUser = false;
$canEditUser = false;
$canManageUserGroups = false;

if ($currentRoleId > 0) {

    // users.view
    $sqlView = "SELECT 1
                FROM role_permissions rp
                INNER JOIN permissions p ON p.id = rp.permission_id
                WHERE rp.role_id = ?
                  AND p.code = 'users.view'
                LIMIT 1";

    if ($stmt = $conn->prepare($sqlView)) {
        $stmt->bind_param('i', $currentRoleId);
        $stmt->execute();
        $res = $stmt->get_result();
        $canViewUsers = ($res && $res->num_rows > 0);
        $stmt->close();
    }

    // users.create
    $sqlCreate = "SELECT 1
                  FROM role_permissions rp
                  INNER JOIN permissions p ON p.id = rp.permission_id
                  WHERE rp.role_id = ?
                    AND p.code = 'users.create'
                  LIMIT 1";

    if ($stmt = $conn->prepare($sqlCreate)) {
        $stmt->bind_param('i', $currentRoleId);
        $stmt->execute();
        $res = $stmt->get_result();
        $canCreateUser = ($res && $res->num_rows > 0);
        $stmt->close();
    }

    // users.edit
    $sqlEdit = "SELECT 1
                FROM role_permissions rp
                INNER JOIN permissions p ON p.id = rp.permission_id
                WHERE rp.role_id = ?
                  AND p.code = 'users.edit'
                LIMIT 1";

    if ($stmt = $conn->prepare($sqlEdit)) {
        $stmt->bind_param('i', $currentRoleId);
        $stmt->execute();
        $res = $stmt->get_result();
        $canEditUser = ($res && $res->num_rows > 0);
        $stmt->close();
    }

    // users.groups.manage
    $sqlGroups = "SELECT 1
                  FROM role_permissions rp
                  INNER JOIN permissions p ON p.id = rp.permission_id
                  WHERE rp.role_id = ?
                    AND p.code = 'users.groups.manage'
                  LIMIT 1";

    if ($stmt = $conn->prepare($sqlGroups)) {
        $stmt->bind_param('i', $currentRoleId);
        $stmt->execute();
        $res = $stmt->get_result();
        $canManageUserGroups = ($res && $res->num_rows > 0);
        $stmt->close();
    }
}

// BLOQUEO
if (!$canViewUsers) {
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
            <p style="margin:0;color:#6b7280;">
                No tienes permisos para ver usuarios.
            </p>
        </div>
    </div>
    <?php
    require_once __DIR__ . '/../../app/views/partials/footer.php';
    exit;
}

// CONSULTA
$sql = "SELECT
            u.id,
            u.username,
            u.first_name,
            u.last_name,
            u.status,
            r.name AS role_name,
            primary_group.name AS primary_group_name,
            COALESCE(group_count.total_groups, 0) AS total_groups
        FROM users u
        INNER JOIN roles r ON r.id = u.role_id

        LEFT JOIN (
            SELECT uga.user_id, sg.name
            FROM user_group_assignments uga
            INNER JOIN study_groups sg ON sg.id = uga.study_group_id
            WHERE uga.assignment_type = 'primary'
        ) AS primary_group ON primary_group.user_id = u.id

        LEFT JOIN (
            SELECT user_id, COUNT(*) AS total_groups
            FROM user_group_assignments
            GROUP BY user_id
        ) AS group_count ON group_count.user_id = u.id

        ORDER BY u.id DESC";

$result = $conn->query($sql);

$showActionsColumn = ($canEditUser || $canManageUserGroups);

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
        align-items:center;
        flex-wrap:wrap;
        gap:10px;
    ">
        <div>
            <h2 style="margin:0;font-size:18px;">Usuarios</h2>
            <p style="margin:4px 0 0 0;color:#6b7280;font-size:13px;">
                Gestión de usuarios del sistema NOVA 1.0
            </p>
        </div>

        <?php if ($canCreateUser): ?>
            <a href="/nova1/modules/users/create_user.php"
               style="
                    background:#16a34a;
                    color:#fff;
                    padding:10px 14px;
                    border-radius:8px;
                    text-decoration:none;
                    font-weight:600;
                    font-size:13px;
               ">
                + Nuevo usuario
            </a>
        <?php endif; ?>
    </div>

    <div style="
        background:#ffffff;
        border:1px solid #e5e7eb;
        border-radius:12px;
        overflow:hidden;
    ">

        <table style="width:100%;border-collapse:collapse;font-size:13px;">

            <thead style="background:#f1f5f9;">
                <tr>
                    <th style="padding:10px;text-align:left;">Nombre</th>
                    <th style="padding:10px;text-align:left;">Usuario</th>
                    <th style="padding:10px;text-align:center;">Rol</th>
                    <th style="padding:10px;text-align:left;">Grupo</th>
                    <th style="padding:10px;text-align:center;">Estado</th>
                    <?php if ($showActionsColumn): ?>
                        <th style="padding:10px;text-align:center;">Acción</th>
                    <?php endif; ?>
                </tr>
            </thead>

            <tbody>

                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <?php
                        $primaryGroupName = trim((string)($row['primary_group_name'] ?? ''));
                        $totalGroups = (int)($row['total_groups'] ?? 0);

                        if ($totalGroups <= 0 || $primaryGroupName === '') {
                            $groupDisplay = 'SIN GRUPO';
                            $groupColor = '#6b7280';
                        } elseif ($totalGroups === 1) {
                            $groupDisplay = $primaryGroupName;
                            $groupColor = '#111827';
                        } else {
                            $groupDisplay = $primaryGroupName . ' ...';
                            $groupColor = '#111827';
                        }
                        ?>
                        <tr style="border-top:1px solid #e5e7eb;">
                            
                            <td style="padding:10px;font-weight:600;">
                                <?php echo htmlspecialchars((string)$row['last_name'] . ' ' . (string)$row['first_name']); ?>
                            </td>

                            <td style="padding:10px;">
                                <?php echo htmlspecialchars((string)$row['username']); ?>
                            </td>

                            <td style="padding:10px;text-align:center;">
                                <?php echo htmlspecialchars((string)$row['role_name']); ?>
                            </td>

                            <td style="padding:10px;color:<?php echo htmlspecialchars($groupColor); ?>;font-weight:600;">
                                <?php echo htmlspecialchars($groupDisplay); ?>
                            </td>

                            <td style="padding:10px;text-align:center;">
                                <?php if ($row['status'] === 'active'): ?>
                                    <span style="background:#dcfce7;color:#166534;padding:4px 8px;border-radius:6px;font-weight:600;">
                                        ACTIVO
                                    </span>
                                <?php else: ?>
                                    <span style="background:#fee2e2;color:#991b1b;padding:4px 8px;border-radius:6px;font-weight:600;">
                                        INACTIVO
                                    </span>
                                <?php endif; ?>
                            </td>

                            <?php if ($showActionsColumn): ?>
                                <td style="padding:10px;text-align:center;">
                                    <div style="
                                        display:flex;
                                        justify-content:center;
                                        align-items:center;
                                        gap:8px;
                                        flex-wrap:wrap;
                                    ">
                                        <?php if ($canEditUser): ?>
                                            <a href="/nova1/modules/users/edit_user.php?id=<?php echo (int)$row['id']; ?>"
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
                                                <i class="fa-solid fa-pen"></i>
                                                Editar
                                            </a>
                                        <?php endif; ?>

                                        <?php if ($canManageUserGroups): ?>
                                            <a href="/nova1/modules/users/user_groups.php?id=<?php echo (int)$row['id']; ?>"
                                               style="
                                                    display:inline-flex;
                                                    align-items:center;
                                                    justify-content:center;
                                                    gap:6px;
                                                    background:#0f766e;
                                                    color:#ffffff;
                                                    text-decoration:none;
                                                    padding:8px 12px;
                                                    border-radius:8px;
                                                    font-weight:600;
                                                    font-size:12px;
                                               ">
                                                <i class="fa-solid fa-layer-group"></i>
                                                Grupos
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            <?php endif; ?>

                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="<?php echo $showActionsColumn ? '6' : '5'; ?>" style="padding:12px;text-align:center;color:#6b7280;">
                            No existen usuarios registrados
                        </td>
                    </tr>
                <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>

<?php require_once __DIR__ . '/../../app/views/partials/footer.php'; ?>