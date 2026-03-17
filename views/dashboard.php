<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

require_once __DIR__ . '/../core/Flash.php';
require_once __DIR__ . '/../models/Activite.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /?page=login');
    exit;
}

$error   = get_flash('error');
$success = get_flash('success');

$activiteModel = new Activite();
$activites     = $activiteModel->listerParUser($_SESSION['user_id']);

$total    = count($activites);
$pending  = count(array_filter($activites, fn($a) => $a['id_etat'] == 1));
$progress = count(array_filter($activites, fn($a) => $a['id_etat'] == 2));
$done     = count(array_filter($activites, fn($a) => $a['id_etat'] == 3));

$userNom = $_SESSION['nom'] ?? 'Utilisateur';
$initials = strtoupper(substr($userNom, 0, 1));

$priorityLabels = ['haute' => 'Haute', 'moyenne' => 'Moyenne', 'basse' => 'Basse'];
$priorityBadge  = ['haute' => 'badge-high', 'moyenne' => 'badge-medium', 'basse' => 'badge-low'];
$etatDot        = [1 => 'dot-pending', 2 => 'dot-progress', 3 => 'dot-done'];
$etatLabel      = [1 => 'En attente', 2 => 'En cours', 3 => 'Terminée'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tableau de bord – WorkFlow</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>

<div id="nprogress-bar"></div>
<div id="toast-container"></div>

<?php if ($error): ?>
  <div id="flash-error" data-flash="error" data-message="<?= htmlspecialchars($error) ?>" style="display:none;"></div>
<?php endif; ?>
<?php if ($success): ?>
  <div id="flash-success" data-flash="success" data-message="<?= htmlspecialchars($success) ?>" style="display:none;"></div>
<?php endif; ?>

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
        <?php if ($total > 0): ?>
          <span class="nav-badge" style="background:var(--primary);"><?= $total ?></span>
        <?php endif; ?>
      </button>

      <button class="nav-item" data-section="nouvelle-activite" data-title="Nouvelle activité">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
        Nouvelle activité
      </button>

      <div class="nav-section-label" style="margin-top:0.75rem;">Collaboration</div>

      <button class="nav-item" data-section="notifications" data-title="Notifications">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>
        Notifications
        <?php if ($pending > 0): ?>
          <span class="nav-badge"><?= $pending ?></span>
        <?php endif; ?>
      </button>

      <button class="nav-item" data-section="messages" data-title="Messages">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
        Messages
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
      <a href="/?action=logout" class="nav-item" style="margin-top:4px;" data-confirm="Voulez-vous vraiment vous déconnecter ?">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        Déconnexion
      </a>
    </div>
  </nav>

  <!-- ── MAIN CONTENT ── -->
  <div class="main-content">

    <!-- Topbar -->
    <header class="topbar">
      <div class="topbar-left">
        <button class="sidebar-toggle" id="sidebar-toggle" aria-label="Menu">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
        </button>
        <span class="topbar-title" id="topbar-title">Tableau de bord</span>
      </div>
      <div class="topbar-right">
        <button class="icon-btn" title="Notifications" onclick="WorkFlow.nav.navigate('notifications','Notifications')">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>
          <?php if ($pending > 0): ?><span class="notif-dot"></span><?php endif; ?>
        </button>
        <div class="user-avatar" style="width:32px;height:32px;font-size:0.75rem;cursor:default;"><?= htmlspecialchars($initials) ?></div>
      </div>
    </header>

    <!-- Page body -->
    <main class="page-body">

      <!-- ══ SECTION: ACCUEIL ══ -->
      <section class="page-section active" data-id="accueil">
        <div class="breadcrumb">
          <span>WorkFlow</span>
          <span class="breadcrumb-sep">›</span>
          <span>Tableau de bord</span>
        </div>

        <h2 style="font-size:1.3rem;font-weight:800;margin-bottom:0.25rem;">
          Bonjour, <?= htmlspecialchars($userNom) ?> 👋
        </h2>
        <p style="color:var(--text-muted);font-size:0.875rem;margin-bottom:1.5rem;">
          Voici un aperçu de vos activités en cours.
        </p>

        <!-- Stats -->
        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-icon indigo">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg>
            </div>
            <div>
              <div class="stat-value"><?= $total ?></div>
              <div class="stat-label">Total activités</div>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon amber">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
            <div>
              <div class="stat-value"><?= $pending ?></div>
              <div class="stat-label">En attente</div>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon cyan">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"/></svg>
            </div>
            <div>
              <div class="stat-value"><?= $progress ?></div>
              <div class="stat-label">En cours</div>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon green">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
            </div>
            <div>
              <div class="stat-value"><?= $done ?></div>
              <div class="stat-label">Terminées</div>
            </div>
          </div>
        </div>

        <!-- Recent activities -->
        <div class="card">
          <div class="card-header">
            <span class="card-title">Activités récentes</span>
            <button class="btn btn-primary btn-sm" onclick="WorkFlow.nav.navigate('nouvelle-activite','Nouvelle activité')">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
              Ajouter
            </button>
          </div>
          <div class="card-body">
            <?php if (empty($activites)): ?>
              <div class="empty-state">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
                <p>Aucune activité pour l'instant.<br>
                  <a href="#" onclick="WorkFlow.nav.navigate('nouvelle-activite','Nouvelle activité');return false;" style="color:var(--primary);font-weight:600;">Créer votre première activité →</a>
                </p>
              </div>
            <?php else: ?>
              <div class="activite-list">
                <?php foreach (array_slice($activites, 0, 6) as $a): ?>
                  <div class="activite-item">
                    <div class="activite-dot <?= $etatDot[$a['id_etat']] ?? 'dot-pending' ?>"></div>
                    <div class="activite-info">
                      <div class="activite-label"><?= htmlspecialchars($a['libelle']) ?></div>
                      <div class="activite-meta">
                        <?= $etatLabel[$a['id_etat']] ?? '—' ?>
                        <?php if ($a['date_activite']): ?>
                          · <?= htmlspecialchars($a['date_activite']) ?>
                        <?php endif; ?>
                      </div>
                    </div>
                    <?php $p = strtolower($a['priorite'] ?? ''); ?>
                    <?php if (isset($priorityBadge[$p])): ?>
                      <span class="badge <?= $priorityBadge[$p] ?>"><?= $priorityLabels[$p] ?></span>
                    <?php endif; ?>
                  </div>
                <?php endforeach; ?>
              </div>
              <?php if ($total > 6): ?>
                <div style="text-align:center;margin-top:1rem;">
                  <button class="btn btn-outline btn-sm" onclick="WorkFlow.nav.navigate('activites','Activités')">
                    Voir toutes les activités (<?= $total ?>)
                  </button>
                </div>
              <?php endif; ?>
            <?php endif; ?>
          </div>
        </div>
      </section>

      <!-- ══ SECTION: ACTIVITÉS ══ -->
      <section class="page-section" data-id="activites">
        <div class="breadcrumb">
          <span>WorkFlow</span>
          <span class="breadcrumb-sep">›</span>
          <span>Activités</span>
        </div>

        <div class="card">
          <div class="card-header">
            <span class="card-title">Toutes mes activités</span>
            <button class="btn btn-primary btn-sm" onclick="WorkFlow.nav.navigate('nouvelle-activite','Nouvelle activité')">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
              Nouvelle activité
            </button>
          </div>
          <div class="card-body">
            <?php if (empty($activites)): ?>
              <div class="empty-state">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg>
                <p>Aucune activité à afficher.</p>
              </div>
            <?php else: ?>
              <div class="activite-list">
                <?php foreach ($activites as $a): ?>
                  <div class="activite-item">
                    <div class="activite-dot <?= $etatDot[$a['id_etat']] ?? 'dot-pending' ?>"></div>
                    <div class="activite-info">
                      <div class="activite-label"><?= htmlspecialchars($a['libelle']) ?></div>
                      <div class="activite-meta">
                        <?php if ($a['description']): ?>
                          <?= htmlspecialchars(mb_substr($a['description'], 0, 60)) ?><?= mb_strlen($a['description']) > 60 ? '…' : '' ?> ·
                        <?php endif; ?>
                        <?= $etatLabel[$a['id_etat']] ?? '—' ?>
                        <?php if ($a['date_activite']): ?> · <?= htmlspecialchars($a['date_activite']) ?><?php endif; ?>
                        <?php if ($a['heure_debut'] && $a['heure_fin']): ?>
                          · <?= htmlspecialchars($a['heure_debut']) ?> → <?= htmlspecialchars($a['heure_fin']) ?>
                        <?php endif; ?>
                      </div>
                    </div>
                    <?php $p = strtolower($a['priorite'] ?? ''); ?>
                    <?php if (isset($priorityBadge[$p])): ?>
                      <span class="badge <?= $priorityBadge[$p] ?>"><?= $priorityLabels[$p] ?></span>
                    <?php endif; ?>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </section>

      <!-- ══ SECTION: NOUVELLE ACTIVITÉ ══ -->
      <section class="page-section" data-id="nouvelle-activite">
        <div class="breadcrumb">
          <span>WorkFlow</span>
          <span class="breadcrumb-sep">›</span>
          <span>Activités</span>
          <span class="breadcrumb-sep">›</span>
          <span>Nouvelle activité</span>
        </div>

        <div class="card" style="max-width:620px;">
          <div class="card-header">
            <span class="card-title">Créer une activité</span>
          </div>
          <div class="card-body">
            <form method="POST" action="/?action=creer-activite" novalidate>

              <div class="form-group">
                <label for="libelle">Titre de l'activité *</label>
                <input class="form-control" type="text" id="libelle" name="libelle"
                       placeholder="Ex: Préparer la réunion hebdomadaire" required>
              </div>

              <div class="form-group">
                <label for="description">Description</label>
                <textarea class="form-control" id="description" name="description" rows="3"
                          placeholder="Décrivez l'activité…" style="resize:vertical;"></textarea>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label for="priorite">Priorité *</label>
                  <select class="form-control" id="priorite" name="priorite" required>
                    <option value="">Choisir…</option>
                    <option value="haute">🔴 Haute</option>
                    <option value="moyenne">🟡 Moyenne</option>
                    <option value="basse">🟢 Basse</option>
                  </select>
                </div>
                <div class="form-group">
                  <label for="date_activite">Date *</label>
                  <input class="form-control" type="date" id="date_activite" name="date_activite" required>
                </div>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label for="heure_debut">Heure de début</label>
                  <input class="form-control" type="time" id="heure_debut" name="heure_debut">
                </div>
                <div class="form-group">
                  <label for="heure_fin">Heure de fin</label>
                  <input class="form-control" type="time" id="heure_fin" name="heure_fin">
                </div>
              </div>

              <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                  Créer l'activité
                </button>
                <button type="button" class="btn btn-outline" onclick="WorkFlow.nav.navigate('activites','Activités')">
                  Annuler
                </button>
              </div>

            </form>
          </div>
        </div>
      </section>

      <!-- ══ SECTION: NOTIFICATIONS ══ -->
      <section class="page-section" data-id="notifications">
        <div class="breadcrumb">
          <span>WorkFlow</span>
          <span class="breadcrumb-sep">›</span>
          <span>Notifications</span>
        </div>

        <div class="card">
          <div class="card-header">
            <span class="card-title">Notifications</span>
            <?php if ($pending > 0): ?>
              <span class="badge" style="background:var(--danger);color:#fff;font-size:0.75rem;"><?= $pending ?> non lue(s)</span>
            <?php endif; ?>
          </div>
          <div class="card-body">
            <?php if (empty($activites)): ?>
              <div class="empty-state">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>
                <p>Aucune notification.</p>
              </div>
            <?php else: ?>
              <div class="notif-list">
                <?php foreach ($activites as $a): ?>
                  <div class="notif-item unread">
                    <div class="notif-icon">
                      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                    </div>
                    <div>
                      <div class="notif-text">Activité créée : <strong><?= htmlspecialchars($a['libelle']) ?></strong></div>
                      <div class="notif-time"><?= $a['date_activite'] ? htmlspecialchars($a['date_activite']) : 'Date non définie' ?></div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </section>

      <!-- ══ SECTION: MESSAGES ══ -->
      <section class="page-section" data-id="messages">
        <div class="breadcrumb">
          <span>WorkFlow</span>
          <span class="breadcrumb-sep">›</span>
          <span>Messages</span>
        </div>

        <div class="card">
          <div class="card-header">
            <span class="card-title">Conversations</span>
          </div>
          <div class="card-body">
            <div class="empty-state">
              <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
              <p>La messagerie sera disponible prochainement.</p>
            </div>
          </div>
        </div>
      </section>

    </main>
  </div><!-- /main-content -->
</div><!-- /app-layout -->

<script src="/assets/js/app.js"></script>
<script>
  // Pick up flash messages from hidden divs and display as toasts
  document.addEventListener('DOMContentLoaded', function () {
    const errEl  = document.getElementById('flash-error');
    const succEl = document.getElementById('flash-success');
    if (errEl)  WorkFlow.toast.show(errEl.dataset.message, 'error');
    if (succEl) WorkFlow.toast.show(succEl.dataset.message, 'success');

    // Set today as default date for new activity
    const dateInput = document.getElementById('date_activite');
    if (dateInput && !dateInput.value) {
      dateInput.value = new Date().toISOString().split('T')[0];
    }
  });
</script>
</body>
</html>
