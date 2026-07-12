<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../core/Flash.php';

if (!isset($_SESSION['user_id']) || (int)($_SESSION['id_role'] ?? 0) !== 1) {
    header('Location: index.php?page=login');
    exit;
}

$adminNom    = $_SESSION['nom'] ?? 'Admin';
$adminPrenom = $_SESSION['prenom'] ?? '';
$initials    = strtoupper(substr($adminNom, 0, 1));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Administration – WorkFlow</title>
  <meta name="description" content="Tableau de bord d'administration WorkFlow – gestion des utilisateurs, publications et activités.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --primary: #4f46e5; --primary-dark: #4338ca;
      --bg: #0a0a14; --surface: #12121f; --surface2: #1a1a2e; --surface3: #1e1e35;
      --border: rgba(255,255,255,0.07); --border2: rgba(255,255,255,0.12);
      --text: #f1f5f9; --text-muted: #64748b; --text-soft: #94a3b8;
      --danger: #ef4444; --danger-dim: rgba(239,68,68,0.12);
      --success: #10b981; --success-dim: rgba(16,185,129,0.12);
      --warning: #f59e0b; --warning-dim: rgba(245,158,11,0.12);
      --info: #3b82f6; --info-dim: rgba(59,130,246,0.12);
      --sidebar-w: 240px;
      --radius: 12px;
    }
    body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; display: flex; }

    /* ── SIDEBAR ── */
    .admin-sidebar {
      width: var(--sidebar-w); background: var(--surface); border-right: 1px solid var(--border);
      display: flex; flex-direction: column; position: fixed; top: 0; left: 0; height: 100vh; z-index: 100;
    }
    .sidebar-brand {
      padding: 1.5rem 1.25rem 1rem;
      border-bottom: 1px solid var(--border);
    }
    .brand-row { display: flex; align-items: center; gap: 0.6rem; }
    .brand-icon {
      width: 36px; height: 36px;
      background: linear-gradient(135deg, var(--primary), #7c3aed);
      border-radius: 9px; display: flex; align-items: center; justify-content: center;
      flex-shrink: 0;
    }
    .brand-name { font-size: 1.1rem; font-weight: 800; }
    .brand-badge {
      display: inline-flex; align-items: center; gap: 0.35rem;
      background: rgba(239,68,68,0.15); border: 1px solid rgba(239,68,68,0.3);
      color: #fca5a5; border-radius: 99px; padding: 0.2rem 0.55rem;
      font-size: 0.65rem; font-weight: 700; letter-spacing: 0.06em;
      margin-top: 0.4rem; width: fit-content;
    }
    .sidebar-nav { flex: 1; padding: 1rem 0.75rem; display: flex; flex-direction: column; gap: 0.2rem; }
    .nav-section { font-size: 0.65rem; font-weight: 700; color: var(--text-muted); letter-spacing: 0.1em; text-transform: uppercase; padding: 0.6rem 0.5rem 0.3rem; }
    .nav-btn {
      display: flex; align-items: center; gap: 0.65rem;
      padding: 0.65rem 0.85rem; border-radius: 8px;
      font-family: inherit; font-size: 0.875rem; font-weight: 500; color: var(--text-soft);
      background: none; border: none; cursor: pointer; text-align: left; width: 100%;
      transition: background 0.15s, color 0.15s;
    }
    .nav-btn:hover { background: rgba(255,255,255,0.05); color: var(--text); }
    .nav-btn.active { background: rgba(79,70,229,0.15); color: #818cf8; font-weight: 600; }
    .nav-btn svg { flex-shrink: 0; opacity: 0.75; }
    .nav-btn.active svg { opacity: 1; }
    .sidebar-footer { padding: 1rem 0.75rem; border-top: 1px solid var(--border); }
    .admin-info { display: flex; align-items: center; gap: 0.65rem; padding: 0.5rem; }
    .admin-avatar {
      width: 34px; height: 34px; background: linear-gradient(135deg, var(--primary), #7c3aed);
      border-radius: 50%; display: flex; align-items: center; justify-content: center;
      font-size: 0.8rem; font-weight: 700; flex-shrink: 0;
    }
    .admin-meta { flex: 1; overflow: hidden; }
    .admin-name { font-size: 0.8rem; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .admin-role { font-size: 0.7rem; color: #fca5a5; }
    .logout-btn {
      display: flex; align-items: center; gap: 0.5rem;
      padding: 0.6rem 0.85rem; border-radius: 8px; margin-top: 0.3rem;
      font-family: inherit; font-size: 0.85rem; font-weight: 500;
      color: var(--danger); background: none; border: none; cursor: pointer; width: 100%;
      transition: background 0.15s;
    }
    .logout-btn:hover { background: var(--danger-dim); }

    /* ── MAIN ── */
    .admin-main { margin-left: var(--sidebar-w); flex: 1; display: flex; flex-direction: column; min-height: 100vh; }
    .topbar {
      position: sticky; top: 0; z-index: 50;
      background: rgba(10,10,20,0.85); backdrop-filter: blur(12px);
      border-bottom: 1px solid var(--border);
      padding: 0.9rem 1.75rem; display: flex; align-items: center; justify-content: space-between;
    }
    .topbar-title { font-size: 1.05rem; font-weight: 700; }
    .topbar-right { display: flex; align-items: center; gap: 0.75rem; }
    .status-pill {
      display: flex; align-items: center; gap: 0.4rem;
      background: var(--success-dim); border: 1px solid rgba(16,185,129,0.25);
      color: #6ee7b7; border-radius: 99px; padding: 0.3rem 0.75rem;
      font-size: 0.75rem; font-weight: 600;
    }
    .status-dot { width: 6px; height: 6px; border-radius: 50%; background: var(--success); animation: pulse 2s infinite; }
    @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:0.4} }

    /* ── CONTENT ── */
    .content { padding: 1.75rem; flex: 1; }
    .page-section { display: none; }
    .page-section.active { display: block; }
    .section-title { font-size: 1.2rem; font-weight: 800; margin-bottom: 0.25rem; }
    .section-subtitle { color: var(--text-muted); font-size: 0.85rem; margin-bottom: 1.75rem; }

    /* ── STAT CARDS ── */
    .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 1.75rem; }
    .stat-card {
      background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius);
      padding: 1.25rem; display: flex; align-items: center; gap: 1rem;
      transition: transform 0.2s, box-shadow 0.2s;
    }
    .stat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.3); }
    .stat-icon {
      width: 48px; height: 48px; border-radius: 12px;
      display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    }
    .stat-icon.indigo { background: rgba(79,70,229,0.15); color: #818cf8; }
    .stat-icon.green  { background: var(--success-dim); color: #34d399; }
    .stat-icon.amber  { background: var(--warning-dim); color: #fbbf24; }
    .stat-icon.rose   { background: rgba(244,63,94,0.12); color: #fb7185; }
    .stat-value { font-size: 1.8rem; font-weight: 800; line-height: 1; }
    .stat-label { font-size: 0.78rem; color: var(--text-muted); margin-top: 0.2rem; }
    .stat-skel { animation: skel 1.5s infinite alternate; }
    @keyframes skel { from{opacity:0.3} to{opacity:0.7} }

    /* ── CARD ── */
    .card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; }
    .card + .card { margin-top: 1.25rem; }
    .card-header {
      padding: 1rem 1.25rem; border-bottom: 1px solid var(--border);
      display: flex; align-items: center; justify-content: space-between;
    }
    .card-title { font-size: 0.95rem; font-weight: 700; }
    .card-body { padding: 0; }

    /* ── TABLE ── */
    .data-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
    .data-table th {
      padding: 0.65rem 1.25rem; text-align: left;
      font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em;
      color: var(--text-muted); background: var(--surface2); border-bottom: 1px solid var(--border);
    }
    .data-table td { padding: 0.8rem 1.25rem; border-bottom: 1px solid var(--border); vertical-align: middle; }
    .data-table tr:last-child td { border-bottom: none; }
    .data-table tr:hover td { background: rgba(255,255,255,0.02); }
    .user-cell { display: flex; align-items: center; gap: 0.65rem; }
    .mini-avatar {
      width: 30px; height: 30px; border-radius: 50%;
      background: linear-gradient(135deg, var(--primary), #7c3aed);
      display: flex; align-items: center; justify-content: center;
      font-size: 0.7rem; font-weight: 700; flex-shrink: 0;
    }
    .mini-avatar img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; }
    .user-name-cell { font-weight: 600; font-size: 0.85rem; }
    .user-handle { color: var(--text-muted); font-size: 0.75rem; }

    /* ── BADGES ── */
    .badge {
      display: inline-flex; align-items: center; gap: 0.3rem;
      padding: 0.22rem 0.65rem; border-radius: 99px;
      font-size: 0.7rem; font-weight: 700; letter-spacing: 0.04em;
    }
    .badge-admin { background: rgba(239,68,68,0.15); color: #fca5a5; border: 1px solid rgba(239,68,68,0.25); }
    .badge-user  { background: rgba(79,70,229,0.12); color: #a5b4fc; border: 1px solid rgba(79,70,229,0.2); }
    .badge-online { background: var(--success-dim); color: #6ee7b7; }
    .badge-offline { background: rgba(100,116,139,0.15); color: var(--text-muted); }
    .badge-verified { background: var(--success-dim); color: #6ee7b7; }
    .badge-unverified { background: var(--warning-dim); color: #fbbf24; }

    /* ── BUTTONS ── */
    .btn {
      display: inline-flex; align-items: center; gap: 0.35rem;
      padding: 0.4rem 0.9rem; border-radius: 7px;
      font-family: inherit; font-size: 0.78rem; font-weight: 600;
      cursor: pointer; border: none; transition: all 0.15s;
    }
    .btn-danger { background: var(--danger-dim); color: #fca5a5; border: 1px solid rgba(239,68,68,0.25); }
    .btn-danger:hover { background: var(--danger); color: #fff; }
    .btn-info { background: var(--info-dim); color: #93c5fd; border: 1px solid rgba(59,130,246,0.25); }
    .btn-info:hover { background: var(--info); color: #fff; }
    .btn-sm { padding: 0.3rem 0.7rem; font-size: 0.75rem; }
    .btn-primary { background: linear-gradient(135deg, var(--primary), #7c3aed); color: #fff; }
    .btn-primary:hover { opacity: 0.9; }

    /* ── EMPTY STATE ── */
    .empty-state { padding: 3rem; text-align: center; color: var(--text-muted); }
    .empty-state svg { opacity: 0.3; margin-bottom: 0.75rem; }

    /* ── LOADING ── */
    .loading-row td { text-align: center; padding: 2rem; color: var(--text-muted); }

    /* ── POST CARD ── */
    .post-mod-card {
      padding: 1rem 1.25rem; border-bottom: 1px solid var(--border);
      display: flex; align-items: flex-start; gap: 1rem;
    }
    .post-mod-card:last-child { border-bottom: none; }
    .post-body { flex: 1; min-width: 0; }
    .post-author { font-weight: 600; font-size: 0.85rem; }
    .post-time { color: var(--text-muted); font-size: 0.75rem; }
    .post-content { color: var(--text-soft); font-size: 0.85rem; margin-top: 0.3rem; line-height: 1.5; }
    .post-img { width: 56px; height: 56px; object-fit: cover; border-radius: 8px; flex-shrink: 0; }
    .vis-badge { display: inline-flex; align-items: center; gap: 0.3rem; font-size: 0.72rem; color: var(--text-muted); margin-top: 0.25rem; }

    /* ── MODAL ── */
    .modal-overlay {
      display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7);
      z-index: 1000; align-items: center; justify-content: center; backdrop-filter: blur(4px);
    }
    .modal-overlay.open { display: flex; }
    .modal { background: var(--surface2); border: 1px solid var(--border2); border-radius: var(--radius); padding: 1.75rem; max-width: 420px; width: 100%; }
    .modal-title { font-size: 1rem; font-weight: 700; margin-bottom: 0.5rem; }
    .modal-body { color: var(--text-soft); font-size: 0.875rem; line-height: 1.6; margin-bottom: 1.25rem; }
    .modal-actions { display: flex; gap: 0.75rem; justify-content: flex-end; }
    .btn-cancel { background: var(--surface3); color: var(--text-soft); border: 1px solid var(--border); }
    .btn-cancel:hover { background: var(--border2); }

    /* ── SCROLLBAR ── */
    ::-webkit-scrollbar { width: 5px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 3px; }

    @media (max-width: 1100px) { .stats-grid { grid-template-columns: repeat(2,1fr); } }
    @media (max-width: 768px) {
      .admin-sidebar { transform: translateX(-100%); }
      .admin-main { margin-left: 0; }
    }

    /* Spinner */
    .spinner { display: inline-block; width: 18px; height: 18px; border: 2px solid rgba(255,255,255,0.2); border-top-color: #818cf8; border-radius: 50%; animation: spin 0.6s linear infinite; }
    @keyframes spin { to { transform: rotate(360deg); } }

    /* Toast */
    #admin-toast {
      position: fixed; bottom: 1.5rem; right: 1.5rem; z-index: 9999;
      display: flex; flex-direction: column; gap: 0.5rem;
    }
    .toast-item {
      padding: 0.75rem 1.1rem; border-radius: 9px; font-size: 0.85rem; font-weight: 500;
      box-shadow: 0 8px 24px rgba(0,0,0,0.4); animation: slideIn 0.25s ease;
      display: flex; align-items: center; gap: 0.5rem; min-width: 240px;
    }
    .toast-success { background: #065f46; border: 1px solid #10b981; color: #d1fae5; }
    .toast-error   { background: #7f1d1d; border: 1px solid #ef4444; color: #fecaca; }
    @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
  </style>
</head>
<body>

<!-- ── SIDEBAR ── -->
<aside class="admin-sidebar">
  <div class="sidebar-brand">
    <div class="brand-row">
      <div class="brand-icon">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5">
          <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>
        </svg>
      </div>
      <span class="brand-name">WorkFlow</span>
    </div>
    <div class="brand-badge">
      <svg width="8" height="8" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="10"/></svg>
      ADMIN PANEL
    </div>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-section">Vue d'ensemble</div>
    <button class="nav-btn active" data-section="dashboard" onclick="Admin.navigate('dashboard', this)">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
        <rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
      </svg>
      Tableau de bord
    </button>

    <div class="nav-section">Gestion</div>
    <button class="nav-btn" data-section="users" onclick="Admin.navigate('users', this)">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/>
        <path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/>
      </svg>
      Utilisateurs
      <span id="users-count" style="margin-left:auto;background:rgba(79,70,229,0.2);color:#a5b4fc;border-radius:99px;padding:0.1rem 0.5rem;font-size:0.7rem;font-weight:700;"></span>
    </button>
    <button class="nav-btn" data-section="posts" onclick="Admin.navigate('posts', this)">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
        <polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/>
        <line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/>
      </svg>
      Publications
    </button>
    <button class="nav-btn" data-section="activities" onclick="Admin.navigate('activities', this)">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/>
      </svg>
      Activités
    </button>

    <div class="nav-section">Accès rapide</div>
    <button class="nav-btn" onclick="window.location.href='index.php?page=dashboard'">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
        <polyline points="9 22 9 12 15 12 15 22"/>
      </svg>
      Mon espace
    </button>
  </nav>

  <div class="sidebar-footer">
    <div class="admin-info">
      <div class="admin-avatar"><?= $initials ?></div>
      <div class="admin-meta">
        <div class="admin-name"><?= htmlspecialchars($adminNom . ' ' . $adminPrenom) ?></div>
        <div class="admin-role">Administrateur</div>
      </div>
    </div>
    <a href="index.php?action=logout" class="logout-btn">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/>
        <polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>
      </svg>
      Déconnexion
    </a>
  </div>
</aside>

<!-- ── MAIN ── -->
<div class="admin-main">
  <header class="topbar">
    <span class="topbar-title" id="page-title">Tableau de bord</span>
    <div class="topbar-right">
      <div class="status-pill">
        <span class="status-dot"></span>
        <span id="online-count">…</span> en ligne
      </div>
    </div>
  </header>

  <div class="content">

    <!-- ══ DASHBOARD ══ -->
    <section class="page-section active" id="section-dashboard">
      <div class="section-title">Tableau de bord</div>
      <div class="section-subtitle">Vue d'ensemble de la plateforme en temps réel.</div>

      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon indigo">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/>
              <path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/>
            </svg>
          </div>
          <div>
            <div class="stat-value stat-skel" id="stat-total-users">…</div>
            <div class="stat-label">Utilisateurs total</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon green">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
            </svg>
          </div>
          <div>
            <div class="stat-value stat-skel" id="stat-online">…</div>
            <div class="stat-label">En ligne maintenant</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon amber">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
              <polyline points="14 2 14 8 20 8"/>
            </svg>
          </div>
          <div>
            <div class="stat-value stat-skel" id="stat-posts">…</div>
            <div class="stat-label">Publications</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon rose">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M9 11l3 3L22 4"/>
              <path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/>
            </svg>
          </div>
          <div>
            <div class="stat-value stat-skel" id="stat-activities">…</div>
            <div class="stat-label">Activités</div>
          </div>
        </div>
      </div>

      <!-- Quick preview table des derniers users -->
      <div class="card">
        <div class="card-header">
          <span class="card-title">Derniers utilisateurs inscrits</span>
          <button class="btn btn-primary btn-sm" onclick="Admin.navigate('users', document.querySelector('[data-section=users]'))">
            Voir tous →
          </button>
        </div>
        <div class="card-body">
          <table class="data-table" id="dash-users-table">
            <thead><tr>
              <th>Utilisateur</th><th>Email</th><th>Rôle</th><th>Statut</th><th>Inscription</th>
            </tr></thead>
            <tbody id="dash-users-body">
              <tr class="loading-row"><td colspan="5"><span class="spinner"></span> Chargement…</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <!-- ══ UTILISATEURS ══ -->
    <section class="page-section" id="section-users">
      <div class="section-title">Gestion des utilisateurs</div>
      <div class="section-subtitle">Consultez, modifiez les rôles et supprimez les comptes.</div>
      <div class="card">
        <div class="card-header">
          <span class="card-title" id="users-table-title">Tous les utilisateurs</span>
          <input id="user-search" type="text" placeholder="🔍 Rechercher…" oninput="Admin.filterUsers(this.value)"
            style="background:var(--surface2);border:1px solid var(--border);border-radius:7px;padding:0.4rem 0.75rem;color:var(--text);font-family:inherit;font-size:0.82rem;outline:none;width:200px;">
        </div>
        <div class="card-body" style="overflow-x:auto;">
          <table class="data-table">
            <thead><tr>
              <th>Utilisateur</th><th>Email</th><th>Rôle</th>
              <th>Vérifié</th><th>Statut</th><th>Inscrit le</th><th>Actions</th>
            </tr></thead>
            <tbody id="users-body">
              <tr class="loading-row"><td colspan="7"><span class="spinner"></span> Chargement…</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <!-- ══ PUBLICATIONS ══ -->
    <section class="page-section" id="section-posts">
      <div class="section-title">Modération des publications</div>
      <div class="section-subtitle">Supprimez les contenus inappropriés.</div>
      <div class="card">
        <div class="card-header">
          <span class="card-title">Toutes les publications</span>
          <span id="posts-count" style="font-size:0.8rem;color:var(--text-muted);"></span>
        </div>
        <div class="card-body" id="posts-container">
          <div class="loading-row" style="padding:2rem;text-align:center;color:var(--text-muted);">
            <span class="spinner"></span> Chargement…
          </div>
        </div>
      </div>
    </section>

    <!-- ══ ACTIVITÉS ══ -->
    <section class="page-section" id="section-activities">
      <div class="section-title">Modération des activités</div>
      <div class="section-subtitle">Visualisez et supprimez toutes les activités de la plateforme.</div>
      <div class="card">
        <div class="card-header">
          <span class="card-title">Toutes les activités</span>
          <span id="activities-count" style="font-size:0.8rem;color:var(--text-muted);"></span>
        </div>
        <div class="card-body" style="overflow-x:auto;">
          <table class="data-table">
            <thead><tr>
              <th>Titre</th><th>Utilisateur</th><th>Priorité</th><th>État</th><th>Date</th><th>Actions</th>
            </tr></thead>
            <tbody id="activities-body">
              <tr class="loading-row"><td colspan="6"><span class="spinner"></span> Chargement…</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </section>

  </div><!-- /content -->
</div><!-- /admin-main -->

<!-- ── MODAL CONFIRMATION ── -->
<div class="modal-overlay" id="confirm-modal">
  <div class="modal">
    <div class="modal-title" id="confirm-title">Confirmer la suppression</div>
    <div class="modal-body" id="confirm-body">Êtes-vous sûr de vouloir effectuer cette action ?</div>
    <div class="modal-actions">
      <button class="btn btn-cancel" onclick="Admin.closeModal()">Annuler</button>
      <button class="btn btn-danger" id="confirm-ok" onclick="Admin.executeConfirm()">Supprimer</button>
    </div>
  </div>
</div>

<div id="admin-toast"></div>

<script>
const Admin = (() => {
  const api = (endpoint, opts = {}) =>
    fetch(`index.php?action=api&endpoint=${endpoint}`, {
      headers: { 'Content-Type': 'application/json' },
      ...opts
    }).then(r => r.json());

  let allUsers = [];
  let pendingAction = null;

  function navigate(section, btn) {
    document.querySelectorAll('.page-section').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.nav-btn').forEach(b => b.classList.remove('active'));
    document.getElementById(`section-${section}`).classList.add('active');
    if (btn) btn.classList.add('active');
    const titles = { dashboard:'Tableau de bord', users:'Utilisateurs', posts:'Publications', activities:'Activités' };
    document.getElementById('page-title').textContent = titles[section] || section;

    if (section === 'users') loadUsers();
    if (section === 'posts') loadPosts();
    if (section === 'activities') loadActivities();
  }

  function toast(msg, type = 'success') {
    const el = document.createElement('div');
    el.className = `toast-item toast-${type}`;
    el.innerHTML = (type === 'success' ? '✓ ' : '✗ ') + msg;
    document.getElementById('admin-toast').appendChild(el);
    setTimeout(() => el.remove(), 3500);
  }

  function openModal(title, body, action) {
    document.getElementById('confirm-title').textContent = title;
    document.getElementById('confirm-body').textContent = body;
    pendingAction = action;
    document.getElementById('confirm-modal').classList.add('open');
  }

  function closeModal() {
    document.getElementById('confirm-modal').classList.remove('open');
    pendingAction = null;
  }

  async function executeConfirm() {
    closeModal();
    if (pendingAction) await pendingAction();
  }

  // ── STATS ──────────────────────────────────────────────────
  async function loadStats() {
    const data = await api('admin-get-stats');
    document.getElementById('stat-total-users').textContent = data.total_users ?? '—';
    document.getElementById('stat-online').textContent      = data.online_users ?? '—';
    document.getElementById('stat-posts').textContent       = data.total_posts ?? '—';
    document.getElementById('stat-activities').textContent  = data.total_activites ?? '—';
    document.getElementById('online-count').textContent     = data.online_users ?? '0';
    document.querySelectorAll('.stat-skel').forEach(el => el.classList.remove('stat-skel'));
  }

  // ── USERS ──────────────────────────────────────────────────
  function renderUsers(users) {
    const tbody = document.getElementById('users-body');
    const dashBody = document.getElementById('dash-users-body');
    document.getElementById('users-table-title').textContent = `Tous les utilisateurs (${users.length})`;
    document.getElementById('users-count').textContent = users.length;

    if (!users.length) {
      tbody.innerHTML = '<tr class="loading-row"><td colspan="7"><em>Aucun utilisateur.</em></td></tr>';
      return;
    }

    tbody.innerHTML = users.map(u => `
      <tr id="user-row-${u.id_user}">
        <td><div class="user-cell">
          <div class="mini-avatar">${(u.nom || '?')[0].toUpperCase()}</div>
          <div>
            <div class="user-name-cell">${esc(u.nom + ' ' + u.prenom)}</div>
            <div class="user-handle">@${esc(u.username || '—')}</div>
          </div>
        </div></td>
        <td style="color:var(--text-soft);font-size:0.82rem;">${esc(u.email)}</td>
        <td>
          <span class="badge ${u.id_role == 1 ? 'badge-admin' : 'badge-user'}">${esc(u.libelle_role)}</span>
        </td>
        <td>
          <span class="badge ${u.email_verified == 1 ? 'badge-verified' : 'badge-unverified'}">
            ${u.email_verified == 1 ? '✓ Vérifié' : '⏳ En attente'}
          </span>
        </td>
        <td>
          <span class="badge ${u.statut_en_ligne == 1 ? 'badge-online' : 'badge-offline'}">
            ${u.statut_en_ligne == 1 ? '● En ligne' : '○ Hors ligne'}
          </span>
        </td>
        <td style="font-size:0.8rem;color:var(--text-muted);">${u.created_at ? u.created_at.substring(0,10) : '—'}</td>
        <td>
          <div style="display:flex;gap:0.4rem;flex-wrap:wrap;">
            <button class="btn btn-info btn-sm" onclick="Admin.toggleRole(${u.id_user}, ${u.id_role == 1 ? 2 : 1}, '${esc(u.nom)}')">
              ${u.id_role == 1 ? '↓ User' : '↑ Admin'}
            </button>
            <button class="btn btn-danger btn-sm" onclick="Admin.deleteUser(${u.id_user}, '${esc(u.nom + ' ' + u.prenom)}')">
              <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/>
              </svg>
            </button>
          </div>
        </td>
      </tr>`).join('');

    // Dashboard preview (last 5)
    dashBody.innerHTML = users.slice(0, 5).map(u => `
      <tr>
        <td><div class="user-cell">
          <div class="mini-avatar">${(u.nom || '?')[0].toUpperCase()}</div>
          <div class="user-name-cell">${esc(u.nom + ' ' + u.prenom)}</div>
        </div></td>
        <td style="color:var(--text-soft);font-size:0.82rem;">${esc(u.email)}</td>
        <td><span class="badge ${u.id_role == 1 ? 'badge-admin' : 'badge-user'}">${esc(u.libelle_role)}</span></td>
        <td><span class="badge ${u.statut_en_ligne == 1 ? 'badge-online' : 'badge-offline'}">${u.statut_en_ligne == 1 ? '● En ligne' : '○ Hors ligne'}</span></td>
        <td style="font-size:0.8rem;color:var(--text-muted);">${u.created_at ? u.created_at.substring(0,10) : '—'}</td>
      </tr>`).join('');
  }

  async function loadUsers() {
    allUsers = await api('admin-get-users');
    renderUsers(allUsers);
  }

  function filterUsers(q) {
    const filtered = q.length < 2 ? allUsers : allUsers.filter(u =>
      [u.nom, u.prenom, u.email, u.username].some(v => v && v.toLowerCase().includes(q.toLowerCase()))
    );
    renderUsers(filtered);
  }

  async function toggleRole(userId, newRole, name) {
    openModal(
      'Modifier le rôle',
      `Changer le rôle de "${name}" vers ${newRole === 1 ? 'Administrateur' : 'Utilisateur'} ?`,
      async () => {
        const r = await api('admin-toggle-role', { method: 'POST', body: JSON.stringify({ id_user: userId, id_role: newRole }) });
        if (r.success) { toast(`Rôle de ${name} mis à jour.`); await loadUsers(); }
        else toast(r.error || 'Erreur', 'error');
      }
    );
  }

  async function deleteUser(userId, name) {
    openModal(
      'Supprimer l\'utilisateur',
      `Cette action supprimera définitivement le compte de "${name}" ainsi que toutes ses données. Cette action est irréversible.`,
      async () => {
        const r = await api('admin-delete-user', { method: 'POST', body: JSON.stringify({ id_user: userId }) });
        if (r.success) {
          toast(`${name} supprimé avec succès.`);
          document.getElementById(`user-row-${userId}`)?.remove();
          allUsers = allUsers.filter(u => u.id_user != userId);
          document.getElementById('users-table-title').textContent = `Tous les utilisateurs (${allUsers.length})`;
          document.getElementById('users-count').textContent = allUsers.length;
          loadStats();
        } else toast(r.error || 'Erreur', 'error');
      }
    );
  }

  // ── POSTS ──────────────────────────────────────────────────
  async function loadPosts() {
    const container = document.getElementById('posts-container');
    container.innerHTML = '<div class="loading-row" style="padding:2rem;text-align:center;color:var(--text-muted);"><span class="spinner"></span> Chargement…</div>';
    const posts = await api('admin-get-posts');
    document.getElementById('posts-count').textContent = `${posts.length} publication(s)`;

    if (!posts.length) {
      container.innerHTML = '<div class="empty-state"><svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/></svg><p>Aucune publication.</p></div>';
      return;
    }

    container.innerHTML = posts.map(p => `
      <div class="post-mod-card" id="post-${p.id_post}">
        <div class="mini-avatar" style="width:36px;height:36px;flex-shrink:0;margin-top:2px;">${(p.nom || '?')[0].toUpperCase()}</div>
        <div class="post-body">
          <div style="display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap;">
            <span class="post-author">${esc(p.nom + ' ' + p.prenom)}</span>
            <span class="vis-badge">${p.visibilite === 'public' ? '🌍' : '👥'} ${p.visibilite}</span>
          </div>
          <div class="post-time">${p.date_publication ? p.date_publication.substring(0,16) : '—'}</div>
          ${p.titre ? `<div style="font-weight:600;font-size:0.85rem;margin-top:0.25rem;">${esc(p.titre)}</div>` : ''}
          <div class="post-content">${esc(p.contenu).substring(0, 180)}${p.contenu.length > 180 ? '…' : ''}</div>
          ${p.id_activite ? `<div style="margin-top:0.3rem;font-size:0.78rem;color:#818cf8;">⚡ Activité liée</div>` : ''}
        </div>
        ${p.photo_path ? `<img src="${esc(p.photo_path)}" alt="Photo" class="post-img">` : ''}
        <button class="btn btn-danger btn-sm" style="flex-shrink:0;align-self:flex-start;" onclick="Admin.deletePost(${p.id_post})">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/>
          </svg>
          Supprimer
        </button>
      </div>`).join('');
  }

  async function deletePost(id) {
    openModal('Supprimer la publication', 'Cette publication sera définitivement supprimée.', async () => {
      const r = await api('admin-delete-post', { method: 'POST', body: JSON.stringify({ id_post: id }) });
      if (r.success) { document.getElementById(`post-${id}`)?.remove(); toast('Publication supprimée.'); loadStats(); }
      else toast(r.error || 'Erreur', 'error');
    });
  }

  // ── ACTIVITIES ──────────────────────────────────────────────
  const priorityColors = { haute:'#fb7185', moyenne:'#fbbf24', basse:'#34d399' };
  const etatLabels = { 1:'En attente', 2:'En cours', 3:'Terminée' };

  async function loadActivities() {
    const tbody = document.getElementById('activities-body');
    tbody.innerHTML = '<tr class="loading-row"><td colspan="6"><span class="spinner"></span> Chargement…</td></tr>';
    const acts = await api('admin-get-activities');
    document.getElementById('activities-count').textContent = `${acts.length} activité(s)`;

    if (!acts.length) {
      tbody.innerHTML = '<tr class="loading-row"><td colspan="6"><em>Aucune activité.</em></td></tr>';
      return;
    }

    tbody.innerHTML = acts.map(a => `
      <tr id="act-${a.id_activite}">
        <td><div style="font-weight:600;font-size:0.85rem;">${esc(a.libelle)}</div>${a.description ? `<div style="font-size:0.75rem;color:var(--text-muted);">${esc(a.description).substring(0,60)}</div>` : ''}</td>
        <td><div class="user-cell">
          <div class="mini-avatar" style="width:26px;height:26px;font-size:0.65rem;">${(a.nom||'?')[0].toUpperCase()}</div>
          <span style="font-size:0.82rem;">${esc(a.nom + ' ' + a.prenom)}</span>
        </div></td>
        <td><span style="color:${priorityColors[a.priorite] || '#94a3b8'};font-weight:600;font-size:0.8rem;">${a.priorite || '—'}</span></td>
        <td><span class="badge badge-user">${etatLabels[a.id_etat] || a.libelle_etat || '—'}</span></td>
        <td style="font-size:0.8rem;color:var(--text-muted);">${a.date_activite || '—'}</td>
        <td>
          <button class="btn btn-danger btn-sm" onclick="Admin.deleteActivity(${a.id_activite})">
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/>
            </svg>
            Supprimer
          </button>
        </td>
      </tr>`).join('');
  }

  async function deleteActivity(id) {
    openModal('Supprimer l\'activité', 'Cette activité sera définitivement supprimée.', async () => {
      const r = await api('admin-delete-activity', { method: 'POST', body: JSON.stringify({ id_activite: id }) });
      if (r.success) { document.getElementById(`act-${id}`)?.remove(); toast('Activité supprimée.'); loadStats(); }
      else toast(r.error || 'Erreur', 'error');
    });
  }

  function esc(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  // Init
  loadStats();
  loadUsers();
  setInterval(loadStats, 30000); // Refresh stats every 30s

  return { navigate, filterUsers, toggleRole, deleteUser, deletePost, deleteActivity, closeModal, executeConfirm, toast };
})();
</script>
</body>
</html>
