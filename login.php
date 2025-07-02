<?php
session_start();
include_once('config.php');

$mensagem = '';

if (isset($_POST['submit'])) {
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';

    if ($email && $senha) {
        $sql = "SELECT * FROM users WHERE email = :email";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($senha, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['is_admin'] = $user['is_admin'];

            if ($user['is_admin']) {
                header('Location: admin.php');
            } else {
                header('Location: user.php');
            }
            exit();
        } else {
            $mensagem = "Email ou senha inválidos.";
        }
    } else {
        $mensagem = "Por favor, preencha todos os campos.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title> Login </title>
    <style>
        :root {
            --amarelo-principal: #D6A24C;
            --amarelo-hover: #A5753F;
            --fundo-gradiente: linear-gradient(to bottom, #FDF6EC, #FCE9D4);
            --fundo-claro: #FFF8F1;
            --marrom: #5C4B2D;
            --marrom-claro: #4f422f;
            --hover-link: #d1b25d;
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
            color: var(--marrom);
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

        header a {
            color: white;
            margin: 0 15px;
            text-decoration: none;
            font-weight: bold;
            font-size: 18px;
            transition: all 0.3s ease;
        }

        header a:hover {
            text-decoration: underline;
            opacity: 0.9;
        }

        .login-box {
            background-color: var(--fundo-claro);
            padding: 40px 35px;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 450px;
            margin-top: 20px;
            color: var(--marrom-claro);
        }

        .login-box h1 {
            text-align: center;
            margin-bottom: 30px;
        }

        input, label {
            display: block;
            width: 100%;
            margin-bottom: 15px;
            font-size: 16px;
        }

        input[type="email"],
        input[type="password"] {
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 8px;
            box-sizing: border-box;
            transition: border 0.2s ease;
            background-color: white;
        }

        input:focus {
            border-color: var(--amarelo-principal);
            outline: none;
            box-shadow: 0 0 5px var(--amarelo-principal);
        }

        button {
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

        button:hover {
            background-color: var(--amarelo-hover);
        }

        .mensagem-erro {
            color: red;
            font-weight: bold;
            text-align: center;
            margin-bottom: 15px;
        }

        @media (max-width: 768px) {
            .login-box {
                padding: 35px 25px;
            }
        }
    </style>
</head>
<body>
    <header>
        <a href="index.html">Início</a>
    </header>

    <div class="login-box">
        <h1>Login</h1>
        <?php if (!empty($mensagem)) echo "<p class='mensagem-erro'>$mensagem</p>"; ?>
        <form method="POST" action="">
            <input type="email" name="email" placeholder="Email" required />
            <input type="password" name="senha" placeholder="Senha" required />
            <button type="submit" name="submit">Entrar</button>
        </form>
    </div>
</body>
</html>
