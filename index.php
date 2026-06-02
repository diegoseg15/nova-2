<?php

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';

$db = new Database();
$conn = $db->connect();

if (!$conn) {
    die('Error de conexión al sistema.');
}

echo '<h1>' . APP_NAME . '</h1>';
echo '<p>Sistema base operativo.</p>';
echo '<p>Conexión a base de datos: OK</p>';