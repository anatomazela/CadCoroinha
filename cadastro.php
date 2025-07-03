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
    $tipo = $_POST['tipo'] ?? null;
    $admin = isset($_POST['admin']) ? 1 : 0;

    // Validação modificada para permitir tipo nulo para admins
    $camposObrigatorios = [$nome, $email, $telefone, $nascimento, $endereco, $senha];
    if ($admin == 0) {
        $camposObrigatorios[] = $tipo;
    }

    if (in_array('', $camposObrigatorios)) {
        $mensagem = "Por favor, preencha todos os campos.";
    } else {
        try {
            $pdo->beginTransaction();

            $sqlCheck = "SELECT id FROM users WHERE email = ?";
            $stmtCheck = $pdo->prepare($sqlCheck);
            $stmtCheck->execute([$email]);
            
            if ($stmtCheck->fetch()) {
                $mensagem = "Este email já está cadastrado.";
                $pdo->rollBack();
            } else {
                $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

                // Primeiro insere na tabela users
                $sqlInsertUser = "INSERT INTO users (username, email, password, is_admin) 
                                VALUES (?, ?, ?, ?)";
                $stmtUser = $pdo->prepare($sqlInsertUser);
                $stmtUser->execute([$nome, $email, $senhaHash, $admin]);

                // Depois insere na new_table
                if ($admin) {
                    $sqlInsertNew = "INSERT INTO new_table (NOME, EMAIL, TELEFONE, NASCIMENTO, ENDERECO) 
                                   VALUES (?, ?, ?, ?, ?)";
                    $stmtNew = $pdo->prepare($sqlInsertNew);
                    $stmtNew->execute([$nome, $email, $telefone, $nascimento, $endereco]);
                } else {
                    $sqlInsertNew = "INSERT INTO new_table (NOME, EMAIL, TELEFONE, NASCIMENTO, ENDERECO, tipo) 
                                   VALUES (?, ?, ?, ?, ?, ?)";
                    $stmtNew = $pdo->prepare($sqlInsertNew);
                    $stmtNew->execute([$nome, $email, $telefone, $nascimento, $endereco, $tipo]);
                }

                $pdo->commit();
                $_SESSION['msg_cadastro'] = "Cadastro realizado com sucesso! Faça login.";
                header("Location: login.php");
                exit();
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
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
            padding: 40px 60px;
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

        .tipo-container {
            margin: 20px 0;
        }

        .tipo-option {
            display: flex;
            align-items: baseline;
            justify-content: flex-start;
            margin-bottom: 10px;
        }

        .tipo-option input {
            width: auto;
            margin-right: 10px;
        }

        .checkbox-label {
            display: flex;
            align-items: baseline;
            justify-content: flex-start;
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

        .input-simples {
            margin-bottom: 15px;
        }

        .input-simples input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 16px;
        }

        .input-simples input:focus {
            border-color: var(--amarelo-principal);
            outline: none;
            box-shadow: 0 0 5px var(--amarelo-principal);
        }

        input[type="date"]::-webkit-calendar-picker-indicator {
            background: none;
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
            <div class="input-simples">
                <input type="text" class="campo-data" name='nascimento' placeholder="Data de Nascimento" 
                       onfocus="(this.type='date')" required>
            </div>

            <input type="text" name="endereco" placeholder="Endereço" required>
            <input type="password" name="senha" placeholder="Senha" required>
            
            <div class="tipo-container" id="tipoContainer">
                <h3>Tipo de participante:</h3>
                <label class="tipo-option">
                    <input type="radio" name="tipo" value="acolito" required> Acólito
                </label>
                <label class="tipo-option">
                    <input type="radio" name="tipo" value="coroinha" required> Coroinha
                </label>
            </div>

            <label class="checkbox-label">
                <input type="checkbox" name="admin">
                É administrador?
            </label>
            
            <button type="submit" name="submit">Cadastrar</button>
        </form>
        
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Mantém o placeholder visível quando perde o foco sem valor
                document.querySelectorAll('.campo-data').forEach(input => {
                    input.addEventListener('blur', function() {
                        if(!this.value) {
                            this.type = 'text';
                        }
                    });
                });

                const adminCheckbox = document.querySelector('input[name="admin"]');
                const tipoContainer = document.getElementById('tipoContainer');
                
                function toggleTipoContainer() {
                    if(adminCheckbox.checked) {
                        tipoContainer.style.display = 'none';
                        document.querySelectorAll('input[name="tipo"]').forEach(radio => {
                            radio.required = false;
                        });
                    } else {
                        tipoContainer.style.display = 'block';
                        document.querySelectorAll('input[name="tipo"]').forEach(radio => {
                            radio.required = true;
                        });
                    }
                }
                
                adminCheckbox.addEventListener('change', toggleTipoContainer);
                toggleTipoContainer();
            });
        </script>
    </div>
</body>
</html>