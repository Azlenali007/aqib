<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';

$rawBody = file_get_contents('php://input') ?: '';
$sig = (string) ($_SERVER['HTTP_X_RAZORPAY_SIGNATURE'] ?? '');
$res = PaymentService::handleRazorpayWebhook($rawBody, $sig);

Response::json($res, $res['success'] ? 200 : 400);
