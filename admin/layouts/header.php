<?php
declare(strict_types=1);
$siteName = (string) get_setting('site_name', 'SMM Panel');
$adminTitle = $adminTitle ?? 'Admin Dashboard';
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= e($adminTitle) ?> — Admin | <?= e($siteName) ?></title>
  <link rel="icon" href="/favicon.ico">
  <link rel="stylesheet" href="/admin/assets/css/admin.css">
</head>
<body>
<div class="adm-shell">
<?php require __DIR__ . '/sidebar.php'; ?>
<div class="adm-main">
<?php require __DIR__ . '/topbar.php'; ?>
<div class="adm-content">
<?php require dirname(__DIR__) . '/components/alerts.php'; ?>
