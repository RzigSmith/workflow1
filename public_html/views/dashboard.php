<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

require_once __DIR__ . '/../core/Flash.php';
require_once __DIR__ . '/../models/Activite.php';
require_once __DIR__ . '/../models/Ami.php';
require_once __DIR__ . '/../models/Utilisateur.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?page=login');
    exit;
}

$error   = get_flash('error');
$success = get_flash('success');

$activiteModel = new Activite();
$activites     = $activiteModel->listerParUser($_SESSION['user_id']);

$amiModel   = new Ami();
$amis       = $amiModel->getAmis($_SESSION['user_id']);
$demandes   = $amiModel->getDemandesRecues($_SESSION['user_id']);

$total    = count($activites);
$pending  = count(array_filter($activites, fn($a) => $a['id_etat'] == 1));
$progress = count(array_filter($activites, fn($a) => $a['id_etat'] == 2));
$done     = count(array_filter($activites, fn($a) => $a['id_etat'] == 3));

$userNom      = $_SESSION['nom'] ?? 'Utilisateur';
$userPrenom   = $_SESSION['prenom'] ?? '';
$userId       = (int)$_SESSION['user_id'];
$initials     = strtoupper(substr($userNom, 0, 1));

$userModel    = new Utilisateur();
$currentUser  = $userModel->find($userId);
$photoProfil  = $currentUser['photo_profil'] ?? null;
$userEmail    = $currentUser['email'] ?? '';

