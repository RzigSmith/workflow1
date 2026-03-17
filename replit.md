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
controllers/    - AuthController, ActiviteController, MessageController
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

## Key Notes

- Session-based authentication
- Passwords hashed with `password_hash()`
- Flash messages via `core/Flash.php`
- `getConn()` is the canonical method name on the `Database` class (PHP method names are case-insensitive, so `getconn()` and `getConn()` are equivalent)
