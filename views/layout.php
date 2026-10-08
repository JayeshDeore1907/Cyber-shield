<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$title = $title ?? 'CyberShield';
$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($title) ?> | CyberShield</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header class="site-header">
  <div class="container nav-wrap">
    <a class="brand" href="index.php"><span class="brand-mark">🛡</span> CyberShield</a>
    <button class="nav-toggle" id="navToggle" aria-label="Open menu">☰</button>
    <nav id="mainNav">
      <a href="index.php">Home</a><a href="index.php?page=checker">Scam Checker</a><a href="index.php?page=deepfake">Deepfake Guide</a><a href="index.php?page=quiz">Quiz</a><a href="index.php?page=report">Report Scam</a><a href="index.php?page=track">Track</a>
      <?php if (!empty($_SESSION['admin'])): ?><a href="index.php?page=admin">Admin</a><a href="index.php?page=logout">Logout</a><?php else: ?><a class="nav-admin" href="index.php?page=admin-login">Admin</a><?php endif; ?>
    </nav>
  </div>
</header>
<main>
<?php if ($flash): ?><div class="container"><div class="alert <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div></div><?php endif; ?>
<?= $content ?>
</main>
<footer class="footer"><div class="container footer-grid"><div><strong>CyberShield</strong><p>Awareness-first tools for the AI-era web.</p></div><div><strong>Emergency reference</strong><p>For cyber-fraud support in India, use the official National Cyber Crime Reporting Portal and helpline 1930.</p></div><div><strong>Built with</strong><p>HTML · CSS · JavaScript · PHP · Laravel source · XML</p></div></div></footer>
<script src="assets/js/app.js"></script>
</body></html>
