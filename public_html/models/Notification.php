<?php

require_once __DIR__. "/../core/Model.php";

class Notification extends Model {  
    private $table = "notification";
    public $date_notification;
    public $etat_notification;
    public $id_activite;
    public $id_type;
    public $id_user;

    public function creer() {
        $sql = "INSERT INTO $this->table
        (date_notification, etat_notification, id_activite, id_type, id_user)
        VALUES 
        (:date_notification, :etat_notification, :id_activite, :id_type, :id_user)";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':date_notification'=>$this->date_notification,
            ':etat_notification'=>$this->etat_notification,
            ':id_activite'=>$this->id_activite,
            ':id_type'=>$this->id_type,
            ':id_user'=>$this->id_user
        ]);
    }
}