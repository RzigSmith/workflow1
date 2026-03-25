<?php
/**
 * Script d'initialisation de la base de données
 * Accédez à: http://localhost/workflow2/public_html/init-db.php
 */

try {
    $conn = new PDO(
        "mysql:host=localhost;charset=utf8",
        "root",
        ""
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Créer la base de données
    $conn->exec("CREATE DATABASE IF NOT EXISTS workflow");
    echo "✓ Base de données 'workflow' créée/existe<br>";

    // Utiliser la base de données
    $conn->exec("USE workflow");

    // Créer les tables principales
    $tables = [
        // Table utilisateur
        "CREATE TABLE IF NOT EXISTS utilisateur (
            id_user INT AUTO_INCREMENT PRIMARY KEY,
            nom VARCHAR(50),
            prenom VARCHAR(50),
            email VARCHAR(100) UNIQUE,
            password VARCHAR(255),
            username VARCHAR(50) UNIQUE,
            id_role INT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )",

        // Table role
        "CREATE TABLE IF NOT EXISTS role (
            id_role INT AUTO_INCREMENT PRIMARY KEY,
            libelle_role VARCHAR(100)
        )",

        // Table amis - LA PLUS IMPORTANTE
        "CREATE TABLE IF NOT EXISTS amis (
            id_amitie INT AUTO_INCREMENT PRIMARY KEY,
            id_demandeur INT NOT NULL,
            id_receveur INT NOT NULL,
            statut VARCHAR(20) DEFAULT 'pending',
            date_demande DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_amitie (id_demandeur, id_receveur),
            FOREIGN KEY (id_demandeur) REFERENCES utilisateur(id_user) ON DELETE CASCADE,
            FOREIGN KEY (id_receveur) REFERENCES utilisateur(id_user) ON DELETE CASCADE
        )",

        // Table activite
        "CREATE TABLE IF NOT EXISTS activite (
            id_activite INT AUTO_INCREMENT PRIMARY KEY,
            libelle VARCHAR(100),
            description VARCHAR(255),
            priorite VARCHAR(50),
            date_activite DATE,
            heure_debut TIME,
            heure_fin TIME,
            id_etat INT,
            id_user INT NOT NULL,
            FOREIGN KEY (id_user) REFERENCES utilisateur(id_user) ON DELETE CASCADE
        )",

        // Table etat
        "CREATE TABLE IF NOT EXISTS etat (
            id_etat INT AUTO_INCREMENT PRIMARY KEY,
            libelle_etat VARCHAR(50)
        )",

        // Table conversation
        "CREATE TABLE IF NOT EXISTS conversation (
            id_conversation INT AUTO_INCREMENT PRIMARY KEY,
            type_conversation VARCHAR(50),
            date_creation DATETIME DEFAULT CURRENT_TIMESTAMP
        )",

        // Table chat_message
        "CREATE TABLE IF NOT EXISTS chat_message (
            id_msg INT AUTO_INCREMENT PRIMARY KEY,
            id_conversation INT NOT NULL,
            id_user INT NOT NULL,
            contenu TEXT NOT NULL,
            date_envoi DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (id_conversation) REFERENCES conversation(id_conversation) ON DELETE CASCADE,
            FOREIGN KEY (id_user) REFERENCES utilisateur(id_user) ON DELETE CASCADE
        )",

        // Table post
        "CREATE TABLE IF NOT EXISTS post (
            id_post INT AUTO_INCREMENT PRIMARY KEY,
            id_user INT NOT NULL,
            contenu TEXT,
            visibilite VARCHAR(20),
            id_activite INT,
            date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (id_user) REFERENCES utilisateur(id_user) ON DELETE CASCADE,
            FOREIGN KEY (id_activite) REFERENCES activite(id_activite) ON DELETE SET NULL
        )",

        // Table notification
        "CREATE TABLE IF NOT EXISTS notification (
            id_notification INT AUTO_INCREMENT PRIMARY KEY,
            id_user INT NOT NULL,
            type_notification VARCHAR(50),
            message TEXT,
            lu INT DEFAULT 0,
            date_notification DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (id_user) REFERENCES utilisateur(id_user) ON DELETE CASCADE
        )"
    ];

    foreach ($tables as $sql) {
        $conn->exec($sql);
    }
    echo "✓ Toutes les tables sont créées/existent<br>";

    // Vérifier la table amis
    $stmt = $conn->query("DESCRIBE amis");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<br><strong>Structure table 'amis':</strong><pre>";
    print_r($columns);
    echo "</pre>";

    echo "<div style='padding:1rem;background:#d4edda;border:1px solid #c3e6cb;border-radius:4px;margin-top:1rem;'>";
    echo "<strong style='color:#155724;'>✓ Base de données initialisée avec succès!</strong><br>";
    echo "Vous pouvez maintenant tester l'ajout d'amis.";
    echo "</div>";

} catch (PDOException $e) {
    echo "<div style='padding:1rem;background:#f8d7da;border:1px solid #f5c6cb;border-radius:4px;'>";
    echo "<strong style='color:#721c24;'>✗ Erreur:</strong> " . htmlspecialchars($e->getMessage());
    echo "</div>";
}
?>
