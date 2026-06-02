<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';

$db = new Database();
$conn = $db->connect();

$currentRoleId = (int)($_SESSION['role_id'] ?? 0);
$hasViewAccess = false;

if ($currentRoleId > 0) {
    $permissionSql = "SELECT 1
                      FROM role_permissions rp
                      INNER JOIN permissions p ON p.id = rp.permission_id
                      WHERE rp.role_id = ?
                        AND p.code = 'permissions.view'
                      LIMIT 1";

    if ($stmtPermission = $conn->prepare($permissionSql)) {
        $stmtPermission->bind_param('i', $currentRoleId);
        $stmtPermission->execute();
        $permissionResult = $stmtPermission->get_result();
        $hasViewAccess = ($permissionResult && $permissionResult->num_rows > 0);
        $stmtPermission->close();
    }
}

if (!$hasViewAccess) {
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
                No tienes permisos para visualizar el catálogo de permisos en NOVA 1.0.
            </p>
        </div>
    </div>
    <?php
    require_once __DIR__ . '/../../app/views/partials/footer.php';
    exit;
}

$sql = "SELECT module, code, name, description, status
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

$result = $conn->query($sql);

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

require_once __DIR__ . '/../../app/views/partials/header.php';
?>

<div style="max-width:1200px;margin:0 auto;">

    <div style="
        background:#ffffff;
        border:1px solid #e5e7eb;
        border-radius:12px;
        padding:18px;
        margin-bottom:16px;
    ">
        <h2 style="margin:0;font-size:18px;">Permisos del Sistema</h2>
        <p style="margin:4px 0 0 0;color:#6b7280;font-size:13px;">
            Catálogo general de permisos disponibles en NOVA 1.0
        </p>
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
                    <th style="padding:10px;text-align:left;">Módulo</th>
                    <th style="padding:10px;text-align:left;">Código</th>
                    <th style="padding:10px;text-align:left;">Nombre</th>
                    <th style="padding:10px;text-align:left;">Descripción</th>
                    <th style="padding:10px;text-align:center;">Estado</th>
                </tr>
            </thead>

            <tbody>

                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr style="border-top:1px solid #e5e7eb;">
                            <td style="padding:10px;">
                                <?php echo htmlspecialchars(getModuleLabel((string)$row['module'])); ?>
                            </td>
                            <td style="padding:10px;">
                                <?php echo htmlspecialchars((string)$row['code']); ?>
                            </td>
                            <td style="padding:10px;">
                                <?php echo htmlspecialchars((string)$row['name']); ?>
                            </td>
                            <td style="padding:10px;">
                                <?php echo htmlspecialchars((string)$row['description']); ?>
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
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="padding:12px;text-align:center;color:#6b7280;">
                            No existen permisos registrados
                        </td>
                    </tr>
                <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>

<?php require_once __DIR__ . '/../../app/views/partials/footer.php'; ?>