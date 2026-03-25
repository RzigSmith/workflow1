<?php

require_once __DIR__ ."/../core/Model.php";

class Message extends Model {
    private $table = "message";

    public $contenu;
    public $date_envoi;

    public function creer() {
        $sql= "INSERT INTO $this->table (contenu, date_envoi)
            VALUES (:contenu, :date_envoi)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ":contenu"=> $this->contenu,
            ":date_envoi"=> $this->date_envoi
        ]);
    
    }
}