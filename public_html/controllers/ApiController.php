<?php
require_once __DIR__ . '/../core/Flash.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Utilisateur.php';
require_once __DIR__ . '/../models/Ami.php';
require_once __DIR__ . '/../models/Post.php';
require_once __DIR__ . '/../models/ChatConversation.php';
require_once __DIR__ . '/../models/Activite.php';
require_once __DIR__ . '/../models/Notification.php';

class ApiController {

    private function json(mixed $data, int $status = 200): void {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function requireAuth(): int {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        if (empty($_SESSION['user_id'])) {
            $this->json(['error' => 'Non authentifié'], 401);
        }
        return (int) $_SESSION['user_id'];
    }

    private function requireAdmin(): int {
        $userId = $this->requireAuth();
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        if (empty($_SESSION['id_role']) || (int)$_SESSION['id_role'] !== 1) {
            $this->json(['error' => 'Accès refusé – Admin requis'], 403);
        }
        return $userId;
    }

    /**
     * Gère l'upload d'un fichier image et retourne le chemin relatif.
     */
    private function handleImageUpload(string $fileKey, string $subfolder = 'posts'): ?string {
        if (!isset($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $file = $_FILES[$fileKey];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);

        if (!in_array($mime, $allowedTypes)) {
            return null;
        }

        if ($file['size'] > 5 * 1024 * 1024) { // 5 MB max
            return null;
        }

        $ext = match($mime) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
            'image/webp' => 'webp',
            default      => 'jpg'
        };

        $uploadDir = __DIR__ . "/../uploads/$subfolder/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $filename = bin2hex(random_bytes(12)) . '.' . $ext;
        $destination = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            return null;
        }

        return "uploads/$subfolder/$filename";
    }

