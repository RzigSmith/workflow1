<?php
require_once __DIR__ . '/../core/Flash.php';

$error   = get_flash('error');
$success = get_flash('success');

$pageTitle = 'Inscription – WorkFlow';

ob_start();
?>

<h1 class="auth-title">Créer un compte</h1>
<p class="auth-sub">Rejoignez votre espace de workflow collaboratif</p>

<?php if ($error): ?>
  <div class="alert alert-error">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
    <?= htmlspecialchars($error) ?>
  </div>
<?php endif; ?>

<form method="POST" action="../public/index.php?action=register" novalidate>

  <div class="form-row">
    <div class="form-group">
      <label for="nom">Nom</label>
      <input class="form-control" type="text" id="nom" name="nom" autocomplete="family-name"
             placeholder="Dupont" required
             value="<?= htmlspecialchars(old('nom')) ?>">
    </div>
    <div class="form-group">
      <label for="prenom">Prénom</label>
      <input class="form-control" type="text" id="prenom" name="prenom" autocomplete="given-name"
             placeholder="Marie" required
             value="<?= htmlspecialchars(old('prenom')) ?>">
    </div>
  </div>

  <div class="form-group">
    <label for="email">Adresse email</label>
    <input class="form-control" type="email" id="email" name="email" autocomplete="email"
           placeholder="vous@exemple.com" required
           value="<?= htmlspecialchars(old('email')) ?>">
  </div>

  <div class="form-group">
    <label for="password">Mot de passe</label>
    <div style="position:relative;">
      <input class="form-control" type="password" id="password" name="password"
             autocomplete="new-password" placeholder="Minimum 6 caractères" required minlength="6"
             style="padding-right:2.6rem;">
      <button type="button" data-password-toggle="password"
              style="position:absolute;right:0.7rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--text-muted);padding:2px;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
      </button>
    </div>
  </div>

  <button type="submit" class="btn btn-primary" style="margin-top:0.5rem;">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
    Créer mon compte
  </button>

</form>

<p class="auth-footer">Déjà un compte ? <a href="../views/login.php">Se connecter</a></p>

<?php clear_old(); ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout_auth.php';
