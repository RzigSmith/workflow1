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

    public function searchByName(string $q, int $excludeId): array {
        $term = '%' . $q . '%';
        $sql = "SELECT id_user, nom, prenom, username FROM {$this->table}
                WHERE id_user != :excl
                  AND (nom LIKE :q OR prenom LIKE :q2 OR username LIKE :q3
                       OR (nom || ' ' || prenom) LIKE :q4)
                ORDER BY nom ASC
                LIMIT 15";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':excl'=>$excludeId, ':q'=>$term, ':q2'=>$term, ':q3'=>$term, ':q4'=>$term]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function setUsername(int $id, string $username): void {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET username=:u WHERE id_user=:id");
        $stmt->execute([':u'=>$username, ':id'=>$id]);
    }

    public function generateUsername(string $nom, string $prenom): string {
        $base = strtolower(
            preg_replace('/[^a-z0-9]/', '', iconv('UTF-8','ASCII//TRANSLIT', $nom . '.' . $prenom))
        );
        $base = preg_replace('/\.+/', '.', trim($base, '.'));
        $base = $base ?: 'user';
        $suffix = rand(10, 999);
        return $base . $suffix;
    }
}