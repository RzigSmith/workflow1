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

<form method="POST" action="../public/index.php?action=login">

<label>Email</label>
<input type="email" name="email" required value="<?php echo htmlspecialchars(old('email')); ?>">

<label>Mot de passe</label>
<input type="password" name="password" required>

<button type="submit">Connexion</button>

</form>

<p>Pas encore de compte ? <a href="../views/register.php">S'inscrire</a></p>

<?php clear_old(); ?>