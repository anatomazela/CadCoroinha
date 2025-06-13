<?php
session_start();

// Verificar se o usuário está logado e se é admin
if (!isset($_SESSION['user_id']) || $_SESSION['is_admin'] != 1) {
    header('Location: login.php');
    exit();
}
?>

<h1>Bem-vindo, Admin <?php echo $_SESSION['username']; ?>!</h1>
<a href="logout.php">Sair</a>
