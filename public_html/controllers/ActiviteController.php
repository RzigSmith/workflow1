<?php

require_once __DIR__ . "/../models/Activite.php";
require_once __DIR__ . "/../models/Notification.php";
require_once __DIR__ . "/../core/Flash.php";

class ActiviteController {

    public function creer() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                if (session_status() !== PHP_SESSION_ACTIVE) {
                    session_start();
                }


                $user_id = $_SESSION['user_id'] ?? null;
                
                if (empty($user_id)) {
                    set_flash('error', 'Vous devez être connecté pour créer une activité.');
                    header('Location: index.php?page=login');
                    exit;
                }

                $activite = new Activite();

                $data = [
                    'libelle' => $_POST['libelle'] ?? '',
                    'description' => $_POST['description'] ?? '',
                    'priorite' => $_POST['priorite'] ?? '',
                    'date_activite' => $_POST['date_activite'] ?? '',
                    'heure_debut' => $_POST['heure_debut'] ?? '',
                    'heure_fin' => $_POST['heure_fin'] ?? '',
                    'id_etat' => 1,
                    'id_user' => $user_id
                ];

                $activite->creer($data);

                // Récupérer id activité créée
                $db = Database::getInstance()->getconn();
                $id_activite = $db->lastInsertId();

                // Création de notification
                $notification = new Notification();
                $notification->date_notification = date('Y-m-d H:i:s');
                $notification->etat_notification = "non lue";
                $notification->id_activite = $id_activite;
                $notification->id_type = 1;
                $notification->id_user = $_SESSION['user_id'];

                $notification->creer();

                set_flash('success', 'Activité créée avec succès.');
                header('Location: index.php?page=dashboard');
                exit;
            } catch (Throwable $e) {
                set_flash('error', 'Impossible de créer l\'activité.' . (defined('DEBUG') && DEBUG ? ' ' . $e->getMessage() : ''));
                header('Location: index.php?page=dashboard');
                exit;
            }
        }
    }

    public function modifier() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                if (session_status() !== PHP_SESSION_ACTIVE) {
                    session_start();
                }

                $id_activite = (int)($_POST['id_activite'] ?? 0);
                $user_id = $_SESSION['user_id'] ?? null;

                if (!$id_activite || empty($user_id)) {
                    set_flash('error', 'Données invalides.');
                    header('Location: index.php?page=dashboard');
                    exit;
                }

                $activite = new Activite();
                $existante = $activite->obtenirParId($id_activite);

                if (!$existante || $existante['id_user'] != $user_id) {
                    set_flash('error', 'Activité non trouvée ou non autorisée.');
                    header('Location: index.php?page=dashboard');
                    exit;
                }

                $data = [
                    'libelle' => $_POST['libelle'] ?? '',
                    'description' => $_POST['description'] ?? '',
                    'priorite' => $_POST['priorite'] ?? '',
                    'date_activite' => $_POST['date_activite'] ?? '',
                    'heure_debut' => $_POST['heure_debut'] ?? '',
                    'heure_fin' => $_POST['heure_fin'] ?? '',
                    'id_etat' => (int)($_POST['id_etat'] ?? $existante['id_etat']),
                    'id_user' => $user_id
                ];

                $activite->modifier($id_activite, $data);

                set_flash('success', 'Activité modifiée avec succès.');
                header('Location: index.php?page=dashboard');
                exit;
            } catch (Throwable $e) {
                set_flash('error', 'Impossible de modifier l\'activité.' . (defined('DEBUG') && DEBUG ? ' ' . $e->getMessage() : ''));
                header('Location: index.php?page=dashboard');
                exit;
            }
        }
    }

    public function supprimer() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                if (session_status() !== PHP_SESSION_ACTIVE) {
                    session_start();
                }

                $id_activite = (int)($_POST['id_activite'] ?? 0);
                $user_id = $_SESSION['user_id'] ?? null;

                if (!$id_activite || empty($user_id)) {
                    set_flash('error', 'Données invalides.');
                    header('Location: index.php?page=dashboard');
                    exit;
                }

                $activite = new Activite();
                $existante = $activite->obtenirParId($id_activite);

                if (!$existante || $existante['id_user'] != $user_id) {
                    set_flash('error', 'Activité non trouvée ou non autorisée.');
                    header('Location: index.php?page=dashboard');
                    exit;
                }

                $activite->supprimer($id_activite, $user_id);

                set_flash('success', 'Activité supprimée avec succès.');
                header('Location: index.php?page=dashboard');
                exit;
            } catch (Throwable $e) {
                set_flash('error', 'Impossible de supprimer l\'activité.' . (defined('DEBUG') && DEBUG ? ' ' . $e->getMessage() : ''));
                header('Location: index.php?page=dashboard');
                exit;
            }
        }
    }
}