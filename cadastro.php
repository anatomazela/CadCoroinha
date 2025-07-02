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
        try {

            $sqlCheck = "SELECT id FROM users WHERE email = ?";
            $stmtCheck = $pdo->prepare($sqlCheck);
            $stmtCheck->execute([$email]);
            
            if ($stmtCheck->fetch()) {
                $mensagem = "Este email já está cadastrado.";
            } else {
                $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

                $sqlInsertUser = "INSERT INTO users (username, email, password, is_admin) 
                                VALUES (?, ?, ?, ?)";
                $stmtUser = $pdo->prepare($sqlInsertUser);
                $stmtUser->execute([$nome, $email, $senhaHash, $admin]);

                $sqlInsertNew = "INSERT INTO new_table (nome, email, telefone, nascimento, endereco) 
                               VALUES (?, ?, ?, ?, ?)";
                $stmtNew = $pdo->prepare($sqlInsertNew);
                $stmtNew->execute([$nome, $email, $telefone, $nascimento, $endereco]);

                $_SESSION['msg_cadastro'] = "Cadastro realizado com sucesso! Faça login.";
                header("Location: login.php");
                exit();
            }
        } catch (PDOException $e) {
            $mensagem = "Erro ao cadastrar: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro</title>
    <link rel="stylesheet" href="style.css">
    <style>
        :root {
    --amarelo-principal: #D6A24C;
    --amarelo-hover: #A5753F;
    --fundo-claro: #FFF8F1;
    --fundo-gradiente: linear-gradient(to bottom, #FDF6EC, #FCE9D4);
    --texto-escuro: #5C4B2D;
}

body {
    font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
    margin: 0;
    background: var(--fundo-gradiente);
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: baseline;
    padding: 80px 20px 20px;
    color: var(--texto-escuro);
}

header {
    background-color: var(--amarelo-principal);
    padding: 20px;
    text-align: center;
    position: fixed;
    top: 0;
    width: 100%;
    z-index: 1000;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.header-content {
    max-width: 1200px;
    margin: 0 auto;
}

.header-link {
    color: white !important;
    text-decoration: none !important;
    font-weight: bold;
    font-size: 18px;
    margin: 0 15px;
    transition: all 0.3s ease;
}

.header-link:hover {
    text-decoration: underline !important;
    opacity: 0.9;
}

.cadastro-box {
    background-color: var(--fundo-claro);
    padding: 40px 40px;
    border-radius: 15px;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
    color: var(--texto-escuro);
    width: 100%;
    max-width: 450px;
    margin-top: 20px;
}

.cadastro-box h1 {
    text-align: center;
    margin-bottom: 30px;
    color: var(--texto-escuro);
}

.cadastro-box input {
    width: 100%;
    padding: 15px;
    margin-bottom: 15px;
    border: 1px solid #ccc;
    border-radius: 8px;
    font-size: 16px;
    transition: border 0.2s ease;
}

.cadastro-box input:focus {
    border-color: var(--amarelo-principal);
    outline: none;
    box-shadow: 0 0 5px var(--amarelo-principal);
}

.cadastro-box button {
    width: 100%;
    background-color: var(--amarelo-principal);
    color: white;
    border: none;
    padding: 14px;
    font-size: 16px;
    border-radius: 8px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.cadastro-box button:hover {
    background-color: var(--amarelo-hover);
}

.checkbox-label {
    display: flex;
    align-items: center;
    margin: 15px 0;
    font-size: 16px;
}

.checkbox-label input {
    width: auto;
    margin-right: 10px;
}

.mensagem-erro {
    color: #d9534f;
    font-weight: bold;
    text-align: center;
    margin-bottom: 20px;
    padding: 10px;
    background-color: #f8d7da;
    border-radius: 5px;
    border: 1px solid #f5c6cb;
}

.input-date-container {
    position: relative;
}

.date-placeholder {
    position: absolute;
    left: 12px;
    top: 12px;
    color: #999;
    pointer-events: none;
    background-color: white;
    padding: 0 5px;
}

input:focus {
    border-color: var(--amarelo-principal);
    outline: none;
    box-shadow: 0 0 5px var(--amarelo-principal);
}

input[type="date"]:valid + .date-placeholder {
    display: none;
}

@media (max-width: 768px) {
    .cadastro-box {
        padding: 35px;
        margin: 20px auto;
    }
}

@media (max-width: 522px) {
    body {
        padding: 70px 15px 15px;
    }

    .cadastro-box {
        padding: 45px 40px;
        border-radius: 10px;
    }

    .cadastro-box h1 {
        font-size: 24px;
        margin-bottom: 20px;

    }

    .cadastro-box input {
        padding: 12px;
        font-size: 15px;
    }
}

    </style>
</head>
<body>
    <header>
    <div class="header-content">
        <a href="index.html" class="header-link">Início</a>
    </div>
</header>

    <div class="cadastro-box">
        <h1>Cadastro</h1>
        
        <?php if (!empty($mensagem)): ?>
            <p class="mensagem-erro"><?= htmlspecialchars($mensagem) ?></p>
        <?php endif; ?>
        
        <form method="POST" action="">
            <input type="text" name="nome" placeholder="Nome completo" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="tel" name="telefone" placeholder="Telefone" required>
            <div class="input-date-container">
        <input type="date" name="nascimento" id="nascimento" required
               onchange="this.nextElementSibling.style.display='none'">
        <label for="nascimento" class="date-placeholder">Nascimento</label>
    </div>
            <input type="text" name="endereco" placeholder="Endereço" required>
            <input type="password" name="senha" placeholder="Senha" required>
            
            <label class="checkbox-label">
                <input type="checkbox" name="admin">
                É administrador?
            </label>
            
            <button type="submit" name="submit">Cadastrar</button>
        </form>
    </div>
</body>
</html>