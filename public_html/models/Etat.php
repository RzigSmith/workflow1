<?php

require_once __DIR__ . "/../core/Model.php";

class Etat extends Model {

    private $table = "etat";

    public $libelle_etat;

    public function creer() {

        $sql = "INSERT INTO $this->table (libelle_etat)
                VALUES (:libelle_etat)";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':libelle_etat' => $this->libelle_etat
        ]);
    }

    public function lister() {

        $sql = "SELECT * FROM $this->table";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}