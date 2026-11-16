<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/init.php';
AdminAuth::logout();
Response::redirect('/admin/login.php');
