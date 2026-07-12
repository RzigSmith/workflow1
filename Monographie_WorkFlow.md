# Monographie du projet WorkFlow

## 1. Introduction générale

### 1.1 Contexte

Le projet WorkFlow est une application web développée dans le cadre d’une formation en informatique de premier cycle. Il vise à proposer une plateforme collaborative pour la gestion d’activités, la communication entre utilisateurs, et le suivi des tâches. Ce projet est conçu pour répondre aux besoins des étudiants ou d’un petit groupe de travail qui souhaitent organiser des activités, partager des publications et échanger via un système de messagerie.

Dans un environnement académique, les tâches quotidiennes sont souvent gérées manuellement, ce qui peut générer des oublis, un manque de suivi et des difficultés de coordination. WorkFlow s’inscrit dans cette démarche de transition numérique en offrant une solution simple et légère adaptée au niveau L1/L2.

### 1.2 Problématique

Malgré l’existence de nombreux outils, plusieurs activités restent encore gérées de manière dispersée, sans coordination centralisée. Le problème que ce projet tente de résoudre est le suivant : comment concevoir une application web accessible, simple et fonctionnelle pour organiser des activités, gérer des relations sociales et partager des informations entre utilisateurs? L’objectif est de proposer un outil qui facilite la gestion des activités, la mise en relation des utilisateurs et l’échange d’informations.

### 1.3 Objectifs

#### 1.3.1 Objectif général

Réaliser une application web collaborative qui permet à un utilisateur authentifié de créer, modifier et suivre des activités, de gérer un réseau d’amis, d’échanger des messages et de publier du contenu.

#### 1.3.2 Objectifs spécifiques

- Permettre l’inscription et la connexion sécurisée des utilisateurs.
- Gérer la création, la modification et la suppression d’activités.
- Mettre en place un système d’amis et de demandes d’amitié.
- Offrir une messagerie directe entre utilisateurs.
- Proposer un fil de publication avec photos et visibilité.
- Implémenter un système de notifications.
- Fournir un tableau de bord administrateur pour gérer les utilisateurs, activités et publications.

### 1.4 Méthodologie adoptée

Le projet a été réalisé en plusieurs étapes :

- Analyse des besoins et définition du périmètre fonctionnel.
- Conception de la base de données et des modèles de données.
- Développement de l’architecture backend en PHP avec un routeur centralisé.
- Création des pages de l’interface utilisateur en HTML/CSS/JavaScript.
- Mise en place des fonctionnalités d’authentification, d’activités, d’amis, de chat, de posts et de notifications.
- Tests fonctionnels et validation des flux principaux.

Cette méthodologie a combiné une conception progressive avec des tests réguliers, en s’appuyant sur un workflow simple adapté au niveau L1/L2.

### 1.5 Structure du document

Ce document est structuré comme suit :

- Chapitre 1 : Introduction générale.
- Chapitre 2 : Cadre théorique et technologique.
- Chapitre 3 : Réalisation du projet.
- Chapitre 4 : Difficultés rencontrées et limites.
- Chapitre 5 : Conclusion générale.
- Chapitre 6 : Bibliographie et webographie.
- Chapitre 7 : Annexes.

## 2. Cadre théorique et technologique

### 2.1 Définitions des concepts clés

- **Application web** : logiciel accessible depuis un navigateur web, exécuté sur un serveur et communiquant avec un client via HTTP. Dans ce projet, WorkFlow est une application web accessible depuis un navigateur.
- **Authentification** : processus de vérification de l’identité d’un utilisateur. Ici, l’authentification se fait par email et mot de passe, avec vérification OTP pour l’activation du compte.
- **Base de données** : système de stockage structuré permettant de conserver les données de l’application. WorkFlow utilise MySQL pour stocker les utilisateurs, activités, amis, messages, posts et notifications.
- **Interface utilisateur (UI)** : ensemble des écrans et éléments graphiques permettant à l’utilisateur d’interagir avec l’application.
- **Notification** : message système informant l’utilisateur d’un événement important (demande d’ami, nouveau message, partage d’activité, etc.).
- **Messagerie** : fonctionnalité qui permet l’échange de messages directs entre utilisateurs.
- **Réseau social** : mécanisme de connexion entre utilisateurs via des demandes d’amitié et un fil de publications.

### 2.2 Présentation des outils ou technologies utilisés

