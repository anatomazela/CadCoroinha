<?php
include_once('config.php');
session_start();

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('Location: login.php');
    exit();
}

$mensagem = '';

if (isset($_POST['submit'])) {
    $data_missa = $_POST['data_missa'] ?? '';
    $horario = $_POST['horario'] ?? '';
    $descricao = $_POST['descricao'] ?? '';
    $funcoes = $_POST['funcoes'] ?? [];

    if (!$data_missa || !$horario || !$descricao || empty($funcoes)) {
        $mensagem = "Preencha todos os campos e adicione pelo menos uma função.";
    } else {
        try {
            $pdo->beginTransaction();

            $sqlEscala = "INSERT INTO escalas (data_missa, horario, descricao) VALUES (?, ?, ?)";
            $stmtEscala = $pdo->prepare($sqlEscala);
            $stmtEscala->execute([$data_missa, $horario, $descricao]);
            $id_escala = $pdo->lastInsertId();

            $sqlFuncao = "INSERT INTO funcoes (id_escala, funcao) VALUES (?, ?)";
            $stmtFuncao = $pdo->prepare($sqlFuncao);

            foreach ($funcoes as $funcao) {
                if (trim($funcao) != '') {
                    $stmtFuncao->execute([$id_escala, $funcao]);
                }
            }

            $pdo->commit();
            $mensagem = "Escala cadastrada com sucesso!";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $mensagem = "Erro ao cadastrar escala: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrador - Cadastrar Escala</title>
    <link rel="stylesheet" href="css/style.css">
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
    align-items: center;
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

.admin-box {
    background-color: var(--fundo-claro);
    padding: 40px;
    border-radius: 15px;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
    color: var(--texto-escuro);
    width: 95%;
    max-width: 500px;
    margin: 20px 0;
}

.admin-box h1 {
    text-align: center;
    margin-bottom: 30px;
    color: var(--texto-escuro);
}

.admin-box input {
    width: 95%;
    padding: 12px;
    margin-bottom: 15px;
    border: 1px solid #ccc;
    border-radius: 8px;
    font-size: 16px;
    transition: border 0.2s ease;
}

.admin-box input:focus {
    border-color: var(--amarelo-principal);
    outline: none;
    box-shadow: 0 0 5px var(--amarelo-principal);
}

.admin-box button {
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

.admin-box button:hover {
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

.mensagem-sucesso {
    color: #28a745;
    font-weight: bold;
    text-align: center;
    margin-bottom: 20px;
    padding: 10px;
    background-color: #d4edda;
    border-radius: 5px;
    border: 1px solid #c3e6cb;
}

@media (max-width: 768px) {
    .admin-box {
        padding: 35px;
    }
}

@media (max-width: 480px) {
    body {
        padding: 70px 15px 15px;
    }

    .admin-box {
        padding: 30px 20px;
        border-radius: 10px;
    }

    .admin-box h1 {
        font-size: 24px;
        margin-bottom: 20px;
    }

    .admin-box input {
        padding: 10px;
        font-size: 15px;
    }
}

.input-date-container,
.input-time-container {
    position: relative;
}

.date-placeholder,
.time-placeholder {
    position: absolute;
    left: 12px;
    top: 12px;
    color: #999;
    pointer-events: none;
    padding: 0 5px;
}

input:focus {
    border-color: var(--amarelo-principal);
    outline: none;
    box-shadow: 0 0 5px var(--amarelo-principal);
}

input[type=\"date\"]:valid + .date-placeholder,
input[type=\"time\"]:valid + .time-placeholder {
    display: none;
}
.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 2000;
}

.modal-content {
    background-color: var(--fundo-claro);
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
    text-align: center;
    max-width: 400px;
    width: 90%;
}

.modal h3 {
    color: var(--marrom);
    margin-bottom: 20px;
}

.modal-buttons {
    display: flex;
    justify-content: center;
    gap: 15px;
}

.modal-buttons button {
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: bold;
    transition: background-color 0.3s ease;
}

.modal-buttons button:first-child {
    background-color: var(--amarelo-principal);
    color: white;
}

.modal-buttons button:first-child:hover {
    background-color: var(--amarelo-hover);
}

.modal-buttons button:last-child {
    background-color: #f0f0f0;
    color: var(--marrom);
}

.modal-buttons button:last-child:hover {
    background-color: #e0e0e0;
}
.input-container {
    position: relative;
    margin-bottom: 15px;
}

input[type="date"]::before,
input[type="time"]::before {
    content: attr(placeholder);
    position: absolute;
    color: #999;
    left: 12px;
    top: 12px;
    pointer-events: none;
}

input[type="date"]:focus::before,
input[type="time"]:focus::before,
input[type="date"]:valid::before,
input[type="time"]:valid::before {
    display: none;
}

input[type="date"],
input[type="time"] {
    width: 94%;
    padding: 12px;
    border: 1px solid #ccc;
    border-radius: 8px;
    font-size: 16px;
}

input[type="date"]:focus,
input[type="time"]:focus {
    border-color: var(--amarelo-principal);
    outline: none;
    box-shadow: 0 0 5px var(--amarelo-principal);
}
.input-simples {
    margin-bottom: 15px;
}

.input-simples input {
    width: 94%;
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
</style>
</head>
<body>
    <header>
        <div class="header-content">
            <a href="index.html" class="header-link">Início</a>
            <a href="cadastro.php" class="header-link">Cadastrar Usuários</a>

    </header>
<div class="admin-box">
        <h1>Cadastrar Escala</h1>
        
        <?php if (!empty($mensagem)): ?>
            <p class="<?= strpos($mensagem, 'sucesso') !== false ? 'mensagem-sucesso' : 'mensagem-erro' ?>">
                <?= htmlspecialchars($mensagem) ?>
            </p>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="input-simples">
                <input type="date" name="data_missa" placeholder="Data da Missa" required>
            </div>

            <div class="input-simples">
                <input type="time" name="horario" placeholder="Horário" required>
            </div>
            
            <input type="text" name="descricao" placeholder="Descrição da Missa" required>

            <div style="margin: 20px 0;">
                <h3 style="margin-bottom: 15px; color: #2f4f4f;">Funções:</h3>
                <input type="text" name="funcoes[]" value="Acólito" required>
                <input type="text" name="funcoes[]" value="Acólito">
                <input type="text" name="funcoes[]" value="Acólito I">
                <input type="text" name="funcoes[]" value="Coroinha">
                <input type="text" name="funcoes[]" value="Coroinha">
                <input type="text" name="funcoes[]" value="Coroinha">
                <input type="text" name="funcoes[]" value="Coroinha I">
                <input type="text" name="funcoes[]" value="Coroinha I">
                <input type="text" name="funcoes[]" value="Coroinha I">
                <input type="text" name="funcoes[]" value="Não Participarei">
            </div>

            <button type="submit" name="submit">Cadastrar Escala</button>
        </form>
    </div>
<div id="logoutModal" class="modal" style="display: none;">
    <div class="modal-content">
        <h3>Tem certeza que deseja sair?</h3>
        <div class="modal-buttons">
            <button onclick="confirmLogout()">Sim, sair</button>
            <button onclick="closeModal()">Cancelar</button>
        </div>
    </div>
</div>
<script>
    function openLogoutModal(event) {
        event.preventDefault(); 
        document.getElementById('logoutModal').style.display = 'flex';
    }
    function confirmLogout() {
        window.location.href = 'logout.php';
    }
    function closeModal() {
        document.getElementById('logoutModal').style.display = 'none';
    }
    
</script>
<script>
document.getElementById('data_missa').addEventListener('click', function() {
    this.showPicker();
});

document.querySelectorAll('input[type="date"], input[type="time"]').forEach(input => {
    input.addEventListener('change', function() {
        if(this.value) {
            this.setAttribute('data-filled', 'true');
        } else {
            this.removeAttribute('data-filled');
        }
    });
    
    if(input.value) {
        input.setAttribute('data-filled', 'true');
    }
});
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.campo-data, .campo-horario').forEach(input => {
        input.addEventListener('blur', function() {
            if(!this.value) {
                this.type = 'text';
            }
        });
    });
});
</script>   
</body>
</html>