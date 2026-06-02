<?php

require_once __DIR__ . '/../../../config/database.php';

$dbHeader = new Database();
$connHeader = $dbHeader->connect();

$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);

$isDashboard      = ($currentPath === '/nova1/dashboard.php');
$isStudentsCreate = ($currentPath === '/nova1/modules/students/create.php');
$isStudentsSearch = ($currentPath === '/nova1/modules/students/search.php');
$isStudentsModule = (strpos($currentPath, '/nova1/modules/students/') === 0);

$isAcademicModule = (strpos($currentPath, '/nova1/modules/academic/') === 0);
$isPaymentsModule = (strpos($currentPath, '/nova1/modules/payments/') === 0);
$isRegimeModule   = (strpos($currentPath, '/nova1/modules/school_regime/') === 0);
$isUsersModule    = (strpos($currentPath, '/nova1/modules/users/') === 0);

$isUsersPermissions = ($currentPath === '/nova1/modules/users/permissions.php');
$isUsersRoles       = ($currentPath === '/nova1/modules/users/roles.php');
$isUsersIndex       = ($currentPath === '/nova1/modules/users/index.php');

$fullName = trim((string)($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Administrador'));
$roleName = trim((string)($_SESSION['role_name'] ?? 'ADMIN'));

$canSeeUsersMenu = false;

if (isset($_SESSION['role_id'])) {
    $currentRoleId = (int)($_SESSION['role_id'] ?? 0);

    if ($currentRoleId > 0) {
        $menuPermissionSql = "SELECT 1
                              FROM role_permissions rp
                              INNER JOIN permissions p ON p.id = rp.permission_id
                              WHERE rp.role_id = ?
                                AND p.code IN (
                                    'users.view',
                                    'users.create',
                                    'users.edit',
                                    'users.groups.manage',
                                    'roles.view',
                                    'roles.create',
                                    'roles.manage',
                                    'permissions.view'
                                )
                              LIMIT 1";

        if ($stmtMenuPermission = $connHeader->prepare($menuPermissionSql)) {
            $stmtMenuPermission->bind_param('i', $currentRoleId);
            $stmtMenuPermission->execute();
            $menuPermissionResult = $stmtMenuPermission->get_result();
            $canSeeUsersMenu = ($menuPermissionResult && $menuPermissionResult->num_rows > 0);
            $stmtMenuPermission->close();
        }
    }
}

$sidebarExpanded = ($isStudentsModule || ($isUsersModule && $canSeeUsersMenu)) ? 'menu-expanded' : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>NOVA 1.0</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
:root{
    --topbar-height: 58px;
    --contextbar-height: 44px;
    --sidebar-collapsed: 60px;
    --sidebar-expanded: 230px;
    --sidebar-bg: #031733;
    --sidebar-hover: #0b2a55;
    --sidebar-active: #2563eb;
    --sidebar-text: #e5edf8;
    --sidebar-muted: #a9bdd8;
    --border-soft: #e5e7eb;
    --topbar-bg: #052247;
    --content-bg: #f8fafc;
}

*{
    box-sizing:border-box;
}

html, body{
    margin:0;
    padding:0;
    font-family: Arial, sans-serif;
    background:var(--content-bg);
}

body.menu-expanded .sidebar{
    width:var(--sidebar-expanded);
}

body.menu-expanded .sidebar-top-fill{
    width:var(--sidebar-expanded);
}

body.menu-expanded .content{
    margin-left:var(--sidebar-expanded);
}

body.menu-expanded .context-fixed-wrap{
    left:var(--sidebar-expanded);
}

body.menu-expanded .topbar{
    padding-left:calc(var(--sidebar-expanded) + 14px);
}

.sidebar-top-fill{
    position:fixed;
    top:0;
    left:0;
    width:var(--sidebar-collapsed);
    height:calc(var(--topbar-height) + var(--contextbar-height));
    background:var(--sidebar-bg);
    z-index:1101;
    transition:width .22s ease;
}

.topbar{
    position:fixed;
    top:0;
    left:0;
    right:0;
    height:var(--topbar-height);
    background:var(--topbar-bg);
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:0 14px 0 calc(var(--sidebar-collapsed) + 14px);
    z-index:1200;
    border-bottom:1px solid rgba(255,255,255,0.06);
    transition:padding-left .22s ease;
}

.topbar-left{
    display:flex;
    align-items:center;
    gap:12px;
    min-width:220px;
}

.topbar-menu-btn{
    width:34px;
    height:34px;
    border:none;
    background:transparent;
    color:#ffffff;
    border-radius:8px;
    display:flex;
    align-items:center;
    justify-content:center;
    cursor:pointer;
    flex-shrink:0;
    transition:background .18s ease;
}

.topbar-menu-btn:hover{
    background:rgba(255,255,255,0.10);
}

.topbar-menu-btn i{
    font-size:18px;
    color:#ffffff;
}

.topbar-brand{
    display:flex;
    align-items:center;
    gap:10px;
    text-decoration:none;
    color:#ffffff;
}

.topbar-brand-mark{
    width:22px;
    height:22px;
    border-radius:50%;
    border:3px solid #2563eb;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    position:relative;
    flex-shrink:0;
}

.topbar-brand-mark::after{
    content:'';
    width:6px;
    height:6px;
    background:#2563eb;
    border-radius:50%;
    display:block;
}

.topbar-brand-text{
    display:flex;
    flex-direction:column;
    line-height:1;
}

.topbar-brand-name{
    font-size:14px;
    font-weight:800;
    letter-spacing:.02em;
    color:#ffffff;
}

.topbar-brand-version{
    font-size:10px;
    font-weight:700;
    color:#93c5fd;
    margin-left:2px;
}

.topbar-brand-sub{
    font-size:10px;
    font-weight:700;
    color:#dbeafe;
    margin-top:2px;
    text-transform:uppercase;
}

.topbar-center{
    position:absolute;
    left:50%;
    transform:translateX(-50%);
    display:flex;
    align-items:center;
    justify-content:center;
    pointer-events:none;
}

.topbar-center img{
    height:42px;
    width:auto;
    display:block;
}

.topbar-right{
    display:flex;
    align-items:center;
    gap:10px;
    color:#ffffff;
    min-width:180px;
    justify-content:flex-end;
}

.topbar-logout{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:6px;
    height:34px;
    padding:0 12px;
    background:rgba(255,255,255,0.12);
    color:#ffffff;
    text-decoration:none;
    border-radius:8px;
    font-size:12px;
    font-weight:700;
    transition:background .18s ease;
}

.topbar-logout:hover{
    background:rgba(255,255,255,0.20);
}

.topbar-user-icon{
    width:30px;
    height:30px;
    border-radius:50%;
    background:rgba(255,255,255,0.16);
    display:flex;
    align-items:center;
    justify-content:center;
    flex-shrink:0;
}

.topbar-user-icon i{
    font-size:16px;
    color:#ffffff;
}

.topbar-user-text{
    display:flex;
    flex-direction:column;
    align-items:flex-start;
    line-height:1.05;
}

.topbar-user-name{
    font-size:12px;
    font-weight:700;
    color:#ffffff;
}

.topbar-user-role{
    font-size:11px;
    font-weight:700;
    color:#dbeafe;
    text-transform:uppercase;
}

.context-fixed-wrap{
    position:fixed;
    top:var(--topbar-height);
    left:var(--sidebar-collapsed);
    right:0;
    z-index:1150;
    background:#ffffff;
    border-bottom:1px solid var(--border-soft);
    transition:left .22s ease;
}

.sidebar{
    position:fixed;
    top:0;
    left:0;
    bottom:0;
    width:var(--sidebar-collapsed);
    background:var(--sidebar-bg);
    z-index:1100;
    overflow-x:hidden;
    overflow-y:auto;
    transition:width .22s ease;
    padding:calc(var(--topbar-height) + var(--contextbar-height) + 10px) 8px 14px;
}

.sidebar::-webkit-scrollbar{
    width:6px;
}

.sidebar::-webkit-scrollbar-thumb{
    background:#22314d;
    border-radius:10px;
}

.sidebar-section{
    display:flex;
    flex-direction:column;
    gap:8px;
}

.menu-entry{
    display:flex;
    flex-direction:column;
    width:100%;
}

.menu-link,
.menu-toggle{
    width:100%;
    min-height:44px;
    border:none;
    background:transparent;
    color:var(--sidebar-text);
    text-decoration:none;
    border-radius:12px;
    cursor:pointer;
    display:flex;
    align-items:center;
    gap:12px;
    padding:0 10px;
    transition:background .18s ease, color .18s ease;
    position:relative;
}

.menu-link:hover,
.menu-toggle:hover{
    background:var(--sidebar-hover);
    color:#ffffff;
}

.menu-link.active,
.menu-toggle.active{
    background:var(--sidebar-active);
    color:#ffffff;
}

.menu-icon{
    width:24px;
    min-width:24px;
    text-align:center;
    font-size:21px;
    line-height:1;
}

.menu-text{
    font-size:14px;
    font-weight:600;
    white-space:nowrap;
    opacity:0;
    visibility:hidden;
    transition:opacity .18s ease;
}

.menu-caret{
    margin-left:auto;
    font-size:11px;
    opacity:0;
    visibility:hidden;
    transition:opacity .18s ease, transform .18s ease;
}

body.menu-expanded .menu-text,
body.menu-expanded .menu-caret{
    opacity:1;
    visibility:visible;
}

.menu-toggle.expanded .menu-caret{
    transform:rotate(180deg);
}

.menu-tooltip-target:hover::after{
    content:attr(data-title);
    position:absolute;
    left:56px;
    white-space:nowrap;
    background:#111827;
    color:#fff;
    font-size:12px;
    padding:6px 9px;
    border-radius:8px;
    box-shadow:0 8px 20px rgba(0,0,0,0.18);
    z-index:1300;
}

body.menu-expanded .menu-tooltip-target:hover::after{
    display:none;
}

.submenu{
    display:none;
    margin-top:6px;
    padding-left:0;
}

.submenu.show{
    display:block;
}

.submenu-link{
    display:flex;
    align-items:center;
    gap:10px;
    min-height:38px;
    padding:0 12px 0 44px;
    margin:0 0 6px 0;
    border-radius:10px;
    text-decoration:none;
    color:var(--sidebar-muted);
    transition:background .18s ease, color .18s ease;
    white-space:nowrap;
}

.submenu-link i{
    width:16px;
    min-width:16px;
    text-align:center;
    font-size:13px;
}

.submenu-link span{
    font-size:13px;
    font-weight:600;
}

.submenu-link:hover{
    background:#0c2345;
    color:#ffffff;
}

.submenu-link.active{
    background:#17376a;
    color:#ffffff;
    border-left:3px solid #60a5fa;
    padding-left:41px;
}

.content{
    margin-left:var(--sidebar-collapsed);
    padding:calc(var(--topbar-height) + var(--contextbar-height) + 16px) 16px 16px 16px;
    min-height:100vh;
    transition:margin-left .22s ease;
}

@media (max-width: 900px){
    .topbar-user-text{
        display:none;
    }

    .topbar-left,
    .topbar-right{
        min-width:90px;
    }

    .topbar-brand-sub{
        display:none;
    }

    .topbar-center img{
        height:34px;
    }

    :root{
        --sidebar-expanded: 210px;
    }
}
</style>
</head>

<body class="<?php echo $sidebarExpanded; ?>">

<div class="sidebar-top-fill"></div>

<div class="topbar">
    <div class="topbar-left">
        <button type="button" class="topbar-menu-btn" onclick="toggleSidebar()" title="Menú">
            <i class="fa-solid fa-bars"></i>
        </button>

        <a href="/nova1/dashboard.php" class="topbar-brand">
            <span class="topbar-brand-mark"></span>
            <span class="topbar-brand-text">
                <span>
                    <span class="topbar-brand-name">NOVA</span><span class="topbar-brand-version">1.0</span>
                </span>
                <span class="topbar-brand-sub">ERP EDUCATIVO</span>
            </span>
        </a>
    </div>

    <div class="topbar-center">
        <img src="/nova1/public/images/logo.png" alt="Institución">
    </div>

    <div class="topbar-right">
        <a href="/nova1/logout.php" class="topbar-logout">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span>Salir</span>
        </a>

        <div class="topbar-user-icon">
            <i class="fa-solid fa-user"></i>
        </div>
        <div class="topbar-user-text">
            <div class="topbar-user-name"><?php echo htmlspecialchars($fullName); ?></div>
            <div class="topbar-user-role"><?php echo htmlspecialchars($roleName); ?></div>
        </div>
    </div>
</div>

<div class="context-fixed-wrap">
    <?php require_once __DIR__ . '/context_bar.php'; ?>
</div>

<div class="sidebar" id="sidebar">
    <div class="sidebar-section">

        <div class="menu-entry">
            <a href="/nova1/dashboard.php"
               class="menu-link menu-tooltip-target <?php echo $isDashboard ? 'active' : ''; ?>"
               data-title="Tablero">
                <i class="fa-solid fa-chart-line menu-icon"></i>
                <span class="menu-text">Tablero</span>
            </a>
        </div>

        <div class="menu-entry">
            <button type="button"
                    id="studentsMenuButton"
                    class="menu-toggle menu-tooltip-target <?php echo $isStudentsModule ? 'active expanded' : ''; ?>"
                    data-title="Estudiantes"
                    onclick="toggleStudentsMenu()">
                <i class="fa-solid fa-user-graduate menu-icon"></i>
                <span class="menu-text">Estudiantes</span>
                <i class="fa-solid fa-chevron-down menu-caret"></i>
            </button>

            <div id="studentsSubmenu" class="submenu <?php echo $isStudentsModule ? 'show' : ''; ?>">
                <a href="/nova1/modules/students/search.php"
                   class="submenu-link <?php echo $isStudentsSearch ? 'active' : ''; ?>">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <span>Buscar estudiantes</span>
                </a>

                <a href="/nova1/modules/students/create.php"
                   class="submenu-link <?php echo $isStudentsCreate ? 'active' : ''; ?>">
                    <i class="fa-solid fa-id-card"></i>
                    <span>Ficha estudiantil</span>
                </a>
            </div>
        </div>
        
                <div class="menu-entry">
            <a href="#"
               class="menu-link menu-tooltip-target <?php echo $isAcademicModule ? 'active' : ''; ?>"
               data-title="Académico"
               onclick="collapseSidebarMenu(event)">
                <i class="fa-solid fa-book menu-icon"></i>
                <span class="menu-text">Académico</span>
            </a>
        </div>

        <div class="menu-entry">
            <a href="#"
               class="menu-link menu-tooltip-target <?php echo $isPaymentsModule ? 'active' : ''; ?>"
               data-title="Pagos"
               onclick="collapseSidebarMenu(event)">
                <i class="fa-solid fa-dollar-sign menu-icon"></i>
                <span class="menu-text">Pagos</span>
            </a>
        </div>

        <div class="menu-entry">
            <a href="#"
               class="menu-link menu-tooltip-target <?php echo $isRegimeModule ? 'active' : ''; ?>"
               data-title="Régimen Escolar"
               onclick="collapseSidebarMenu(event)">
                <i class="fa-solid fa-school menu-icon"></i>
                <span class="menu-text">Régimen Escolar</span>
            </a>
        </div>

        <?php if ($canSeeUsersMenu): ?>
            <div class="menu-entry">
                <button type="button"
                        id="usersMenuButton"
                        class="menu-toggle menu-tooltip-target <?php echo $isUsersModule ? 'active expanded' : ''; ?>"
                        data-title="Usuarios"
                        onclick="toggleUsersMenu()">
                    <i class="fa-solid fa-users-gear menu-icon"></i>
                    <span class="menu-text">Usuarios</span>
                    <i class="fa-solid fa-chevron-down menu-caret"></i>
                </button>

                <div id="usersSubmenu" class="submenu <?php echo $isUsersModule ? 'show' : ''; ?>">
                    <a href="/nova1/modules/users/permissions.php"
                       class="submenu-link <?php echo $isUsersPermissions ? 'active' : ''; ?>">
                        <i class="fa-solid fa-lock"></i>
                        <span>Permisos</span>
                    </a>

                    <a href="/nova1/modules/users/roles.php"
                       class="submenu-link <?php echo $isUsersRoles ? 'active' : ''; ?>">
                        <i class="fa-solid fa-user-shield"></i>
                        <span>Roles</span>
                    </a>

                    <a href="/nova1/modules/users/index.php"
                       class="submenu-link <?php echo $isUsersIndex ? 'active' : ''; ?>">
                        <i class="fa-solid fa-users"></i>
                        <span>Usuarios</span>
                    </a>
                </div>
            </div>
        <?php endif; ?>

    </div>
</div>

<div class="content">

<script>
function openSidebarMenu() {
    document.body.classList.add('menu-expanded');
}

function closeSidebarMenu() {
    document.body.classList.remove('menu-expanded');
}

function toggleSidebar() {
    const isExpanded = document.body.classList.contains('menu-expanded');
    const studentsSubmenu = document.getElementById('studentsSubmenu');
    const studentsTrigger = document.getElementById('studentsMenuButton');
    const usersSubmenu = document.getElementById('usersSubmenu');
    const usersTrigger = document.getElementById('usersMenuButton');

    if (isExpanded) {
        <?php if (!$isStudentsModule && !$isUsersModule): ?>
        if (studentsSubmenu && studentsTrigger) {
            studentsSubmenu.classList.remove('show');
            studentsTrigger.classList.remove('expanded');
        }
        if (usersSubmenu && usersTrigger) {
            usersSubmenu.classList.remove('show');
            usersTrigger.classList.remove('expanded');
        }
        closeSidebarMenu();
        <?php endif; ?>
    } else {
        openSidebarMenu();
        <?php if ($isStudentsModule): ?>
        if (studentsSubmenu && studentsTrigger) {
            studentsSubmenu.classList.add('show');
            studentsTrigger.classList.add('expanded');
        }
        <?php endif; ?>
        <?php if ($isUsersModule && $canSeeUsersMenu): ?>
        if (usersSubmenu && usersTrigger) {
            usersSubmenu.classList.add('show');
            usersTrigger.classList.add('expanded');
        }
        <?php endif; ?>
    }
}

function toggleStudentsMenu() {
    const submenu = document.getElementById('studentsSubmenu');
    const trigger = document.getElementById('studentsMenuButton');
    const usersSubmenu = document.getElementById('usersSubmenu');
    const usersTrigger = document.getElementById('usersMenuButton');
    const isOpen = submenu.classList.contains('show');

    openSidebarMenu();

    if (usersSubmenu && usersTrigger) {
        usersSubmenu.classList.remove('show');
        usersTrigger.classList.remove('expanded');
    }

    if (isOpen) {
        submenu.classList.remove('show');
        trigger.classList.remove('expanded');
        <?php if (!$isStudentsModule): ?>
        closeSidebarMenu();
        <?php endif; ?>
    } else {
        submenu.classList.add('show');
        trigger.classList.add('expanded');
    }
}

function toggleUsersMenu() {
    const submenu = document.getElementById('usersSubmenu');
    const trigger = document.getElementById('usersMenuButton');
    const studentsSubmenu = document.getElementById('studentsSubmenu');
    const studentsTrigger = document.getElementById('studentsMenuButton');
    const isOpen = submenu.classList.contains('show');

    openSidebarMenu();

    if (studentsSubmenu && studentsTrigger) {
        studentsSubmenu.classList.remove('show');
        studentsTrigger.classList.remove('expanded');
    }

    if (isOpen) {
        submenu.classList.remove('show');
        trigger.classList.remove('expanded');
        <?php if (!$isUsersModule): ?>
        closeSidebarMenu();
        <?php endif; ?>
    } else {
        submenu.classList.add('show');
        trigger.classList.add('expanded');
    }
}

function collapseSidebarMenu(event) {
    const studentsSubmenu = document.getElementById('studentsSubmenu');
    const studentsTrigger = document.getElementById('studentsMenuButton');
    const usersSubmenu = document.getElementById('usersSubmenu');
    const usersTrigger = document.getElementById('usersMenuButton');

    if (studentsSubmenu && studentsTrigger) {
        studentsSubmenu.classList.remove('show');
        studentsTrigger.classList.remove('expanded');
    }

    if (usersSubmenu && usersTrigger) {
        usersSubmenu.classList.remove('show');
        usersTrigger.classList.remove('expanded');
    }

    <?php if (!$isStudentsModule && !$isUsersModule): ?>
    closeSidebarMenu();
    <?php endif; ?>

    if (event && event.currentTarget && event.currentTarget.getAttribute('href') === '#') {
        event.preventDefault();
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const studentsSubmenu = document.getElementById('studentsSubmenu');
    const studentsTrigger = document.getElementById('studentsMenuButton');
    const usersSubmenu = document.getElementById('usersSubmenu');
    const usersTrigger = document.getElementById('usersMenuButton');

    <?php if ($isStudentsModule || ($isUsersModule && $canSeeUsersMenu)): ?>
    document.body.classList.add('menu-expanded');
    <?php else: ?>
    document.body.classList.remove('menu-expanded');
    <?php endif; ?>

    <?php if ($isStudentsModule): ?>
    if (studentsSubmenu && studentsTrigger) {
        studentsSubmenu.classList.add('show');
        studentsTrigger.classList.add('expanded');
    }
    <?php else: ?>
    if (studentsSubmenu && studentsTrigger) {
        studentsSubmenu.classList.remove('show');
        studentsTrigger.classList.remove('expanded');
    }
    <?php endif; ?>

    <?php if ($isUsersModule && $canSeeUsersMenu): ?>
    if (usersSubmenu && usersTrigger) {
        usersSubmenu.classList.add('show');
        usersTrigger.classList.add('expanded');
    }
    <?php else: ?>
    if (usersSubmenu && usersTrigger) {
        usersSubmenu.classList.remove('show');
        usersTrigger.classList.remove('expanded');
    }
    <?php endif; ?>
});
</script>

