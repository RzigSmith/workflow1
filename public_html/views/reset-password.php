<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../core/Flash.php';
$error   = get_flash('error');
$success = get_flash('success');
$token   = trim($_GET['token'] ?? '');
if ($token === '') {
    set_flash('error', 'Token de récupération manquant ou invalide.');
    header('Location: index.php?page=login');
    exit;
}
$pageTitle = 'Nouveau mot de passe – WorkFlow';

ob_start();
?>

<div class="auth-icon-circle success">
  <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="var(--success)" stroke-width="1.8">
    <polyline points="20 6 9 17 4 12"/>
  </svg>
</div>

<h1 class="auth-title" style="text-align:center;">Nouveau mot de passe</h1>
<p class="auth-sub" style="text-align:center;">Choisissez un mot de passe sécurisé d'au moins 6 caractères.</p>

<?php if ($error): ?>
  <div class="alert alert-error">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/></svg>
    <?= htmlspecialchars($error) ?>
  </div>
<?php endif; ?>
<?php if ($success): ?>
  <div class="alert alert-success">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
    <?= htmlspecialchars($success) ?>
  </div>
<?php endif; ?>

<form method="POST" action="index.php?action=reset-password" novalidate id="reset-form">
  <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

  <div class="form-group">
    <label for="password">Nouveau mot de passe</label>
    <div style="position:relative;">
      <input class="form-control" type="password" id="password" name="password" placeholder="Au moins 6 caractères" required autocomplete="new-password" oninput="checkStrength(this.value)" style="padding-right:2.6rem;">
      <button type="button" data-password-toggle="password" style="position:absolute;right:0.7rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--text-muted);padding:2px;">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
      </button>
    </div>
    <div class="strength-bar"><div class="strength-fill" id="strength-fill"></div></div>
    <div class="strength-label" id="strength-label"></div>
  </div>

  <div class="form-group">
    <label for="confirm_password">Confirmer le mot de passe</label>
    <div style="position:relative;">
      <input class="form-control" type="password" id="confirm_password" name="confirm_password" placeholder="Répétez le mot de passe" required autocomplete="new-password" style="padding-right:2.6rem;">
      <button type="button" data-password-toggle="confirm_password" style="position:absolute;right:0.7rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--text-muted);padding:2px;">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
      </button>
    </div>
  </div>

  <button type="submit" class="btn btn-primary">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
    Réinitialiser
  </button>
</form>

<p class="auth-footer"><a href="index.php?page=login">← Retour à la connexion</a></p>

<?php
$content = ob_get_clean();
$pageScripts = <<<'JS'
function checkStrength(pw) {
  const fill  = document.getElementById('strength-fill');
  const label = document.getElementById('strength-label');
  let score = 0;
  if (pw.length >= 6) score++;
  if (pw.length >= 10) score++;
  if (/[A-Z]/.test(pw)) score++;
  if (/[0-9]/.test(pw)) score++;
  if (/[^A-Za-z0-9]/.test(pw)) score++;
  const configs = [
    {w: 0, bg: 'transparent', txt: ''},
    {w: 20, bg: '#ef4444', txt: 'Très faible'},
    {w: 40, bg: '#f97316', txt: 'Faible'},
    {w: 60, bg: '#eab308', txt: 'Moyen'},
    {w: 80, bg: '#22c55e', txt: 'Bon'},
    {w: 100, bg: '#10b981', txt: 'Excellent'},
  ];
  const c = configs[score] || configs[0];
  fill.style.width = c.w + '%';
  fill.style.background = c.bg;
  label.textContent = c.txt;
  label.style.color = c.bg;
}
document.getElementById('reset-form').addEventListener('submit', function(e) {
  const pw = document.getElementById('password').value;
  const cp = document.getElementById('confirm_password').value;
  if (pw !== cp) {
    e.preventDefault();
    if (window.WorkFlow?.toast) WorkFlow.toast.show('Les mots de passe ne correspondent pas.', 'error');
    else alert('Les mots de passe ne correspondent pas.');
  }
});
JS;
require __DIR__ . '/layout_auth.php';
