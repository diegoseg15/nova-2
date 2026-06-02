<?php

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/app/helpers/auth.php';

requireLogin();
// requireContext();

require_once __DIR__ . '/app/views/partials/header.php';
?>

<h1>Dashboard</h1>

<p>Bienvenido: <?php echo htmlspecialchars($_SESSION['full_name']); ?></p>

<?php
require_once __DIR__ . '/app/views/partials/footer.php';

