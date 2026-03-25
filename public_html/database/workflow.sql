
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- ========================================
-- Base de données : workflow
-- ========================================

DROP DATABASE IF EXISTS workflow;
CREATE DATABASE IF NOT EXISTS workflow;
USE workflow;

-- ========================================
-- Table: role
-- ========================================
CREATE TABLE role (
  id_role INT AUTO_INCREMENT PRIMARY KEY,
  libelle_role VARCHAR(100)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- ========================================
-- Table: utilisateur
-- ========================================
CREATE TABLE utilisateur (
  id_user INT AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(50),
  prenom VARCHAR(50),
  email VARCHAR(100) UNIQUE,
  password VARCHAR(255),
  username VARCHAR(50) UNIQUE,
  id_role INT NOT NULL,
  statut_en_ligne TINYINT DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_role) REFERENCES role(id_role) ON DELETE RESTRICT
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- ========================================
-- Table: etat
-- ========================================
CREATE TABLE etat (
  id_etat INT AUTO_INCREMENT PRIMARY KEY,
  libelle_etat VARCHAR(50)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- ========================================
-- Table: activite
-- ========================================
CREATE TABLE activite (
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
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- ========================================
-- Table: amis
-- ========================================
CREATE TABLE amis (
  id_amitie INT AUTO_INCREMENT PRIMARY KEY,
  id_demandeur INT NOT NULL,
  id_receveur INT NOT NULL,
  statut VARCHAR(20) DEFAULT 'pending',
  date_demande DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_amitie (id_demandeur, id_receveur),
  FOREIGN KEY (id_demandeur) REFERENCES utilisateur(id_user) ON DELETE CASCADE,
  FOREIGN KEY (id_receveur) REFERENCES utilisateur(id_user) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- ========================================
-- Table: type_notification
-- ========================================
CREATE TABLE type_notification (
  id_type INT AUTO_INCREMENT PRIMARY KEY,
  libelle VARCHAR(50)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- ========================================
-- Table: notification
-- ========================================
CREATE TABLE notification (
  id_notification INT AUTO_INCREMENT PRIMARY KEY,
  id_user INT NOT NULL,
  id_type INT NOT NULL,
  id_activite INT,
  message TEXT,
  date_notification DATETIME DEFAULT CURRENT_TIMESTAMP,
  etat_notification VARCHAR(50) DEFAULT 'unread',
  FOREIGN KEY (id_user) REFERENCES utilisateur(id_user) ON DELETE CASCADE,
  FOREIGN KEY (id_type) REFERENCES type_notification(id_type) ON DELETE RESTRICT,
  FOREIGN KEY (id_activite) REFERENCES activite(id_activite) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- ========================================
-- Table: utilisateur_notification
-- ========================================
CREATE TABLE utilisateur_notification (
  id_user INT NOT NULL,
  id_notification INT NOT NULL,
  PRIMARY KEY (id_user, id_notification),
  FOREIGN KEY (id_user) REFERENCES utilisateur(id_user) ON DELETE CASCADE,
  FOREIGN KEY (id_notification) REFERENCES notification(id_notification) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- ========================================
-- Table: conversation
-- ========================================
CREATE TABLE conversation (
  id_conversation INT AUTO_INCREMENT PRIMARY KEY,
  type_conversation VARCHAR(50),
  date_creation DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- ========================================
-- Table: conversation_membre
-- ========================================
CREATE TABLE conversation_membre (
  id_conversation INT NOT NULL,
  id_user INT NOT NULL,
  PRIMARY KEY (id_conversation, id_user),
  FOREIGN KEY (id_conversation) REFERENCES conversation(id_conversation) ON DELETE CASCADE,
  FOREIGN KEY (id_user) REFERENCES utilisateur(id_user) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- ========================================
-- Table: message
-- ========================================
CREATE TABLE message (
  id_message INT AUTO_INCREMENT PRIMARY KEY,
  contenu VARCHAR(255),
  date_envoi DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- ========================================
-- Table: contient (junction table)
-- ========================================
CREATE TABLE contient (
  id_message INT NOT NULL,
  id_conversation INT NOT NULL,
  PRIMARY KEY (id_message, id_conversation),
  FOREIGN KEY (id_message) REFERENCES message(id_message) ON DELETE CASCADE,
  FOREIGN KEY (id_conversation) REFERENCES conversation(id_conversation) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- ========================================
-- Table: chat_message
-- ========================================
CREATE TABLE chat_message (
  id_msg INT AUTO_INCREMENT PRIMARY KEY,
  id_conversation INT NOT NULL,
  id_user INT NOT NULL,
  contenu TEXT NOT NULL,
  date_envoi DATETIME DEFAULT CURRENT_TIMESTAMP,
  lu TINYINT DEFAULT 0,
  FOREIGN KEY (id_conversation) REFERENCES conversation(id_conversation) ON DELETE CASCADE,
  FOREIGN KEY (id_user) REFERENCES utilisateur(id_user) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- ========================================
-- Table: conversation_message
-- ========================================
CREATE TABLE conversation_message (
  id_conversation INT NOT NULL,
  id_user INT NOT NULL,
  PRIMARY KEY (id_conversation, id_user),
  FOREIGN KEY (id_conversation) REFERENCES conversation(id_conversation) ON DELETE CASCADE,
  FOREIGN KEY (id_user) REFERENCES utilisateur(id_user) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- ========================================
-- Table: post
-- ========================================
CREATE TABLE post (
  id_post INT AUTO_INCREMENT PRIMARY KEY,
  id_user INT NOT NULL,
  titre VARCHAR(200),
  contenu TEXT NOT NULL,
  visibilite VARCHAR(20) DEFAULT 'amis',
  id_activite INT,
  date_publication DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_user) REFERENCES utilisateur(id_user) ON DELETE CASCADE,
  FOREIGN KEY (id_activite) REFERENCES activite(id_activite) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- ========================================
-- Table: rendez_vous
-- ========================================
CREATE TABLE rendez_vous (
  id_rendez_vous INT AUTO_INCREMENT PRIMARY KEY,
  date_rdv DATE,
  heure_rdv TIME,
  statut VARCHAR(50)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- ========================================
-- Table: participe
-- ========================================
CREATE TABLE participe (
  id_user INT NOT NULL,
  id_rendez_vous INT NOT NULL,
  id_conversation INT NOT NULL,
  PRIMARY KEY (id_user, id_rendez_vous, id_conversation),
  FOREIGN KEY (id_user) REFERENCES utilisateur(id_user) ON DELETE CASCADE,
  FOREIGN KEY (id_rendez_vous) REFERENCES rendez_vous(id_rendez_vous) ON DELETE CASCADE,
  FOREIGN KEY (id_conversation) REFERENCES conversation(id_conversation) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- ========================================
-- SEED DATA
-- ========================================

-- Rôles
INSERT INTO role (id_role, libelle_role) VALUES 
(1, 'Admin'),
(2, 'User');

-- États des activités
INSERT INTO etat (id_etat, libelle_etat) VALUES 
(1, 'À faire'),
(2, 'En cours'),
(3, 'Terminée');

-- Types de notifications
INSERT INTO type_notification (id_type, libelle) VALUES 
(1, 'Demande amitié'),
(2, 'Acceptation amitié'),
(3, 'Nouveau message'),
(4, 'Nouvelle activité'),
(5, 'Commentaire post');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

