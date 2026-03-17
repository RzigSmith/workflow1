# Workflow PHP App

A French-language PHP 8.2 web application for managing tasks/activities (activités), users, notifications, and conversations.

## Architecture

- **Language:** PHP 8.2
- **Database:** SQLite (via PDO) — stored at `database/workflow.sqlite`
- **Pattern:** Custom MVC (no framework)
- **Server:** PHP built-in development server on port 5000

## Directory Structure

```
config/         - Database configuration (SQLite PDO)
controllers/    - AuthController, ActiviteController, ApiController
core/           - Base Controller, Model, Flash helper
models/         - Activite, Utilisateur, Role, Etat, Notification, etc.
public/         - Entry point (index.php), web root
views/          - PHP view templates (login, register, dashboard)
database/       - workflow.sqlite (auto-created), workflow.sql (original MySQL schema)
```

## Running the App

The workflow runs: `php -S 0.0.0.0:5000 -t public/`

## Database

Originally MySQL (`workflow` database), converted to SQLite for Replit compatibility. The database is auto-created at startup with seeded reference data (roles, etats, type_notifications).

## Frontend Assets

- `public/assets/css/style.css` — Design system complet (variables CSS, layout sidebar, cartes, badges, toasts, modals, animations, responsive)
- `public/assets/js/app.js` — JavaScript pour la fluidité : navigation SPA (sections sans rechargement), barre de progression, toasts de notification, toggle mot de passe, états de chargement des formulaires, sidebar mobile, gestion des confirmations

## UI / Navigation

- **Routing SPA** : le dashboard utilise des sections (`data-section`) commutées côté client — navigation fluide sans rechargement de page
- **Toasts** : les messages flash sont affichés en notifications animées en bas à droite
- **Barre de progression** : animation de chargement sur chaque navigation de section
- **Sidebar responsive** : fixe sur desktop, drawer mobile avec overlay

## Routing

- `/?page=login` — page connexion
- `/?page=register` — page inscription
- `/?page=dashboard` — tableau de bord (requiert session)
- `/?action=login` (POST) — traitement connexion
- `/?action=register` (POST) — traitement inscription
- `/?action=logout` — déconnexion
- `/?action=creer-activite` (POST) — création d'activité
- `/?action=api&endpoint=<name>` — API JSON pour les fonctionnalités sociales

## Social Features (v2)

### Base de données (tables ajoutées)
- `amis` — demandes d'amitié (pending / accepted / declined), UNIQUE(u1,u2)
- `post` — publications/blog avec visibilité (amis/public) et lien optionnel vers activite
- `chat_message` — messages de chat par conversation
- `conversation_membre` — membres de chaque conversation
- `utilisateur.username` — identifiant public (auto-généré à l'inscription)

### API JSON (/?action=api&endpoint=...)
- `search-users?q=` — recherche utilisateurs par nom/username
- `add-friend` POST — envoyer demande d'ami
- `friend-respond` POST — accepter/refuser demande
- `remove-friend` POST — retirer un ami
- `start-conversation` POST — ouvrir/créer conversation DM
- `get-conversations` — liste des conversations de l'utilisateur
- `get-messages?conv_id=&last_id=` — messages (polling, since last_id)
- `send-message` POST — envoyer message
- `create-post` POST — créer publication/blog
- `delete-post` POST — supprimer sa propre publication
- `get-feed` — fil d'actualité (ses posts + posts des amis)

### Fichiers JS
- `public/assets/js/app.js` — navigation SPA, toasts, modals, progress bar
- `public/assets/js/social.js` — FriendSearch, Social, Feed, Chat (polling toutes 2.5s)

## Key Notes

- Session-based authentication
- Passwords hashed with `password_hash()`
- Flash messages via `core/Flash.php`
- `getConn()` is the canonical method name on the `Database` class (PHP method names are case-insensitive)
- All redirects go through `index.php` — never direct view access
