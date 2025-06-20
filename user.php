<?php
include_once('config.php');
session_start();

$mensagem = '';
$nome_participante_logado = $_SESSION['username'] ?? null;

if (!$nome_participante_logado) {
    $mensagem = "Você precisa estar logado para reservar.";
} else if (isset($_POST['reservar'])) {
    $id_funcao = $_POST['id_funcao'] ?? '';

    if (!$id_funcao) {
        $mensagem = "Função inválida.";
    } else {
        try {
            // Busca o id_escala da função
            $sqlEscala = "SELECT id_escala FROM funcoes WHERE id = ?";
            $stmtEscala = $pdo->prepare($sqlEscala);
            $stmtEscala->execute([$id_funcao]);
            $id_escala = $stmtEscala->fetchColumn();

            if (!$id_escala) {
                $mensagem = "Ainda não há escalas cadastradas.";
            } else {
                // Verifica se o participante já reservou nessa escala
                $sqlVerifica = "
                    SELECT COUNT(*) FROM participantes_funcoes pf
                    JOIN funcoes f ON pf.id_funcao = f.id
                    WHERE f.id_escala = ? AND pf.nome_participante = ?
                ";
                $stmtVerifica = $pdo->prepare($sqlVerifica);
                $stmtVerifica->execute([$id_escala, $nome_participante_logado]);
                $jaReservou = $stmtVerifica->fetchColumn();

                if ($jaReservou > 0) {
                    $mensagem = "Você já reservou uma função nesta escala e não pode reservar outra.";
                } else {
                    // Busca o nome da função
                    $sqlFuncao = "SELECT funcao FROM funcoes WHERE id = ?";
                    $stmtFuncao = $pdo->prepare($sqlFuncao);
                    $stmtFuncao->execute([$id_funcao]);
                    $funcao = $stmtFuncao->fetchColumn();

                    if (!$funcao) {
                        $mensagem = "Função inválida.";
                    } else {
                        $funcaoLower = mb_strtolower(trim($funcao), 'UTF-8');

                        if ($funcaoLower === 'não participarei') {
                            // Insere diretamente a reserva
                            $sqlInsere = "INSERT INTO participantes_funcoes (id_funcao, nome_participante) VALUES (?, ?)";
                            $stmtInsere = $pdo->prepare($sqlInsere);
                            $stmtInsere->execute([$id_funcao, $nome_participante_logado]);
                            $mensagem = "Função 'Não participarei' reservada com sucesso!";
                        } else {
                            // Verifica se a função já foi reservada
                            $sqlConta = "SELECT COUNT(*) FROM participantes_funcoes WHERE id_funcao = ?";
                            $stmtConta = $pdo->prepare($sqlConta);
                            $stmtConta->execute([$id_funcao]);
                            $contagem = $stmtConta->fetchColumn();

                            if ($contagem > 0) {
                                $mensagem = "Essa função já foi reservada.";
                            } else {
                                $sqlInsere = "INSERT INTO participantes_funcoes (id_funcao, nome_participante) VALUES (?, ?)";
                                $stmtInsere = $pdo->prepare($sqlInsere);
                                $stmtInsere->execute([$id_funcao, $nome_participante_logado]);
                                $mensagem = "Função reservada com sucesso!";
                            }
                        }
                    }
                }
            }
        } catch (PDOException $e) {
            $mensagem = "Erro ao reservar função: " . $e->getMessage();
        }
    }
}

// Busca os dados para exibir
$sql = "
SELECT 
    e.id AS escala_id, e.data_missa, e.horario, e.descricao,
    f.id AS funcao_id, f.funcao,
    pf.nome_participante
FROM escalas e
JOIN funcoes f ON e.id = f.id_escala
LEFT JOIN participantes_funcoes pf ON f.id = pf.id_funcao
ORDER BY e.data_missa, e.horario, f.id, pf.nome_participante
";

$stmt = $pdo->query($sql);
$dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

