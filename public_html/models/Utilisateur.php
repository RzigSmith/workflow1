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
    public $statut_en_ligne;
    public $photo_profil;
    public $email_verified;
    public $otp_code;
    public $otp_expires_at;
    public $recovery_token;
    public $recovery_token_expires_at;

    public function create() {
        $sql  = "INSERT INTO $this->table (nom, prenom, email, id_role, password, email_verified, otp_code, otp_expires_at)
        VALUES (:nom, :prenom, :email, :id_role, :password, :email_verified, :otp_code, :otp_expires_at)";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':nom' => $this->nom,
            ':prenom' => $this->prenom,
            ':email'=> $this->email,
            ':id_role'=> $this->id_role ?? 2,
            ':password' => password_hash($this->password, PASSWORD_DEFAULT),
            ':email_verified' => $this->email_verified ?? 0,
            ':otp_code' => $this->otp_code ?? null,
            ':otp_expires_at' => $this->otp_expires_at ?? null
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

    public function findByEmail(string $email) {
        $sql = "SELECT * FROM $this->table WHERE email = :email LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email]);
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
        // En MySQL, concaténation se fait avec CONCAT(nom, ' ', prenom)
        $sql = "SELECT id_user, nom, prenom, username, photo_profil FROM {$this->table}
                WHERE id_user != :excl
                  AND (nom LIKE :q OR prenom LIKE :q2 OR username LIKE :q3
                       OR CONCAT(nom, ' ', prenom) LIKE :q4)
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

    // ── GESTION OTP ───────────────────────────────────
    public function updateOtp(int $userId, string $code, string $expiresAt): bool {
        $sql = "UPDATE {$this->table} SET otp_code = :code, otp_expires_at = :expires WHERE id_user = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':code' => $code, ':expires' => $expiresAt, ':id' => $userId]);
    }

    public function verifyOtp(int $userId, string $code): bool {
        $sql = "SELECT 1 FROM {$this->table} 
                WHERE id_user = :id AND otp_code = :code AND otp_expires_at > NOW() LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $userId, ':code' => $code]);
        if ($stmt->fetchColumn()) {
            // OTP valide, on vérifie l'email
            $this->db->prepare("UPDATE {$this->table} SET email_verified = 1, otp_code = NULL, otp_expires_at = NULL WHERE id_user = :id")
                     ->execute([':id' => $userId]);
            return true;
        }
        return false;
    }

    // ── RÉCUPÉRATION MDP ──────────────────────────────
    public function setRecoveryToken(int $userId, string $token, string $expiresAt): bool {
        $sql = "UPDATE {$this->table} SET recovery_token = :token, recovery_token_expires_at = :expires WHERE id_user = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':token' => $token, ':expires' => $expiresAt, ':id' => $userId]);
    }

    public function findByRecoveryToken(string $token) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE recovery_token = :token AND recovery_token_expires_at > NOW() LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':token' => $token]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function resetPassword(int $userId, string $newPassword): bool {
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $sql = "UPDATE {$this->table} 
                SET password = :pass, recovery_token = NULL, recovery_token_expires_at = NULL 
                WHERE id_user = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':pass' => $hash, ':id' => $userId]);
    }

    // ── GESTION PROFIL & PHOTO ────────────────────────
    public function updateProfilePic(int $userId, string $path): bool {
        $sql = "UPDATE {$this->table} SET photo_profil = :path WHERE id_user = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':path' => $path, ':id' => $userId]);
    }

    // ── GESTION COLLECTION DE PHOTOS ──────────────────
    public function addToCollection(int $userId, string $path): bool {
        $sql = "INSERT INTO photos_collection (id_user, chemin_photo) VALUES (:uid, :path)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':uid' => $userId, ':path' => $path]);
    }

    public function getCollection(int $userId): array {
        $sql = "SELECT * FROM photos_collection WHERE id_user = :uid ORDER BY date_creation DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function deleteFromCollection(int $photoId, int $userId): bool {
        $sql = "DELETE FROM photos_collection WHERE id_photo = :pid AND id_user = :uid";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':pid' => $photoId, ':uid' => $userId]);
    }

    // ── GESTION ADMIN ─────────────────────────────────
    public function updateRole(int $userId, int $roleId): bool {
        $sql = "UPDATE {$this->table} SET id_role = :role WHERE id_user = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':role' => $roleId, ':id' => $userId]);
    }
}