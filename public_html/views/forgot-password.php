<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../core/Flash.php';
$error   = get_flash('error');
$success = get_flash('success');
$pageTitle = 'Mot de passe oublié – WorkFlow';

ob_start();
?>

<div class="auth-icon-circle">
  <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="var(--primary-light)" stroke-width="1.8">
    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
    <path d="M7 11V7a5 5 0 0110 0v4"/>
  </svg>
</div>

<h1 class="auth-title" style="text-align:center;">Mot de passe oublié</h1>
<p class="auth-sub" style="text-align:center;">Entrez votre adresse e-mail. Nous vous enverrons un lien de réinitialisation.</p>

<?php if ($error): ?>
  <div class="alert alert-error">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12" y2="16"/></svg>
    <?= htmlspecialchars($error) ?>
  </div>
<?php endif; ?>
<?php if ($success): ?>
  <div class="alert alert-success">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
    <?= htmlspecialchars($success) ?>
  </div>
<?php endif; ?>

<div class="hint-box">
  💡 En mode test local (XAMPP), le lien de réinitialisation sera écrit dans <strong>uploads/email_log.txt</strong>.
</div>

<form method="POST" action="index.php?action=forgot-password" novalidate>
  <div class="form-group">
    <label for="email">Adresse e-mail</label>
    <input class="form-control" type="email" id="email" name="email" placeholder="votre@email.com" required autocomplete="email">
  </div>
  <button type="submit" class="btn btn-primary">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
      <line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>
    </svg>
    Envoyer le lien
  </button>
</form>

<p class="auth-footer"><a href="index.php?page=login">← Retour à la connexion</a></p>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout_auth.php';
