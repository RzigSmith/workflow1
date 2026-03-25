<?php
require_once __DIR__ . '/../core/Model.php';

class Post extends Model {
    private $table = 'post';

    public function creer(array $data): bool {
        $sql = "INSERT INTO {$this->table} (id_user, titre, contenu, visibilite, id_activite)
                VALUES (:id_user, :titre, :contenu, :visibilite, :id_activite)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id_user'     => $data['id_user'],
            ':titre'       => $data['titre'] ?? '',
            ':contenu'     => $data['contenu'],
            ':visibilite'  => $data['visibilite'] ?? 'amis',
            ':id_activite' => $data['id_activite'] ?? null,
        ]);
    }

    public function getFeed(int $userId, array $amisIds): array {
        if (empty($amisIds)) {
            $sql = "SELECT p.*, u.nom, u.prenom, u.username,
                           a.libelle AS activite_libelle
                    FROM {$this->table} p
                    JOIN utilisateur u ON p.id_user = u.id_user
                    LEFT JOIN activite a ON p.id_activite = a.id_activite
                    WHERE p.id_user = :uid
                    ORDER BY p.date_publication DESC
                    LIMIT 50";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':uid' => $userId]);
        } else {
            $placeholders = implode(',', array_fill(0, count($amisIds), '?'));
            $sql = "SELECT p.*, u.nom, u.prenom, u.username,
                           a.libelle AS activite_libelle
                    FROM {$this->table} p
                    JOIN utilisateur u ON p.id_user = u.id_user
                    LEFT JOIN activite a ON p.id_activite = a.id_activite
                    WHERE p.id_user = ?
                       OR (p.id_user IN ($placeholders) AND p.visibilite IN ('amis','public'))
                    ORDER BY p.date_publication DESC
                    LIMIT 50";
            $stmt = $this->db->prepare($sql);
            $params = array_merge([$userId], $amisIds);
            $stmt->execute($params);
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getMesPosts(int $userId): array {
        $sql = "SELECT p.*, u.nom, u.prenom, a.libelle AS activite_libelle
                FROM {$this->table} p
                JOIN utilisateur u ON p.id_user = u.id_user
                LEFT JOIN activite a ON p.id_activite = a.id_activite
                WHERE p.id_user = :uid
                ORDER BY p.date_publication DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function supprimer(int $id_post, int $id_user): bool {
        $sql = "DELETE FROM {$this->table} WHERE id_post = :id AND id_user = :uid";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id_post, ':uid' => $id_user]);
    }

    public function lastInsertId(): string {
        return $this->db->lastInsertId();
    }
}
