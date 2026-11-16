<?php
declare(strict_types=1);

// Handle currency switcher request
if (isset($_GET['set_currency'])) {
    $code = strtoupper(trim((string) $_GET['set_currency']));
    $valid = Database::fetchOne('SELECT code FROM currencies WHERE code = ? AND is_active = 1 LIMIT 1', [$code]);
    if ($valid) {
        $_SESSION['display_currency'] = $valid['code'];
        Database::execute('UPDATE users SET preferred_currency = ? WHERE id = ?', [$valid['code'], $user['id']]);
        $user['preferred_currency'] = $valid['code'];
        $ctxCheck = Currency::getDisplayContext($valid['code']);
        if (!$ctxCheck['rate_available']) {
            Session::flash('error', $ctxCheck['warning'] ?? "Exchange rate unavailable for {$valid['code']}.");
        } else {
            Session::flash('success', "Display currency switched to {$valid['code']}.");
        }
    } else {
        Session::flash('error', 'Selected currency is inactive or unsupported.');
    }

    $redirectParams = [];
    foreach ($_GET as $k => $v) {
        if ($k === 'set_currency' || $k === 'ajax' || !is_scalar($v)) {
            continue;
        }
        $cleanKey = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $k);
        if ($cleanKey !== '') {
            $redirectParams[$cleanKey] = (string) $v;
        }
    }
    $cleanPage = trim((string) ($redirectParams['page'] ?? 'dashboard'), '/');
    if (Router::resolveUserPage($cleanPage) === null) {
        $cleanPage = 'dashboard';
    }
    $redirectParams['page'] = $cleanPage ?: 'dashboard';
    Response::redirect('/user/index.php?' . http_build_query($redirectParams));
}

// Handle AJAX endpoints for User Panel (charge calculation, favourite toggle, notification read)
if (isset($_GET['ajax'])) {
    $ajax = $_GET['ajax'];
    if ($ajax === 'toggle_fav' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        Security::requireCsrf();
        $sid = (int) ($_POST['service_id'] ?? 0);
        $exists = Database::fetchOne('SELECT 1 FROM user_favourites WHERE user_id = ? AND service_id = ?', [$user['id'], $sid]);
        if ($exists) {
            Database::execute('DELETE FROM user_favourites WHERE user_id = ? AND service_id = ?', [$user['id'], $sid]);
            Response::json(['success' => true, 'favourited' => false]);
        } else {
            Database::execute('INSERT IGNORE INTO user_favourites (user_id, service_id) VALUES (?, ?)', [$user['id'], $sid]);
            Response::json(['success' => true, 'favourited' => true]);
        }
    }
    if ($ajax === 'mark_notifications_read' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        Security::requireCsrf();
        Database::execute('UPDATE notifications SET is_read = 1, read_at = NOW() WHERE user_id = ? AND is_read = 0', [$user['id']]);
        Response::json(['success' => true]);
    }
    if ($ajax === 'create_razorpay_order' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        Security::requireCsrf();
        $amount = (string) ($_POST['amount'] ?? '0');
        Response::json(PaymentService::createRazorpayDeposit((int) $user['id'], $amount));
    }
    if ($ajax === 'verify_razorpay' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        Security::requireCsrf();
        $res = PaymentService::verifyAndCreditRazorpay(
            (string) ($_POST['razorpay_order_id'] ?? ''),
            (string) ($_POST['razorpay_payment_id'] ?? ''),
            (string) ($_POST['razorpay_signature'] ?? ''),
            (int) $user['id']
        );
        Response::json($res);
    }
    Response::json(['success' => false, 'error' => 'Unknown AJAX action'], 400);
}

$page = trim((string) ($_GET['page'] ?? 'dashboard'), '/');
$targetFile = Router::resolveUserPage($page);

if ($targetFile === null) {
    http_response_code(404);
    $page = 'dashboard';
    $targetFile = Router::resolveUserPage('dashboard');
}

$displayCurrency = active_user_currency($user);
$currencyCtx = Currency::getDisplayContext($displayCurrency);
$unreadNotifications = Notification::unreadCount((int) $user['id']);
$recentNotifications = get_user_notifications((int) $user['id'], 6);
$activeCurrencies = Currency::activeCurrencies();

require $targetFile;
