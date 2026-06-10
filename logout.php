<?php

require_once __DIR__ . '/app/core/bootstrap.php';

logoutUser();

redirect_to('login.php');
