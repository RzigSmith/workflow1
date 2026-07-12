<?php

require_once __DIR__. "/../core/Model.php";

class Notification extends Model {  
    private $table = "notification";
    public $date_notification;
    public $etat_notification;
    public $id_activite;
    public $id_type;
    public $id_user;
    public $message;

    public function creer() {
        $sql = "INSERT INTO $this->table
        (date_notification, etat_notification, id_activite, id_type, id_user, message)
        VALUES 
        (:date_notification, :etat_notification, :id_activite, :id_type, :id_user, :message)";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':date_notification'=>$this->date_notification ?? date('Y-m-d H:i:s'),
            ':etat_notification'=>$this->etat_notification ?? 'unread',
            ':id_activite'=>$this->id_activite,
            ':id_type'=>$this->id_type,
            ':id_user'=>$this->id_user,
            ':message'=>$this->message ?? ''
        ]);
    }

    public function listerParUser(int $userId): array {
        $sql = "SELECT n.*, tn.libelle AS type_libelle 
                FROM {$this->table} n
                JOIN type_notification tn ON n.id_type = tn.id_type
                WHERE n.id_user = :uid 
                ORDER BY n.date_notification DESC 
                LIMIT 50";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function compterNonLues(int $userId): int {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE id_user = :uid AND etat_notification = 'unread'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':uid' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    public function marquerCommeLus(int $userId): bool {
        $sql = "UPDATE {$this->table} SET etat_notification = 'read' WHERE id_user = :uid";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':uid' => $userId]);
    }

    public function supprimer(int $idNotif, int $userId): bool {
        $sql = "DELETE FROM {$this->table} WHERE id_notification = :id AND id_user = :uid";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $idNotif, ':uid' => $userId]);
    }
}