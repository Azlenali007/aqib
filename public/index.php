<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';
enforce_maintenance_mode();

$page = preg_replace('/[^a-z]/', '', strtolower((string) ($_GET['page'] ?? 'home')));
$allowed = ['home', 'services', 'faq', 'about', 'contact', 'terms', 'privacy'];
if (!in_array($page, $allowed, true)) {
    $page = 'home';
}

$siteName = (string) get_setting('site_name', 'SMM Panel');
$siteTagline = (string) get_setting('site_tagline', 'Grow Your Social Media');
$seoDesc = (string) get_setting('seo_description', 'Professional Social Media Marketing Panel');
$seoKeywords = (string) get_setting('seo_keywords', 'smm panel');
$isUserLoggedIn = Auth::check();

require __DIR__ . '/pages/' . $page . '.php';
