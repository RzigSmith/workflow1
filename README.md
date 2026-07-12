# WorkFlow

## Description

WorkFlow est une application web collaborative développée en PHP/MySQL. Elle permet de gérer des activités personnelles ou de groupe, d’ajouter des amis, d’échanger des messages, de publier du contenu et de recevoir des notifications. Le projet a été conçu pour un contexte académique de niveau Licence 1/2.

## Fonctionnalités principales

- Inscription et connexion sécurisées
- Vérification de compte par OTP email
- Réinitialisation du mot de passe
- Gestion des activités (création, modification, suppression)
- Suivi automatique du statut des activités (À faire / En cours / Terminée)
- Système d’amis et de demandes de connexion
- Messagerie entre utilisateurs
- Publication de posts avec photo et visibilité
- Notifications utilisateur
- Tableau de bord administrateur

## Technologies utilisées

- PHP natif
- MySQL / MariaDB
- HTML, CSS, JavaScript
- PDO pour l’accès à la base de données
- XAMPP ou tout autre environnement local compatible PHP/MySQL

## Installation

1. Copier le dossier du projet dans le répertoire de votre serveur local, par exemple `C:\xampp\htdocs\workflow1`.
2. Importer la base de données MySQL depuis `public_html/database/workflow.sql`.
3. Vérifier la configuration de la connexion dans `public_html/config/Database.php` :
   - hôte : `localhost`
   - base : `workflow`
   - utilisateur : `root`
   - mot de passe : `` (vide par défaut)
4. Lancer le serveur local Apache et MySQL.
5. Ouvrir le navigateur et accéder à `http://localhost/workflow1/public_html/index.php`.

## Accès par défaut

- Administrateur :
  - Email : `admin@workflow.com`
  - Mot de passe : `admin123` (si le mot de passe est bien celui attendu dans le script SQL, sinon réinitialiser via l’interface)
- Utilisateur simple :
  - Email : `user@workflow.com`
  - Mot de passe : `user123`

> Remarque : si les mots de passe par défaut ne fonctionnent pas, recréez les comptes ou utilisez la fonction de réinitialisation du mot de passe.

## Structure du projet

- `public_html/` : racine de l’application web
  - `index.php` : point d’entrée et routeur
  - `config/Database.php` : configuration de la base de données
  - `controllers/` : logique de traitement des actions
  - `models/` : classes de gestion des données
  - `views/` : pages HTML de l’application
  - `assets/` : CSS et JavaScript front-end
  - `database/workflow.sql` : script de création et d’initialisation de la base
  - `uploads/email_log.txt` : journal local des emails envoyés

## Détails importants

- Le système de notification email est simulé localement dans `uploads/email_log.txt`.
- `core/Flash.php` gère les messages flash entre pages.
- `core/EmailService.php` enregistre les emails dans le journal et tente un envoi via `mail()`.
- Toutes les requêtes principales sont centralisées via `public_html/index.php`.

## Utilisation

- Créez un compte utilisateur via la page d’inscription.
- Validez l’inscription avec le code OTP envoyé et disponible dans `uploads/email_log.txt`.
- Connectez-vous et utilisez le tableau de bord pour créer des activités.
- Ajoutez des amis, discutez en messages privés et publiez du contenu.
- L’administrateur peut gérer les utilisateurs, posts et activités via la page `admin`.

## À noter

- Ce projet est principalement conçu pour un usage local et pédagogique.
- Il peut être amélioré avec des protections CSRF, des validations côté client, une gestion plus robuste des uploads et un style responsive.

## Contact

Pour toute question ou ajout fonctionnel, modifiez directement les fichiers du projet et testez localement sur votre serveur XAMPP.
