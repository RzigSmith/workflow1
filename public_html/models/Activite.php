<?php

require_once __DIR__ . '/../core/Model.php';
class Activite extends Model {
    private $table = "activite";

    public function creer($data) {
        // Vérifier que id_user n'est pas vide
        if (empty($data['id_user'])) {
            throw new Exception('L\'id_user est obligatoire pour créer une activité.');
        }

        $sql = "INSERT INTO $this->table
                (libelle, description, priorite, date_activite, heure_debut, heure_fin, id_etat, id_user)
                values 
                (:libelle, :description, :priorite, :date_activite, :heure_debut, :heure_fin, :id_etat, :id_user)";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':libelle' => $data['libelle'],
            ':description' => $data['description'],
            ':priorite' => $data['priorite'],
            ':date_activite' => $data['date_activite'],
            ':heure_debut' => $data['heure_debut'],
            ':heure_fin' => $data['heure_fin'],
            ':id_etat' => $data['id_etat'],
            ':id_user' => (int)$data['id_user']
        ]);
    }


    public function listerParUser($id_user) {
        $sql = "SELECT * FROM activite WHERE id_user = :id_user";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id_user' => $id_user]);
        $activites = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Mise à jour automatique du statut en fonction de l'heure et de la date
        foreach ($activites as $a) {
            $this->mettreAJourEtat($a['id_activite']);
        }

        // Recharger après mise à jour pour refléter l'état exact
        $stmt->execute([':id_user' => $id_user]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenirParId($id_activite) {
        $sql = "SELECT * FROM $this->table WHERE id_activite = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id_activite]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function modifier($id_activite, $data) {
        $sql = "UPDATE $this->table 
                SET libelle = :libelle, 
                    description = :description,
                    priorite = :priorite,
                    date_activite = :date_activite,
                    heure_debut = :heure_debut,
                    heure_fin = :heure_fin,
                    id_etat = :id_etat
                WHERE id_activite = :id_activite AND id_user = :id_user";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':libelle' => $data['libelle'],
            ':description' => $data['description'],
            ':priorite' => $data['priorite'],
            ':date_activite' => $data['date_activite'],
            ':heure_debut' => $data['heure_debut'],
            ':heure_fin' => $data['heure_fin'],
            ':id_etat' => $data['id_etat'],
            ':id_activite' => $id_activite,
            ':id_user' => $data['id_user']
        ]);
    }

    public function supprimer($id_activite, $id_user) {
        $sql = "DELETE FROM $this->table WHERE id_activite = :id_activite AND id_user = :id_user";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id_activite' => $id_activite,
            ':id_user' => $id_user
        ]);
    }

    public function mettreAJourEtat($id_activite) {
        $activite = $this->obtenirParId($id_activite);
        if (!$activite) return false;

        $now = new DateTime();
        $dateInfos = new DateTime($activite['date_activite']);

        $start = null;
        $end = null;
        if (!empty($activite['heure_debut'])) {
            $start = new DateTime($activite['date_activite'] . ' ' . $activite['heure_debut']);
        }
        if (!empty($activite['heure_fin'])) {
            $end = new DateTime($activite['date_activite'] . ' ' . $activite['heure_fin']);
        }

        // Date passée
        if ($dateInfos < (new DateTime('today'))) {
            $nouvelEtat = 3; // Terminée
        }
        // Date aujourd'hui
        elseif ($dateInfos->format('Y-m-d') === (new DateTime('today'))->format('Y-m-d')) {
            if ($start && $end) {
                if ($now < $start) {
                    $nouvelEtat = 1; // En attente
                } elseif ($now >= $start && $now <= $end) {
                    $nouvelEtat = 2; // En cours
                } else {
                    $nouvelEtat = 3; // Terminée
                }
            } elseif ($start) {
                $nouvelEtat = ($now < $start) ? 1 : 2;
            } elseif ($end) {
                $nouvelEtat = ($now <= $end) ? 2 : 3;
            } else {
                $nouvelEtat = 2; // Sans heure -> en cours aujourd'hui
            }
        }
        // Date future
        else {
            $nouvelEtat = 1; // En attente
        }

        // Mettre à jour seulement si l'état change
        if ($activite['id_etat'] != $nouvelEtat) {
            $sql = "UPDATE $this->table SET id_etat = :id_etat WHERE id_activite = :id_activite";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':id_etat' => $nouvelEtat,
                ':id_activite' => $id_activite
            ]);
        }

        return true;
    }
}