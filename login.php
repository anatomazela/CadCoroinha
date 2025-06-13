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
    <title>Login</title>
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
        .login-box {
            background-color: #f0f9fa;
            padding: 40px 35px;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
            color: #2f4f4f;
            width: 100%;
            max-width: 450px;
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
