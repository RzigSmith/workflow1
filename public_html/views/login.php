<?php
require_once __DIR__ . '/../core/Flash.php';

$error   = get_flash('error');
$success = get_flash('success');

$pageTitle = 'Connexion – WorkFlow';

ob_start();
?>

<h1 class="auth-title">Bon retour 👋</h1>
<p class="auth-sub">Connectez-vous à votre espace de travail</p>

<?php if ($error): ?>
  <div class="alert alert-error">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
    <?= htmlspecialchars($error) ?>
  </div>
<?php endif; ?>

<?php if ($success): ?>
  <div class="alert alert-success">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
    <?= htmlspecialchars($success) ?>
  </div>
<?php endif; ?>

<form method="POST" action="index.php?action=login" novalidate>

  <div class="form-group">
    <label for="email">Adresse email</label>
    <input class="form-control" type="email" id="email" name="email" autocomplete="email"
           placeholder="vous@exemple.com" required
           value="<?= htmlspecialchars(old('email')) ?>">
  </div>

  <div class="form-group">
    <label for="password" style="display:flex;justify-content:space-between;">
      <span>Mot de passe</span>
      <a href="index.php?page=forgot-password" style="font-size:0.8rem;color:var(--primary);text-decoration:none;">Mot de passe oublié ?</a>
    </label>
    <div style="position:relative;">
      <input class="form-control" type="password" id="password" name="password"
             autocomplete="current-password" placeholder="••••••••" required
             style="padding-right:2.6rem;">
      <button type="button" data-password-toggle="password"
              style="position:absolute;right:0.7rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--text-muted);padding:2px;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
      </button>
    </div>
  </div>

  <button type="submit" class="btn btn-primary" style="margin-top:0.5rem;">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
    Se connecter
  </button>

</form>

<p class="auth-footer">Pas encore de compte ? <a href="index.php?page=register">S'inscrire</a></p>

<?php clear_old(); ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout_auth.php';
