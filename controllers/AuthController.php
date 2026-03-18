<?php

require_once __DIR__ . "/../models/Utilisateur.php";
require_once __DIR__ . "/../core/Flash.php";

class AuthController
{

    public function login()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                $email    = $_POST['email'] ?? '';
                $password = $_POST['password'] ?? '';

                $userModel = new Utilisateur();
                $users     = $userModel->readAll();

                foreach ($users as $user) {
                    if ($user["email"] == $email && password_verify($password, $user["password"])) {
                        $_SESSION["user_id"] = $user["id_user"];
                        $_SESSION["nom"]     = $user["nom"];

                        header("Location: ../views/dashboard.php");
                        exit;
                    }
                }

                set_old($_POST);
                set_flash('error', 'Email ou mot de passe incorrect.');
                header('Location: ../views/login.php');
                exit;
            } catch (Throwable $e) {
                set_flash('error', 'Impossible de se connecter.' . (defined('DEBUG') && DEBUG ? ' ' . $e->getMessage() : ''));
                header('Location: ../views/login.php');
                exit;
            }
        }
    }

    public function register()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $nom      = trim($_POST['nom'] ?? '');
                $prenom   = trim($_POST['prenom'] ?? '');
                $email    = trim($_POST['email'] ?? '');
                $password = $_POST['password'] ?? '';

                if ($nom === '' || $prenom === '' || $email === '' || $password === '') {
                    set_old($_POST);
                    set_flash('error', 'Tous les champs sont obligatoires.');
                    header('Location: ../views/register.php');
                    exit;
                }

                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    set_old($_POST);
                    set_flash('error', 'Veuillez saisir une adresse email valide.');
                    header('Location: ../views.register.php');
                    exit;
                }

                if (strlen($password) < 6) {
                    set_old($_POST);
                    set_flash('error', 'Le mot de passe doit contenir au moins 6 caractères.');
                    header('Location: ../views/register.php');
                    exit;
                }

                $user = new Utilisateur();

                if ($user->existsByEmail($email)) {
                    set_old($_POST);
                    set_flash('error', 'Cette adresse email est déjà utilisée.');
                    header('Location: ../views/register?php');
                    exit;
                }

                $user->nom      = $nom;
                $user->prenom   = $prenom;
                $user->email    = $email;
                $user->password = $password;
                $user->id_role  = 2;

                $user->create();

                // Générer un username automatique
                $db     = Database::getInstance()->getConn();
                $newId  = (int) $db->lastInsertId();
                $user->setUsername($newId, $user->generateUsername($nom, $prenom));

                set_flash('success', 'Compte créé avec succès. Vous pouvez maintenant vous connecter.');
                header('Location: ../views/login.php');
                exit;
            } catch (Throwable $e) {
                set_flash('error', 'Impossible de créer le compte.' . (defined('DEBUG') && DEBUG ? ' ' . $e->getMessage() : ''));
                header('Location: ../views/register.php');
                exit;
            }
        }
    }

    public function logout()
    {
        session_destroy();
        header("Location: ../views/login.php");
        exit;
    }
}