- **PHP** : langage de programmation serveur utilisé pour développer la logique métier, les contrôleurs, les modèles et la gestion des sessions.
  - Rôle : traitement des actions utilisateur, accès à la base de données et génération des pages web.
- **MySQL** : système de gestion de base de données relationnelle.
  - Rôle : stockage des utilisateurs, activités, posts, relations d’amitié, conversation et notifications.
- **HTML / CSS** : technologies frontales pour structurer et styliser les pages.
  - Rôle : création de l’interface utilisateur et mise en forme responsive.
- **JavaScript** : langage client pour gérer l’interactivité, la navigation interne, les appels API et les notifications.
  - Rôle : affichage dynamique, SPA partielle, gestion des actions de l’utilisateur.
- **XAMPP** : environnement de développement local utilisé pour exécuter l’application PHP/MySQL.
- **VS Code** : éditeur de code utilisé pour développer l’application.
- **Fichiers de configuration** : `config/Database.php`, `core/Flash.php`, `core/EmailService.php`.

## 3. Réalisation du projet

### 3.1 Analyse des besoins

#### Besoins fonctionnels

- Authentification sécurisée des utilisateurs.
- Création et gestion d’activités avec dates, priorité et état.
- Recherche et ajout d’amis.
- Envoi et réception de messages privés.
- Publication de contenus textuels ou photo.
- Visualisation d’un fil de posts en fonction des relations.
- Notifications d’événements importants.
- Gestion administrative des comptes et contenus.

#### Besoins non fonctionnels

- Simplicité d’utilisation.
- Sécurité minimale avec hashage des mots de passe.
- Fiabilité locale sur XAMPP.
- Interface claire et réactive.
- Extensibilité future.

#### Public cible

- Étudiants en L1/L2 en informatique.
- Utilisateurs recherchant une plateforme de suivi de tâches et de communication.
- Administrateur chargé de superviser les utilisateurs et contenus.

### 3.2 Architecture de la solution

L’architecture du projet est organisée selon un modèle léger type MVC :

- `index.php` : point d’entrée unique, routeur des actions et pages.
- `controllers/` : classes qui gèrent les actions utilisateur (authentification, activités, API).
- `models/` : classes d’accès aux données et aux règles métiers.
- `views/` : pages HTML affichées à l’utilisateur.
- `assets/` : ressources frontales JavaScript et CSS.
- `config/` : connexion à la base de données.

Diagramme simplifié :

Utilisateur <-> Front-end (HTML/JS) <-> `index.php` <-> Contrôleurs <-> Modèles <-> Base de données MySQL

Principaux composants :

- Authentification et gestion de session.
- Gestion des activités et des états.
- Module amis et système de relation.
- Chat conversationnel.
- Système de publication.
- Notifications.
- Dashboard administrateur.

### 3.3 Étapes de mise en œuvre

#### Phase de conception

- Définition du modèle de données dans `database/workflow.sql`.
- Identification des tables nécessaires : `utilisateur`, `activite`, `amis`, `conversation`, `chat_message`, `post`, `notification`, etc.
- Conception des pages principales : login, tableau de bord, admin, inscriptions, vérification OTP.

#### Phase d’implémentation

- Mise en place du routeur central dans `public_html/index.php`.
- Développement des contrôleurs :
  - `AuthController.php` pour l’inscription, la connexion, OTP, mot de passe oublié et réinitialisation.
  - `ActiviteController.php` pour créer, modifier et supprimer des activités.
  - `ApiController.php` pour exposer des endpoints AJAX/JSON.
- Développement des modèles :
  - `Utilisateur.php` pour la gestion des comptes et profils.
  - `Activite.php` pour la gestion des activités et des états.
  - `Ami.php` pour le système de relation.
  - `ChatConversation.php` pour le chat.
  - `Post.php` pour les publications.
  - `Notification.php` pour les notifications.
- Ajout de l’interface utilisateur dans `views/dashboard.php` et `views/admin.php`.
- Ajout du JavaScript frontal dans `assets/js/app.js` et `assets/js/social.js`.
- Mise en place du journal d’e-mails `uploads/email_log.txt` pour le débogage local.

#### Phase de tests

- Vérification de l’inscription et de l’activation par OTP.
- Test des connexions et redirections selon rôle.
- Vérification de la création et du suivi des activités.
- Validation du système d’amis (envoi, acceptation, annulation).
- Test du chat et des publications.
- Vérification des notifications et de la gestion admin.

### 3.4 Résultats obtenus

