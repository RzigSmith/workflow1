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

                if (empty($_SESSION['user_id'])) {
                    set_flash('error', 'Vous devez être connecté pour créer une activité.');
                    header('Location: /?page=login');
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
                    'id_user' => $_SESSION['user_id']
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

                $notification->creer();

                set_flash('success', 'Activité créée avec succès.');
                header('Location: /?page=dashboard');
                exit;
            } catch (Throwable $e) {
                set_flash('error', 'Impossible de créer l\'activité.' . (defined('DEBUG') && DEBUG ? ' ' . $e->getMessage() : ''));
                header('Location: /?page=dashboard');
                exit;
            }
        }
    }
}