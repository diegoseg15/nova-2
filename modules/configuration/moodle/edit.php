<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once $_SERVER['DOCUMENT_ROOT'] . '/nova1/app/views/partials/header.php';

$db = new Database();
$conn = $db->connect();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $conn->prepare("SELECT * FROM moodle_configurations WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$config = $stmt->get_result()->fetch_assoc();

if(!$config){
    die("Configuración no encontrada.");
}

$institutions = $conn->query("SELECT id, name FROM institutions WHERE status='active' ORDER BY name ASC");

$groups = $conn->query("SELECT id, name FROM study_groups 
                        WHERE institution_id = {$config['institution_id']}
                        AND status='active'
                        ORDER BY name ASC");
?>

<style>
.form-card{
    background:#f3f4f6;
    border-radius:14px;
    padding:14px;
}
.form-box{
    background:#fff;
    border-radius:14px;
    padding:25px;
    border:1px solid #dbe2ea;
}
.form-title h1{
    margin:0;
    font-size:20px;
    color:#0f172a;
}
.form-title p{
    margin:4px 0 20px;
    color:#64748b;
    font-size:13px;
}
.form-row{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:18px;
    margin-bottom:18px;
}
.form-group label{
    display:block;
    margin-bottom:7px;
    font-size:13px;
    font-weight:700;
    color:#334155;
}
.form-group input,
.form-group select,
.form-group textarea{
    width:100%;
    padding:11px 12px;
    border:1px solid #cbd5e1;
    border-radius:10px;
    font-size:14px;
}
.btn-save{
    background:#2563eb;
    color:#fff;
    border:none;
    padding:11px 20px;
    border-radius:10px;
    font-weight:700;
    cursor:pointer;
}
.btn-back{
    background:#0f172a;
    color:#fff;
    text-decoration:none;
    padding:11px 20px;
    border-radius:10px;
    font-weight:700;
}
</style>

<div class="form-card">
    <div class="form-box">
        <div class="form-title">
            <h1>Editar Configuración Moodle</h1>
            <p>Actualización de parámetros de conexión Moodle</p>
        </div>

        <form action="update.php" method="POST">

            <input type="hidden" name="id" value="<?php echo $config['id']; ?>">

            <div class="form-row">
                <div class="form-group">
                    <label>Institución</label>
                    <select name="institution_id" id="institution_id" required onchange="loadGroups(this.value)">
                        <?php while($ins = $institutions->fetch_assoc()): ?>
                            <option value="<?php echo $ins['id']; ?>" <?php echo ($ins['id']==$config['institution_id'])?'selected':''; ?>>
                                <?php echo $ins['name']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Grupo</label>
                    <select name="group_id" id="group_id" required>
                        <?php while($grp = $groups->fetch_assoc()): ?>
                            <option value="<?php echo $grp['id']; ?>" <?php echo ($grp['id']==$config['group_id'])?'selected':''; ?>>
                                <?php echo $grp['name']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>URL Moodle</label>
                    <input type="text" name="moodle_url" value="<?php echo htmlspecialchars($config['moodle_url']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Estado</label>
                    <select name="status">
                        <option value="active" <?php echo ($config['status']=='active')?'selected':''; ?>>ACTIVA</option>
                        <option value="inactive" <?php echo ($config['status']=='inactive')?'selected':''; ?>>INACTIVA</option>
                    </select>
                </div>
            </div>

            <div class="form-group" style="margin-bottom:20px;">
                <label>Nuevo Token API Moodle</label>
                <textarea name="api_token" rows="4" placeholder="Dejar vacío para conservar el token actual"></textarea>
            </div>

            <a href="index.php" class="btn-back">Volver</a>
            <button type="submit" class="btn-save">Actualizar Configuración</button>

        </form>
    </div>
</div>

<script>
function loadGroups(institutionId){
    const xhr = new XMLHttpRequest();
    xhr.open("GET","get_groups.php?institution_id="+institutionId,true);
    xhr.onload = function(){
        document.getElementById("group_id").innerHTML = this.responseText;
    };
    xhr.send();
}
</script>

</div>
</body>
</html>