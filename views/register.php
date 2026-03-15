<?php
require_once __DIR__ . '/../core/Flash.php';

$error = get_flash('error');
$success = get_flash('success');
?>

<?php if ($error): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<form method="POST" action="../public/index.php?action=register">

<input type="text" name="nom" placeholder="Nom" required value="<?php echo htmlspecialchars(old('nom')); ?>">

<input type="text" name="prenom" placeholder="Prénom" required value="<?php echo htmlspecialchars(old('prenom')); ?>">

<input type="email" name="email" placeholder="Email" required value="<?php echo htmlspecialchars(old('email')); ?>">

<input type="password" name="password" placeholder="Mot de passe" required>

<button type="submit">Créer compte</button>

</form>

<?php clear_old(); ?>