$escalas = [];
foreach ($dados as $linha) {
    $idEscala = $linha['escala_id'];
    $idFuncao = $linha['funcao_id'];

    if (!isset($escalas[$idEscala])) {
        $escalas[$idEscala] = [
            'data_missa' => $linha['data_missa'],
            'horario' => $linha['horario'],
            'descricao' => $linha['descricao'],
            'funcoes' => []
        ];
    }

    if (!isset($escalas[$idEscala]['funcoes'][$idFuncao])) {
        $escalas[$idEscala]['funcoes'][$idFuncao] = [
            'funcao' => $linha['funcao'],
            'participantes' => []
        ];
    }

    if ($linha['nome_participante']) {
        $escalas[$idEscala]['funcoes'][$idFuncao]['participantes'][] = $linha['nome_participante'];
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Escala das Missas</title>
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
            align-items: flex-start;
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
        .tabela-box {
            background-color: var(--fundo-claro);
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            color: var(--texto-escuro);
            width: 100%;
            max-width: 450px;
            margin-top: 20px;
        }
        h1 {
            text-align: center;
            margin-bottom: 30px;
            color: var(--texto-escuro);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 12px;
            text-align: center;
            vertical-align: middle;
        }
        th {
            background-color: var(--amarelo-principal);
            color: white;
        }
        ul.participantes {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        ul.participantes li {
            margin: 5px 0;
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
            margin-top: 5px;
        }
        button:hover {
            background-color: var(--amarelo-hover);
        }
        .mensagem {
            color: #d9534f;
            font-weight: bold;
            text-align: center;
            margin-bottom: 20px;
            padding: 10px;
            background-color: #f8d7da;
            border-radius: 5px;
            border: 1px solid #f5c6cb;
        }
        .mensagem.sucesso {
            color: #3c763d;
            background-color: #dff0d8;
            border-color: #d6e9c6;
        }
        @media (max-width: 480px) {
            body {
                padding: 70px 15px 15px;
            }
            .tabela-box {
                padding: 30px 20px;
                border-radius: 10px;
                max-width: 100%;
            }
            h1 {
                font-size: 24px;
                margin-bottom: 20px;
            }
            th, td {
                padding: 10px;
                font-size: 14px;
            }
            button {
                padding: 12px;
                font-size: 14px;
            }
            header {
                padding: 15px 0;
            }
            .header-link {
                font-size: 16px;
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

<div class="tabela-box">
    <h1>Escala das Missas</h1>

    <?php if (!empty($mensagem)): ?>
        <?php 
            $classe = (strpos($mensagem, 'sucesso') !== false) ? 'mensagem sucesso' : 'mensagem';
        ?>
        <p class="<?= $classe ?>"><?= htmlspecialchars($mensagem) ?></p>
    <?php endif; ?>

    <?php foreach ($escalas as $escala): ?>
        <h3><?= date('d/m/Y', strtotime($escala['data_missa'])) ?> - <?= date('H:i', strtotime($escala['horario'])) ?> | <?= htmlspecialchars($escala['descricao']) ?></h3>

        <?php
        $usuarioJaReservou = false;
        foreach ($escala['funcoes'] as $f) {
            if (in_array($nome_participante_logado, $f['participantes'])) {
                $usuarioJaReservou = true;
                break;
            }
        }
        ?>

        <table>
            <thead>
                <tr>
                    <th>Função</th>
                    <th>Participantes</th>
                    <th>Reservar</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($escala['funcoes'] as $funcao_id => $funcao): ?>
                    <?php
                    $funcaoLower = mb_strtolower(trim($funcao['funcao']), 'UTF-8');
                    $funcaoNormal = $funcaoLower !== 'não participarei';
                    $jaReservada = $funcaoNormal && count($funcao['participantes']) > 0;

                    $mostrarBotao = !$usuarioJaReservou && (!$jaReservada || !$funcaoNormal);
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($funcao['funcao']) ?></td>
                        <td>
                            <?php if (!empty($funcao['participantes'])): ?>
                                <ul class="participantes">
                                    <?php foreach ($funcao['participantes'] as $participante): ?>
                                        <li><?= htmlspecialchars($participante) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <em>Sem participantes</em>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($mostrarBotao): ?>
                                <form method="POST" action="">
                                    <input type="hidden" name="id_funcao" value="<?= $funcao_id ?>">
                                    <button type="submit" name="reservar">Reservar</button>
                                </form>
                            <?php else: ?>
                                <?php if ($usuarioJaReservou): ?>
                                    <em>Você já reservou uma função nesta escala</em>
                                <?php else: ?>
                                    <em>Essa função já foi reservada.</em>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endforeach; ?>
</div>

</body>
</html>
