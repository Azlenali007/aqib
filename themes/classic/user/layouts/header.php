<?php
declare(strict_types=1);
$siteName = (string) get_setting('site_name', 'SMM Panel');
$siteTagline = (string) get_setting('site_tagline', 'Grow Your Social Media');
$pageTitle = $pageTitle ?? 'Dashboard';
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle) ?> — <?= e($siteName) ?></title>
  <link rel="icon" href="/favicon.ico">
  <link rel="stylesheet" href="/themes/classic/user/assets/css/classic.css">
</head>
<body>
<div class="app-shell">
<?php require __DIR__ . '/sidebar.php'; ?>
<div class="main-area">
<?php require __DIR__ . '/navbar.php'; ?>
<main class="page-wrap">
<?php require dirname(__DIR__) . '/components/alerts.php'; ?>
