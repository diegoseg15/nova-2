<?php

require_once __DIR__ . '/../../config/session.php';

function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

function requireLogin()
{
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function logoutUser()
{
    $_SESSION = [];
    session_destroy();
}
function setContext($institution_id, $group_id, $period_id)
{
    $_SESSION['institution_id'] = $institution_id;
    $_SESSION['study_group_id'] = $group_id;
    $_SESSION['period_id'] = $period_id;
}

function hasContext()
{
    return isset($_SESSION['institution_id'], $_SESSION['study_group_id'], $_SESSION['period_id']);
}

function requireContext()
{
    if (!hasActiveContext() || !isContextLocked()) {
        header('Location: /nova1/dashboard.php');
        exit;
    }
}

function isContextLocked()
{
    return !empty($_SESSION['context_locked']) && $_SESSION['context_locked'] === 'Y';
}

function lockContext()
{
    $_SESSION['context_locked'] = 'Y';
}

function unlockContext()
{
    $_SESSION['context_locked'] = 'N';
    unset($_SESSION['study_group_id']);
    unset($_SESSION['period_id']);
}

function hasActiveContext()
{
    return !empty($_SESSION['study_group_id']) && !empty($_SESSION['period_id']);
}


