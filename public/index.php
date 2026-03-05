<?php

require_once '../config/Database.php';
require_once '../core/Model.php';

spl_autoload_register(function ($class) {
    if (file_exists("../models/$class.php")) {
        require_once "../models/$class.php";
    }
    if (file_exists("../controllers/$class.php")) {
        require_once "../controllers/$class.php";
    }
});

$page = $_GET['page'] ?? 'login' ;

switch ($page) {
    case 'dashboard':
        require '../views/dashboard.php';
        break;
    case 'creer-activite':
        (new ActiviteController())->creer();
        break;
    default : 
    require '../views/login.php';
}