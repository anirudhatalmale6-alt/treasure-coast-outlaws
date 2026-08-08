<?php require_once __DIR__ . '/functions.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= isset($pageTitle) ? e($pageTitle) . ' — ' : '' ?><?= e(SITE_NAME) ?></title>
<meta name="description" content="<?= e(SITE_NAME) ?> — <?= e(SITE_TAGLINE) ?>. News, photos and videos from the team.">
<link rel="icon" type="image/png" href="assets/img/logo.png">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Oswald:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<nav class="nav">
  <div class="wrap">
    <a class="brand" href="index.php">
      <img src="assets/img/logo.png" alt="<?= e(SITE_NAME) ?> logo">
      <span class="brand-txt">
        <b><?= e(SITE_NAME) ?></b>
        <i><?= e(SITE_TAGLINE) ?></i>
      </span>
    </a>
    <button class="nav-toggle" aria-label="Menu" onclick="document.getElementById('navLinks').classList.toggle('open')">&#9776;</button>
    <div class="nav-links" id="navLinks">
      <a href="index.php#top">Home</a>
      <a href="index.php#news">News</a>
      <a href="index.php#photos">Photos</a>
      <a href="index.php#videos">Videos</a>
      <a class="btn btn-primary btn-sm" href="admin.php">Admin</a>
    </div>
  </div>
</nav>
