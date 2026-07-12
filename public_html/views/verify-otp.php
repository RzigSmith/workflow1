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
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Vérification OTP – WorkFlow</title>
  <meta name="description" content="Entrez le code OTP envoyé à votre adresse e-mail pour activer votre compte WorkFlow.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --primary: #4f46e5;
      --primary-dark: #4338ca;
      --bg: #0f0f1a;
      --surface: #1a1a2e;
      --surface2: #16213e;
      --border: rgba(255,255,255,0.08);
      --text: #f1f5f9;
      --text-muted: #94a3b8;
      --danger: #ef4444;
      --success: #10b981;
      --radius: 14px;
    }
    body {
      font-family: 'Inter', sans-serif;
      background: var(--bg);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 1rem;
      position: relative;
      overflow: hidden;
    }
    body::before {
      content: '';
      position: fixed;
      top: -30%;
      left: -20%;
      width: 60vw;
      height: 60vw;
      background: radial-gradient(circle, rgba(79,70,229,0.15) 0%, transparent 70%);
      pointer-events: none;
    }
    body::after {
      content: '';
      position: fixed;
      bottom: -20%;
      right: -10%;
      width: 50vw;
      height: 50vw;
      background: radial-gradient(circle, rgba(99,102,241,0.1) 0%, transparent 70%);
      pointer-events: none;
    }
    .auth-card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 2.5rem 2rem;
      width: 100%;
      max-width: 440px;
      position: relative;
      z-index: 1;
      backdrop-filter: blur(20px);
      box-shadow: 0 25px 50px rgba(0,0,0,0.5);
    }
    .logo {
      display: flex;
      align-items: center;
      gap: 0.6rem;
      justify-content: center;
      margin-bottom: 1.75rem;
    }
    .logo-icon {
      width: 38px; height: 38px;
      background: linear-gradient(135deg, var(--primary), #7c3aed);
      border-radius: 10px;
      display: flex; align-items: center; justify-content: center;
    }
    .logo-text { font-size: 1.4rem; font-weight: 800; color: var(--text); }
    .otp-icon {
      width: 72px; height: 72px;
      background: linear-gradient(135deg, rgba(79,70,229,0.2), rgba(124,58,237,0.2));
      border: 1.5px solid rgba(79,70,229,0.3);
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      margin: 0 auto 1.5rem;
    }
    h1 { font-size: 1.5rem; font-weight: 800; color: var(--text); text-align: center; margin-bottom: 0.5rem; }
    .subtitle { color: var(--text-muted); font-size: 0.875rem; text-align: center; margin-bottom: 2rem; line-height: 1.6; }
    .subtitle strong { color: var(--text); }
    .alert {
      padding: 0.75rem 1rem;
      border-radius: 8px;
      font-size: 0.875rem;
      margin-bottom: 1rem;
      display: flex; align-items: center; gap: 0.5rem;
    }
    .alert-error { background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.3); color: #fca5a5; }
    .alert-success { background: rgba(16,185,129,0.12); border: 1px solid rgba(16,185,129,0.3); color: #6ee7b7; }
    .otp-inputs {
      display: flex;
      gap: 0.75rem;
      justify-content: center;
      margin-bottom: 1.5rem;
    }
    .otp-digit {
      width: 54px; height: 62px;
      background: var(--surface2);
      border: 2px solid var(--border);
      border-radius: 10px;
      color: var(--text);
      font-size: 1.6rem;
      font-weight: 700;
      text-align: center;
      outline: none;
      transition: border-color 0.2s, box-shadow 0.2s;
    }
    .otp-digit:focus {
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(79,70,229,0.2);
    }
    .otp-hidden { display: none; }
    .btn {
      display: flex; align-items: center; justify-content: center; gap: 0.5rem;
      width: 100%;
      padding: 0.85rem 1.5rem;
      border-radius: 10px;
      font-family: inherit; font-size: 0.9rem; font-weight: 600;
      cursor: pointer; border: none; transition: all 0.2s;
    }
    .btn-primary {
      background: linear-gradient(135deg, var(--primary), #7c3aed);
      color: #fff;
    }
    .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 8px 20px rgba(79,70,229,0.35); }
    .btn-primary:active { transform: translateY(0); }
    .resend-row {
      text-align: center;
      margin-top: 1.25rem;
      font-size: 0.85rem;
      color: var(--text-muted);
    }
    .resend-link {
      color: var(--primary);
      font-weight: 600;
      text-decoration: none;
      cursor: pointer;
      background: none; border: none; font-family: inherit; font-size: inherit;
    }
    .resend-link:hover { text-decoration: underline; }
    .back-link {
      display: block;
      text-align: center;
      margin-top: 1.25rem;
      font-size: 0.85rem;
      color: var(--text-muted);
      text-decoration: none;
    }
    .back-link:hover { color: var(--text); }
    .hint-box {
      background: rgba(79,70,229,0.08);
      border: 1px solid rgba(79,70,229,0.2);
      border-radius: 8px;
      padding: 0.75rem 1rem;
      font-size: 0.8rem;
      color: var(--text-muted);
      margin-bottom: 1.5rem;
      line-height: 1.5;
    }
    .hint-box strong { color: #a5b4fc; }
  </style>
</head>
<body>

<div class="auth-card">
  <div class="logo">
    <div class="logo-icon">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5">
        <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>
      </svg>
    </div>
    <span class="logo-text">WorkFlow</span>
  </div>

  <div class="otp-icon">
    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#818cf8" stroke-width="1.8">
      <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/>
      <path d="M16 3h-8a2 2 0 00-2 2v2h12V5a2 2 0 00-2-2z"/>
    </svg>
  </div>

  <h1>Vérification e-mail</h1>
  <p class="subtitle">
    Un code à 6 chiffres a été envoyé à<br>
    <strong><?= htmlspecialchars($email) ?></strong>
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

  <div class="resend-row">
    Vous n'avez pas reçu le code ?
    <a href="index.php?action=resend-otp" class="resend-link">Renvoyer</a>
  </div>
  <a href="index.php?page=login" class="back-link">← Retour à la connexion</a>
</div>

<script>
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
    }
  });
})();
</script>
</body>
</html>