    public function handle(string $endpoint): void {
        $userId = $this->requireAuth();
        $method = $_SERVER['REQUEST_METHOD'];

        switch ($endpoint) {

            // ── Recherche d'utilisateurs ──────────────────
            case 'search-users':
                $q = trim($_GET['q'] ?? '');
                if (strlen($q) < 2) { $this->json([]); }
                $userModel = new Utilisateur();
                $results   = $userModel->searchByName($q, $userId);
                $amiModel  = new Ami();
                foreach ($results as &$u) {
                    $u['relation'] = $amiModel->statutRelation($userId, (int)$u['id_user']);
                }
                $this->json($results);
                break;

            // ── Envoyer demande d'ami ─────────────────────
            case 'add-friend':
                if ($method !== 'POST') $this->json(['error'=>'POST required'], 405);
                $data   = json_decode(file_get_contents('php://input'), true) ?? [];
                $target = (int)($data['id_user'] ?? 0);
                if (!$target || $target === $userId) $this->json(['error'=>'Invalid'], 400);
                $model  = new Ami();

                $statut = $model->statutRelation($userId, $target);
                if ($statut === 'accepted') {
                    $this->json(['error'=>'Vous êtes déjà amis'], 400);
                }
                if ($statut === 'sent') {
                    $this->json(['error'=>'Demande déjà envoyée'], 400);
                }
                if ($statut === 'received') {
                    $this->json(['error'=>'Acceptez d\'abord sa demande'], 400);
                }
                if ($statut === 'declined' && !$model->peutRenvoyerDemande($userId, $target)) {
                    $this->json(['error'=>'Attendez 24h avant de renvoyer'], 429);
                }

                $model->envoyerDemande($userId, $target);
                $this->json(['success' => true]);
                break;

            // ── Annuler une demande d'ami ─────────────────
            case 'cancel-friend-request':
                if ($method !== 'POST') $this->json(['error'=>'POST required'], 405);
                $data   = json_decode(file_get_contents('php://input'), true) ?? [];
                $target = (int)($data['id_user'] ?? 0);
                if (!$target) $this->json(['error'=>'Invalid'], 400);
                $model  = new Ami();
                $model->annulerDemande($userId, $target);
                $this->json(['success' => true]);
                break;

            // ── Répondre à une demande ────────────────────
            case 'friend-respond':
                if ($method !== 'POST') $this->json(['error'=>'POST required'], 405);
                $data      = json_decode(file_get_contents('php://input'), true) ?? [];
                $id_amitie = (int)($data['id_amitie'] ?? 0);
                $statut    = in_array($data['statut'] ?? '', ['accepted','declined']) ? $data['statut'] : null;
                if (!$id_amitie || !$statut) $this->json(['error'=>'Invalid'], 400);
                $model  = new Ami();
                $model->repondre($id_amitie, $userId, $statut);
                $this->json(['success' => true]);
                break;

            // ── Retirer un ami ────────────────────────────
            case 'remove-friend':
                if ($method !== 'POST') $this->json(['error'=>'POST required'], 405);
                $data   = json_decode(file_get_contents('php://input'), true) ?? [];
                $target = (int)($data['id_user'] ?? 0);
                if (!$target) $this->json(['error'=>'Invalid'], 400);
                $model  = new Ami();
                $model->supprimerAmitie($userId, $target);
                $this->json(['success' => true]);
                break;

            // ── Ouvrir/créer une conversation ─────────────
            case 'start-conversation':
                if ($method !== 'POST') $this->json(['error'=>'POST required'], 405);
                $data   = json_decode(file_get_contents('php://input'), true) ?? [];
                $target = (int)($data['id_user'] ?? 0);
                if (!$target) $this->json(['error'=>'Invalid'], 400);
                $amiModel = new Ami();
                if (!$amiModel->sontAmis($userId, $target)) {
                    $this->json(['error'=>'Vous n\'êtes pas amis'], 403);
                }
                $chat   = new ChatConversation();
                $convId = $chat->trouverOuCreer($userId, $target);
                $this->json(['id_conversation' => $convId]);
                break;

            // ── Récupérer messages d'une conversation ─────
            case 'get-messages':
                $convId = (int)($_GET['conv_id'] ?? 0);
                $lastId = (int)($_GET['last_id'] ?? 0);
                if (!$convId) $this->json([], 400);
                $chat = new ChatConversation();
                if ($lastId > 0) {
                    $messages = $chat->getMessagesSince($convId, $userId, $lastId);
                } else {
                    $messages = $chat->getMessages($convId, $userId);
                }
                $this->json($messages);
                break;

            // ── Envoyer un message ────────────────────────
            case 'send-message':
                if ($method !== 'POST') $this->json(['error'=>'POST required'], 405);
                $data   = json_decode(file_get_contents('php://input'), true) ?? [];
                $convId = (int)($data['conv_id'] ?? 0);
                $contenu = trim($data['contenu'] ?? '');
                if (!$convId || $contenu === '') $this->json(['error'=>'Invalid'], 400);
                $chat = new ChatConversation();
                $ok   = $chat->envoyerMessage($convId, $userId, $contenu);

                // Créer une notification "Nouveau message" pour le destinataire
                $db = Database::getInstance()->getConn();
                $stmt = $db->prepare("SELECT id_user FROM conversation_membre WHERE id_conversation = :cid AND id_user != :uid");
                $stmt->execute([':cid' => $convId, ':uid' => $userId]);
                $recipient = $stmt->fetchColumn();
                if ($recipient) {
                    $notif = new Notification();
                    $notif->id_user = (int)$recipient;
                    $notif->id_type = 3; // Nouveau message
                    $notif->id_activite = null;
                    $notif->message = 'Vous avez reçu un nouveau message.';
                    $notif->creer();
                }

                $this->json(['success' => $ok]);
                break;

            // ── Liste conversations ────────────────────────
            case 'get-conversations':
                $chat  = new ChatConversation();
                $convs = $chat->getMesConversations($userId);
                $this->json($convs);
                break;

            // ── Liste amis (fallback mobile) ────────────────
            case 'get-friends':
                $ami = new Ami();
                $amis = $ami->getAmis($userId);
                $this->json($amis);
                break;

            // ── Créer un post (avec upload photo optionnel) ─
            case 'create-post':
                if ($method !== 'POST') $this->json(['error'=>'POST required'], 405);

                // Déterminer si c'est multipart ou JSON
                $photoPath = null;
                if (!empty($_FILES['photo'])) {
                    // multipart/form-data
                    $contenu    = trim($_POST['contenu'] ?? '');
                    $titre      = trim($_POST['titre'] ?? '');
                    $visibilite = in_array($_POST['visibilite'] ?? '', ['public','amis']) ? $_POST['visibilite'] : 'amis';
                    $id_activite = isset($_POST['id_activite']) && $_POST['id_activite'] !== '' ? (int)$_POST['id_activite'] : null;
                    $photoPath  = $this->handleImageUpload('photo', 'posts');
                } else {
                    // JSON
                    $data    = json_decode(file_get_contents('php://input'), true) ?? [];
                    $contenu = trim($data['contenu'] ?? '');
                    $titre   = trim($data['titre'] ?? '');
                    $visibilite = in_array($data['visibilite'] ?? '', ['public','amis']) ? $data['visibilite'] : 'amis';
                    $id_activite = isset($data['id_activite']) ? (int)$data['id_activite'] : null;
                }

                if ($contenu === '') $this->json(['error'=>'Contenu vide'], 400);
                $post = new Post();
                $post->creer([
                    'id_user'    => $userId,
                    'titre'      => $titre,
                    'contenu'    => $contenu,
                    'visibilite' => $visibilite,
                    'id_activite'=> $id_activite,
                    'photo_path' => $photoPath,
                ]);
                $this->json(['success' => true, 'photo_path' => $photoPath]);
                break;

            // ── Supprimer un post ─────────────────────────
            case 'delete-post':
                if ($method !== 'POST') $this->json(['error'=>'POST required'], 405);
                $data    = json_decode(file_get_contents('php://input'), true) ?? [];
                $id_post = (int)($data['id_post'] ?? 0);
                $post = new Post();
                $post->supprimer($id_post, $userId);
                $this->json(['success' => true]);
                break;

            // ── Données du feed + amis ────────────────────
            case 'get-feed':
                $amiModel = new Ami();
                $amis     = $amiModel->getAmis($userId);
                $amisIds  = array_column($amis, 'id_user');
                $postModel = new Post();
                $posts    = $postModel->getFeed($userId, array_map('intval', $amisIds));
                $this->json($posts);
                break;

            // ── Mettre à jour état activité ───────────────
            case 'update-activite-etat':
                if ($method !== 'POST') $this->json(['error'=>'POST required'], 405);
                $data = json_decode(file_get_contents('php://input'), true) ?? [];
                $id_activite = (int)($data['id_activite'] ?? 0);
                if (!$id_activite) $this->json(['error'=>'Invalid'], 400);
                $activiteModel = new Activite();
                $activiteModel->mettreAJourEtat($id_activite);
                $activite = $activiteModel->obtenirParId($id_activite);
                $this->json(['success' => true, 'id_etat' => $activite['id_etat']]);
                break;

            // ── Marquer messages comme lus ──────────────────
            case 'mark-messages-read':
                if ($method !== 'POST') $this->json(['error'=>'POST required'], 405);
                $data = json_decode(file_get_contents('php://input'), true) ?? [];
                $convId = (int)($data['conv_id'] ?? 0);
                if (!$convId) $this->json(['error'=>'Invalid'], 400);
                $chat = new ChatConversation();
                $chat->marquerCommeLus($convId, $userId);
                $this->json(['success' => true]);
                break;

            // ── Compter messages non lus ────────────────────
            case 'unread-count':
                $chat = new ChatConversation();
                $count = $chat->comptNonLus($userId);
                $this->json(['unread' => $count]);
                break;

            // ── Mettre à jour statut en ligne ──────────────
            case 'update-online-status':
                if ($method !== 'POST') $this->json(['error'=>'POST required'], 405);
                $data = json_decode(file_get_contents('php://input'), true) ?? [];
                $online = isset($data['online']) ? (int)$data['online'] : 1;
                $db = Database::getInstance()->getConn();
                $stmt = $db->prepare("UPDATE utilisateur SET statut_en_ligne = :status WHERE id_user = :id");
                $stmt->execute([':status' => $online, ':id' => $userId]);
                $this->json(['success' => true]);
                break;

            // ══════════════════════════════════════════════
            // ── PROFIL & COLLECTION ────────────────────────
            // ══════════════════════════════════════════════

            // ── Upload photo de profil ─────────────────────
            case 'upload-profile-pic':
                if ($method !== 'POST') $this->json(['error'=>'POST required'], 405);
                $path = $this->handleImageUpload('photo', 'profils');
                if (!$path) $this->json(['error'=>'Fichier invalide ou trop volumineux (max 5 Mo)'], 400);
                $userModel = new Utilisateur();
                $userModel->updateProfilePic($userId, $path);
                $this->json(['success' => true, 'path' => $path]);
                break;

            // ── Upload photo dans la collection ────────────
            case 'upload-collection-photo':
                if ($method !== 'POST') $this->json(['error'=>'POST required'], 405);
                $path = $this->handleImageUpload('photo', 'collection');
                if (!$path) $this->json(['error'=>'Fichier invalide ou trop volumineux (max 5 Mo)'], 400);
                $userModel = new Utilisateur();
                $userModel->addToCollection($userId, $path);
                $this->json(['success' => true, 'path' => $path]);
                break;

            // ── Supprimer photo de la collection ───────────
            case 'delete-collection-photo':
                if ($method !== 'POST') $this->json(['error'=>'POST required'], 405);
                $data = json_decode(file_get_contents('php://input'), true) ?? [];
                $photoId = (int)($data['id_photo'] ?? 0);
                if (!$photoId) $this->json(['error'=>'Invalid'], 400);
                $userModel = new Utilisateur();
                $ok = $userModel->deleteFromCollection($photoId, $userId);
                $this->json(['success' => $ok]);
                break;

            // ── Récupérer la collection d'un utilisateur ───
            case 'get-collection':
                $userModel = new Utilisateur();
                $photos = $userModel->getCollection($userId);
                $this->json($photos);
                break;

            // ══════════════════════════════════════════════
            // ── PARTAGE D'ACTIVITÉS ────────────────────────
            // ══════════════════════════════════════════════

            // ── Ajouter une activité partagée à son agenda ─
            case 'add-shared-activity':
                if ($method !== 'POST') $this->json(['error'=>'POST required'], 405);
                $data = json_decode(file_get_contents('php://input'), true) ?? [];
                $id_activite = (int)($data['id_activite'] ?? 0);
                if (!$id_activite) $this->json(['error'=>'Invalid'], 400);

                $activiteModel = new Activite();
                $source = $activiteModel->obtenirParId($id_activite);

                if (!$source) $this->json(['error'=>'Activité introuvable'], 404);
                if ((int)$source['id_user'] === $userId) $this->json(['error'=>'Vous ne pouvez pas copier votre propre activité'], 400);

                // Copier l'activité dans l'agenda de l'utilisateur connecté
                $activiteModel->creer([
                    'libelle'      => $source['libelle'],
                    'description'  => $source['description'] ?? '',
                    'priorite'     => $source['priorite'],
                    'date_activite'=> $source['date_activite'],
                    'heure_debut'  => $source['heure_debut'],
                    'heure_fin'    => $source['heure_fin'],
                    'id_etat'      => 1, // Toujours "À faire" pour le nouvel utilisateur
                    'id_user'      => $userId,
                ]);

                // Notifier le propriétaire de l'activité source
                $notif = new Notification();
                $notif->id_user = (int)$source['id_user'];
                $notif->id_type = 6; // Partage activité
                $notif->id_activite = $id_activite;
                $notif->message = 'Un utilisateur a ajouté votre activité "' . htmlspecialchars($source['libelle']) . '" à son agenda.';
                $notif->creer();

                $this->json(['success' => true]);
                break;

            // ══════════════════════════════════════════════
            // ── NOTIFICATIONS ─────────────────────────────
            // ══════════════════════════════════════════════

            // ── Récupérer les notifications ────────────────
            case 'get-notifications':
                $notifModel = new Notification();
                $notifications = $notifModel->listerParUser($userId);
                $unread = $notifModel->compterNonLues($userId);
                $this->json(['notifications' => $notifications, 'unread' => $unread]);
                break;

            // ── Marquer les notifications comme lues ───────
            case 'mark-notifications-read':
                if ($method !== 'POST') $this->json(['error'=>'POST required'], 405);
                $notifModel = new Notification();
                $notifModel->marquerCommeLus($userId);
                $this->json(['success' => true]);
                break;

            // ══════════════════════════════════════════════
            // ── ADMINISTRATION ────────────────────────────
            // ══════════════════════════════════════════════

            // ── Stats globales ─────────────────────────────
            case 'admin-get-stats':
                $this->requireAdmin();
                $db = Database::getInstance()->getConn();
                $users    = $db->query("SELECT COUNT(*) FROM utilisateur")->fetchColumn();
                $online   = $db->query("SELECT COUNT(*) FROM utilisateur WHERE statut_en_ligne = 1")->fetchColumn();
                $posts    = $db->query("SELECT COUNT(*) FROM post")->fetchColumn();
                $activites= $db->query("SELECT COUNT(*) FROM activite")->fetchColumn();
                $this->json([
                    'total_users'   => (int)$users,
                    'online_users'  => (int)$online,
                    'total_posts'   => (int)$posts,
                    'total_activites' => (int)$activites,
                ]);
                break;

            // ── Liste utilisateurs ─────────────────────────
            case 'admin-get-users':
                $this->requireAdmin();
                $db = Database::getInstance()->getConn();
                $stmt = $db->query("SELECT u.id_user, u.nom, u.prenom, u.email, u.username, u.id_role, u.statut_en_ligne, u.email_verified, u.created_at, r.libelle_role FROM utilisateur u JOIN role r ON u.id_role = r.id_role ORDER BY u.created_at DESC");
                $this->json($stmt->fetchAll(PDO::FETCH_ASSOC));
                break;

            // ── Changer le rôle d'un utilisateur ──────────
            case 'admin-toggle-role':
                $this->requireAdmin();
                if ($method !== 'POST') $this->json(['error'=>'POST required'], 405);
                $data = json_decode(file_get_contents('php://input'), true) ?? [];
                $targetId = (int)($data['id_user'] ?? 0);
                $newRole  = (int)($data['id_role'] ?? 0);
                if (!$targetId || !in_array($newRole, [1, 2])) $this->json(['error'=>'Invalid'], 400);
                if ($targetId === $userId) $this->json(['error'=>'Vous ne pouvez pas modifier votre propre rôle'], 400);
                $userModel = new Utilisateur();
                $userModel->updateRole($targetId, $newRole);
                $this->json(['success' => true]);
                break;

            // ── Supprimer un utilisateur ───────────────────
            case 'admin-delete-user':
                $this->requireAdmin();
                if ($method !== 'POST') $this->json(['error'=>'POST required'], 405);
                $data = json_decode(file_get_contents('php://input'), true) ?? [];
                $targetId = (int)($data['id_user'] ?? 0);
                if (!$targetId) $this->json(['error'=>'Invalid'], 400);
                if ($targetId === $userId) $this->json(['error'=>'Vous ne pouvez pas vous supprimer vous-même'], 400);
                $userModel = new Utilisateur();
                $userModel->delete($targetId);
                $this->json(['success' => true]);
                break;

            // ── Liste de tous les posts (admin) ────────────
            case 'admin-get-posts':
                $this->requireAdmin();
                $postModel = new Post();
                $this->json($postModel->listerTous());
                break;

            // ── Supprimer un post (admin) ──────────────────
            case 'admin-delete-post':
                $this->requireAdmin();
                if ($method !== 'POST') $this->json(['error'=>'POST required'], 405);
                $data = json_decode(file_get_contents('php://input'), true) ?? [];
                $id_post = (int)($data['id_post'] ?? 0);
                if (!$id_post) $this->json(['error'=>'Invalid'], 400);
                $postModel = new Post();
                $postModel->supprimer($id_post); // Sans contrainte d'id_user
                $this->json(['success' => true]);
                break;

            // ── Liste de toutes les activités (admin) ──────
            case 'admin-get-activities':
                $this->requireAdmin();
                $activiteModel = new Activite();
                $this->json($activiteModel->listerToutes());
                break;

            // ── Supprimer une activité (admin) ─────────────
            case 'admin-delete-activity':
                $this->requireAdmin();
                if ($method !== 'POST') $this->json(['error'=>'POST required'], 405);
                $data = json_decode(file_get_contents('php://input'), true) ?? [];
                $id_activite = (int)($data['id_activite'] ?? 0);
                if (!$id_activite) $this->json(['error'=>'Invalid'], 400);
                $activiteModel = new Activite();
                $activiteModel->supprimerParAdmin($id_activite);
                $this->json(['success' => true]);
                break;

            default:
                $this->json(['error' => 'Endpoint inconnu'], 404);
        }
    }
}
