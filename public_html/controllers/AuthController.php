<?php

require_once __DIR__ . "/../models/Utilisateur.php";
require_once __DIR__ . "/../core/Flash.php";
require_once __DIR__ . "/../core/EmailService.php";

class AuthController
{
    public function login()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                $email    = trim($_POST['email'] ?? '');
                $password = $_POST['password'] ?? '';

                if ($email === '' || $password === '') {
                    set_old($_POST);
                    set_flash('error', 'Veuillez remplir tous les champs.');
                    header('Location: index.php?page=login');
                    exit;
                }

                $userModel = new Utilisateur();
                $user = $userModel->findByEmail($email);

                if ($user && password_verify($password, $user["password"])) {
                    // Vérifier si le compte est activé
                    if ((int)$user['email_verified'] !== 1) {
                        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
                        $_SESSION['temp_user_id'] = $user['id_user'];
                        $_SESSION['temp_user_email'] = $user['email'];
                        
                        // Renvoyer un OTP s'il n'y en a pas ou s'il a expiré
                        $otp = str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT);
                        $expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                        $userModel->updateOtp($user['id_user'], $otp, $expires);
                        
                        EmailService::send(
                            $user['email'],
                            "Verification de votre compte",
                            "Bonjour,\n\nVoici votre code de vérification OTP : $otp\n\nCe code expirera dans 15 minutes."
                        );

                        set_flash('error', 'Votre compte n\'est pas vérifié. Saisissez le code OTP envoyé.');
                        header('Location: index.php?page=verify-otp');
                        exit;
                    }

                    // Enregistrer en session
                    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
                    $_SESSION["user_id"] = $user["id_user"];
                    $_SESSION["nom"]     = $user["nom"];
                    $_SESSION["prenom"]  = $user["prenom"];
                    $_SESSION["id_role"] = (int)$user["id_role"];

                    // Mettre à jour le statut en ligne
                    $db = Database::getInstance()->getConn();
                    $db->prepare("UPDATE utilisateur SET statut_en_ligne = 1 WHERE id_user = :id")
                       ->execute([':id' => $user["id_user"]]);

