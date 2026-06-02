<?php

require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /nova1/login.php');
    exit;
}

$db = new Database();
$conn = $db->connect();

$roleId = (int)($_SESSION['role_id'] ?? 0);

$canManageInstitutions = false;

$sqlPermission = "SELECT 1
                  FROM role_permissions rp
                  INNER JOIN permissions p ON p.id = rp.permission_id
                  WHERE rp.role_id = ?
                    AND p.code = 'school_regime.institutions.manage'
                    AND p.status = 'active'
                  LIMIT 1";

$stmtPermission = $conn->prepare($sqlPermission);
$stmtPermission->bind_param('i', $roleId);
$stmtPermission->execute();
$resultPermission = $stmtPermission->get_result();
$canManageInstitutions = ($resultPermission && $resultPermission->num_rows > 0);
$stmtPermission->close();

if (!$canManageInstitutions) {
    require_once __DIR__ . '/../../../app/views/partials/header.php';
    echo '<div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:20px;">';
    echo '<h2>Acceso restringido</h2>';
    echo '<p>No tiene permiso para administrar instituciones.</p>';
    echo '</div>';
    require_once __DIR__ . '/../../../app/views/partials/footer.php';
    exit;
}

$message = '';
$error = '';

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'created') {
        $message = 'Institución creada correctamente.';
    } elseif ($_GET['msg'] === 'updated') {
        $message = 'Institución actualizada correctamente.';
    } elseif ($_GET['msg'] === 'activated') {
        $message = 'Institución activada correctamente.';
    }
}

if (isset($_GET['error'])) {
    if ($_GET['error'] === 'required') {
        $error = 'Debe completar los campos obligatorios.';
    } elseif ($_GET['error'] === 'duplicate') {
        $error = 'El código ya existe.';
    } elseif ($_GET['error'] === 'save') {
        $error = 'Error al guardar la institución.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $institutionId = (int)($_POST['institution_id'] ?? 0);

    if ($action === 'activate' && $institutionId > 0) {
        $conn->begin_transaction();

        try {
            $conn->query("UPDATE institutions SET status = 'inactive' WHERE status = 'active'");

            $stmtActive = $conn->prepare("UPDATE institutions SET status = 'active' WHERE id = ? LIMIT 1");
            $stmtActive->bind_param('i', $institutionId);
            $stmtActive->execute();
            $stmtActive->close();

            $conn->commit();

            header('Location: /nova1/modules/school_regime/institutions/index.php?msg=activated');
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            $error = 'No se pudo activar la institución.';
        }
    }
}

$sql = "SELECT id, code, name, legal_name, ruc, phone, email, address, status
        FROM institutions
        ORDER BY status ASC, name ASC";

$result = $conn->query($sql);

require_once __DIR__ . '/../../../app/views/partials/header.php';
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
    <div>
        <h1 style="margin:0;font-size:24px;color:#0f172a;">Institución</h1>
        <p style="margin:4px 0 0;color:#64748b;font-size:14px;">
            Administración de la institución principal del sistema NOVA 1.0
        </p>
    </div>

    <a href="/nova1/modules/school_regime/institutions/create.php"
       style="background:#2563eb;color:#fff;text-decoration:none;padding:10px 14px;border-radius:10px;font-weight:700;font-size:14px;">
        + Nueva institución
    </a>
</div>

<?php if ($message !== ''): ?>
    <div style="background:#dcfce7;border:1px solid #86efac;color:#166534;padding:12px;border-radius:10px;margin-bottom:14px;">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<?php if ($error !== ''): ?>
    <div style="background:#fee2e2;border:1px solid #fecaca;color:#991b1b;padding:12px;border-radius:10px;margin-bottom:14px;">
        <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<div style="background:#ffffff;border:1px solid #e5e7eb;border-radius:14px;overflow:hidden;box-shadow:0 8px 20px rgba(15,23,42,0.04);">
    <table style="width:100%;border-collapse:collapse;font-size:14px;">
        <thead>
            <tr style="background:#f1f5f9;color:#334155;text-align:left;">
                <th style="padding:12px;">Código</th>
                <th style="padding:12px;">Nombre</th>
                <th style="padding:12px;">Razón social</th>
                <th style="padding:12px;">RUC</th>
                <th style="padding:12px;">Contacto</th>
                <th style="padding:12px;">Estado</th>
                <th style="padding:12px;text-align:center;">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td style="padding:12px;font-weight:700;"><?php echo htmlspecialchars($row['code']); ?></td>
                        <td style="padding:12px;"><?php echo htmlspecialchars($row['name']); ?></td>
                        <td style="padding:12px;"><?php echo htmlspecialchars($row['legal_name'] ?? ''); ?></td>
                        <td style="padding:12px;"><?php echo htmlspecialchars($row['ruc'] ?? ''); ?></td>

                        <td style="padding:12px;">
                            <div><?php echo htmlspecialchars($row['phone'] ?? ''); ?></div>
                            <div style="font-size:12px;color:#64748b;">
                                <?php echo htmlspecialchars($row['email'] ?? ''); ?>
                            </div>
                        </td>

                        <td style="padding:12px;">
                            <?php if ($row['status'] === 'active'): ?>
                                <span style="background:#dcfce7;color:#166534;padding:5px 9px;border-radius:999px;font-size:12px;font-weight:700;">ACTIVA</span>
                            <?php else: ?>
                                <span style="background:#f1f5f9;color:#475569;padding:5px 9px;border-radius:999px;font-size:12px;font-weight:700;">INACTIVA</span>
                            <?php endif; ?>
                        </td>

                        <td style="padding:12px;text-align:center;">
                            <a href="/nova1/modules/school_regime/institutions/edit.php?id=<?php echo (int)$row['id']; ?>"
                               style="background:#0f172a;color:#fff;padding:6px 10px;border-radius:8px;font-size:12px;text-decoration:none;">
                                Editar
                            </a>

                            <?php if ($row['status'] !== 'active'): ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="activate">
                                    <input type="hidden" name="institution_id" value="<?php echo (int)$row['id']; ?>">
                                    <button type="submit"
                                            style="background:#16a34a;color:#fff;padding:6px 10px;border:none;border-radius:8px;font-size:12px;cursor:pointer;">
                                        Activar
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" style="padding:16px;text-align:center;color:#64748b;">
                        No existen instituciones registradas.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../../../app/views/partials/footer.php'; ?>

