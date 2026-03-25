<?php
require_once __DIR__ . '/../core/Flash.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Utilisateur.php';
require_once __DIR__ . '/../models/Ami.php';
require_once __DIR__ . '/../models/Post.php';
require_once __DIR__ . '/../models/ChatConversation.php';
require_once __DIR__ . '/../models/Activite.php';

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
                
                // Vérifier si relation existe déjà
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

            // ── Créer un post ─────────────────────────────
            case 'create-post':
                if ($method !== 'POST') $this->json(['error'=>'POST required'], 405);
                $data    = json_decode(file_get_contents('php://input'), true) ?? [];
                $contenu = trim($data['contenu'] ?? '');
                if ($contenu === '') $this->json(['error'=>'Contenu vide'], 400);
                $post = new Post();
                $post->creer([
                    'id_user'    => $userId,
                    'titre'      => trim($data['titre'] ?? ''),
                    'contenu'    => $contenu,
                    'visibilite' => in_array($data['visibilite']??'', ['public','amis']) ? $data['visibilite'] : 'amis',
                    'id_activite'=> isset($data['id_activite']) ? (int)$data['id_activite'] : null,
                ]);
                $this->json(['success' => true]);
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

            default:
                $this->json(['error' => 'Endpoint inconnu'], 404);
        }
    }
}
