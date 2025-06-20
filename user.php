<?php
include_once('config.php');
session_start();

$mensagem = '';

// Nome do usuário logado na sessão (ajuste conforme seu login)
$nome_participante_logado = $_SESSION['username'] ?? null;

if (!$nome_participante_logado) {
    $mensagem = "Você precisa estar logado para reservar.";
} else if (isset($_POST['reservar'])) {
    $id_funcao = $_POST['id_funcao'];

    // Buscar a escala da função
    $sqlEscala = "SELECT f.id_escala FROM funcoes f WHERE f.id = ?";
    $stmtEscala = $pdo->prepare($sqlEscala);
    $stmtEscala->execute([$id_funcao]);
    $id_escala = $stmtEscala->fetchColumn();

    if (!$id_escala) {
        $mensagem = "Ainda não há escalas cadastradas.";
    } else {
        // Verificar se o usuário já reservou alguma função nesta escala
        $sqlJaReservou = "
            SELECT COUNT(*) FROM participantes_funcoes pf
            JOIN funcoes f ON pf.id_funcao = f.id
            WHERE f.id_escala = ? AND pf.nome_participante = ?
        ";
        $stmtJaReservou = $pdo->prepare($sqlJaReservou);
        $stmtJaReservou->execute([$id_escala, $nome_participante_logado]);
        $jaReservouCount = $stmtJaReservou->fetchColumn();

        if ($jaReservouCount > 0) {
            $mensagem = "Você já reservou uma função nesta escala e não pode reservar outra.";
        } else {
            // Buscar o nome da função para tratamento especial "Não participarei"
            $sqlFunc = "SELECT funcao FROM funcoes WHERE id = ?";
            $stmtFunc = $pdo->prepare($sqlFunc);
            $stmtFunc->execute([$id_funcao]);
            $funcao = $stmtFunc->fetchColumn();

            if (!$funcao) {
                $mensagem = "Função inválida.";
            } else {
                if (mb_strtolower(trim($funcao), 'UTF-8') === 'não participarei') {
                    // Permite múltiplos participantes para "Não participarei"
                    $sqlInsert = "INSERT INTO participantes_funcoes (id_funcao, nome_participante) VALUES (?, ?)";
                    $stmtInsert = $pdo->prepare($sqlInsert);
                    $stmtInsert->execute([$id_funcao, $nome_participante_logado]);
                    $mensagem = "Função 'Não participarei' reservada com sucesso!";
                } else {
                    // Funções normais: verifica se já tem participante
                    $sqlCount = "SELECT COUNT(*) FROM participantes_funcoes WHERE id_funcao = ?";
                    $stmtCount = $pdo->prepare($sqlCount);
                    $stmtCount->execute([$id_funcao]);
                    $count = $stmtCount->fetchColumn();

                    if ($count > 0) {
                        $mensagem = "Essa função já foi reservada.";
                    } else {
                        $sqlInsert = "INSERT INTO participantes_funcoes (id_funcao, nome_participante) VALUES (?, ?)";
                        $stmtInsert = $pdo->prepare($sqlInsert);
                        $stmtInsert->execute([$id_funcao, $nome_participante_logado]);
                        $mensagem = "Função reservada com sucesso!";
                    }
                }
            }
        }
    }
}

// Buscar escalas e funções com participantes
$sql = "
SELECT 
    e.id AS escala_id, e.data_missa, e.horario, e.descricao,
    f.id AS funcao_id, f.funcao,
    pf.nome_participante, pf.id AS participante_id
FROM escalas e
JOIN funcoes f ON e.id = f.id_escala
LEFT JOIN participantes_funcoes pf ON f.id = pf.id_funcao
ORDER BY e.data_missa, e.horario, f.id, pf.id
";

