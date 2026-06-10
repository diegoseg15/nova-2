<?php

require_once __DIR__ . '/app/core/bootstrap.php';

requireLogin();

require_once view_path('partials/header.php');
?>

<h1>Dashboard</h1>

<p>Bienvenido: <?php echo e(currentUserName()); ?></p>

<?php
require_once view_path('partials/footer.php');
