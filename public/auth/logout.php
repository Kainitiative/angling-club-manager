<?php
declare(strict_types=1);

require __DIR__ . '/../../app/bootstrap.php';

session_unset();
session_destroy();

redirect('/public/auth/login.php');
