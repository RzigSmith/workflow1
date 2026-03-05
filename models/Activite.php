<?php


class Activite extends Model {
    public function creer($data) {
        $sql = "INSERT INTO activite 
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
            ':etat' => $data['id_etat'],
            ':user' => $data['id_user']
        ]);
    }


    public function listerParUser($id_user) {
        $sql = "SELECT * FROM activite WHERE id_user = :id_user";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id_user' => $id_user]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);                   
    }
}