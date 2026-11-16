<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/init.php';
$admin = AdminAuth::requireAuth('website.manage');
$adminTitle = 'Public Website & SEO Management';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::requireCsrf();
    $faqJson = trim((string) ($_POST['faq_json'] ?? '[]'));
    $decoded = json_decode($faqJson, true);
    if (!is_array($decoded)) {
        Session::flash('error', 'FAQ JSON is invalid JSON array.');
        Response::redirect('/admin/website/index.php');
    }

    set_setting('hero_title', trim((string) ($_POST['hero_title'] ?? '')), 'website');
    set_setting('hero_subtitle', trim((string) ($_POST['hero_subtitle'] ?? '')), 'website');
    set_setting('about_content', trim((string) ($_POST['about_content'] ?? '')), 'website');
    set_setting('terms_content', trim((string) ($_POST['terms_content'] ?? '')), 'website');
    set_setting('privacy_content', trim((string) ($_POST['privacy_content'] ?? '')), 'website');
    set_setting('faq_json', json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), 'website');
    set_setting('seo_description', trim((string) ($_POST['seo_description'] ?? '')), 'website');
    set_setting('seo_keywords', trim((string) ($_POST['seo_keywords'] ?? '')), 'website');
    set_setting('support_email', trim((string) ($_POST['support_email'] ?? '')), 'general');
    set_setting('whatsapp_number', trim((string) ($_POST['whatsapp_number'] ?? '')), 'general');
    set_setting('telegram_username', trim((string) ($_POST['telegram_username'] ?? '')), 'general');

    Logger::activity('admin', (int) $admin['id'], 'website.update');
    Session::flash('success', 'Public website pages, contact info, and SEO settings saved.');
    Response::redirect('/admin/website/index.php');
}

require dirname(__DIR__) . '/layouts/header.php';
?>
<div class="adm-card">
  <form method="post">
    <?= csrf_field() ?>
    <div class="grid-2">
      <div class="form-group"><label class="form-label">Hero Title</label><input class="form-control" name="hero_title" value="<?= e((string) get_setting('hero_title', '')) ?>" required></div>
      <div class="form-group"><label class="form-label">Support Email</label><input class="form-control" type="email" name="support_email" value="<?= e((string) get_setting('support_email', '')) ?>"></div>
      <div class="form-group"><label class="form-label">WhatsApp Number (with country code)</label><input class="form-control" name="whatsapp_number" value="<?= e((string) get_setting('whatsapp_number', '')) ?>"></div>
      <div class="form-group"><label class="form-label">Telegram Username</label><input class="form-control" name="telegram_username" value="<?= e((string) get_setting('telegram_username', '')) ?>"></div>
    </div>
    <div class="form-group"><label class="form-label">Hero Subtitle</label><textarea class="form-control" rows="2" name="hero_subtitle"><?= e((string) get_setting('hero_subtitle', '')) ?></textarea></div>
    <div class="form-group"><label class="form-label">About Us Page Content</label><textarea class="form-control" rows="4" name="about_content"><?= e((string) get_setting('about_content', '')) ?></textarea></div>
    <div class="form-group"><label class="form-label">FAQ Items (JSON Array of {"q":"...","a":"..."})</label><textarea class="form-control" rows="7" name="faq_json" style="font-family:monospace;font-size:12.5px"><?= e((string) get_setting('faq_json', '[]')) ?></textarea></div>
    <div class="grid-2">
      <div class="form-group"><label class="form-label">Terms of Service Content</label><textarea class="form-control" rows="5" name="terms_content"><?= e((string) get_setting('terms_content', '')) ?></textarea></div>
      <div class="form-group"><label class="form-label">Privacy Policy Content</label><textarea class="form-control" rows="5" name="privacy_content"><?= e((string) get_setting('privacy_content', '')) ?></textarea></div>
    </div>
    <div class="grid-2">
      <div class="form-group"><label class="form-label">SEO Meta Description</label><input class="form-control" name="seo_description" value="<?= e((string) get_setting('seo_description', '')) ?>"></div>
      <div class="form-group"><label class="form-label">SEO Meta Keywords</label><input class="form-control" name="seo_keywords" value="<?= e((string) get_setting('seo_keywords', '')) ?>"></div>
    </div>
    <button class="btn btn-primary" type="submit">Save Public Website Content</button>
    <a class="btn btn-outline" target="_blank" href="/">Preview Public Website</a>
  </form>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
