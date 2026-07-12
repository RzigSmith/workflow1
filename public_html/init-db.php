<?php
/**
 * Script d'initialisation de la base de données
 * Accédez à: http://localhost/workflow1/public_html/init-db.php
 */

try {
    $conn = new PDO(
        "mysql:host=localhost;charset=utf8",
        "root",
        ""
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Supprimer et recréer la base de données pour appliquer le nouveau schéma
    $conn->exec("DROP DATABASE IF EXISTS workflow");
    $conn->exec("CREATE DATABASE workflow");
    echo "✓ Base de données 'workflow' recréée avec succès<br>";

    // Utiliser la base de données
    $conn->exec("USE workflow");

    // Créer les tables principales
    $tables = [
        // Table role
        "CREATE TABLE role (
            id_role INT AUTO_INCREMENT PRIMARY KEY,
            libelle_role VARCHAR(100)
        ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci",

        // Table utilisateur
        "CREATE TABLE utilisateur (
            id_user INT AUTO_INCREMENT PRIMARY KEY,
            nom VARCHAR(50),
            prenom VARCHAR(50),
            email VARCHAR(100) UNIQUE,
            password VARCHAR(255),
            username VARCHAR(50) UNIQUE,
            id_role INT NOT NULL,
            statut_en_ligne TINYINT DEFAULT 0,
            photo_profil VARCHAR(255) DEFAULT NULL,
            email_verified TINYINT DEFAULT 0,
            otp_code VARCHAR(6) DEFAULT NULL,
            otp_expires_at DATETIME DEFAULT NULL,
            recovery_token VARCHAR(100) DEFAULT NULL,
            recovery_token_expires_at DATETIME DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (id_role) REFERENCES role(id_role) ON DELETE RESTRICT
        ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci",

        // Table photos_collection (NOUVEAU)
        "CREATE TABLE photos_collection (
            id_photo INT AUTO_INCREMENT PRIMARY KEY,
            id_user INT NOT NULL,
            chemin_photo VARCHAR(255) NOT NULL,
            date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (id_user) REFERENCES utilisateur(id_user) ON DELETE CASCADE
        ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci",

        // Table amis
        "CREATE TABLE amis (
            id_amitie INT AUTO_INCREMENT PRIMARY KEY,
            id_demandeur INT NOT NULL,
            id_receveur INT NOT NULL,
            statut VARCHAR(20) DEFAULT 'pending',
            date_demande DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_amitie (id_demandeur, id_receveur),
            FOREIGN KEY (id_demandeur) REFERENCES utilisateur(id_user) ON DELETE CASCADE,
            FOREIGN KEY (id_receveur) REFERENCES utilisateur(id_user) ON DELETE CASCADE
        ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci",

        // Table etat
        "CREATE TABLE etat (
            id_etat INT AUTO_INCREMENT PRIMARY KEY,
            libelle_etat VARCHAR(50)
        ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci",

        // Table activite
        "CREATE TABLE activite (
            id_activite INT AUTO_INCREMENT PRIMARY KEY,
            libelle VARCHAR(100),
            description VARCHAR(255),
            priorite VARCHAR(50),
            date_activite DATE,
            heure_debut TIME,
            heure_fin TIME,
            id_etat INT NOT NULL,
            id_user INT NOT NULL,
            FOREIGN KEY (id_etat) REFERENCES etat(id_etat) ON DELETE RESTRICT,
            FOREIGN KEY (id_user) REFERENCES utilisateur(id_user) ON DELETE CASCADE
        ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci",

        // Table conversation
        "CREATE TABLE conversation (
            id_conversation INT AUTO_INCREMENT PRIMARY KEY,
            type_conversation VARCHAR(50),
            date_creation DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci",

        // Table conversation_membre
        "CREATE TABLE conversation_membre (
            id_conversation INT NOT NULL,
            id_user INT NOT NULL,
            PRIMARY KEY (id_conversation, id_user),
            FOREIGN KEY (id_conversation) REFERENCES conversation(id_conversation) ON DELETE CASCADE,
            FOREIGN KEY (id_user) REFERENCES utilisateur(id_user) ON DELETE CASCADE
        ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci",

        // Table chat_message
        "CREATE TABLE chat_message (
            id_msg INT AUTO_INCREMENT PRIMARY KEY,
            id_conversation INT NOT NULL,
            id_user INT NOT NULL,
            contenu TEXT NOT NULL,
            date_envoi DATETIME DEFAULT CURRENT_TIMESTAMP,
            lu TINYINT DEFAULT 0,
            FOREIGN KEY (id_conversation) REFERENCES conversation(id_conversation) ON DELETE CASCADE,
            FOREIGN KEY (id_user) REFERENCES utilisateur(id_user) ON DELETE CASCADE
        ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci",

        // Table post
        "CREATE TABLE post (
            id_post INT AUTO_INCREMENT PRIMARY KEY,
            id_user INT NOT NULL,
            titre VARCHAR(200),
            contenu TEXT NOT NULL,
            visibilite VARCHAR(20) DEFAULT 'amis',
            id_activite INT DEFAULT NULL,
            photo_path VARCHAR(255) DEFAULT NULL,
            date_publication DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (id_user) REFERENCES utilisateur(id_user) ON DELETE CASCADE,
            FOREIGN KEY (id_activite) REFERENCES activite(id_activite) ON DELETE SET NULL
        ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci",

        // Table type_notification
        "CREATE TABLE type_notification (
            id_type INT AUTO_INCREMENT PRIMARY KEY,
            libelle VARCHAR(50)
        ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci",

        // Table notification
        "CREATE TABLE notification (
            id_notification INT AUTO_INCREMENT PRIMARY KEY,
            id_user INT NOT NULL,
            id_type INT NOT NULL,
            id_activite INT DEFAULT NULL,
            message TEXT,
            date_notification DATETIME DEFAULT CURRENT_TIMESTAMP,
            etat_notification VARCHAR(50) DEFAULT 'unread',
            FOREIGN KEY (id_user) REFERENCES utilisateur(id_user) ON DELETE CASCADE,
            FOREIGN KEY (id_type) REFERENCES type_notification(id_type) ON DELETE RESTRICT,
            FOREIGN KEY (id_activite) REFERENCES activite(id_activite) ON DELETE CASCADE
        ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci"
    ];

    foreach ($tables as $sql) {
        $conn->exec($sql);
    }
    echo "✓ Toutes les tables sont créées avec succès<br>";

    // SEED DATA
    echo "Insertion des données de seed...<br>";

    // Rôles
    $conn->exec("INSERT INTO role (id_role, libelle_role) VALUES (1, 'Admin'), (2, 'User')");
    
    // États
    $conn->exec("INSERT INTO etat (id_etat, libelle_etat) VALUES (1, 'À faire'), (2, 'En cours'), (3, 'Terminée')");

    // Types notifications
    $conn->exec("INSERT INTO type_notification (id_type, libelle) VALUES 
        (1, 'Demande amitié'), 
        (2, 'Acceptation amitié'), 
        (3, 'Nouveau message'), 
        (4, 'Nouvelle activité'), 
        (5, 'Commentaire post'),
        (6, 'Partage activité')
    ");

    // Utilisateurs par défaut
    $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $userPassword = password_hash('user123', PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO utilisateur (nom, prenom, email, password, username, id_role, email_verified) VALUES (:nom, :prenom, :email, :password, :username, :id_role, :email_verified)");
    
    // Admin
    $stmt->execute([
        ':nom' => 'System',
        ':prenom' => 'Admin',
        ':email' => 'admin@workflow.com',
        ':password' => $adminPassword,
        ':username' => 'admin',
        ':id_role' => 1,
        ':email_verified' => 1
    ]);

    // Simple User
    $stmt->execute([
        ':nom' => 'Simple',
        ':prenom' => 'User',
        ':email' => 'user@workflow.com',
        ':password' => $userPassword,
        ':username' => 'user',
        ':id_role' => 2,
        ':email_verified' => 1
    ]);

    echo "<div style='padding:1rem;background:#d4edda;border:1px solid #c3e6cb;border-radius:4px;margin-top:1rem;'>";
    echo "<strong style='color:#155724;'>✓ Base de données initialisée et configurée avec succès !</strong><br>";
    echo "Comptes créés :<br>";
    echo "- Administrateur : admin@workflow.com / admin123<br>";
    echo "- Utilisateur : user@workflow.com / user123<br>";
    echo "</div>";

} catch (PDOException $e) {
    echo "<div style='padding:1rem;background:#f8d7da;border:1px solid #f5c6cb;border-radius:4px;'>";
    echo "<strong style='color:#721c24;'>✗ Erreur:</strong> " . htmlspecialchars($e->getMessage());
    echo "</div>";
}
?>
