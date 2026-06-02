<?php

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/app/helpers/auth.php';

requireLogin();

$db = new Database();
$conn = $db->connect();

// obtener instituciones
$institutions = $conn->query("SELECT id, name FROM institutions WHERE status = 'active'");

// obtener grupos
$groups = $conn->query("SELECT id, name FROM study_groups WHERE status = 'active'");

// obtener períodos
$periods = $conn->query("SELECT id, name FROM periods WHERE status = 'open'");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $institution_id = $_POST['institution_id'];
    $group_id = $_POST['group_id'];
    $period_id = $_POST['period_id'];

    setContext($institution_id, $group_id, $period_id);

    header('Location: dashboard.php');
    exit;
}
?>

<h2>Seleccionar contexto</h2>

<form method="POST">
    <label>Institución</label><br>
    <select name="institution_id" required>
        <option value="">Seleccione</option>
        <?php while ($i = $institutions->fetch_assoc()): ?>
            <option value="<?php echo $i['id']; ?>"><?php echo $i['name']; ?></option>
        <?php endwhile; ?>
    </select>
    <br><br>

    <label>Grupo</label><br>
    <select name="group_id" required>
        <option value="">Seleccione</option>
        <?php while ($g = $groups->fetch_assoc()): ?>
            <option value="<?php echo $g['id']; ?>"><?php echo $g['name']; ?></option>
        <?php endwhile; ?>
    </select>
    <br><br>

    <label>Período</label><br>
    <select name="period_id" required>
        <option value="">Seleccione</option>
        <?php while ($p = $periods->fetch_assoc()): ?>
            <option value="<?php echo $p['id']; ?>"><?php echo $p['name']; ?></option>
        <?php endwhile; ?>
    </select>
    <br><br>

    <button type="submit">Activar</button>
</form>