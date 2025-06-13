<?php
session_start();
include_once('config.php');

$mensagem = '';

if (isset($_POST['submit'])) {
    $nome = $_POST['nome'] ?? '';
    $email = $_POST['email'] ?? '';
    $telefone = $_POST['telefone'] ?? '';
    $nascimento = $_POST['nascimento'] ?? '';
    $endereco = $_POST['endereco'] ?? '';
    $senha = $_POST['senha'] ?? '';
    $admin = isset($_POST['admin']) ? 1 : 0;

    if (!$nome || !$email || !$telefone || !$nascimento || !$endereco || !$senha) {
        $mensagem = "Por favor, preencha todos os campos.";
    } else {
        // Verificar se email já existe na tabela users
        $sqlCheck = "SELECT id FROM users WHERE email = :email";
        $stmtCheck = $pdo->prepare($sqlCheck);
        $stmtCheck->execute([':email' => $email]);
        
        if ($stmtCheck->fetch()) {
            $mensagem = "Este email já está cadastrado.";
        } else {
            // Hash da senha
            $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

            // Inserir dados no new_table (sem senha)
            $sqlInsertNew = "INSERT INTO new_table (nome, email, telefone, nascimento, endereco) 
                             VALUES (:nome, :email, :telefone, :nascimento, :endereco)";
            $stmtNew = $pdo->prepare($sqlInsertNew);
            $insertNewOk = $stmtNew->execute([
                ':nome' => $nome,
                ':email' => $email,
                ':telefone' => $telefone,
                ':nascimento' => $nascimento,
                ':endereco' => $endereco
            ]);

            // Inserir dados no users (com senha e is_admin)
            $sqlInsertUser = "INSERT INTO users (username, email, password, is_admin) 
                              VALUES (:username, :email, :password, :is_admin)";
            $stmtUser = $pdo->prepare($sqlInsertUser);
            $insertUserOk = $stmtUser->execute([
                ':username' => $nome,
                ':email' => $email,
                ':password' => $senhaHash,
                ':is_admin' => $admin
            ]);

            if ($insertNewOk && $insertUserOk) {
                $_SESSION['msg_cadastro'] = "Cadastro realizado com sucesso! Faça login.";
                header("Location: login.php");
                exit();
            } else {
                $mensagem = "Erro ao cadastrar, tente novamente.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Cadastro</title>
  <style>
    body {
      font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
      margin: 0;
      background: linear-gradient(to bottom, #A7D8DD, #E8FCFC);
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
    }
    .cadastro-box {
      background-color: #f0f9fa;
      padding: 40px 35px;
      border-radius: 15px;
      box-shadow: 0 8px 20px rgba(0,0,0,0.1);
      color: #2f4f4f;
      width: 100%;
      max-width: 450px;
    }
    .cadastro-box h1 {
      text-align: center;
      margin-bottom: 30px;
    }
    input, label {
      display: block;
      width: 100%;
      margin-bottom: 15px;
      font-size: 16px;
    }
    input[type="checkbox"] {
      width: auto;
      margin-right: 8px;
      vertical-align: middle;
      display: inline-block;
    }
    label.checkbox-label {
      display: flex;
      align-items: center;
      font-weight: normal;
      margin-bottom: 25px;
      user-select: none;
    }
    input[type="text"],
    input[type="email"],
    input[type="password"],
    input[type="tel"],
    input[type="date"] {
      padding: 12px;
      border: 1px solid #ccc;
      border-radius: 8px;
      box-sizing: border-box;
      transition: border 0.2s ease;
    }
    input:focus {
      border-color: #5da7d1;
      outline: none;
      box-shadow: 0 0 5px #5da7d1;
    }
    button {
      width: 100%;
      background-color: #5da7d1;
      color: white;
      border: none;
      padding: 14px;
      font-size: 16px;
      border-radius: 8px;
      transition: background-color 0.3s ease;
    }
    button:hover {
      background-color: #3b85c3;
      cursor: pointer;
    }
    .mensagem-erro {
      color: red;
      text-align: center;
      margin-bottom: 15px;
    }
  </style>
</head>
<body>
  <div class="cadastro-box">
    <h1>Cadastro</h1>
    <?php if (!empty($mensagem)) echo "<p class='mensagem-erro'>$mensagem</p>"; ?>
    <form method="POST" action="">
      <input type="text" name="nome" placeholder="Nome completo" required />
      <input type="email" name="email" placeholder="Email" required />
      <input type="tel" name="telefone" placeholder="Telefone" required />
      <input type="date" name="nascimento" placeholder="Data de nascimento" required />
      <input type="text" name="endereco" placeholder="Endereço" required />
      <input type="password" name="senha" placeholder="Senha" required />
      <label class="checkbox-label">
        <input type="checkbox" name="admin" />
        É administrador?
      </label>
      <button type="submit" name="submit">Cadastrar</button>
    </form>
  </div>
</body>
</html>