$priorityBadge = ['haute'=>'badge-high','moyenne'=>'badge-medium','basse'=>'badge-low'];
$priorityLabel = ['haute'=>'Haute','moyenne'=>'Moyenne','basse'=>'Basse'];
$etatDot       = [1=>'dot-pending',2=>'dot-progress',3=>'dot-done'];
$etatLabel     = [1=>'En attente',2=>'En cours',3=>'Terminée'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tableau de bord – WorkFlow</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div id="nprogress-bar"></div>
<div id="toast-container"></div>
<div id="flash-data"
     data-error="<?= htmlspecialchars($error ?? '') ?>"
     data-success="<?= htmlspecialchars($success ?? '') ?>"
     style="display:none;">
</div>

<script>
  window.APP = {
    userId: <?= $userId ?>,
    userName: <?= json_encode($userNom) ?>,
    userPrenom: <?= json_encode($userPrenom) ?>,
    userEmail: <?= json_encode($userEmail) ?>,
    photoProfil: <?= json_encode($photoProfil) ?>,
    activites: <?= json_encode(array_map(fn($a) => ['id'=>$a['id_activite'],'libelle'=>$a['libelle']], $activites)) ?>
  };
</script>

<div class="app-layout">

  <!-- ── SIDEBAR ── -->
  <nav class="sidebar" id="sidebar">
    <div class="sidebar-header">
      <div class="sidebar-logo">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
      </div>
      <span class="sidebar-brand">WorkFlow</span>
    </div>

    <div class="sidebar-nav">
      <div class="nav-section-label">Principal</div>

      <button class="nav-item active" data-section="accueil" data-title="Tableau de bord">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
        Tableau de bord
      </button>

      <button class="nav-item" data-section="activites" data-title="Activités">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg>
        Activités
        <?php if ($total > 0): ?><span class="nav-badge" style="background:var(--primary);"><?= $total ?></span><?php endif; ?>
      </button>

      <div class="nav-section-label" style="margin-top:0.75rem;">Social</div>

      <button class="nav-item" data-section="feed" data-title="Publications">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        Publications
      </button>

      <button class="nav-item" data-section="amis" data-title="Amis">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
        Amis
        <?php if (count($demandes) > 0): ?><span class="nav-badge"><?= count($demandes) ?></span><?php endif; ?>
      </button>

      <button class="nav-item" data-section="messages" data-title="Messages">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
        Messages
      </button>

      <button class="nav-item" data-section="profil" data-title="Mon Profil">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        Mon Profil
      </button>

      <div class="nav-section-label" style="margin-top:0.75rem;">Tâches</div>

      <button class="nav-item" data-section="nouvelle-activite" data-title="Nouvelle activité">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
        Nouvelle activité
      </button>

      <button class="nav-item" data-section="notifications" data-title="Notifications">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>
        Notifications
        <span class="nav-badge" id="nav-notif-badge" style="display:none;">0</span>
      </button>
    </div>

    <div class="sidebar-footer">
      <div class="user-info">
        <div class="user-avatar"><?= htmlspecialchars($initials) ?></div>
        <div>
          <div class="user-name"><?= htmlspecialchars($userNom) ?></div>
          <div class="user-role">Utilisateur</div>
        </div>
      </div>
      <a href="index.php?action=logout" class="nav-item" style="margin-top:4px;" data-confirm="Voulez-vous vous déconnecter ?">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        Déconnexion
      </a>
    </div>
  </nav>

  <!-- ── MAIN CONTENT ── -->
  <div class="main-content">

    <header class="topbar">
      <div class="topbar-left">
        <button class="sidebar-toggle" id="sidebar-toggle" aria-label="Menu">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
        </button>
        <span class="topbar-title" id="topbar-title">Tableau de bord</span>
      </div>
      <div class="topbar-right">
        <button class="icon-btn" id="topbar-notif-btn" title="Notifications" onclick="WorkFlow.nav.navigate('notifications','Notifications')">
          <span class="notif-dot" id="topbar-notif-dot" style="display:none;"></span>
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>
        </button>
        <button class="icon-btn" title="Messages" onclick="WorkFlow.nav.navigate('messages','Messages')">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
        </button>
        <button class="icon-btn" title="Amis" onclick="WorkFlow.nav.navigate('amis','Amis')">
          <?php if (count($demandes) > 0): ?><span class="notif-dot"></span><?php endif; ?>
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
        </button>
        <div class="user-avatar profile-avatar-sm" id="topbar-avatar" style="width:32px;height:32px;font-size:0.75rem;cursor:pointer;overflow:hidden;" onclick="WorkFlow.nav.navigate('profil','Mon Profil')" title="Mon profil">
          <?php if ($photoProfil): ?>
            <img src="<?= htmlspecialchars($photoProfil) ?>" alt="Profil" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
          <?php else: ?>
            <?= htmlspecialchars($initials) ?>
          <?php endif; ?>
        </div>
      </div>
    </header>

    <main class="page-body">

      <!-- ══ ACCUEIL ══ -->
      <section class="page-section active" data-id="accueil">
        <h2 style="font-size:1.3rem;font-weight:800;margin-bottom:0.25rem;">Bonjour, <?= htmlspecialchars($userNom) ?> 👋</h2>
        <p style="color:var(--text-muted);font-size:0.875rem;margin-bottom:1.5rem;">Voici un aperçu de votre activité.</p>
        <div class="stats-grid">
          <div class="stat-card"><div class="stat-icon indigo"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg></div><div><div class="stat-value"><?= $total ?></div><div class="stat-label">Activités</div></div></div>
          <div class="stat-card"><div class="stat-icon green"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/></svg></div><div><div class="stat-value"><?= count($amis) ?></div><div class="stat-label">Amis</div></div></div>
          <div class="stat-card"><div class="stat-icon amber"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div><div><div class="stat-value"><?= $pending ?></div><div class="stat-label">En attente</div></div></div>
          <div class="stat-card"><div class="stat-icon cyan"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg></div><div><div class="stat-value"><?= $done ?></div><div class="stat-label">Terminées</div></div></div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;" class="two-col-grid">
          <div class="card">
            <div class="card-header">
              <span class="card-title">Activités récentes</span>
              <button class="btn btn-primary btn-sm" onclick="WorkFlow.nav.navigate('nouvelle-activite','Nouvelle activité')">+ Ajouter</button>
            </div>
            <div class="card-body">
              <?php if (empty($activites)): ?>
                <div class="empty-state"><svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg><p>Aucune activité</p></div>
              <?php else: ?>
                <div class="activite-list">
                  <?php foreach (array_slice($activites,0,5) as $a): ?>
                    <div class="activite-item">
                      <div class="activite-dot <?= $etatDot[$a['id_etat']] ?? 'dot-pending' ?>"></div>
                      <div class="activite-info">
                        <div class="activite-label"><?= htmlspecialchars($a['libelle']) ?></div>
                        <div class="activite-meta"><?= $etatLabel[$a['id_etat']] ?? '—' ?><?= $a['date_activite'] ? ' · '.htmlspecialchars($a['date_activite']) : '' ?></div>
                      </div>
                      <?php $p=strtolower($a['priorite']??''); if(isset($priorityBadge[$p])): ?><span class="badge <?= $priorityBadge[$p] ?>"><?= $priorityLabel[$p] ?></span><?php endif; ?>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>
          </div>
          <div class="card">
            <div class="card-header">
              <span class="card-title">Mes amis</span>
              <button class="btn btn-outline btn-sm" onclick="WorkFlow.nav.navigate('amis','Amis')">Voir tout</button>
            </div>
            <div class="card-body">
              <?php if (empty($amis)): ?>
                <div class="empty-state"><svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/></svg><p><a href="#" onclick="WorkFlow.nav.navigate('amis','Amis');return false;" style="color:var(--primary);font-weight:600;">Trouver des amis →</a></p></div>
              <?php else: ?>
                <div style="display:flex;flex-direction:column;gap:0.5rem;">
                  <?php foreach (array_slice($amis,0,5) as $ami): ?>
                    <div class="friend-card" style="padding:0.6rem 0.8rem;">
                      <div class="user-avatar-sm"><?= strtoupper(substr($ami['nom'],0,1)) ?></div>
                      <div class="user-info-sm"><div class="name"><?= htmlspecialchars($ami['nom'].' '.$ami['prenom']) ?></div><div class="handle">@<?= htmlspecialchars($ami['username']??'—') ?></div></div>
                      <button class="btn btn-outline btn-sm" data-user-id="<?= $ami['id_user'] ?>" data-user-name="<?= htmlspecialchars($ami['nom'].' '.$ami['prenom']) ?>" onclick="Social.openChatFromBtn(this)">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
                        Chat
                      </button>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </section>

      <!-- ══ ACTIVITÉS ══ -->
      <section class="page-section" data-id="activites">
        <div class="card">
          <div class="card-header">
            <span class="card-title">Mes activités</span>
            <div style="display:flex;gap:0.5rem;">
              <button class="btn btn-outline btn-sm" id="share-activite-btn" style="display:none;" onclick="Social.openShareActivity()">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
                Partager
              </button>
              <button class="btn btn-primary btn-sm" onclick="WorkFlow.nav.navigate('nouvelle-activite','Nouvelle activité')">+ Nouvelle</button>
            </div>
          </div>
          <div class="card-body">
            <?php if (empty($activites)): ?>
              <div class="empty-state"><svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg><p>Aucune activité à afficher.</p></div>
            <?php else: ?>
              <div class="activite-list">
                <?php foreach ($activites as $a): ?>
                  <div class="activite-item" style="display:flex;align-items:center;justify-content:space-between;gap:0.5rem;">
                    <div style="display:flex;align-items:center;gap:0.75rem;flex:1;cursor:pointer;" onclick="document.querySelectorAll('.activite-item').forEach(i=>i.style.background=''); this.parentElement.style.background='rgba(79,70,229,0.04)'; document.getElementById('share-activite-btn').style.display='flex'; window._selectedActivite={id:<?= $a['id_activite'] ?>,libelle:<?= json_encode($a['libelle']) ?>};">
                      <div class="activite-dot <?= $etatDot[$a['id_etat']] ?? 'dot-pending' ?>"></div>
                      <div class="activite-info">
                        <div class="activite-label"><?= htmlspecialchars($a['libelle']) ?></div>
                        <div class="activite-meta">
                          <?= $a['description'] ? htmlspecialchars(mb_substr($a['description'],0,50)).'… · ' : '' ?>
                          <?= $etatLabel[$a['id_etat']] ?? '—' ?>
                          <?= $a['date_activite'] ? ' · '.htmlspecialchars($a['date_activite']) : '' ?>
                          <?= ($a['heure_debut']&&$a['heure_fin']) ? ' · '.htmlspecialchars($a['heure_debut']).'→'.htmlspecialchars($a['heure_fin']) : '' ?>
                        </div>
                      </div>
                      <?php $p=strtolower($a['priorite']??''); if(isset($priorityBadge[$p])): ?><span class="badge <?= $priorityBadge[$p] ?>"><?= $priorityLabel[$p] ?></span><?php endif; ?>
                    </div>
                    <div style="display:flex;gap:0.3rem;">
                      <button class="btn btn-outline btn-sm" style="padding:0.4rem 0.8rem;font-size:0.8rem;" data-activite='<?= htmlspecialchars(json_encode($a, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT)) ?>' onclick="Activite.openEditModalFromButton(this,event);" title="Modifier">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                      </button>
                      <button class="btn btn-outline btn-sm" style="padding:0.4rem 0.8rem;font-size:0.8rem;color:var(--danger);border-color:var(--danger);" onclick="Activite.delete(<?= $a['id_activite'] ?>)" title="Supprimer">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                      </button>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </section>

      <!-- ══ NOUVELLE ACTIVITÉ ══ -->
      <section class="page-section" data-id="nouvelle-activite">
        <div class="card" style="max-width:620px;">
          <div class="card-header"><span class="card-title">Créer une activité</span></div>
          <div class="card-body">
            <form method="POST" action="index.php?action=creer-activite" novalidate>
              <div class="form-group"><label for="libelle">Titre *</label><input class="form-control" type="text" id="libelle" name="libelle" placeholder="Ex: Préparer la réunion" required></div>
              <div class="form-group"><label for="description">Description</label><textarea class="form-control" id="description" name="description" rows="3" placeholder="Détails…" style="resize:vertical;"></textarea></div>
              <div class="form-row">
                <div class="form-group"><label for="priorite">Priorité *</label><select class="form-control" id="priorite" name="priorite" required><option value="">Choisir…</option><option value="haute">🔴 Haute</option><option value="moyenne">🟡 Moyenne</option><option value="basse">🟢 Basse</option></select></div>
                <div class="form-group"><label for="date_activite">Date *</label><input class="form-control" type="date" id="date_activite" name="date_activite" required></div>
              </div>
              <div class="form-row">
                <div class="form-group"><label for="heure_debut">Heure début</label><input class="form-control" type="time" id="heure_debut" name="heure_debut"></div>
                <div class="form-group"><label for="heure_fin">Heure fin</label><input class="form-control" type="time" id="heure_fin" name="heure_fin"></div>
              </div>
              <div class="form-actions">
                <button type="submit" class="btn btn-primary"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Créer</button>
                <button type="button" class="btn btn-outline" onclick="WorkFlow.nav.navigate('activites','Activités')">Annuler</button>
              </div>
            </form>
          </div>
        </div>
      </section>

      <!-- ══ MODIFIER ACTIVITÉ ══ -->
      <section class="page-section" data-id="modifier-activite">
        <div class="card" style="max-width:620px;">
          <div class="card-header"><span class="card-title">Modifier l'activité</span></div>
          <div class="card-body">
            <form method="POST" action="index.php?action=modifier-activite" novalidate id="form-modifier-activite">
              <input type="hidden" name="id_activite" id="mod-id_activite" value="">
              <div class="form-group"><label for="mod-libelle">Titre *</label><input class="form-control" type="text" id="mod-libelle" name="libelle" placeholder="Ex: Préparer la réunion" required></div>
              <div class="form-group"><label for="mod-description">Description</label><textarea class="form-control" id="mod-description" name="description" rows="3" placeholder="Détails…" style="resize:vertical;"></textarea></div>
              <div class="form-row">
                <div class="form-group"><label for="mod-priorite">Priorité *</label><select class="form-control" id="mod-priorite" name="priorite" required><option value="">Choisir…</option><option value="haute">🔴 Haute</option><option value="moyenne">🟡 Moyenne</option><option value="basse">🟢 Basse</option></select></div>
                <div class="form-group"><label for="mod-date_activite">Date *</label><input class="form-control" type="date" id="mod-date_activite" name="date_activite" required></div>
              </div>
              <div class="form-row">
                <div class="form-group"><label for="mod-heure_debut">Heure début</label><input class="form-control" type="time" id="mod-heure_debut" name="heure_debut"></div>
                <div class="form-group"><label for="mod-heure_fin">Heure fin</label><input class="form-control" type="time" id="mod-heure_fin" name="heure_fin"></div>
              </div>
              <div class="form-row">
                <div class="form-group"><label for="mod-id_etat">État</label><select class="form-control" id="mod-id_etat" name="id_etat"><option value="1">En attente</option><option value="2">En cours</option><option value="3">Terminée</option></select></div>
              </div>
              <div class="form-actions">
                <button type="submit" class="btn btn-primary"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Enregistrer</button>
                <button type="button" class="btn btn-outline" onclick="WorkFlow.nav.navigate('activites','Activités')">Annuler</button>
              </div>
            </form>
          </div>
        </div>
      </section>

      <!-- ══ PUBLICATIONS / FEED ══ -->
      <section class="page-section" data-id="feed">
        <div style="max-width:680px;margin:0 auto;">

          <!-- Compose -->
          <div class="feed-compose">
            <div class="compose-top">
              <div class="user-avatar"><?= htmlspecialchars($initials) ?></div>
              <textarea class="compose-textarea" id="compose-text" placeholder="Partagez quelque chose…" rows="3"></textarea>
            </div>
            <div id="compose-activite-tag" style="display:none;margin-bottom:0.5rem;margin-left:3rem;">
              <span class="post-activite-tag" id="compose-activite-label">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                <span id="compose-activite-name"></span>
                <button onclick="Social.clearComposeActivite()" style="background:none;border:none;cursor:pointer;color:var(--primary);font-size:0.8rem;padding:0 2px;">✕</button>
              </span>
            </div>
            <div id="compose-photo-preview" style="display:none;margin:0 0 0.5rem 3rem;">
              <img id="compose-photo-img" src="" alt="Aperçu" style="max-height:120px;border-radius:8px;border:1px solid var(--border);">
              <button type="button" onclick="Social.clearComposePhoto()" style="display:block;margin-top:0.25rem;background:none;border:none;color:var(--danger);font-size:0.75rem;cursor:pointer;">Retirer l'image</button>
            </div>
            <div class="compose-footer">
              <div class="compose-opts">
                <select id="compose-visibilite" title="Visibilité">
                  <option value="amis">👥 Amis</option>
                  <option value="public">🌍 Public</option>
                </select>
                <label class="btn btn-outline btn-sm" style="cursor:pointer;margin:0;" title="Ajouter une photo">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                  Photo
                  <input type="file" id="compose-photo" accept="image/jpeg,image/png,image/gif,image/webp" style="display:none;" onchange="Social.previewComposePhoto(this)">
                </label>
                <button class="btn btn-outline btn-sm" onclick="Social.openAttachActivity()" title="Lier une activité">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                  Activité
                </button>
              </div>
              <button class="btn btn-primary btn-sm" id="compose-submit" onclick="Social.submitPost()">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                Publier
              </button>
            </div>
          </div>

          <!-- Feed -->
          <div id="feed-container">
            <div class="feed-skeleton">
              <?php for($i=0;$i<3;$i++): ?>
                <div class="skel-post">
                  <div class="skel-row"><div class="skeleton skel-avatar"></div><div style="flex:1;"><div class="skeleton skel-line" style="width:40%;margin-bottom:5px;"></div><div class="skeleton skel-line" style="width:25%;"></div></div></div>
                  <div class="skeleton skel-line" style="width:90%;margin-bottom:5px;"></div>
                  <div class="skeleton skel-line" style="width:70%;"></div>
                </div>
              <?php endfor; ?>
            </div>
          </div>
        </div>
      </section>

      <!-- ══ AMIS ══ -->
      <section class="page-section" data-id="amis">

        <?php if (!empty($demandes)): ?>
          <div class="card" style="margin-bottom:1.25rem;border-left:3px solid var(--primary);">
            <div class="card-header"><span class="card-title">Demandes reçues <span class="badge" style="background:var(--danger);color:#fff;"><?= count($demandes) ?></span></span></div>
            <div class="card-body">
              <div style="display:flex;flex-direction:column;gap:0.5rem;">
                <?php foreach ($demandes as $d): ?>
                  <div class="friend-card" id="demande-<?= $d['id_amitie'] ?>">
                    <div class="user-avatar-sm"><?= strtoupper(substr($d['nom'],0,1)) ?></div>
                    <div class="user-info-sm">
                      <div class="name"><?= htmlspecialchars($d['nom'].' '.$d['prenom']) ?></div>
                      <div class="handle">@<?= htmlspecialchars($d['username']??'—') ?></div>
                    </div>
                    <div class="actions">
                      <button class="btn btn-success btn-sm" onclick="Social.respondFriend(<?= $d['id_amitie'] ?>, 'accepted', this)">✓ Accepter</button>
                      <button class="btn btn-outline btn-sm" onclick="Social.respondFriend(<?= $d['id_amitie'] ?>, 'declined', this)">✕</button>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        <?php endif; ?>

        <!-- Recherche -->
        <div class="card" style="margin-bottom:1.25rem;">
          <div class="card-header"><span class="card-title">Rechercher un utilisateur</span></div>
          <div class="card-body">
            <div class="search-box">
              <svg class="search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
              <input class="form-control" type="text" id="friend-search" placeholder="Nom, prénom ou @username…" autocomplete="off" style="padding-left:2.4rem;">
            </div>
            <div class="search-results" id="search-results"></div>
          </div>
        </div>

        <!-- Liste amis -->
        <div class="card">
          <div class="card-header"><span class="card-title">Mes amis (<?= count($amis) ?>)</span></div>
          <div class="card-body" id="amis-list">
            <?php if (empty($amis)): ?>
              <div class="empty-state"><svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/></svg><p>Vous n'avez pas encore d'amis.<br>Utilisez la recherche pour en trouver !</p></div>
            <?php else: ?>
              <div style="display:flex;flex-direction:column;gap:0.5rem;">
                <?php foreach ($amis as $ami): ?>
                  <div class="friend-card" id="ami-<?= $ami['id_user'] ?>">
                    <div class="user-avatar-sm"><?= strtoupper(substr($ami['nom'],0,1)) ?></div>
                    <div class="user-info-sm">
                      <div class="name"><?= htmlspecialchars($ami['nom'].' '.$ami['prenom']) ?></div>
                      <div class="handle">@<?= htmlspecialchars($ami['username']??'—') ?></div>
                    </div>
                    <div class="actions">
                      <button class="btn btn-outline btn-sm" data-user-id="<?= $ami['id_user'] ?>" data-user-name="<?= htmlspecialchars($ami['nom'].' '.$ami['prenom']) ?>" onclick="Social.openChatFromBtn(this)" title="Démarrer un chat">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg> Chat
                      </button>
                      <button class="btn btn-outline btn-sm" style="color:var(--danger);border-color:var(--danger);" onclick="Social.removeFriend(<?= $ami['id_user'] ?>, this)" title="Retirer">✕</button>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </section>

      <!-- ══ MESSAGES / CHAT ══ -->
      <section class="page-section" data-id="messages">
        <div class="chat-layout" id="chat-layout">

          <!-- Conv list -->
          <div class="chat-sidebar" id="chat-sidebar">
            <div class="chat-sidebar-header" style="display:flex;align-items:center;justify-content:space-between;">
              <div style="display:flex;align-items:center;gap:0.4rem;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:0.4rem;"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
                <span>Conversations</span>
              </div>
              <button class="btn btn-sm btn-primary" onclick="WorkFlow.nav.navigate('amis','Amis')" style="padding:0.35rem 0.7rem;">+ Nouveau chat</button>
            </div>
            <div class="conv-list" id="conv-list">
              <div style="padding:1.5rem;text-align:center;color:var(--text-muted);font-size:0.875rem;">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="opacity:0.4;margin-bottom:0.5rem;"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg><br>
                Chargement…
              </div>
            </div>
          </div>

          <!-- Chat window -->
          <div class="chat-main" id="chat-main">
            <div class="chat-empty" id="chat-empty">
              <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="opacity:0.3;"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
              <p>Sélectionnez une conversation<br>ou commencez un chat depuis vos <strong>Amis</strong></p>
              <p style="color:var(--text-muted);font-size:0.85rem;">Cliquez sur un ami pour commencer.</p>
            </div>
            <div id="chat-active" style="flex-direction:column;overflow:hidden;">
              <div class="chat-header" id="chat-header">
                <button class="btn btn-outline btn-sm chat-back-btn" id="chat-back-btn" onclick="Chat.showConversations()">←</button>
                <div class="user-avatar-sm" id="chat-avatar" style="width:34px;height:34px;"></div>
                <div><div class="name" id="chat-contact-name">—</div><div class="chat-status">En ligne</div></div>
              </div>
              <div class="chat-messages" id="chat-messages"></div>
              <div class="chat-input-bar">
                <input class="chat-input" id="chat-input" type="text" placeholder="Écrivez un message…" autocomplete="off" maxlength="500">
                <button class="chat-send-btn" id="chat-send" onclick="Chat.send()">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                </button>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ══ MON PROFIL ══ -->
      <section class="page-section" data-id="profil">
        <div style="max-width:760px;margin:0 auto;">
          <div class="card" style="margin-bottom:1.25rem;">
            <div class="card-header"><span class="card-title">Mon Profil</span></div>
            <div class="card-body">
              <div class="profile-header">
                <div class="profile-avatar-wrap">
                  <div class="profile-avatar" id="profile-avatar">
                    <?php if ($photoProfil): ?>
                      <img src="<?= htmlspecialchars($photoProfil) ?>" alt="Photo de profil" id="profile-avatar-img">
                    <?php else: ?>
                      <span id="profile-avatar-initials"><?= htmlspecialchars($initials) ?></span>
                    <?php endif; ?>
                  </div>
                  <label class="btn btn-outline btn-sm profile-upload-btn">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 01-2 2H3a2 2 0 01-2-2V8a2 2 0 012-2h4l2-3h6l2 3h4a2 2 0 012 2z"/><circle cx="12" cy="13" r="4"/></svg>
                    Changer la photo
                    <input type="file" id="profile-pic-input" accept="image/jpeg,image/png,image/gif,image/webp" style="display:none;" onchange="Profile.uploadProfilePic(this)">
                  </label>
                </div>
                <div class="profile-info">
                  <h2 style="font-size:1.25rem;font-weight:800;"><?= htmlspecialchars($userPrenom . ' ' . $userNom) ?></h2>
                  <p style="color:var(--text-muted);font-size:0.875rem;margin-top:0.25rem;"><?= htmlspecialchars($userEmail) ?></p>
                </div>
              </div>
            </div>
          </div>

          <div class="card">
            <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;">
              <span class="card-title">Ma collection de photos</span>
              <label class="btn btn-primary btn-sm" style="cursor:pointer;margin:0;">
                + Ajouter
                <input type="file" id="collection-photo-input" accept="image/jpeg,image/png,image/gif,image/webp" style="display:none;" onchange="Profile.uploadCollectionPhoto(this)">
              </label>
            </div>
            <div class="card-body">
              <div id="collection-grid" class="collection-grid">
                <div class="empty-state" style="grid-column:1/-1;padding:2rem 0;">
                  <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                  <p>Chargement de votre collection…</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ══ NOTIFICATIONS ══ -->
      <section class="page-section" data-id="notifications">
        <div class="card">
          <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;">
            <span class="card-title">Notifications</span>
            <button class="btn btn-outline btn-sm" onclick="Notifications.markAllRead()">Tout marquer comme lu</button>
          </div>
          <div class="card-body">
            <div id="notif-list" class="notif-list">
              <div class="empty-state" style="padding:2rem 0;">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>
                <p>Chargement des notifications…</p>
              </div>
            </div>
          </div>
        </div>
      </section>

    </main>
  </div>
</div>

<!-- Modal: lier une activité -->
<div class="modal-overlay" id="modal-attach-activity">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title">Lier une activité</span>
      <button class="modal-close" data-modal-close>✕</button>
    </div>
    <div style="display:flex;flex-direction:column;gap:0.5rem;max-height:300px;overflow-y:auto;" id="attach-activity-list">
      <?php foreach ($activites as $a): ?>
        <button class="activite-item" onclick="Social.attachActivity(<?= $a['id_activite'] ?>, <?= json_encode($a['libelle']) ?>)" style="border:none;background:none;text-align:left;cursor:pointer;width:100%;">
          <div class="activite-dot <?= $etatDot[$a['id_etat']] ?? 'dot-pending' ?>"></div>
          <div class="activite-info"><div class="activite-label"><?= htmlspecialchars($a['libelle']) ?></div></div>
        </button>
      <?php endforeach; ?>
      <?php if (empty($activites)): ?><p style="color:var(--text-muted);font-size:0.875rem;text-align:center;padding:1rem;">Aucune activité</p><?php endif; ?>
    </div>
  </div>
</div>

<!-- Modal: partager activité -->
<div class="modal-overlay" id="modal-share-activity">
  <div class="modal">
    <div class="modal-header"><span class="modal-title">Partager l'activité</span><button class="modal-close" data-modal-close>✕</button></div>
    <div id="share-activity-preview" style="margin-bottom:1rem;"></div>
    <div class="form-group"><label>Message (optionnel)</label><textarea class="form-control" id="share-activity-text" rows="3" placeholder="Ajoutez un commentaire…"></textarea></div>
    <div class="form-group"><label>Visibilité</label><select class="form-control" id="share-visibilite"><option value="amis">👥 Amis</option><option value="public">🌍 Public</option></select></div>
    <div class="form-actions"><button class="btn btn-primary" onclick="Social.shareActivity()">Partager</button><button class="btn btn-outline" data-modal-close>Annuler</button></div>
  </div>
</div>

<script src="assets/js/app.js"></script>
<script src="assets/js/social.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const flash = document.getElementById('flash-data');
  if (flash.dataset.error)   WorkFlow.toast.show(flash.dataset.error,   'error');
  if (flash.dataset.success) WorkFlow.toast.show(flash.dataset.success, 'success');

  const dateInput = document.getElementById('date_activite');
  if (dateInput && !dateInput.value) dateInput.value = new Date().toISOString().split('T')[0];
});
</script>
<style>
@media (max-width: 900px) { .two-col-grid { grid-template-columns: 1fr !important; } }
#chat-active { display: none; flex-direction: column; overflow: hidden; }
#chat-active.visible { display: flex; flex: 1; }
.profile-header { display: flex; align-items: center; gap: 1.5rem; flex-wrap: wrap; }
.profile-avatar-wrap { display: flex; flex-direction: column; align-items: center; gap: 0.75rem; }
.profile-avatar {
  width: 96px; height: 96px; border-radius: 50%;
  background: linear-gradient(135deg, var(--primary), #7c3aed);
  display: flex; align-items: center; justify-content: center;
  font-size: 2rem; font-weight: 800; overflow: hidden; flex-shrink: 0;
}
.profile-avatar img { width: 100%; height: 100%; object-fit: cover; }
.collection-grid {
  display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 0.75rem;
}
.collection-item {
  position: relative; aspect-ratio: 1; border-radius: 10px; overflow: hidden;
  border: 1px solid var(--border); background: var(--surface2);
}
.collection-item img { width: 100%; height: 100%; object-fit: cover; }
.collection-item .delete-btn {
  position: absolute; top: 6px; right: 6px;
  width: 26px; height: 26px; border-radius: 50%;
  background: rgba(0,0,0,0.65); color: #fff; border: none;
  cursor: pointer; font-size: 0.75rem; display: flex; align-items: center; justify-content: center;
  opacity: 0; transition: opacity 0.2s;
}
.collection-item:hover .delete-btn { opacity: 1; }
.post-photo { width: 100%; max-height: 360px; object-fit: cover; border-radius: 8px; margin-top: 0.75rem; }
.post-activite-card {
  margin-top: 0.75rem; padding: 0.75rem; border-radius: 8px;
  background: rgba(79,70,229,0.06); border: 1px solid rgba(79,70,229,0.15);
}
.post-activite-card .meta { font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem; }
</style>
</body>
</html>
