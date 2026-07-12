<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle ?? 'WorkFlow') ?></title>
  <script>
    (function(){var t=localStorage.getItem('wf-theme')||(matchMedia('(prefers-color-scheme:dark)').matches?'dark':'light');document.documentElement.setAttribute('data-theme',t);})();
  </script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/theme.css">
  <style>
    .auth-theme-btn {
      position: fixed; top: 1.25rem; right: 1.25rem; z-index: 10;
    }
  </style>
</head>
<body>
<div id="toast-container"></div>
<div id="nprogress-bar"></div>

<button class="theme-toggle auth-theme-btn" data-theme-toggle type="button" title="Mode clair / sombre" aria-label="Changer le thème" aria-pressed="false">
  <svg class="icon-sun" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
  <svg class="icon-moon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/></svg>
</button>

<div class="auth-wrapper">
  <div class="auth-card">
    <div class="auth-logo">
      <div class="logo-icon">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>
        </svg>
      </div>
      <span>WorkFlow</span>
    </div>
    <?= $content ?>
  </div>
</div>

<script src="assets/js/app.js"></script>
<?php if (!empty($pageScripts)): ?>
<script><?= $pageScripts ?></script>
<?php endif; ?>
</body>
</html>
