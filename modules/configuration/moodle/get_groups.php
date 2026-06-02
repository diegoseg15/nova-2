<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/nova1/config/database.php';

$db = new Database();
$conn = $db->connect();

$institutionId = intval($_GET['institution_id']);

$result = $conn->query("SELECT id, name FROM study_groups 
                        WHERE institution_id = $institutionId 
                        AND status='active'
                        ORDER BY name ASC");

echo '<option value="">Seleccione</option>';

while($row = $result->fetch_assoc()){
    echo '<option value="'.$row['id'].'">'.$row['name'].'</option>';
}
?>