Le projet fournit une application opérationnelle avec les fonctionnalités suivantes :

- Création et gestion d’activités avec statut automatique selon la date et l’heure.
- Système de comptes utilisateurs et rôle admin.
- Vérification des comptes via OTP email.
- Gestion d’un réseau social d’amis.
- Messagerie directe entre utilisateurs.
- Fil de publication avec support photo et visibilité.
- Notifications pour les événements importants.
- Dashboard administrateur pour gérer les comptes, les posts et les activités.

## 4. Difficultés rencontrées et limites

### 4.1 Difficultés rencontrées

- Configuration de l’environnement XAMPP et connexion à MySQL.
- Gestion des sessions PHP et du passage d’état entre les pages.
- Mise en place du système OTP et de la réinitialisation de mot de passe.
- Intégration d’une interface frontale fluide en JavaScript.
- Adaptation du développement à une architecture MVC légère.

### 4.2 Limites de la solution

- Sécurité limitée : absence de protections avancées comme CSRF, validation stricte côté client et protection contre les attaques XSS dans tous les champs.
- Expérience utilisateur perfectible : interface simple mais non optimisée pour mobile complet.
- Envoi d’e-mails local dépendant de la configuration PHP `mail()` de l’hôte.
- Absence de tests automatisés.
- Gestion des pièces jointes photo et des uploads possible, mais sans redimensionnement ni stockage optimisé.
- Certaines requêtes SQL sont écrites pour MySQL, et la compatibilité avec d’autres SGBD n’est pas garantie.

## 5. Conclusion générale

### 5.1 Récapitulatif

WorkFlow est un projet de plateforme web qui centralise la gestion d’activités, la communication sociale et les publications. Il a été développé en PHP/MySQL avec une interface JavaScript légère pour offrir une expérience interactive. La solution répond aux besoins de base d’un groupe d’utilisateurs souhaitant organiser, partager et communiquer.

### 5.2 Apports du projet

- Mise en pratique des concepts de développement web backend et frontend.
- Compréhension du modèle client-serveur et de l’architecture MVC légère.
- Expérience sur la gestion des sessions, l’authentification, l’upload de fichiers et les API JSON.
- Développement des compétences en conception de base de données relationnelle.

### 5.3 Perspectives

Pour améliorer le projet, plusieurs évolutions peuvent être envisagées :

- Renforcer la sécurité avec CSRF, validation approfondie et filtres XSS.
- Ajouter des tests unitaires et fonctionnels.
- Améliorer le design responsive pour mobile.
- Ajouter des notifications en temps réel via WebSocket ou Pusher.
- Étendre la plateforme avec des groupes, événements et calendrier partagé.
- Implémenter une recherche avancée et des filtres pour les activités et posts.

## 6. Bibliographie et Webographie

- PHP.net, « PHP: Hypertext Preprocessor », https://www.php.net, consulté le 12 juillet 2026.
- MySQL Documentation, « MySQL Reference Manual », https://dev.mysql.com/doc, consulté le 12 juillet 2026.
- Mozilla Developer Network, « HTML, CSS et JavaScript », https://developer.mozilla.org, consulté le 12 juillet 2026.
- La documentation PHP sur `password_hash`, `PDO` et `session_start`.
- Tutoriels et articles sur la gestion d’API REST avec PHP.

## 7. Annexes

### Annexe A : Schéma de la base de données

Tables principales :

- `utilisateur`
- `role`
- `activite`
- `etat`
- `amis`
- `conversation`
- `conversation_membre`
- `chat_message`
- `post`
- `notification`
- `type_notification`
- `photos_collection`

### Annexe B : Extraits de code importants

#### `AuthController.php`

- Inscription avec OTP.
- Connexion et redirection par rôle.
- Réinitialisation de mot de passe.

#### `ApiController.php`

- Endpoints JSON pour l’ajout d’amis, la messagerie, les publications, les notifications, l’administration.

#### `Activite.php`

- Logique de calcul du statut d’activité selon la date et l’heure.

### Annexe C : Exemple de log d’email

Le fichier `uploads/email_log.txt` contient les emails simulés envoyés pour OTP et réinitialisation de mot de passe.

### Annexe D : Captures d’écran possibles

- Page de connexion.
- Tableaux de bord utilisateur.
- Page d’administration.
- Section messages et notifications.

---

*Document rédigé à partir de l’analyse du code source du projet WorkFlow et du guide de rédaction de monographie L1/L2.*
