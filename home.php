<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8" />
  <title>Home</title>
</head>
<body>
  <h1>Bem-vindo, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>

  <?php
    if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1) {
        echo "<p>Você é um <strong>administrador</strong>.</p>";
    } else {
        echo "<p>Você é um usuário comum.</p>";
    }
  ?>

  <a href="logout.php">Sair</a>
</body>
</html>
