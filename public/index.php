<?php

// Enable debug output during development (set to false in production)
define('DEBUG', true);

session_start();

require_once '../config/Database.php';
require_once '../core/Model.php';
require_once '../core/Flash.php';
require_once '../controllers/AuthController.php';

// Autoload controllers and models when referenced by name
spl_autoload_register(function ($class) {
    if (file_exists("../models/$class.php")) {
        require_once "../models/$class.php";
    }
    if (file_exists("../controllers/$class.php")) {
        require_once "../controllers/$class.php";
    }
});

function handle_exception(Throwable $e): void
{
    // In production, do not expose exception details to end users.
    $message = 'Une erreur est survenue. Veuillez réessayer plus tard.';

    if (defined('DEBUG') && DEBUG) {
        $message .= ' (' . $e->getMessage() . ')';
    }

    set_flash('error', $message);

    header('Location: /?page=login');
    exit;
}

set_exception_handler('handle_exception');

$action = $_GET['action'] ?? null;
if ($action === 'login') {
    $controller = new AuthController();
    $controller->login();
    exit;
}

if ($action === 'register') {
    $controller = new AuthController();
    $controller->register();
    exit;
}

if ($action === 'logout') {
    $controller = new AuthController();
    $controller->logout();
    exit;
}

if ($action === 'creer-activite') {
    (new ActiviteController())->creer();
    exit;
}

if ($action === 'api') {
    $endpoint = $_GET['endpoint'] ?? '';
    (new ApiController())->handle($endpoint);
    exit;
}

$page = $_GET['page'] ?? 'login';

switch ($page) {
    case 'dashboard':
        require '../views/dashboard.php';
        break;
    case 'register':
        require '../views/register.php';
        break;
    default:
        require '../views/login.php';
}
