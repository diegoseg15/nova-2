<?php

function isAuthenticated(): bool
{
    return isset($_SESSION['user_id']);
}

function isLoggedIn(): bool
{
    return isAuthenticated();
}

function requireLogin(): void
{
    if (!isAuthenticated()) {
        redirect_to('login.php');
    }
}

function logoutUser(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'] ?? '',
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}

function currentUserName(): string
{
    return $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Usuario';
}

function currentUserRole(): string
{
    return $_SESSION['role_name'] ?? '';
}
