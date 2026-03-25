<?php

require_once __DIR__ ."/../core/Model.php";

class TypeNotification extends Model {
    private $table = "type_notification";
    public $libelle;
    public function creer() {
        $sql = "INSERT INTO $this->table (libelle)
            VALUES (:libelle) ";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ":libelle"=> $this->libelle
        ]);
        
    }

    public function lister() {
        $sql = "SELECT * FROM $this->table";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}