<?php
declare(strict_types=1);

define('SMM_ROOT', dirname(__DIR__));
define('SMM_CONFIG', SMM_ROOT . '/config');
define('SMM_STORAGE', SMM_ROOT . '/storage');
define('SMM_INSTALL_LOCK', SMM_STORAGE . '/installed.lock');

if (is_file(SMM_INSTALL_LOCK)) {
    http_response_code(403);
    exit('Installer is locked. Remove storage/installed.lock only if you intentionally need to re-run installation.');
}

session_start();
$step = $_GET['step'] ?? 'requirements';
$allowed = ['requirements', 'database', 'setup', 'complete'];
if (!in_array($step, $allowed, true)) {
    $step = 'requirements';
}

function h(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }

?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Install SMM Panel Pro</title>
<style>
  :root{--p:#7045D8;--p2:#8B5CF6;--bg:#F7F5FB;--txt:#211A2D;--mut:#777083;--b:#EAE5F1}
  *{box-sizing:border-box}
  body{margin:0;font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;background:linear-gradient(160deg,#F7F5FB 0%,#EFE9FC 100%);color:var(--txt);min-height:100vh;padding:24px 16px}
  .wrap{max-width:720px;margin:0 auto;background:#fff;border:1px solid var(--b);border-radius:24px;padding:28px;box-shadow:0 20px 50px rgba(112,69,216,.1)}
  .head{display:flex;align-items:center;gap:12px;margin-bottom:24px}
  .logo{width:44px;height:44px;border-radius:14px;background:linear-gradient(135deg,#7045D8,#4C33D6);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700}
  h1{margin:0;font-size:22px}.sub{color:var(--mut);font-size:14px;margin:2px 0 0}
  .steps{display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:24px}
  .step{padding:10px;border-radius:12px;text-align:center;font-size:13px;font-weight:600;background:#F7F5FB;color:var(--mut);border:1px solid var(--b)}
  .step.active{background:linear-gradient(135deg,#7045D8,#8B5CF6);color:#fff;border-color:transparent}
  .row{display:flex;justify-content:space-between;align-items:center;padding:10px 14px;border:1px solid var(--b);border-radius:12px;margin-bottom:8px;font-size:14px}
  .ok{color:#16A36A;font-weight:700}.bad{color:#E5485D;font-weight:700}
  label{display:block;font-size:13px;font-weight:600;margin:12px 0 6px}
  input,select{width:100%;padding:11px 14px;border:1px solid var(--b);border-radius:12px;font-size:14px;outline:none}
  input:focus,select:focus{border-color:var(--p)}
  .grid2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
  @media(max-width:600px){.grid2{grid-template-columns:1fr}.steps{grid-template-columns:1fr 1fr}}
  .btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:12px 22px;border:none;border-radius:12px;background:linear-gradient(135deg,#7045D8,#5B42F3);color:#fff;font-weight:600;font-size:14px;cursor:pointer;text-decoration:none;margin-top:18px}
  .alert{padding:12px 14px;border-radius:12px;margin-bottom:16px;font-size:14px}
  .alert-err{background:#FEECEE;color:#B42318;border:1px solid #F9C6CB}
  .alert-ok{background:#EAF8F0;color:#117A4F;border:1px solid #B7E4C7}
</style>
</head>
<body>
<div class="wrap">
  <div class="head">
    <div class="logo">SMM</div>
    <div><h1>SMM Panel Pro Installer</h1><p class="sub">cPanel-Ready PHP 8.2+ &amp; MySQL Setup Wizard</p></div>
  </div>
  <div class="steps">
    <div class="step <?= $step==='requirements'?'active':'' ?>">1. Requirements</div>
    <div class="step <?= $step==='database'?'active':'' ?>">2. Database</div>
    <div class="step <?= $step==='setup'?'active':'' ?>">3. Admin &amp; Site</div>
    <div class="step <?= $step==='complete'?'active':'' ?>">4. Complete</div>
  </div>
  <?php require __DIR__ . '/' . $step . '.php'; ?>
</div>
</body>
</html>
