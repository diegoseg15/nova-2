<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

require_once __DIR__ . '/app/core/bootstrap.php';
require_once app_path('services/AuthService.php');

if (isAuthenticated()) {
    redirect_to('dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Debe ingresar usuario y contraseña.';
    } else {
        try {
            $db = new Database();
            $authService = new AuthService($db->connect());

            if ($authService->attempt($username, $password)) {
                redirect_to('dashboard.php');
            }

            $error = 'Usuario o contraseña incorrectos.';
        } catch (Throwable $e) {
            error_log('Login error: ' . $e->getMessage());
            $error = 'No se pudo iniciar sesión. Intente nuevamente.';
        }
    }
}