                    // Redirection selon le rôle
                    if ((int)$user["id_role"] === 1) {
                        header("Location: index.php?page=admin");
                    } else {
                        header("Location: index.php?page=dashboard");
                    }
                    exit;
                }

                set_old($_POST);
                set_flash('error', 'Email ou mot de passe incorrect.');
                header('Location: index.php?page=login');
                exit;
            } catch (Throwable $e) {
                set_flash('error', 'Impossible de se connecter.' . (defined('DEBUG') && DEBUG ? ' ' . $e->getMessage() : ''));
                header('Location: index.php?page=login');
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
                    header('Location: index.php?page=register');
                    exit;
                }

                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    set_old($_POST);
                    set_flash('error', 'Veuillez saisir une adresse email valide.');
                    header('Location: index.php?page=register');
                    exit;
                }

                if (strlen($password) < 6) {
                    set_old($_POST);
                    set_flash('error', 'Le mot de passe doit contenir au moins 6 caractères.');
                    header('Location: index.php?page=register');
                    exit;
                }

                $user = new Utilisateur();

                if ($user->existsByEmail($email)) {
                    set_old($_POST);
                    set_flash('error', 'Cette adresse email est déjà utilisée.');
                    header('Location: index.php?page=register');
                    exit;
                }

                // Générer OTP de 6 chiffres
                $otp = str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT);
                $expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));

                $user->nom      = $nom;
                $user->prenom   = $prenom;
                $user->email    = $email;
                $user->password = $password;
                $user->id_role  = 2; // Rôle utilisateur simple
                $user->email_verified = 0;
                $user->otp_code = $otp;
                $user->otp_expires_at = $expires;

                $user->create();

                // Générer un username automatique
                $db     = Database::getInstance()->getConn();
                $newId  = (int) $db->lastInsertId();
                $user->setUsername($newId, $user->generateUsername($nom, $prenom));

                // Envoyer l'email OTP
                EmailService::send(
                    $email,
                    "Activation de votre compte WorkFlow",
                    "Bonjour $prenom,\n\nBienvenue sur WorkFlow ! Pour valider votre inscription, veuillez saisir le code de vérification suivant : $otp\n\nCe code expirera dans 15 minutes."
                );

                // Stocker en session temporaire pour la validation
                if (session_status() !== PHP_SESSION_ACTIVE) session_start();
                $_SESSION['temp_user_id'] = $newId;
                $_SESSION['temp_user_email'] = $email;

                set_flash('success', 'Votre compte a été pré-créé. Un code OTP a été envoyé à votre email pour validation.');
                header('Location: index.php?page=verify-otp');
                exit;
            } catch (Throwable $e) {
                set_flash('error', 'Impossible de créer le compte.' . (defined('DEBUG') && DEBUG ? ' ' . $e->getMessage() : ''));
                header('Location: index.php?page=register');
                exit;
            }
        }
    }

    public function verifyOtp() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (session_status() !== PHP_SESSION_ACTIVE) session_start();
            $userId = $_SESSION['temp_user_id'] ?? null;
            $code = trim($_POST['otp_code'] ?? '');

            if (!$userId) {
                set_flash('error', 'Session de validation expirée ou invalide.');
                header('Location: index.php?page=login');
                exit;
            }

            if (strlen($code) !== 6 || !is_numeric($code)) {
                set_flash('error', 'Le code OTP doit être composé de 6 chiffres.');
                header('Location: index.php?page=verify-otp');
                exit;
            }

            $userModel = new Utilisateur();
            if ($userModel->verifyOtp($userId, $code)) {
                // Succès de la vérification, connexion automatique
                $user = $userModel->find($userId);
                $_SESSION["user_id"] = $user["id_user"];
                $_SESSION["nom"]     = $user["nom"];
                $_SESSION["prenom"]  = $user["prenom"];
                $_SESSION["id_role"] = (int)$user["id_role"];

                // Mettre à jour statut en ligne
                $db = Database::getInstance()->getConn();
                $db->prepare("UPDATE utilisateur SET statut_en_ligne = 1 WHERE id_user = :id")
                   ->execute([':id' => $userId]);

                unset($_SESSION['temp_user_id']);
                unset($_SESSION['temp_user_email']);

                set_flash('success', 'Votre compte a été vérifié et activé avec succès !');
                header('Location: index.php?page=dashboard');
                exit;
            } else {
                set_flash('error', 'Code OTP invalide ou expiré.');
                header('Location: index.php?page=verify-otp');
                exit;
            }
        }
    }

    public function resendOtp() {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        $userId = $_SESSION['temp_user_id'] ?? null;
        $email = $_SESSION['temp_user_email'] ?? null;

        if (!$userId || !$email) {
            set_flash('error', 'Session de validation invalide.');
            header('Location: index.php?page=login');
            exit;
        }

        $otp = str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT);
        $expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));

        $userModel = new Utilisateur();
        $userModel->updateOtp($userId, $otp, $expires);

        EmailService::send(
            $email,
            "Nouveau code de validation WorkFlow",
            "Bonjour,\n\nVous avez demandé un nouveau code. Voici votre nouveau code OTP : $otp\n\nCe code expirera dans 15 minutes."
        );

        set_flash('success', 'Un nouveau code OTP a été généré et envoyé à votre adresse email.');
        header('Location: index.php?page=verify-otp');
        exit;
    }

    public function forgotPassword() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $email = trim($_POST['email'] ?? '');
                if ($email === '') {
                    set_flash('error', 'Veuillez saisir votre adresse email.');
                    header('Location: index.php?page=forgot-password');
                    exit;
                }

                $userModel = new Utilisateur();
                $user = $userModel->findByEmail($email);

                if ($user) {
                    $token = bin2hex(random_bytes(32));
                    $expires = date('Y-m-d H:i:s', strtotime('+30 minutes'));
                    $userModel->setRecoveryToken($user['id_user'], $token, $expires);

                    // Envoyer email avec lien
                    $resetLink = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . "/index.php?page=reset-password&token=$token";
                    EmailService::send(
                        $email,
                        "Récupération de mot de passe WorkFlow",
                        "Bonjour " . $user['prenom'] . ",\n\nVous avez demandé la réinitialisation de votre mot de passe.\nCliquez sur le lien ci-dessous pour saisir un nouveau mot de passe :\n\n$resetLink\n\nCe lien expirera dans 30 minutes. Si vous n'êtes pas à l'origine de cette demande, vous pouvez ignorer cet e-mail."
                    );
                }

                // Pour des raisons de sécurité, nous affichons le même message de réussite
                set_flash('success', 'Si l\'adresse e-mail existe, un lien de réinitialisation y a été envoyé. Veuillez vérifier vos e-mails (ou le fichier uploads/email_log.txt).');
                header('Location: index.php?page=login');
                exit;
            } catch (Throwable $e) {
                set_flash('error', 'Erreur lors du traitement de votre demande.' . (defined('DEBUG') && DEBUG ? ' ' . $e->getMessage() : ''));
                header('Location: index.php?page=forgot-password');
                exit;
            }
        }
    }

    public function resetPassword() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $token = trim($_POST['token'] ?? '');
                $password = $_POST['password'] ?? '';
                $confirm  = $_POST['confirm_password'] ?? '';

                if ($token === '') {
                    set_flash('error', 'Token de récupération manquant.');
                    header('Location: index.php?page=login');
                    exit;
                }

                if (strlen($password) < 6) {
                    set_flash('error', 'Le mot de passe doit contenir au moins 6 caractères.');
                    header('Location: index.php?page=reset-password&token=' . urlencode($token));
                    exit;
                }

                if ($password !== $confirm) {
                    set_flash('error', 'Les mots de passe ne correspondent pas.');
                    header('Location: index.php?page=reset-password&token=' . urlencode($token));
                    exit;
                }

                $userModel = new Utilisateur();
                $user = $userModel->findByRecoveryToken($token);

                if (!$user) {
                    set_flash('error', 'Ce lien de récupération est invalide ou a expiré.');
                    header('Location: index.php?page=login');
                    exit;
                }

                $userModel->resetPassword($user['id_user'], $password);

                set_flash('success', 'Votre mot de passe a été modifié avec succès. Vous pouvez maintenant vous connecter.');
                header('Location: index.php?page=login');
                exit;
            } catch (Throwable $e) {
                set_flash('error', 'Erreur lors de la réinitialisation du mot de passe.' . (defined('DEBUG') && DEBUG ? ' ' . $e->getMessage() : ''));
                header('Location: index.php?page=login');
                exit;
            }
        }
    }

    public function logout()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        $userId = $_SESSION['user_id'] ?? null;
        if ($userId) {
            $db = Database::getInstance()->getConn();
            $db->prepare("UPDATE utilisateur SET statut_en_ligne = 0 WHERE id_user = :id")
               ->execute([':id' => $userId]);
        }

        session_destroy();
        header("Location: index.php?page=login");
        exit;
    }
}
