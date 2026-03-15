<?php

require_once __DIR__ . '/../core/Model.php';

class Utilisateur extends Model {
    private $table = "utilisateur";

    public $id_user;
    public $nom;
    public $prenom;
    public $email;
    public $password;

    public $id_role;
    public function create() {
        $sql  = "INSERT INTO $this->table (nom, prenom, email, id_role, password)
        VALUES (:nom, :prenom, :email, :id_role, :password)";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':nom' => $this->nom,
            ':prenom' => $this->prenom,
            ':email'=> $this->email,
            ':id_role'=> $this->id_role,
            ':password' => password_hash($this->password, PASSWORD_DEFAULT),
        ]);
    }

    public function readAll() {
        $sql = "SELECT * FROM $this->table";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find($id) {
        $sql = "SELECT * FROM $this->table WHERE id_user = :id ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function existsByEmail(string $email): bool
    {
        $sql = "SELECT 1 FROM $this->table WHERE email = :email LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email]);
        return (bool) $stmt->fetchColumn();
    }

    public function delete($id) {
        $sql = "DELETE FROM $this->table WHERE id_user = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([":id"=>$id]);
    }
 }