$stmt = $pdo->query($sql);
$dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Organizar dados: escalas > funções > participantes
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Escala das Missas</title>
    <style>
        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            background: linear-gradient(to bottom, #A7D8DD, #E8FCFC);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
        padding: 80px 20px 20px; /* Espaço para o header e margens laterais */
        }

        /* HEADER IDÊNTICO AO LOGIN */
        header {
            background-color: #5da7d1;
            padding: 20px 0;
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

        /* CONTAINER PRINCIPAL - IDÊNTICO AO LOGIN */
        .tabela-box {
            background-color: #f0f9fa;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
            color: #2f4f4f;
            width: 90%;
            max-width: 800px;
            margin-bottom: 30px;
        }

        h1 {
            text-align: center;
            margin-bottom: 25px;
            color: #2f4f4f;
        }

        /* ESTILOS ORIGINAIS DA TABELA (MANTIDOS) */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        th, td {
            border: 1px solid #ccc;
            padding: 12px;
            text-align: center;
            vertical-align: middle;
        }

        th {
            background-color: #5da7d1;
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

        .funcao-box {
            min-width: 120px;
        }

        .mensagem {
            color: green;
            text-align: center;
            margin-bottom: 15px;
        }

        /* BOTÕES - IDÊNTICOS AO LOGIN */
        button {
            width: 100%;
            background-color: #5da7d1;
            color: white;
            border: none;
            padding: 12px;
            font-size: 16px;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-top: 5px;
        }

        button:hover {
            background-color: #3b85c3;
        }

        /* RESPONSIVIDADE PARA CELULAR */
        @media (max-width: 768px) {
            body {
                padding-top: 70px;
            }
            
            .tabela-box {
                padding: 20px;
                width: 95%;
            }
            
            table {
                font-size: 14px;
            }
            
            th, td {
                padding: 8px;
            }
            
            button {
                padding: 10px;
                font-size: 14px;
            }
            
            header {
                padding: 15px 0;
            }
            
            .header-link {
                font-size: 16px;
            }
        }

        /* ESTILO ORIGINAL DOS TÍTULOS DAS ESCALAS */
        h3 {
            margin: 20px 0 10px 0;
            color: #2f4f4f;
            text-align: center;
        }
    </style>
</head>

<header>
    <div class="header-content">
        <a href="index.html" class="header-link">Início</a>
    </div>
</header>


<body>
    <div class="tabela-box">
        <h1>Escala das Missas</h1>
        <?php if (!empty($mensagem)): ?>
            <p class="mensagem"><?= htmlspecialchars($mensagem) ?></p>
        <?php endif; ?>

        <?php foreach ($escalas as $escala): ?>
            <h3><?= date('d/m/Y', strtotime($escala['data_missa'])) ?> - <?= date('H:i', strtotime($escala['horario'])) ?> | <?= htmlspecialchars($escala['descricao']) ?></h3>

            <?php
            // Verificar se usuário já reservou alguma função nesta escala
            $usuarioJaReservouEscala = false;
            foreach ($escala['funcoes'] as $f) {
                if (in_array($nome_participante_logado, $f['participantes'])) {
                    $usuarioJaReservouEscala = true;
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
                            $nomeFuncaoMinusculo = mb_strtolower(trim($funcao['funcao']), 'UTF-8');
                            $funcao_normal = $nomeFuncaoMinusculo !== 'não participarei';
                            $ja_reservada = $funcao_normal && count($funcao['participantes']) > 0;

                            // Se o usuário já reservou alguma função na escala:
                            // - não mostra o botão para outras funções normais
                            // - não mostra a função "Não participarei"
                            if ($usuarioJaReservouEscala) {
                                if ($nomeFuncaoMinusculo === 'não participarei') {
                                    $mostrarBotao = false;
                                } else {
                                    $mostrarBotao = false;
                                }
                            } else {
                                // Usuário não reservou nada ainda
                                // Pode reservar função normal que não esteja ocupada,
                                // e "Não participarei" aparece normalmente
                                $mostrarBotao = (!$ja_reservada) || !$funcao_normal;
                            }
                        ?>
                        <tr>
                            <td class="funcao-box"><?= htmlspecialchars($funcao['funcao']) ?></td>
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
                                    <?php if ($usuarioJaReservouEscala): ?>
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
