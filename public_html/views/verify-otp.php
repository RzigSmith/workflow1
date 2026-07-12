<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../core/Flash.php';

$error   = get_flash('error');
$success = get_flash('success');
$email   = $_SESSION['temp_user_email'] ?? '';

if (!isset($_SESSION['temp_user_id'])) {
    header('Location: index.php?page=login');
    exit;
}
$pageTitle = 'Vérification OTP – WorkFlow';

ob_start();
?>

<div class="auth-icon-circle">
  <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="var(--primary-light)" stroke-width="1.8">
    <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/>
    <path d="M16 3h-8a2 2 0 00-2 2v2h12V5a2 2 0 00-2-2z"/>
  </svg>
</div>

<h1 class="auth-title" style="text-align:center;">Vérification e-mail</h1>
<p class="auth-sub" style="text-align:center;">
  Un code à 6 chiffres a été envoyé à<br>
  <strong style="color:var(--text);"><?= htmlspecialchars($email) ?></strong>
</p>

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
  💡 En mode test local (XAMPP), consultez le fichier <strong>uploads/email_log.txt</strong> pour retrouver votre code OTP.
</div>

<form method="POST" action="index.php?action=verify-otp" id="otp-form">
  <div class="otp-inputs" id="otp-inputs">
    <input class="otp-digit" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" id="d1" autocomplete="off">
    <input class="otp-digit" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" id="d2" autocomplete="off">
    <input class="otp-digit" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" id="d3" autocomplete="off">
    <input class="otp-digit" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" id="d4" autocomplete="off">
    <input class="otp-digit" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" id="d5" autocomplete="off">
    <input class="otp-digit" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" id="d6" autocomplete="off">
  </div>
  <input type="hidden" name="otp_code" id="otp_code_hidden">
  <button type="submit" class="btn btn-primary" id="verify-btn">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
    Valider mon compte
  </button>
</form>

<p class="auth-footer" style="margin-top:1rem;">
  Vous n'avez pas reçu le code ? <a href="index.php?action=resend-otp">Renvoyer</a>
</p>
<p class="auth-footer"><a href="index.php?page=login">← Retour à la connexion</a></p>

<?php
$content = ob_get_clean();
$pageScripts = <<<'JS'
(function () {
  const digits = ['d1','d2','d3','d4','d5','d6'].map(id => document.getElementById(id));
  const hidden = document.getElementById('otp_code_hidden');
  const form   = document.getElementById('otp-form');

  digits.forEach((input, i) => {
    input.addEventListener('input', () => {
      const val = input.value.replace(/\D/g, '');
      input.value = val;
      if (val && i < digits.length - 1) digits[i + 1].focus();
      updateHidden();
    });
    input.addEventListener('keydown', (e) => {
      if (e.key === 'Backspace' && !input.value && i > 0) {
        digits[i - 1].focus();
        digits[i - 1].value = '';
        updateHidden();
      }
    });
    input.addEventListener('paste', (e) => {
      e.preventDefault();
      const paste = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g,'').slice(0,6);
      paste.split('').forEach((char, j) => { if (digits[j]) digits[j].value = char; });
      digits[Math.min(paste.length, 5)].focus();
      updateHidden();
    });
  });

  function updateHidden() {
    hidden.value = digits.map(d => d.value).join('');
  }

  digits[0].focus();

  form.addEventListener('submit', (e) => {
    updateHidden();
    if (hidden.value.length !== 6) {
      e.preventDefault();
      digits[0].focus();
      if (window.WorkFlow?.toast) WorkFlow.toast.show('Veuillez saisir les 6 chiffres du code.', 'error');
    }
  });
})();
JS;
require __DIR__ . '/layout_auth.php';
