<?php


class ActiviteController {

    public function creer() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $activite = new Activite();
            $activite->creer($_POST);

            header('Location: index.php?page=dashboard');
        }
    }
}