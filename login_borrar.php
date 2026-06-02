<?php

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Debe ingresar usuario y contrase«Ða.';
    } else {
        $db = new Database();
        $conn = $db->connect();

        $sql = "SELECT u.id, u.role_id, u.username, u.password_hash, u.first_name, u.last_name, r.name AS role_name
                FROM users u
                INNER JOIN roles r ON r.id = u.role_id
                WHERE u.username = ? AND u.status = 'active'
                LIMIT 1";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role_id'] = $user['role_id'];
            $_SESSION['role_name'] = $user['role_name'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = trim($user['first_name'] . ' ' . $user['last_name']);

            $update = $conn->prepare("UPDATE users SET last_login_at = NOW() WHERE id = ?");
            $update->bind_param('i', $user['id']);
            $update->execute();

            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Usuario o contrase«Ða incorrectos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingreso - NOVA 1.0</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #031733 0%, #052247 45%, #0b2a55 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .login-shell {
            width: 100%;
            max-width: 980px;
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            background: #ffffff;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 22px 60px rgba(0, 0, 0, 0.28);
        }

        .login-brand {
            background: linear-gradient(180deg, #052247 0%, #031733 100%);
            color: #ffffff;
            padding: 48px 42px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
        }

        .brand-badge {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .brand-mark {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            border: 3px solid #3b82f6;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            position: relative;
            flex-shrink: 0;
        }

        .brand-mark::after {
            content: '';
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #3b82f6;
            display: block;
        }

        .brand-title {
            margin: 0;
            font-size: 34px;
            font-weight: 800;
            letter-spacing: .02em;
        }

        .brand-version {
            color: #93c5fd;
            font-size: 15px;
            font-weight: 700;
            margin-left: 6px;
        }

        .brand-subtitle {
            margin: 10px 0 0 0;
            font-size: 14px;
            font-weight: 700;
            color: #dbeafe;
            letter-spacing: .06em;
            text-transform: uppercase;
        }

        .brand-text {
            margin-top: 26px;
            color: #cbd5e1;
            font-size: 15px;
            line-height: 1.65;
            max-width: 420px;
        }

        .brand-footer {
            margin-top: 32px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .brand-chip {
            padding: 8px 12px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.08);
            color: #dbeafe;
            font-size: 12px;
            font-weight: 700;
        }

        .login-panel {
            padding: 42px 36px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: #ffffff;
        }

        .login-panel h2 {
            margin: 0;
            font-size: 28px;
            color: #111827;
        }

        .login-panel p {
            margin: 8px 0 0 0;
            color: #6b7280;
            font-size: 14px;
        }

        .login-error {
            margin-top: 22px;
            padding: 12px 14px;
            border-radius: 10px;
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
            font-size: 14px;
            font-weight: 600;
        }

        .login-form {
            margin-top: 24px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-label {
            display: block;
            margin-bottom: 7px;
            font-size: 13px;
            font-weight: 700;
            color: #111827;
        }

        .form-input {
            width: 100%;
            height: 46px;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            padding: 0 14px;
            font-size: 14px;
            color: #111827;
            outline: none;
            transition: border-color .18s ease, box-shadow .18s ease;
        }

        .form-input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }

        .login-button {
            width: 100%;
            height: 46px;
            border: none;
            border-radius: 10px;
            background: #2563eb;
            color: #ffffff;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: background .18s ease;
        }

        .login-button:hover {
            background: #1d4ed8;
        }

        .login-note {
            margin-top: 18px;
            font-size: 12px;
            color: #6b7280;
            text-align: center;
        }

        @media (max-width: 900px) {
            .login-shell {
                grid-template-columns: 1fr;
                max-width: 520px;
            }

            .login-brand {
                padding: 32px 28px;
            }

            .login-panel {
                padding: 32px 28px;
            }

            .brand-title {
                font-size: 28px;
            }
        }
    </style>
</head>
<body>

<div class="login-shell">

    <div class="login-brand">
        <div class="brand-badge">
            <span class="brand-mark"></span>
            <div>
                <h1 class="brand-title">NOVA <span class="brand-version">1.0</span></h1>
                <div class="brand-subtitle">ERP EDUCATIVO</div>
            </div>
        </div>

        <div class="brand-text">
            Plataforma institucional para la gesti«Ñn acad«±mica, administrativa y financiera,
            dise«Ðada para operar con control, claridad y visi«Ñn gerencial.
        </div>

        <div class="brand-footer">
            <span class="brand-chip">Acad«±mico</span>
            <span class="brand-chip">Pagos</span>
            <span class="brand-chip">Indicadores</span>
            <span class="brand-chip">Usuarios</span>
        </div>
    </div>

    <div class="login-panel">
        <h2>Ingreso al sistema</h2>
        <p>Introduzca sus credenciales para acceder a NOVA 1.0.</p>

        <?php if ($error !== ''): ?>
            <div class="login-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="" class="login-form">
            <div class="form-group">
                <label class="form-label" for="username">Usuario</label>
                <input
                    class="form-input"
                    type="text"
                    id="username"
                    name="username"
                    value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                    autocomplete="username"
                    required
                >
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Contrase«Ða</label>
                <input
                    class="form-input"
                    type="password"
                    id="password"
                    name="password"
                    autocomplete="current-password"
                    required
                >
            </div>

            <button type="submit" class="login-button">Ingresar</button>
        </form>

        <div class="login-note">
            Acceso restringido a usuarios autorizados.
        </div>
    </div>

</div>

</body>
</html>