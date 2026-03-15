<?php

require_once __DIR__ ."/../core/Model.php";

class Role extends Model {
    private $table = "role";

    public $libelle_role ;
    public function creer() {
        $sql = "INSERT INTO $this->table (libelle_role)
            VALUES (:libelle_role)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ":libelle_role"=> $this->libelle_role
        ]);
    }
    public function lister() {
        $sq = "SELECT * FROM * $this->table";
        return $this->db->query($sq)->fetchAll(PDO::FETCH_ASSOC);
    }
}