<?php
session_start();
include_once('./db_connect.php');

if ($_POST) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $query = "SELECT * FROM users WHERE username = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$username]);

    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        header("Location: /wiki-games/index.php");
        exit();
    } else {
        $error_message = "Invalid username or password";
    }
}

?>
<?php include_once('header.php'); ?>

<?php if (isset($error_message)) : ?>
    <div class="alert alert-danger"><?php echo $error_message; ?></div>
<?php endif; ?>

<form method="POST">
    <label>Username</label>
    <input type="text" name="username" required>
    <label>Password</label>
    <input type="password" name="password" required>
    <button type="submit">Connexion</button>
</form>

<!-- Adding a Register link -->
<div>
    <p>Pas encore de compte ? <a href="./register">S'inscrire</a></p>
</div>