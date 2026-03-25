-- Mises à jour de la base de données pour WorkFlow

-- Ajouter colonne 'lu' à la table chat_message
ALTER TABLE chat_message ADD COLUMN lu TINYINT DEFAULT 0 AFTER date_envoi;

-- Ajouter colonne 'statut_en_ligne' à la table utilisateur
ALTER TABLE utilisateur ADD COLUMN statut_en_ligne TINYINT DEFAULT 0 AFTER id_role;
