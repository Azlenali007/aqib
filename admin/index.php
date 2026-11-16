<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/init.php';
if (AdminAuth::check()) {
    Response::redirect('/admin/dashboard.php');
}
Response::redirect('/admin/login.php');
