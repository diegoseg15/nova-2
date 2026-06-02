<?php

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';

$db = new Database();
$conn = $db->connect();

$currentRoleId = (int)($_SESSION['role_id'] ?? 0);
$hasAccess = false;

if ($currentRoleId > 0) {
    $permissionSql = "SELECT 1
                      FROM role_permissions rp
                      INNER JOIN permissions p ON p.id = rp.permission_id
                      WHERE rp.role_id = ?
                        AND p.code = 'roles.create'
                      LIMIT 1";

    if ($stmtPermission = $conn->prepare($permissionSql)) {
        $stmtPermission->bind_param('i', $currentRoleId);
        $stmtPermission->execute();
        $permissionResult = $stmtPermission->get_result();
        $hasAccess = ($permissionResult && $permissionResult->num_rows > 0);
        $stmtPermission->close();
    }
}

if (!$hasAccess) {
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
                No tienes permisos para crear nuevos roles en NOVA 1.0.
            </p>
        </div>
    </div>
    <?php
    require_once __DIR__ . '/../../app/views/partials/footer.php';
    exit;
}

$errors = [];
$successMessage = '';

$name = '';
$description = '';
$status = 'active';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string)($_POST['name'] ?? ''));
    $description = trim((string)($_POST['description'] ?? ''));
    $status = trim((string)($_POST['status'] ?? 'active'));

    $name = strtoupper($name);

    if ($name === '') {
        $errors[] = 'El nombre del rol es obligatorio.';
    }

    if ($status !== 'active' && $status !== 'inactive') {
        $status = 'active';
    }

    if (empty($errors)) {
        $checkSql = "SELECT id FROM roles WHERE name = ? LIMIT 1";
        if ($stmtCheck = $conn->prepare($checkSql)) {
            $stmtCheck->bind_param('s', $name);
            $stmtCheck->execute();
            $checkResult = $stmtCheck->get_result();

            if ($checkResult && $checkResult->num_rows > 0) {
                $errors[] = 'Ya existe un rol con ese nombre.';
            }

            $stmtCheck->close();
        }
    }

    if (empty($errors)) {
        $insertSql = "INSERT INTO roles (name, description, status)
                      VALUES (?, ?, ?)";

        if ($stmtInsert = $conn->prepare($insertSql)) {
            $stmtInsert->bind_param('sss', $name, $description, $status);

            if ($stmtInsert->execute()) {
                header('Location: /nova1/modules/users/roles.php');
                exit;
            } else {
                $errors[] = 'No fue posible guardar el nuevo rol.';
            }

            $stmtInsert->close();
        } else {
            $errors[] = 'No fue posible preparar el guardado del rol.';
        }
    }
}

require_once __DIR__ . '/../../app/views/partials/header.php';
?>

<div style="max-width:900px;margin:0 auto;">

    <div style="
        background:#ffffff;
        border:1px solid #e5e7eb;
        border-radius:12px;
        padding:18px;
        margin-bottom:16px;
    ">
        <h2 style="margin:0;font-size:18px;">Nuevo Rol</h2>
        <p style="margin:4px 0 0 0;color:#6b7280;font-size:13px;">
            Crea un nuevo rol para NOVA 1.0. Solo ADMIN y CEO pueden realizar esta acción.
        </p>
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

    <div style="
        background:#ffffff;
        border:1px solid #e5e7eb;
        border-radius:12px;
        padding:20px;
    ">

        <form method="post" action="" novalidate>

            <div style="display:grid;grid-template-columns:1fr;gap:16px;">

                <div>
                    <label style="display:block;font-weight:600;margin-bottom:6px;font-size:13px;color:#111827;">
                        Nombre del rol
                    </label>
                    <input type="text"
                           name="name"
                           maxlength="50"
                           value="<?php echo htmlspecialchars($name); ?>"
                           style="
                                width:100%;
                                height:42px;
                                border:1px solid #d1d5db;
                                border-radius:8px;
                                padding:0 12px;
                                font-size:14px;
                           "
                           placeholder="Ejemplo: COORDINADOR"
                           required>
                </div>

                <div>
                    <label style="display:block;font-weight:600;margin-bottom:6px;font-size:13px;color:#111827;">
                        Descripción
                    </label>
                    <textarea name="description"
                              rows="4"
                              style="
                                    width:100%;
                                    border:1px solid #d1d5db;
                                    border-radius:8px;
                                    padding:10px 12px;
                                    font-size:14px;
                                    resize:vertical;
                              "
                              placeholder="Describe la función principal de este rol"><?php echo htmlspecialchars($description); ?></textarea>
                </div>

                <div>
                    <label style="display:block;font-weight:600;margin-bottom:6px;font-size:13px;color:#111827;">
                        Estado
                    </label>
                    <select name="status"
                            style="
                                width:100%;
                                height:42px;
                                border:1px solid #d1d5db;
                                border-radius:8px;
                                padding:0 12px;
                                font-size:14px;
                            ">
                        <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Activo</option>
                        <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactivo</option>
                    </select>
                </div>

            </div>

            <div style="
                display:flex;
                justify-content:flex-end;
                gap:10px;
                margin-top:20px;
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
                    Guardar rol
                </button>
            </div>

        </form>

    </div>

</div>

<?php require_once __DIR__ . '/../../app/views/partials/footer.php'; ?>