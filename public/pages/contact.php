<?php
declare(strict_types=1);
$msgOk = '';
$msgErr = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::requireCsrf('contact');
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $message = trim((string) ($_POST['message'] ?? ''));
    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $message === '') {
        $msgErr = 'Please enter your name, valid email, and message.';
    } else {
        $supportEmail = (string) get_setting('support_email', 'support@example.com');
        $res = Mailer::send($supportEmail, "Contact Form from $name", '<p><strong>From:</strong> ' . e($name) . ' (' . e($email) . ')</p><p>' . nl2br(e($message)) . '</p>');
        Logger::activity('system', null, 'public.contact_submit', null, null, ['email' => $email, 'mail_sent' => $res['success']]);
        $msgOk = 'Thank you! Your inquiry has been received by our support team.';
    }
}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Contact — <?= e($siteName) ?></title><link rel="stylesheet" href="/public/assets/css/public.css"></head><body>
<?php require __DIR__ . '/_header.php'; ?>
<div class="container" style="padding-top:28px;max-width:680px">
  <div class="card">
    <h1 style="margin:0 0 10px">Contact Support</h1>
    <p style="color:#777083;font-size:14px;margin:0 0 16px">Email: <?= e((string) get_setting('support_email', '')) ?></p>
    <?php if ($msgOk): ?><div style="padding:12px;border-radius:12px;background:#E9F8EF;color:#127048;margin-bottom:12px"><?= e($msgOk) ?></div><?php endif; ?>
    <?php if ($msgErr): ?><div style="padding:12px;border-radius:12px;background:#FEECEE;color:#B42318;margin-bottom:12px"><?= e($msgErr) ?></div><?php endif; ?>
    <form method="post">
      <?= csrf_field('contact') ?>
      <div style="margin-bottom:12px"><label style="display:block;font-weight:700;font-size:13px;margin-bottom:5px">Your Name</label><input name="name" required style="width:100%;padding:11px;border:1px solid #EAE5F1;border-radius:12px"></div>
      <div style="margin-bottom:12px"><label style="display:block;font-weight:700;font-size:13px;margin-bottom:5px">Email Address</label><input type="email" name="email" required style="width:100%;padding:11px;border:1px solid #EAE5F1;border-radius:12px"></div>
      <div style="margin-bottom:14px"><label style="display:block;font-weight:700;font-size:13px;margin-bottom:5px">Message</label><textarea name="message" rows="5" required style="width:100%;padding:11px;border:1px solid #EAE5F1;border-radius:12px"></textarea></div>
      <button class="btn btn-primary" type="submit">Send Message</button>
    </form>
  </div>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
</body></html>
