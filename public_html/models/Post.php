<?php
require_once __DIR__ . '/../core/Model.php';

class Post extends Model {
    private $table = 'post';

    public function creer(array $data): bool {
        $sql = "INSERT INTO {$this->table} (id_user, titre, contenu, visibilite, id_activite, photo_path)
                VALUES (:id_user, :titre, :contenu, :visibilite, :id_activite, :photo_path)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id_user'     => $data['id_user'],
            ':titre'       => $data['titre'] ?? '',
            ':contenu'     => $data['contenu'],
            ':visibilite'  => $data['visibilite'] ?? 'amis',
            ':id_activite' => $data['id_activite'] ?? null,
            ':photo_path'  => $data['photo_path'] ?? null
        ]);
    }

    public function getFeed(int $userId, array $amisIds): array {
        $select = "SELECT p.*, u.nom, u.prenom, u.username, u.photo_profil AS user_photo_profil,
                          a.libelle AS activite_libelle, a.description AS activite_description,
                          a.priorite AS activite_priorite, a.date_activite AS activite_date,
                          a.heure_debut AS activite_debut, a.heure_fin AS activite_fin,
                          a.id_activite AS activite_id
                   FROM {$this->table} p
                   JOIN utilisateur u ON p.id_user = u.id_user
                   LEFT JOIN activite a ON p.id_activite = a.id_activite";

        if (empty($amisIds)) {
            $sql = "$select
                    WHERE p.id_user = :uid OR p.visibilite = 'public'
                    ORDER BY p.date_publication DESC
                    LIMIT 50";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':uid' => $userId]);
        } else {
            $placeholders = implode(',', array_fill(0, count($amisIds), '?'));
            $sql = "$select
                    WHERE p.id_user = ?
                       OR p.visibilite = 'public'
                       OR (p.id_user IN ($placeholders) AND p.visibilite = 'amis')
                    ORDER BY p.date_publication DESC
                    LIMIT 50";
            $stmt = $this->db->prepare($sql);
            $params = array_merge([$userId], $amisIds);
            $stmt->execute($params);
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getMesPosts(int $userId): array {
        $sql = "SELECT p.*, u.nom, u.prenom, u.photo_profil AS user_photo_profil, 
                       a.libelle AS activite_libelle, a.description AS activite_description, 
                       a.priorite AS activite_priorite, a.date_activite AS activite_date,
                       a.heure_debut AS activite_debut, a.heure_fin AS activite_fin
                FROM {$this->table} p
                JOIN utilisateur u ON p.id_user = u.id_user
                LEFT JOIN activite a ON p.id_activite = a.id_activite
                WHERE p.id_user = :uid
                ORDER BY p.date_publication DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function supprimer(int $id_post, int $id_user = null): bool {
        if ($id_user !== null) {
            $sql = "DELETE FROM {$this->table} WHERE id_post = :id AND id_user = :uid";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([':id' => $id_post, ':uid' => $id_user]);
        } else {
            $sql = "DELETE FROM {$this->table} WHERE id_post = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([':id' => $id_post]);
        }
    }

    public function listerTous(): array {
        $sql = "SELECT p.*, u.nom, u.prenom, u.username
                FROM {$this->table} p
                JOIN utilisateur u ON p.id_user = u.id_user
                ORDER BY p.date_publication DESC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function lastInsertId(): string {
        return $this->db->lastInsertId();
    }
}
