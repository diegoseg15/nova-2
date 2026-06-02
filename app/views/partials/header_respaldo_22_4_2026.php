<?php

// barra superior de men��, logo y usuario

// sesión ya debe estar iniciada antes de cargar este header
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>NOVA 1.0</title>

<!-- ICONOS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>

/* RESET */
body {
    margin: 0;
    font-family: Arial, sans-serif;
}

/* BARRA SUPERIOR */
.topbar {
    height: 52px;
    background: #1e293b;
    color: white;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 15px;
    position: relative;
}

/* BOTÓN MENÚ */
.menu-btn {
    font-size: 22px;
    cursor: pointer;
}

/* LOGO CENTRADO */
.topbar-center {
    position: absolute;
    left: 50%;
    transform: translateX(-50%);
}

.topbar-center img {
    height: 48px;
}

/* USUARIO DERECHA */
.topbar-right {
    display: flex;
    align-items: center;
}

/* USER MENU */
.user-menu {
    position: relative;
}

.user-icon {
    font-size: 24px;
    cursor: pointer;
    color: white;
}

.user-dropdown {
    display: none;
    position: absolute;
    right: 0;
    top: 36px;
    background: white;
    min-width: 140px;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    z-index: 999;
}

.user-dropdown a {
    display: block;
    padding: 10px;
    color: #111827;
    text-decoration: none;
}

.user-dropdown a:hover {
    background: #f3f4f6;
}

/* CONTENEDOR */
.container {
    display: flex;
}

/* SIDEBAR */
.sidebar {
    width: 0;
    overflow: hidden;
    background: #0f172a;
    color: white;
    transition: width 0.25s ease;
    min-height: calc(100vh - 52px);
    box-sizing: border-box;
    padding: 10px 0;
}

.sidebar.open {
    width: 240px;
    padding: 10px;
}

.sidebar h3 {
    margin-top: 0;
}

/* MENU ITEMS */
.menu-item {
    display: flex;
    align-items: center;
    gap: 10px;
    color: white;
    padding: 10px 14px;
    text-decoration: none;
    border-radius: 10px;
    margin-bottom: 6px;
    transition: all 0.2s ease;
}

.menu-item i {
    width: 20px;
    text-align: center;
}

.menu-item:hover {
    background: #1e293b;
}

/* ACTIVO */
.menu-item.active {
    background: linear-gradient(90deg, #2563eb, #1d4ed8);
    color: #ffffff;
    font-weight: 600;
    border-radius: 10px;
    margin-right: 10px;
    margin-left: 5px;
    padding-left: 12px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.2);
}

/* CONTENIDO */
.content {
    flex: 1;
    padding: 20px;
}

</style>
</head>

<body>

<!-- TOPBAR -->
<div class="topbar">

    <!-- BOTÓN -->
    <div class="menu-btn" onclick="toggleMenu()">
        <i class="fa-solid fa-bars"></i>
    </div>

    <!-- LOGO -->
    <div class="topbar-center">
        <img src="/nova1/public/images/logo.png">
    </div>

    <!-- USER -->
    <div class="topbar-right">
        <div class="user-menu">
            <i class="fa-solid fa-user-circle user-icon" onclick="toggleUserMenu()"></i>

            <div class="user-dropdown" id="userDropdown">
                <a href="#">Perfil</a>
                <a href="/nova1/logout.php">Salir</a>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/context_bar.php'; ?>

<!-- CONTENEDOR -->
<div class="container">

<!-- SIDEBAR -->
<div class="sidebar" id="sidebar">
    <h3>Menu</h3>

    <a href="/nova1/dashboard.php" class="menu-item active">
        <i class="fa-solid fa-chart-line"></i> Tablero
    </a>

    <a href="#" class="menu-item">
        <i class="fa-solid fa-user-graduate"></i> Estudiantes
    </a>

    <a href="#" class="menu-item">
        <i class="fa-solid fa-book"></i> Academico
    </a>

    <a href="#" class="menu-item">
        <i class="fa-solid fa-dollar-sign"></i> Pagos
    </a>

    <a href="#" class="menu-item">
        <i class="fa-solid fa-school"></i> Regimen Escolar
    </a>
</div>

<!-- CONTENIDO -->
<div class="content">