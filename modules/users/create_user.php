<?php

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';

$db = new Database();
$conn = $db->connect();

$currentRoleId = (int)($_SESSION['role_id'] ?? 0);
$canCreateUser = false;

if ($currentRoleId > 0) {
    $permissionSql = "SELECT 1
                      FROM role_permissions rp
                      INNER JOIN permissions p ON p.id = rp.permission_id
                      WHERE rp.role_id = ?
                        AND p.code = 'users.create'
                      LIMIT 1";

    if ($stmtPermission = $conn->prepare($permissionSql)) {
        $stmtPermission->bind_param('i', $currentRoleId);
        $stmtPermission->execute();
        $permissionResult = $stmtPermission->get_result();
        $canCreateUser = ($permissionResult && $permissionResult->num_rows > 0);
        $stmtPermission->close();
    }
}

if (!$canCreateUser) {
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
                No tienes permisos para crear usuarios en NOVA 1.0.
            </p>
        </div>
    </div>
    <?php
    require_once __DIR__ . '/../../app/views/partials/footer.php';
    exit;
}

$roles = [];
$rolesSql = "SELECT id, name
             FROM roles
             WHERE status = 'active'
             ORDER BY id ASC";

$rolesResult = $conn->query($rolesSql);
if ($rolesResult && $rolesResult->num_rows > 0) {
    while ($roleRow = $rolesResult->fetch_assoc()) {
        $roles[] = $roleRow;
    }
}

$errors = [];

