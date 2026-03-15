<?php
session_start();

require_once "../core/Flash.php";
require_once "../models/Activite.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$error = get_flash('error');
$success = get_flash('success');

if ($error):
    echo '<div class="alert alert-error">' . htmlspecialchars($error) . '</div>';
endif;

if ($success):
    echo '<div class="alert alert-success">' . htmlspecialchars($success) . '</div>';
endif;

$activiteModel = new Activite();
$activites = $activiteModel->listerParUser($_SESSION['user_id']);

foreach ($activites as $a) {
    echo "<p>" . htmlspecialchars($a['libelle']) . " - " . htmlspecialchars($a['date_activite']) . "</p>";
}
?>
<form method="post" action="logout">
    <button type="submit">Déconnexion</button>
</form>