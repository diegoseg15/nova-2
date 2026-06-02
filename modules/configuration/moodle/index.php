<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once $_SERVER['DOCUMENT_ROOT'] . '/nova1/app/services/MoodleService.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/nova1/app/views/partials/header.php';

$db = new Database();
$conn = $db->connect();

$sql = "SELECT mc.id,
               mc.moodle_url,
               mc.api_token,
               mc.institution_id,
               mc.group_id,
               i.name AS institution_name,
               g.name AS group_name
        FROM moodle_configurations mc
        INNER JOIN institutions i ON i.id = mc.institution_id
        INNER JOIN study_groups g ON g.id = mc.group_id
        ORDER BY mc.id DESC";

$result = $conn->query($sql);
?>

<style>
    .module-card {
        background: #f3f4f6;
        border-radius: 14px;
        padding: 14px;
    }

    .module-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 14px;
    }

    .module-title h1 {
        margin: 0;
        font-size: 20px;
        color: #0f172a;
        font-weight: 700;
    }

    .module-title p {
        margin: 4px 0 0 0;
        color: #64748b;
        font-size: 13px;
    }

    .btn-primary {
        background: #2563eb;
        color: #fff;
        padding: 10px 16px;
        border-radius: 10px;
        text-decoration: none;
        font-size: 14px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .btn-primary:hover {
        background: #1d4ed8;
    }

    .table-wrapper {
        background: #ffffff;
        border-radius: 14px;
        overflow: hidden;
        border: 1px solid #dbe2ea;
    }

    .nova-table {
        width: 100%;
        border-collapse: collapse;
    }

    .nova-table thead {
        background: #e5e7eb;
    }

    .nova-table th {
        padding: 14px;
        text-align: left;
        font-size: 14px;
        color: #1e293b;
        font-weight: 700;
    }

    .nova-table td {
        padding: 14px;
        font-size: 14px;
        color: #111827;
        border-top: 1px solid #edf0f2;
    }

    .badge-active {
        background: #d1fae5;
        color: #047857;
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 700;
        display: inline-block;
    }

    .badge-inactive {
        background: #fee2e2;
        color: #b91c1c;
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 700;
        display: inline-block;
    }

    .btn-edit {
        background: #0f172a;
        color: #fff;
        padding: 7px 14px;
        border-radius: 9px;
        text-decoration: none;
        font-size: 13px;
        font-weight: 700;
        margin-right: 5px;
    }

    .btn-delete {
        background: #dc2626;
        color: #fff;
        padding: 7px 12px;
        border-radius: 9px;
        text-decoration: none;
        font-size: 13px;
        font-weight: 700;
    }

    .token-mask {
        color: #64748b;
        font-family: monospace;
    }
</style>

<div class="module-card">

    <div class="module-header">
        <div class="module-title">
            <h1>Configuración Moodle</h1>
            <p>Administración de tokens API Moodle por institución y grupo académico</p>
        </div>

        <a href="create.php" class="btn-primary">
            <i class="fa-solid fa-plus"></i> Nueva configuración
        </a>
    </div>

    <div class="table-wrapper">
        <table class="nova-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Institución</th>
                    <th>Grupo</th>
                    <th>URL Moodle</th>
                    <th>Token API</th>
                    <th>Conexión API</th>
                    <th>Acciones</th>
                </tr>
            </thead>

            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo htmlspecialchars($row['institution_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['group_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['moodle_url']); ?></td>
                            <td class="token-mask">••••••••••••••••••••••</td>
                            <?php
                            $apiStatus = MoodleService::getApiStatusByConfiguration(
                                $conn,
                                (int)$row['institution_id'],
                                (int)$row['group_id']
                            );
                            ?>

                            <td>
                                <?php if ($apiStatus['ok']): ?>
                                    <span class="badge-active">
                                        <?php echo htmlspecialchars($apiStatus['message']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge-inactive">
                                        <?php echo htmlspecialchars($apiStatus['message']); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="edit.php?id=<?php echo $row['id']; ?>" class="btn-edit">Editar</a>
                                <a href="delete.php?id=<?php echo $row['id']; ?>" class="btn-delete" onclick="return confirm('¿Eliminar configuración?')">X</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align:center;padding:30px;color:#64748b;">
                            No existen configuraciones Moodle registradas.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

</div>
</body>

</html>