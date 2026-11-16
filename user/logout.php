<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';
Auth::logout();
Response::redirect('/user/login.php');
