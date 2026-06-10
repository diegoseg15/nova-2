<?php

require_once __DIR__ . '/app/core/bootstrap.php';
require_once app_path('helpers/auth.php');

logoutUser();

redirect_to('login.php');
