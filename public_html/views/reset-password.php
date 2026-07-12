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
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Nouveau mot de passe – WorkFlow</title>
  <meta name="description" content="Définissez un nouveau mot de passe pour votre compte WorkFlow.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --primary: #4f46e5; --primary-dark: #4338ca;
      --bg: #0f0f1a; --surface: #1a1a2e; --surface2: #16213e;
      --border: rgba(255,255,255,0.08); --text: #f1f5f9; --text-muted: #94a3b8;
      --danger: #ef4444; --success: #10b981; --radius: 14px;
    }
    body {
      font-family: 'Inter', sans-serif; background: var(--bg);
      min-height: 100vh; display: flex; align-items: center; justify-content: center;
      padding: 1rem; position: relative; overflow: hidden;
    }
    body::before {
      content: ''; position: fixed; top: -30%; left: -20%; width: 60vw; height: 60vw;
      background: radial-gradient(circle, rgba(79,70,229,0.15) 0%, transparent 70%); pointer-events: none;
    }
    body::after {
      content: ''; position: fixed; bottom: -20%; right: -10%; width: 50vw; height: 50vw;
      background: radial-gradient(circle, rgba(99,102,241,0.1) 0%, transparent 70%); pointer-events: none;
    }
    .auth-card {
      background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius);
      padding: 2.5rem 2rem; width: 100%; max-width: 440px;
      position: relative; z-index: 1; backdrop-filter: blur(20px);
      box-shadow: 0 25px 50px rgba(0,0,0,0.5);
    }
    .logo { display: flex; align-items: center; gap: 0.6rem; justify-content: center; margin-bottom: 1.75rem; }
    .logo-icon {
      width: 38px; height: 38px; background: linear-gradient(135deg, var(--primary), #7c3aed);
      border-radius: 10px; display: flex; align-items: center; justify-content: center;
    }
    .logo-text { font-size: 1.4rem; font-weight: 800; color: var(--text); }
    .icon-circle {
      width: 72px; height: 72px;
      background: linear-gradient(135deg, rgba(16,185,129,0.15), rgba(5,150,105,0.15));
      border: 1.5px solid rgba(16,185,129,0.3); border-radius: 50%;
      display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem;
    }
    h1 { font-size: 1.5rem; font-weight: 800; color: var(--text); text-align: center; margin-bottom: 0.5rem; }
    .subtitle { color: var(--text-muted); font-size: 0.875rem; text-align: center; margin-bottom: 2rem; line-height: 1.6; }
    .alert { padding: 0.75rem 1rem; border-radius: 8px; font-size: 0.875rem; margin-bottom: 1rem; display: flex; align-items: flex-start; gap: 0.5rem; }
    .alert-error { background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.3); color: #fca5a5; }
    .alert-success { background: rgba(16,185,129,0.12); border: 1px solid rgba(16,185,129,0.3); color: #6ee7b7; }
    .form-group { margin-bottom: 1.25rem; position: relative; }
    label { display: block; font-size: 0.8rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.4rem; text-transform: uppercase; letter-spacing: 0.05em; }
    .input-wrap { position: relative; }
    .form-control {
      width: 100%; padding: 0.75rem 2.75rem 0.75rem 1rem;
      background: var(--surface2); border: 1.5px solid var(--border);
      border-radius: 10px; color: var(--text); font-family: inherit; font-size: 0.9rem; outline: none;
      transition: border-color 0.2s, box-shadow 0.2s;
    }
    .form-control:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(79,70,229,0.2); }
    .toggle-pw {
      position: absolute; right: 0.9rem; top: 50%; transform: translateY(-50%);
      background: none; border: none; cursor: pointer; color: var(--text-muted); padding: 0; display: flex;
    }
    .toggle-pw:hover { color: var(--text); }
    .strength-bar { margin-top: 0.5rem; height: 4px; border-radius: 2px; background: var(--border); overflow: hidden; }
    .strength-fill { height: 100%; border-radius: 2px; width: 0; transition: width 0.3s, background 0.3s; }
    .strength-label { font-size: 0.75rem; color: var(--text-muted); margin-top: 0.3rem; }
    .btn {
      display: flex; align-items: center; justify-content: center; gap: 0.5rem;
      width: 100%; padding: 0.85rem 1.5rem; border-radius: 10px;
      font-family: inherit; font-size: 0.9rem; font-weight: 600; cursor: pointer; border: none; transition: all 0.2s;
    }
    .btn-primary { background: linear-gradient(135deg, var(--primary), #7c3aed); color: #fff; }
    .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 8px 20px rgba(79,70,229,0.35); }
    .back-link { display: block; text-align: center; margin-top: 1.25rem; font-size: 0.85rem; color: var(--text-muted); text-decoration: none; }
    .back-link:hover { color: var(--text); }
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

  <div class="icon-circle">
    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#34d399" stroke-width="1.8">
      <polyline points="20 6 9 17 4 12"/>
    </svg>
  </div>

  <h1>Nouveau mot de passe</h1>
  <p class="subtitle">Choisissez un mot de passe sécurisé d'au moins 6 caractères.</p>

  <?php if ($error): ?>
    <div class="alert alert-error">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;margin-top:1px"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/></svg>
      <?= htmlspecialchars($error) ?>
    </div>
  <?php endif; ?>
  <?php if ($success): ?>
    <div class="alert alert-success">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;margin-top:1px"><polyline points="20 6 9 17 4 12"/></svg>
      <?= htmlspecialchars($success) ?>
    </div>
  <?php endif; ?>

  <form method="POST" action="index.php?action=reset-password" novalidate id="reset-form">
    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

    <div class="form-group">
      <label for="password">Nouveau mot de passe</label>
      <div class="input-wrap">
        <input class="form-control" type="password" id="password" name="password" placeholder="Au moins 6 caractères" required autocomplete="new-password" oninput="checkStrength(this.value)">
        <button type="button" class="toggle-pw" onclick="togglePw('password', this)" title="Afficher/masquer">
          <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
        </button>
      </div>
      <div class="strength-bar"><div class="strength-fill" id="strength-fill"></div></div>
      <div class="strength-label" id="strength-label"></div>
    </div>

    <div class="form-group">
      <label for="confirm_password">Confirmer le mot de passe</label>
      <div class="input-wrap">
        <input class="form-control" type="password" id="confirm_password" name="confirm_password" placeholder="Répétez le mot de passe" required autocomplete="new-password">
        <button type="button" class="toggle-pw" onclick="togglePw('confirm_password', this)" title="Afficher/masquer">
          <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
        </button>
      </div>
    </div>

    <button type="submit" class="btn btn-primary">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
      Réinitialiser
    </button>
  </form>

  <a href="index.php?page=login" class="back-link">← Retour à la connexion</a>
</div>

<script>
function togglePw(id, btn) {
  const input = document.getElementById(id);
  input.type = input.type === 'password' ? 'text' : 'password';
}
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
    alert('Les mots de passe ne correspondent pas.');
  }
});
</script>
</body>
</html>