$roleId = '';
$username = '';
$password = '';
$firstName = '';
$lastName = '';
$email = '';
$phone = '';
$status = 'active';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $roleId = trim((string)($_POST['role_id'] ?? ''));
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $firstName = trim((string)($_POST['first_name'] ?? ''));
    $lastName = trim((string)($_POST['last_name'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $phone = trim((string)($_POST['phone'] ?? ''));
    $status = trim((string)($_POST['status'] ?? 'active'));

    $roleIdInt = (int)$roleId;
    $username = strtolower($username);
    $firstName = strtoupper($firstName);
    $lastName = strtoupper($lastName);
    $email = strtolower($email);

    if ($roleIdInt <= 0) {
        $errors[] = 'Debe seleccionar un rol.';
    }

    if ($username === '') {
        $errors[] = 'El nombre de usuario es obligatorio.';
    }

    if ($password === '') {
        $errors[] = 'La contraseña es obligatoria.';
    }

    if ($firstName === '') {
        $errors[] = 'Los nombres son obligatorios.';
    }

    if ($lastName === '') {
        $errors[] = 'Los apellidos son obligatorios.';
    }

    if ($status !== 'active' && $status !== 'inactive') {
        $status = 'active';
    }

    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'El correo electrónico no tiene un formato válido.';
    }

    if (empty($errors)) {
        $checkRoleSql = "SELECT id FROM roles WHERE id = ? AND status = 'active' LIMIT 1";
        if ($stmtCheckRole = $conn->prepare($checkRoleSql)) {
            $stmtCheckRole->bind_param('i', $roleIdInt);
            $stmtCheckRole->execute();
            $checkRoleResult = $stmtCheckRole->get_result();

            if (!$checkRoleResult || $checkRoleResult->num_rows === 0) {
                $errors[] = 'El rol seleccionado no es válido.';
            }

            $stmtCheckRole->close();
        }
    }

    if (empty($errors)) {
        $checkUserSql = "SELECT id FROM users WHERE username = ? LIMIT 1";
        if ($stmtCheckUser = $conn->prepare($checkUserSql)) {
            $stmtCheckUser->bind_param('s', $username);
            $stmtCheckUser->execute();
            $checkUserResult = $stmtCheckUser->get_result();

            if ($checkUserResult && $checkUserResult->num_rows > 0) {
                $errors[] = 'Ya existe un usuario con ese nombre de acceso.';
            }

            $stmtCheckUser->close();
        }
    }

    if (empty($errors)) {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $insertSql = "INSERT INTO users (
                        role_id,
                        username,
                        password_hash,
                        first_name,
                        last_name,
                        email,
                        phone,
                        status
                      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        if ($stmtInsert = $conn->prepare($insertSql)) {
            $stmtInsert->bind_param(
                'isssssss',
                $roleIdInt,
                $username,
                $passwordHash,
                $firstName,
                $lastName,
                $email,
                $phone,
                $status
            );

            if ($stmtInsert->execute()) {
                $stmtInsert->close();
                header('Location: /nova1/modules/users/index.php');
                exit;
            } else {
                $errors[] = 'No fue posible guardar el usuario.';
                $stmtInsert->close();
            }
        } else {
            $errors[] = 'No fue posible preparar el guardado del usuario.';
        }
    }
}

require_once __DIR__ . '/../../app/views/partials/header.php';
?>

<div style="max-width:1000px;margin:0 auto;">

    <div style="
        background:#ffffff;
        border:1px solid #e5e7eb;
        border-radius:12px;
        padding:18px;
        margin-bottom:16px;
    ">
        <h2 style="margin:0;font-size:18px;">Nuevo Usuario</h2>
        <p style="margin:4px 0 0 0;color:#6b7280;font-size:13px;">
            Crea un usuario del sistema y asígnale un rol en NOVA 1.0.
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

            <div style="
                display:grid;
                grid-template-columns:repeat(2, minmax(0, 1fr));
                gap:16px;
            ">

                <div>
                    <label style="display:block;font-weight:600;margin-bottom:6px;font-size:13px;color:#111827;">
                        Rol
                    </label>
                    <select name="role_id"
                            style="
                                width:100%;
                                height:42px;
                                border:1px solid #d1d5db;
                                border-radius:8px;
                                padding:0 12px;
                                font-size:14px;
                            "
                            required>
                        <option value="">Seleccione...</option>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?php echo (int)$role['id']; ?>" <?php echo ((string)$roleId === (string)$role['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars((string)$role['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label style="display:block;font-weight:600;margin-bottom:6px;font-size:13px;color:#111827;">
                        Usuario
                    </label>
                    <input type="text"
                           name="username"
                           maxlength="100"
                           value="<?php echo htmlspecialchars($username); ?>"
                           style="
                                width:100%;
                                height:42px;
                                border:1px solid #d1d5db;
                                border-radius:8px;
                                padding:0 12px;
                                font-size:14px;
                           "
                           placeholder="Ejemplo: docente01"
                           required>
                </div>

                <div>
                    <label style="display:block;font-weight:600;margin-bottom:6px;font-size:13px;color:#111827;">
                        Contraseña
                    </label>
                    <input type="password"
                           name="password"
                           maxlength="100"
                           value="<?php echo htmlspecialchars($password); ?>"
                           style="
                                width:100%;
                                height:42px;
                                border:1px solid #d1d5db;
                                border-radius:8px;
                                padding:0 12px;
                                font-size:14px;
                           "
                           placeholder="Ingrese una contraseña"
                           required>
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

                <div>
                    <label style="display:block;font-weight:600;margin-bottom:6px;font-size:13px;color:#111827;">
                        Nombres
                    </label>
                    <input type="text"
                           name="first_name"
                           maxlength="150"
                           value="<?php echo htmlspecialchars($firstName); ?>"
                           style="
                                width:100%;
                                height:42px;
                                border:1px solid #d1d5db;
                                border-radius:8px;
                                padding:0 12px;
                                font-size:14px;
                           "
                           placeholder="Nombres del usuario"
                           required>
                </div>

                <div>
                    <label style="display:block;font-weight:600;margin-bottom:6px;font-size:13px;color:#111827;">
                        Apellidos
                    </label>
                    <input type="text"
                           name="last_name"
                           maxlength="150"
                           value="<?php echo htmlspecialchars($lastName); ?>"
                           style="
                                width:100%;
                                height:42px;
                                border:1px solid #d1d5db;
                                border-radius:8px;
                                padding:0 12px;
                                font-size:14px;
                           "
                           placeholder="Apellidos del usuario"
                           required>
                </div>

                <div>
                    <label style="display:block;font-weight:600;margin-bottom:6px;font-size:13px;color:#111827;">
                        Correo electrónico
                    </label>
                    <input type="email"
                           name="email"
                           maxlength="150"
                           value="<?php echo htmlspecialchars($email); ?>"
                           style="
                                width:100%;
                                height:42px;
                                border:1px solid #d1d5db;
                                border-radius:8px;
                                padding:0 12px;
                                font-size:14px;
                           "
                           placeholder="correo@dominio.com">
                </div>

                <div>
                    <label style="display:block;font-weight:600;margin-bottom:6px;font-size:13px;color:#111827;">
                        Teléfono
                    </label>
                    <input type="text"
                           name="phone"
                           maxlength="50"
                           value="<?php echo htmlspecialchars($phone); ?>"
                           style="
                                width:100%;
                                height:42px;
                                border:1px solid #d1d5db;
                                border-radius:8px;
                                padding:0 12px;
                                font-size:14px;
                           "
                           placeholder="Número de contacto">
                </div>

            </div>

            <div style="
                display:flex;
                justify-content:flex-end;
                gap:10px;
                margin-top:20px;
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
                            background:#2563eb;
                            color:#ffffff;
                            font-weight:600;
                            font-size:13px;
                            cursor:pointer;
                        ">
                    Guardar usuario
                </button>
            </div>

        </form>

    </div>

</div>

<?php require_once __DIR__ . '/../../app/views/partials/footer.php'; ?>