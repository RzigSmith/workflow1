<?php
require_once __DIR__ . '/../core/Model.php';

class Ami extends Model {
    private $table = 'amis';

    public function envoyerDemande(int $demandeur, int $receveur): bool {
        $sql = "INSERT OR IGNORE INTO {$this->table} (id_demandeur, id_receveur, statut)
                VALUES (:d, :r, 'pending')";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':d' => $demandeur, ':r' => $receveur]);
    }

    public function repondre(int $id_amitie, int $receveur, string $statut): bool {
        $sql = "UPDATE {$this->table} SET statut = :s
                WHERE id_amitie = :id AND id_receveur = :r";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':s' => $statut, ':id' => $id_amitie, ':r' => $receveur]);
    }

    public function supprimerAmitie(int $user1, int $user2): bool {
        $sql = "DELETE FROM {$this->table}
                WHERE (id_demandeur = :u1 AND id_receveur = :u2)
                   OR (id_demandeur = :u2b AND id_receveur = :u1b)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':u1' => $user1, ':u2' => $user2, ':u2b' => $user2, ':u1b' => $user1]);
    }

    public function getAmis(int $userId): array {
        $sql = "SELECT u.id_user, u.nom, u.prenom, u.username, a.id_amitie, a.date_demande
                FROM {$this->table} a
                JOIN utilisateur u ON (
                    CASE WHEN a.id_demandeur = :uid THEN a.id_receveur ELSE a.id_demandeur END = u.id_user
                )
                WHERE (a.id_demandeur = :uid2 OR a.id_receveur = :uid3)
                  AND a.statut = 'accepted'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':uid' => $userId, ':uid2' => $userId, ':uid3' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDemandesRecues(int $userId): array {
        $sql = "SELECT a.id_amitie, a.date_demande, u.id_user, u.nom, u.prenom, u.username
                FROM {$this->table} a
                JOIN utilisateur u ON a.id_demandeur = u.id_user
                WHERE a.id_receveur = :uid AND a.statut = 'pending'
                ORDER BY a.date_demande DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDemandesEnvoyees(int $userId): array {
        $sql = "SELECT a.id_amitie, a.date_demande, u.id_user, u.nom, u.prenom, u.username
                FROM {$this->table} a
                JOIN utilisateur u ON a.id_receveur = u.id_user
                WHERE a.id_demandeur = :uid AND a.statut = 'pending'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function sontAmis(int $u1, int $u2): bool {
        $sql = "SELECT 1 FROM {$this->table}
                WHERE ((id_demandeur = :u1 AND id_receveur = :u2)
                    OR (id_demandeur = :u2b AND id_receveur = :u1b))
                  AND statut = 'accepted'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':u1' => $u1, ':u2' => $u2, ':u2b' => $u2, ':u1b' => $u1]);
        return (bool) $stmt->fetchColumn();
    }

    public function statutRelation(int $me, int $autre): string {
        $sql = "SELECT statut, id_demandeur FROM {$this->table}
                WHERE (id_demandeur = :me AND id_receveur = :autre)
                   OR (id_demandeur = :autre2 AND id_receveur = :me2)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':me' => $me, ':autre' => $autre, ':autre2' => $autre, ':me2' => $me]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return 'none';
        if ($row['statut'] === 'accepted') return 'accepted';
        if ($row['statut'] === 'pending' && $row['id_demandeur'] == $me) return 'sent';
        if ($row['statut'] === 'pending' && $row['id_demandeur'] == $autre) return 'received';
        return 'none';
    }
}
