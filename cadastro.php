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
            // Verificar se email já existe
            $sqlCheck = "SELECT id FROM users WHERE email = ?";
            $stmtCheck = $pdo->prepare($sqlCheck);
            $stmtCheck->execute([$email]);
            
            if ($stmtCheck->fetch()) {
                $mensagem = "Este email já está cadastrado.";
            } else {
                $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

                // Inserir dados no users
                $sqlInsertUser = "INSERT INTO users (username, email, password, is_admin) 
                                VALUES (?, ?, ?, ?)";
                $stmtUser = $pdo->prepare($sqlInsertUser);
                $stmtUser->execute([$nome, $email, $senhaHash, $admin]);

                // Inserir dados no new_table (se ainda existir)
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
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            background: linear-gradient(to bottom, #A7D8DD, #E8FCFC);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: baseline;
        padding: 80px 20px 20px; /* Espaço para o header e margens laterais */
        }

        .cadastro-box {
            background-color: #f0f9fa;
            padding: 40px 40px;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            color: #2f4f4f;
            width: 100%;
            max-width: 450px;
            margin-top: 20px;
        }

        .cadastro-box h1 {
            text-align: center;
            margin-bottom: 30px;
            color: #2f4f4f;
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
            border-color: #5da7d1;
            outline: none;
            box-shadow: 0 0 5px #5da7d1;
        }

        .cadastro-box button {
            width: 100%;
            background-color: #5da7d1;
            color: white;
            border: none;
            padding: 14px;
            font-size: 16px;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .cadastro-box button:hover {
            background-color: #3b85c3;
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

        @media (max-width: 768px) {
            .cadastro-box {
                padding: 45px 45px;
                margin: 20px auto;
            }
        }/* HEADER IDÊNTICO AO LOGIN */
header {
    background-color: #5da7d1;
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
    /* Estilo base para todos os inputs */
    input[type="date"],
    input[type="time"],
    input[type="text"] {
        width: 100%;
        padding: 12px;
        margin-bottom: 15px;
        border: 1px solid #ccc;
        border-radius: 8px;
        font-size: 16px;
        background-color: white;
    }
    
    /* Container para os campos especiais */
    .input-date-container,
    .input-time-container {
        position: relative;
    }
    
    /* Placeholder personalizado */
    .date-placeholder,
    .time-placeholder {
        position: absolute;
        left: 12px;
        top: 12px;
        color: #999;
        pointer-events: none;
        background-color: white;
        padding: 0 5px;
    }
    
    /* Estilo quando em foco */
    input:focus {
        border-color: #5da7d1;
        outline: none;
        box-shadow: 0 0 5px #5da7d1;
    }
    
    /* Esconde o placeholder quando o input tem valor */
    input[type="date"]:valid + .date-placeholder,
    input[type="time"]:valid + .time-placeholder {
        display: none;
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