<?php
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../app/helpers/auth.php';

if (!isLoggedIn()) {
    return;
}

$db = new Database();
$conn = $db->connect();

$user_id   = (int) ($_SESSION['user_id'] ?? 0);
$role_name = $_SESSION['role_name'] ?? '';

if (!isset($_SESSION['institution_id'])) {
    $_SESSION['institution_id'] = 1;
}

function formatGroupDisplayName($groupName)
{
    $groupName = trim($groupName);

    if (preg_match('/Grupo\s*(\d+)\s*(Costa|Sierra)/i', $groupName, $matches)) {
        $number = $matches[1];
        $zone   = strtoupper(substr($matches[2], 0, 1));
        return 'Grupo ' . $number . ' ' . ($zone === 'C' ? 'Costa' : 'Sierra');
    }

    if (preg_match('/Grupo\s*(\d+)\s*([CS])/i', $groupName, $matches)) {
        $number = $matches[1];
        $zone   = strtoupper($matches[2]);
        return 'Grupo ' . $number . ' ' . ($zone === 'C' ? 'Costa' : 'Sierra');
    }

    return $groupName;
}

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['context_action'])
    && $_POST['context_action'] === 'unlock'
) {
    unlockContext();
    unset($_SESSION['selected_group_id_temp']);
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['context_action'])
    && $_POST['context_action'] === 'select_group'
) {
    $group_id = (int) ($_POST['group_id'] ?? 0);

    unset($_SESSION['period_id']);
    $_SESSION['selected_group_id_temp'] = $group_id;

    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['context_action'])
    && $_POST['context_action'] === 'change_group'
) {
    unset($_SESSION['selected_group_id_temp']);
    unset($_SESSION['period_id']);

    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['context_action'])
    && $_POST['context_action'] === 'activate'
) {
    $group_id  = (int) ($_POST['group_id'] ?? 0);
    $period_id = (int) ($_POST['period_id'] ?? 0);

    if ($group_id > 0 && $period_id > 0) {
        $_SESSION['study_group_id'] = $group_id;
        $_SESSION['period_id'] = $period_id;
        $_SESSION['institution_id'] = 1;

        lockContext();
        unset($_SESSION['selected_group_id_temp']);

        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
}

$groups = [];

if ($role_name === 'ADMIN') {
    $sql_groups = "
        SELECT id, code, name
        FROM study_groups
        WHERE status = 'active'
        ORDER BY name ASC
    ";
} else {
    $sql_groups = "
        SELECT sg.id, sg.code, sg.name
        FROM study_groups sg
        INNER JOIN user_group_assignments uga
            ON uga.study_group_id = sg.id
        WHERE uga.user_id = {$user_id}
          AND uga.status = 'active'
          AND sg.status = 'active'
        ORDER BY sg.name ASC
    ";
}

$result_groups = $conn->query($sql_groups);
if ($result_groups) {
    while ($row = $result_groups->fetch_assoc()) {
        $groups[] = $row;
    }
}

$locked_group_name  = '';
$locked_period_name = '';

if (isContextLocked()) {
    $locked_group_id  = (int) ($_SESSION['study_group_id'] ?? 0);
    $locked_period_id = (int) ($_SESSION['period_id'] ?? 0);

    if ($locked_group_id > 0) {
        $result_locked_group = $conn->query("
            SELECT name
            FROM study_groups
            WHERE id = {$locked_group_id}
            LIMIT 1
        ");
        if ($result_locked_group && $result_locked_group->num_rows > 0) {
            $locked_group_name = $result_locked_group->fetch_assoc()['name'];
        }
    }

    if ($locked_period_id > 0) {
        $result_locked_period = $conn->query("
            SELECT name
            FROM periods
            WHERE id = {$locked_period_id}
            LIMIT 1
        ");
        if ($result_locked_period && $result_locked_period->num_rows > 0) {
            $locked_period_name = $result_locked_period->fetch_assoc()['name'];
        }
    }
}

$selected_group_id_temp = (int) ($_SESSION['selected_group_id_temp'] ?? 0);

$periods = [];

if (!isContextLocked() && $selected_group_id_temp > 0) {
    $sql_periods = "
        SELECT id, code, name
        FROM periods
        WHERE study_group_id = {$selected_group_id_temp}
          AND status = 'open'
        ORDER BY start_date DESC
    ";
    $result_periods = $conn->query($sql_periods);

    if ($result_periods) {
        while ($row = $result_periods->fetch_assoc()) {
            $periods[] = $row;
        }
    }
}
?>

<style>
.nova-context-bar{
    background:#f3f4f6;
    min-height:44px;
    padding:7px 18px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:16px;
    border-bottom:1px solid #e5e7eb;
}

.nova-context-left{
    display:flex;
    align-items:center;
    gap:10px;
    flex-wrap:wrap;
}

.nova-context-label{
    font-size:12px;
    font-weight:700;
    color:#4b5563;
    padding:0 2px;
}

.nova-context-separator{
    width:1px;
    height:24px;
    background:#dcdfe4;
    margin:0 4px;
}

.nova-chip{
    min-height:30px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:8px;
    padding:0 14px;
    border-radius:8px;
    font-size:12px;
    font-weight:700;
    border:none;
    cursor:pointer;
    text-decoration:none;
    white-space:nowrap;
}

.nova-chip-blue{
    background:#dbeafe;
    color:#1d4ed8;
}

.nova-chip-green{
    background:#16a34a;
    color:#ffffff;
}

.nova-chip-green-soft{
    background:#e8f7ed;
    color:#16a34a;
    cursor:default;
}

.nova-chip-red{
    background:#ef4444;
    color:#ffffff;
}

.nova-chip-dark{
    background:#1e293b;
    color:#ffffff;
}

.nova-chip-white-red{
    background:#ffffff;
    color:#dc2626;
    border:1px solid #fecaca;
}

.nova-chip i{
    font-size:12px;
}

.nova-context-form-inline{
    display:flex;
    align-items:center;
    gap:10px;
    flex-wrap:wrap;
    margin:0;
}

.nova-context-select{
    min-width:210px;
    height:32px;
    border:1px solid #d1d5db;
    border-radius:8px;
    padding:0 10px;
    font-size:12px;
    color:#111827;
    background:#ffffff;
}

@media (max-width: 900px){
    .nova-context-bar{
        padding:8px 12px;
        align-items:flex-start;
    }

    .nova-context-left{
        width:100%;
    }
}
</style>

<div class="nova-context-bar">

    <?php if (isContextLocked()): ?>
        <div class="nova-context-left">
            <span class="nova-chip nova-chip-blue">
                <?php echo htmlspecialchars(formatGroupDisplayName($locked_group_name)); ?>
            </span>

            <span class="nova-context-separator"></span>

            <span class="nova-context-label">Período</span>
            <span class="nova-chip nova-chip-green">
                <?php echo htmlspecialchars($locked_period_name); ?>
            </span>

            <span class="nova-context-separator"></span>

            <span class="nova-chip nova-chip-green-soft">
                <i class="fa-solid fa-lock"></i>
                CONTEXTO ACTIVO
            </span>
        </div>

        <form method="POST" style="margin:0;">
            <button type="submit" name="context_action" value="unlock" class="nova-chip nova-chip-red">
                <i class="fa-solid fa-lock-open"></i>
                Desbloquear contexto
            </button>
        </form>

    <?php else: ?>

        <?php if ($selected_group_id_temp <= 0): ?>

            <div class="nova-context-left">
                <?php foreach ($groups as $group): ?>
                    <form method="POST" style="margin:0;">
                        <input type="hidden" name="group_id" value="<?php echo (int) $group['id']; ?>">
                        <button type="submit" name="context_action" value="select_group" class="nova-chip nova-chip-dark">
                            <?php echo htmlspecialchars(formatGroupDisplayName($group['name'])); ?>
                        </button>
                    </form>
                <?php endforeach; ?>
            </div>

        <?php else: ?>

            <?php
            $selected_group_name_temp = '';
            foreach ($groups as $group) {
                if ((int) $group['id'] === $selected_group_id_temp) {
                    $selected_group_name_temp = $group['name'];
                    break;
                }
            }
            ?>

            <div class="nova-context-left">
                <span class="nova-chip nova-chip-blue">
                    <?php echo htmlspecialchars(formatGroupDisplayName($selected_group_name_temp)); ?>
                </span>

                <span class="nova-context-separator"></span>

                <form method="POST" class="nova-context-form-inline">
                    <input type="hidden" name="group_id" value="<?php echo (int) $selected_group_id_temp; ?>">

                    <span class="nova-context-label">Período</span>

                    <select name="period_id" required class="nova-context-select">
                        <option value="">Seleccione período</option>
                        <?php foreach ($periods as $period): ?>
                            <option value="<?php echo (int) $period['id']; ?>">
                                <?php echo htmlspecialchars($period['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <button type="submit" name="context_action" value="activate" class="nova-chip nova-chip-blue">
                        Activar
                    </button>
                </form>

                <form method="POST" style="margin:0;">
                    <button type="submit" name="context_action" value="change_group" class="nova-chip nova-chip-dark">
                        Cambiar grupo
                    </button>
                </form>
            </div>

        <?php endif; ?>

    <?php endif; ?>

</div>