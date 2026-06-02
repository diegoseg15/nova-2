<?php

require_once __DIR__ . '/../../config/database.php';

// Validar parámetro
if (!isset($_GET['province_id']) || empty($_GET['province_id'])) {
    header('Content-Type: application/json');
    echo json_encode([]);
    exit;
}

$province_id = intval($_GET['province_id']);

$db = new Database();
$conn = $db->connect();

// Consulta segura
$sql = "SELECT id, name 
        FROM cities 
        WHERE province_id = ? 
        AND status = 'activo'
        ORDER BY name ASC";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    header('Content-Type: application/json');
    echo json_encode([]);
    exit;
}

$stmt->bind_param("i", $province_id);
$stmt->execute();

$result = $stmt->get_result();

$cantons = [];

while ($row = $result->fetch_assoc()) {
    $cantons[] = [
        'id'   => $row['id'],
        'name' => $row['name']
    ];
}

// Respuesta JSON
header('Content-Type: application/json');
echo json_encode($cantons);
exit;

