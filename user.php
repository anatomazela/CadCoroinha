<?php
include_once('config.php');
session_start();

$mensagem = '';
$nome_participante_logado = $_SESSION['username'] ?? null;

if (!$nome_participante_logado) {
    $mensagem = "Você precisa estar logado para reservar.";
    header('Location: login.php');
    exit();
}

if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1) {
    $tipo_usuario = 'admin';
} else {
    $sqlTipo = "SELECT tipo FROM new_table WHERE NOME = ?";
    $stmtTipo = $pdo->prepare($sqlTipo);
    $stmtTipo->execute([$nome_participante_logado]);
    $tipo_usuario = $stmtTipo->fetchColumn();
}


if (isset($_POST['excluir_escala']) && $tipo_usuario === 'admin') {
    $id_escala_excluir = $_POST['id_escala'] ?? '';
    if ($id_escala_excluir) {
        try {
            $pdo->beginTransaction();
            
            $sqlFuncoes = "SELECT id FROM funcoes WHERE id_escala = ?";
            $stmtFuncoes = $pdo->prepare($sqlFuncoes);
            $stmtFuncoes->execute([$id_escala_excluir]);
            $funcoes = $stmtFuncoes->fetchAll(PDO::FETCH_COLUMN);
            if ($funcoes) {
                $in = str_repeat('?,', count($funcoes) - 1) . '?';
                $pdo->prepare("DELETE FROM participantes_funcoes WHERE id_funcao IN ($in)")->execute($funcoes);
            }
            
            $pdo->prepare("DELETE FROM funcoes WHERE id_escala = ?")->execute([$id_escala_excluir]);
            
            $pdo->prepare("DELETE FROM escalas WHERE id = ?")->execute([$id_escala_excluir]);
            $pdo->commit();
            $mensagem = "Escala excluída com sucesso!";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $mensagem = "Erro ao excluir escala: " . $e->getMessage();
        }
        
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_escala']) && $tipo_usuario === 'admin') {
    $escalaId = (int) ($_POST['editar_escala_id'] ?? 0);
    $novaData = $_POST['editar_data'] ?? '';
    $novoHorario = $_POST['editar_horario'] ?? '';
    $novaDescricao = $_POST['editar_descricao'] ?? '';
    $funcoesPost = $_POST['funcoes'] ?? [];
    $funcaoIdsPost = $_POST['funcao_ids'] ?? [];
    $funcoesSanitizadas = array_values(array_map('trim', (array)$funcoesPost));
    
    $funcaoIdsNormalized = array_values((array)$funcaoIdsPost);
    if ($escalaId <= 0 || !$novaData || !$novoHorario) {
        $mensagem = 'Dados inválidos para edição.';
    } else {
        try {
            $pdo->beginTransaction();
           
            $pdo->prepare('UPDATE escalas SET data_missa = ?, horario = ?, descricao = ? WHERE id = ?')
                ->execute([$novaData, $novoHorario, $novaDescricao, $escalaId]);

            
            $stmtF = $pdo->prepare('SELECT id, funcao FROM funcoes WHERE id_escala = ?');
            $stmtF->execute([$escalaId]);
            $fAtual = [];
            while ($r = $stmtF->fetch(PDO::FETCH_ASSOC)) {
                $fAtual[$r['id']] = $r['funcao'];
            }

            $seenIds = [];
            $insertStmt = $pdo->prepare('INSERT INTO funcoes (id_escala, funcao) VALUES (?, ?)');
            $updateStmt = $pdo->prepare('UPDATE funcoes SET funcao = ? WHERE id = ?');

            for ($i = 0; $i < count($funcoesSanitizadas); $i++) {
                $name = trim($funcoesSanitizadas[$i]);
                if ($name === '') continue;
                $fid = isset($funcaoIdsNormalized[$i]) ? trim($funcaoIdsNormalized[$i]) : '';
                if ($fid !== '') {
                    $fid = (int)$fid;
                    if (isset($fAtual[$fid])) {
                        
                        $updateStmt->execute([$name, $fid]);
                        $seenIds[] = $fid;
                    } else {
                        
                        $insertStmt->execute([$escalaId, $name]);
                        $seenIds[] = $pdo->lastInsertId();
                    }
                } else {
                    
                    $insertStmt->execute([$escalaId, $name]);
                    $seenIds[] = $pdo->lastInsertId();
                }
            }

            
            $toDelete = array_diff(array_keys($fAtual), $seenIds);
            if (!empty($toDelete)) {
                
                $checkStmt = $pdo->prepare('SELECT COUNT(*) FROM participantes_funcoes WHERE id_funcao = ?');
                $delStmt = $pdo->prepare('DELETE FROM funcoes WHERE id = ?');
                foreach ($toDelete as $delId) {
                    $checkStmt->execute([$delId]);
                    $cnt = $checkStmt->fetchColumn();
                    if ($cnt > 0) {
                   
                        $pdo->rollBack();
                        $mensagem = 'Não é possível remover a função "' . addslashes($fAtual[$delId]) . '" porque existem participantes cadastrados. Remova-os ou troque-os antes.';
                        throw new Exception($mensagem);
                    }
                    $delStmt->execute([$delId]);
                }
            }

            $pdo->commit();
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit();
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            if (empty($mensagem)) $mensagem = 'Erro ao editar escala: ' . $e->getMessage();
        }
    }
}

if (isset($_POST['reservar'])) {
    if ($tipo_usuario === 'admin') {
        $mensagem = "Coordenação não pode reservar funções.";
    } else {
        $id_funcao = $_POST['id_funcao'] ?? '';

        if (!$id_funcao) {
            $mensagem = "Função inválida.";
        } else {
            try {
                $pdo->beginTransaction();

                $sqlEscala = "SELECT id_escala FROM funcoes WHERE id = ?";
                $stmtEscala = $pdo->prepare($sqlEscala);
                $stmtEscala->execute([$id_funcao]);
                $id_escala = $stmtEscala->fetchColumn();

                if (!$id_escala) {
                    $mensagem = "Ainda não há escalas cadastradas.";
                    $pdo->rollBack();
                } else {
                    $sqlVerifica = "SELECT COUNT(*) FROM participantes_funcoes pf
                                  JOIN funcoes f ON pf.id_funcao = f.id
                                  WHERE f.id_escala = ? AND pf.nome_participante = ?";
                    $stmtVerifica = $pdo->prepare($sqlVerifica);
                    $stmtVerifica->execute([$id_escala, $nome_participante_logado]);
                    $jaReservou = $stmtVerifica->fetchColumn();

                    if ($jaReservou > 0) {
                        $mensagem = "Você já reservou uma função nesta escala.";
                        $pdo->rollBack();
                    } else {
                        $sqlFuncao = "SELECT funcao FROM funcoes WHERE id = ?";
                        $stmtFuncao = $pdo->prepare($sqlFuncao);
                        $stmtFuncao->execute([$id_funcao]);
                        $funcao = $stmtFuncao->fetchColumn();

                        if (!$funcao) {
                            $mensagem = "Função inválida.";
                            $pdo->rollBack();
                        } else {
                            $funcaoLower = mb_strtolower(trim($funcao), 'UTF-8');

                            if ($funcaoLower === 'não participarei') {
                                $sqlInsere = "INSERT INTO participantes_funcoes (id_funcao, nome_participante) VALUES (?, ?)";
                                $stmtInsere = $pdo->prepare($sqlInsere);
                                $stmtInsere->execute([$id_funcao, $nome_participante_logado]);
                                
                                $pdo->commit();
                                $mensagem = "Função 'Não participarei' reservada com sucesso!";
                            } else {
                                $funcaoCompativel = false;
                                if ($tipo_usuario === 'acolito' && (strpos($funcaoLower, 'acólito') !== false || strpos($funcaoLower, 'acolito') !== false)) {
                                    $funcaoCompativel = true;
                                } elseif ($tipo_usuario === 'coroinha' && strpos($funcaoLower, 'coroinha') !== false) {
                                    $funcaoCompativel = true;
                                }

                                if (!$funcaoCompativel) {
                                    $mensagem = "Esta função não está disponível para o seu tipo de participante.";
                                    $pdo->rollBack();
                                } else {
                                    $sqlConta = "SELECT COUNT(*) FROM participantes_funcoes WHERE id_funcao = ?";
                                    $stmtConta = $pdo->prepare($sqlConta);
                                    $stmtConta->execute([$id_funcao]);
                                    $contagem = $stmtConta->fetchColumn();

                                    if ($contagem > 0) {
                                        $mensagem = "Essa função já foi reservada.";
                                        $pdo->rollBack();
                                    } else {
                                        $sqlInsere = "INSERT INTO participantes_funcoes (id_funcao, nome_participante) VALUES (?, ?)";
                                        $stmtInsere = $pdo->prepare($sqlInsere);
                                        $stmtInsere->execute([$id_funcao, $nome_participante_logado]);
                                        
                                        $pdo->commit();
                                        $mensagem = "Função reservada com sucesso!";
                                    }
                                }
                            }
                        }
                    }
                }
            } catch (PDOException $e) {
                $pdo->rollBack();
                $mensagem = "Erro ao reservar função: " . $e->getMessage();
            }
        }
    }
}

$sql = "SELECT e.id AS escala_id, e.data_missa, e.horario, e.descricao,
           f.id AS funcao_id, f.funcao, pf.id AS participante_id, pf.nome_participante
    FROM escalas e
    JOIN funcoes f ON e.id = f.id_escala
    LEFT JOIN participantes_funcoes pf ON f.id = pf.id_funcao
    ORDER BY e.data_missa, e.horario, f.id, pf.nome_participante";

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
        $escalas[$idEscala]['funcoes'][$idFuncao]['participantes'][] = [
            'id' => $linha['participante_id'],
            'nome' => $linha['nome_participante']
        ];
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
            max-width: 800px;
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
            padding: 10px;
            font-size: 14px;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-top: 5px;
        }
        button:hover {
            background-color: var(--amarelo-hover);
        }
        button:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
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
        @media (max-width: 768px) {
            .tabela-box {
                padding: 20px;
                max-width: 95%;
            }
            th, td {
                padding: 8px;
                font-size: 14px;
            }
            h1 {
                font-size: 24px;
            }
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
            color: var(--texto-escuro);
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
            color: var(--texto-escuro);
        }
        .modal-buttons button:last-child:hover {
            background-color: #e0e0e0;
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

    <?php if (empty($escalas)): ?>
        <p class="mensagem">Nenhuma escala cadastrada ainda.</p>
    <?php else: ?>
        <?php foreach ($escalas as $escala_id => $escala): ?>
            <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;">
                <h3 style="margin-bottom:0;">
                    <?= date('d/m/Y', strtotime($escala['data_missa'])) ?> - <?= date('H:i', strtotime($escala['horario'])) ?> | <?= htmlspecialchars($escala['descricao']) ?>
                </h3>
                <?php if ($tipo_usuario === 'admin'): ?>
                        <div style="display:flex;gap:8px;">
                            <button type="button" class="admin-edit-btn" data-escala-id="<?= $escala_id ?>" style="background:#0275d8;color:white;border:none;padding:6px 10px;border-radius:6px;cursor:pointer;">Editar</button>
                            <form method="POST" action="" onsubmit="return confirm('Tem certeza que deseja excluir esta escala?');" style="margin:0;">
                                <input type="hidden" name="id_escala" value="<?= $escala_id ?>">
                                <button type="submit" name="excluir_escala" style="background:#d9534f; color:white; border:none; padding:6px 14px; border-radius:6px; font-size:14px; cursor:pointer;">Excluir</button>
                            </form>
                        </div>
                <?php endif; ?>
            </div>

            <?php
            
            $usuarioJaReservou = false;
            foreach ($escala['funcoes'] as $f) {
                foreach ($f['participantes'] as $p) {
                    if (isset($p['nome']) && $p['nome'] === $nome_participante_logado) {
                        $usuarioJaReservou = true;
                        break 2;
                    }
                }
            }
            ?>

            <table>
                <thead>
                    <tr>
                        <th>Função</th>

        

                        <th>Participantes</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($escala['funcoes'] as $funcao_id => $funcao): ?>
                        <?php
                        $funcaoLower = mb_strtolower(trim($funcao['funcao']), 'UTF-8');
                        $funcaoNormal = $funcaoLower !== 'não participarei';
                        $jaReservada = $funcaoNormal && count($funcao['participantes']) > 0;
                        
                        $funcaoCompativel = false;
                        if ($tipo_usuario === 'admin') {
                            $funcaoCompativel = false; 
                        } elseif ($tipo_usuario === 'acolito' && (strpos($funcaoLower, 'acólito') !== false || strpos($funcaoLower, 'acolito') !== false)) {
                            $funcaoCompativel = true;
                        } elseif ($tipo_usuario === 'coroinha' && strpos($funcaoLower, 'coroinha') !== false) {
                            $funcaoCompativel = true;
                        } elseif ($funcaoLower === 'não participarei') {
                            $funcaoCompativel = true;
                        }

                        $mostrarBotao = !$usuarioJaReservou && 
                                       (!$jaReservada || !$funcaoNormal) && 
                                       $funcaoCompativel &&
                                       $tipo_usuario !== 'admin'; 
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($funcao['funcao']) ?></td>
                            <td>
                                <?php if (!empty($funcao['participantes'])): ?>
                                    <ul class="participantes">
                                        <?php foreach ($funcao['participantes'] as $participante): ?>
                                            <li>
                                                <?= htmlspecialchars($participante['nome']) ?>
                                                <?php if ($tipo_usuario === 'admin'): ?>
                                                    <button type="button" class="transfer-btn" data-participante-id="<?= $participante['id'] ?>" data-participante-nome="<?= htmlspecialchars($participante['nome'], ENT_QUOTES) ?>" style="margin-left:8px;padding:4px 8px;border-radius:6px;background:#5cb85c;color:#fff;border:none;cursor:pointer;">Transferir</button>
                                                <?php endif; ?>
                                            </li>
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
                                    <?php if ($tipo_usuario === 'admin'): ?>
                                        <em>Coordenação não reserva funções</em>
                                    <?php elseif ($usuarioJaReservou): ?>
                                        <em>Você já reservou nesta escala</em>
                                    <?php elseif (!$funcaoCompativel): ?>
                                        <em>Indisponível para seu tipo</em>
                                    <?php else: ?>
                                        <em>Já reservado</em>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endforeach; ?>
    <?php endif; ?>
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

<div id="modal-editar-escala" class="modal" style="display:none;">
    <div class="modal-content">
        <h3>Editar Escala</h3>
        <form id="form-editar-escala" method="POST" action="">
            <input type="hidden" name="editar_escala_id" id="editar_escala_id">
            <div style="display:flex;gap:8px;flex-wrap:wrap;justify-content:center;">
                <label>Data:<br><input type="date" name="editar_data" id="editar_data" required></label>
                <label>Horário:<br><input type="time" name="editar_horario" id="editar_horario" required></label>
                <label>Descrição:<br><input type="text" name="editar_descricao" id="editar_descricao" style="min-width:220px;" required></label>
            </div>
            <hr>
            <div id="funcoes-list" style="margin-top:10px;">
                <label style="display:block;margin-bottom:6px;">Funções (uma por linha):</label>
                <div id="funcoes-container"></div>
                <button type="button" id="add-funcao" style="margin-top:8px;padding:6px 10px;border-radius:6px;background:#5cb85c;color:white;border:none;cursor:pointer;">Adicionar função</button>
            </div>
            <div style="margin-top:12px;display:flex;gap:8px;justify-content:center;">
                <button type="submit" name="editar_escala" style="background:#0275d8;color:white;padding:8px 16px;border-radius:8px;border:none;cursor:pointer;">Salvar</button>
                <button type="button" onclick="closeEditarEscala()" style="background:#f0f0f0;color:#333;padding:8px 16px;border-radius:8px;border:none;cursor:pointer;">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<div id="modal-transferir" class="modal" style="display:none;">
    <div class="modal-content">
        <h3>Transferir Participante</h3>
        <form id="form-transferir" method="POST" action="">
            <input type="hidden" name="transfer_participante_id" id="transfer_participante_id">
            <div style="margin-bottom:8px;text-align:left;">Participante: <strong id="transfer_participante_nome"></strong></div>
            <div style="margin-bottom:8px;text-align:left;">
                <label>Escolha a nova escala e função:</label>
                <select id="transfer_escala_select" name="transfer_escala_select" style="width:100%;padding:8px;margin-top:6px;border-radius:6px;" required></select>
            </div>
            <div style="display:flex;gap:8px;justify-content:center;">
                <button type="submit" name="transferir_participante" style="background:#5cb85c;color:white;padding:8px 14px;border-radius:6px;border:none;cursor:pointer;">Transferir</button>
                <button type="button" onclick="closeTransferModal()" style="background:#f0f0f0;padding:8px 14px;border-radius:6px;border:none;cursor:pointer;">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<script>
<?php

$tiposMap = [];
try {
    $stmtTipos = $pdo->query("SELECT NOME, tipo FROM new_table");
    $allTipos = $stmtTipos->fetchAll(PDO::FETCH_ASSOC);
    foreach ($allTipos as $t) {
        $tiposMap[$t['NOME']] = $t['tipo'];
    }
} catch (Exception $e) {
    
}

foreach ($escalas as $esId => $es) {
    foreach ($es['funcoes'] as $fId => $f) {
        if (!empty($escalas[$esId]['funcoes'][$fId]['participantes'])) {
            foreach ($escalas[$esId]['funcoes'][$fId]['participantes'] as $pi => $p) {
                $nomep = $p['nome'];
                $escalas[$esId]['funcoes'][$fId]['participantes'][$pi]['tipo'] = $tiposMap[$nomep] ?? null;
            }
        }
    }
}
?>
const escalasData = <?= json_encode($escalas, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;
const participantesTipos = <?= json_encode($tiposMap, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;

const participantIdToTipo = {};
for (const [escId, esc] of Object.entries(escalasData)) {
    for (const [funcId, func] of Object.entries(esc.funcoes)) {
        for (const p of (func.participantes || [])) {
            if (p && p.id) participantIdToTipo[String(p.id)] = p.tipo || participantesTipos[p.nome] || null;
        }
    }
}

const optionsByTipo = { 'acolito': [], 'coroinha': [], 'any': [] };
for (const [escId, esc] of Object.entries(escalasData)) {
    for (const [funcId, func] of Object.entries(esc.funcoes)) {
        const label = `${esc.data_missa} ${esc.horario} — ${func.funcao}`;
        
        const value = `${escId}|${funcId}`;
        const funcLower = (func.funcao || '').toLowerCase();
        const isNao = funcLower.indexOf('não participarei') !== -1 || funcLower.indexOf('nao participarei') !== -1;
        
        if (isNao) optionsByTipo.any.push({ value, label });
       
        if (funcLower.indexOf('acólito') !== -1 || funcLower.indexOf('acolito') !== -1) optionsByTipo.acolito.push({ value, label });
       
        if (funcLower.indexOf('coroinha') !== -1) optionsByTipo.coroinha.push({ value, label });
       
        if (!isNao && optionsByTipo.any.indexOf(value) === -1) {
            
        }
    }
}
function openEditarEscalaModal(escalaId) {
    const data = escalasData[escalaId];
    if (!data) return alert('Dados da escala não encontrados.');
    document.getElementById('modal-editar-escala').style.display = 'flex';
    document.getElementById('editar_escala_id').value = escalaId;
    document.getElementById('editar_data').value = data.data_missa;
    document.getElementById('editar_horario').value = data.horario;
    document.getElementById('editar_descricao').value = data.descricao;
    const container = document.getElementById('funcoes-container');
    container.innerHTML = '';
    const funcoes = data.funcoes || {};
    if (Object.keys(funcoes).length === 0) addFuncaoInput('', '');
    for (const [fid, f] of Object.entries(funcoes)) addFuncaoInput(f.funcao, fid);
}

function closeEditarEscala() { document.getElementById('modal-editar-escala').style.display = 'none'; }

function addFuncaoInput(value, id) {
    const container = document.getElementById('funcoes-container');
    const wrapper = document.createElement('div');
    wrapper.style.display = 'flex'; wrapper.style.gap = '6px'; wrapper.style.marginBottom = '6px';
    const input = document.createElement('input'); input.type = 'text'; input.name = 'funcoes[]'; input.value = value || ''; input.style.flex = '1'; input.required = true;
    const hidden = document.createElement('input'); hidden.type = 'hidden'; hidden.name = 'funcao_ids[]'; hidden.value = id ? id : '';
    const btn = document.createElement('button'); btn.type = 'button'; btn.textContent = 'Remover'; btn.style.background = '#d9534f'; btn.style.color = 'white'; btn.style.border = 'none'; btn.style.padding = '6px 10px'; btn.style.borderRadius = '6px'; btn.onclick = function(){ container.removeChild(wrapper); };
    wrapper.appendChild(input); wrapper.appendChild(hidden); wrapper.appendChild(btn); container.appendChild(wrapper);
}
document.getElementById('add-funcao').addEventListener('click', function(){ addFuncaoInput('', ''); });

document.addEventListener('click', function(e){
    if (e.target && e.target.classList && e.target.classList.contains('admin-edit-btn')) {
        const id = e.target.dataset.escalaId;
        openEditarEscalaModal(id);
    }
    if (e.target && e.target.classList && e.target.classList.contains('transfer-btn')) {
        const pid = e.target.dataset.participanteId;
        const pname = e.target.dataset.participanteNome;
        openTransferModal(pid, pname);
    }
});


document.getElementById('form-editar-escala').addEventListener('submit', function(e){
    const inputs = Array.from(document.querySelectorAll('input[name="funcoes[]"]'));
    inputs.forEach(i => { if (!i.value.trim()) i.parentNode.removeChild(i); });
});

function openTransferModal(participanteId, participanteNome) {
    document.getElementById('modal-transferir').style.display = 'flex';
    document.getElementById('transfer_participante_id').value = participanteId;
    document.getElementById('transfer_participante_nome').textContent = participanteNome;
    const select = document.getElementById('transfer_escala_select');
    select.innerHTML = '';

    let participanteTipo = null;
    if (participanteId && participantIdToTipo[String(participanteId)]) participanteTipo = participantIdToTipo[String(participanteId)];
    if (!participanteTipo && participantesTipos[participanteNome]) participanteTipo = participantesTipos[participanteNome];

    let optList = [];
    if (participanteTipo === 'acolito') optList = optionsByTipo.acolito.concat(optionsByTipo.any);
    else if (participanteTipo === 'coroinha') optList = optionsByTipo.coroinha.concat(optionsByTipo.any);
    else optList = optionsByTipo.any;

    if (optList.length === 0) {
        const opt = document.createElement('option');
        opt.value = '';
        opt.textContent = 'Nenhuma função compatível encontrada';
        opt.disabled = true;
        select.appendChild(opt);
        return;
    }

    for (const o of optList) {
        const opt = document.createElement('option');
        opt.value = o.value; 
        opt.textContent = o.label;
        select.appendChild(opt);
    }
}

function closeTransferModal(){ document.getElementById('modal-transferir').style.display = 'none'; }
</script>

<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['transferir_participante']) && $tipo_usuario === 'admin') {
    $partId = (int) ($_POST['transfer_participante_id'] ?? 0);
    $target = $_POST['transfer_escala_select'] ?? '';
    if ($partId <= 0 || !$target) {
        $mensagem = 'Dados inválidos para transferência.';
    } else {
      
        $parts = explode('|', $target);
        if (count($parts) !== 2) {
            $mensagem = 'Destino inválido.';
        } else {
            $escId = (int) $parts[0];
            $funcId = (int) $parts[1];
            try {
               
                $stmt = $pdo->prepare('SELECT id, funcao, id_escala FROM funcoes WHERE id = ? AND id_escala = ?');
                $stmt->execute([$funcId, $escId]);
                $targetFunc = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$targetFunc) {
                    $mensagem = 'Função escolhida não pertence à escala selecionada.';
                } else {
                    $nomeFunc = $targetFunc['funcao'];
                    $nomeFuncLower = mb_strtolower($nomeFunc);

                    $stmtP = $pdo->prepare('SELECT id_funcao, nome_participante FROM participantes_funcoes WHERE id = ?');
                    $stmtP->execute([$partId]);
                    $partRow = $stmtP->fetch(PDO::FETCH_ASSOC);
                    if (!$partRow) {
                        $mensagem = 'Participante não encontrado.';
                    } else {
                        $sourceFuncId = (int) $partRow['id_funcao'];
                        $nomeParticipante = $partRow['nome_participante'];

                        $stmtTipo = $pdo->prepare('SELECT tipo FROM new_table WHERE NOME = ?');
                        $stmtTipo->execute([$nomeParticipante]);
                        $tipoParticipante = $stmtTipo->fetchColumn();

                     
                        $isNaoParticiparei = (mb_strpos($nomeFuncLower, 'não participarei') !== false || mb_strpos($nomeFuncLower, 'nao participarei') !== false);
                        $compat = false;
                        if ($isNaoParticiparei) $compat = true;
                        elseif ($tipoParticipante === 'acolito' && (mb_strpos($nomeFuncLower, 'acólito') !== false || mb_strpos($nomeFuncLower, 'acolito') !== false)) $compat = true;
                        elseif ($tipoParticipante === 'coroinha' && mb_strpos($nomeFuncLower, 'coroinha') !== false) $compat = true;

                        if (!$compat) {
                            $mensagem = 'Função de destino não é compatível com o tipo do participante.';
                        } else {
                       
                            $stmtOcc = $pdo->prepare('SELECT id, nome_participante FROM participantes_funcoes WHERE id_funcao = ?');
                            $stmtOcc->execute([$funcId]);
                            $occupants = $stmtOcc->fetchAll(PDO::FETCH_ASSOC);

                            
                            $stmtSourceF = $pdo->prepare('SELECT funcao FROM funcoes WHERE id = ?');
                            $stmtSourceF->execute([$sourceFuncId]);
                            $sourceFuncName = $stmtSourceF->fetchColumn();
                            $sourceFuncLower = mb_strtolower($sourceFuncName ?: '');

                            if ($isNaoParticiparei) {
                                $pdo->beginTransaction();
                                $upd = $pdo->prepare('UPDATE participantes_funcoes SET id_funcao = ? WHERE id = ?');
                                $upd->execute([$funcId, $partId]);
                                $pdo->commit();
                                header('Location: ' . $_SERVER['PHP_SELF']);
                                exit();
                            }

                            
                            if (count($occupants) === 0) {
                                $pdo->beginTransaction();
                                $upd = $pdo->prepare('UPDATE participantes_funcoes SET id_funcao = ? WHERE id = ?');
                                $upd->execute([$funcId, $partId]);
                                $pdo->commit();
                                header('Location: ' . $_SERVER['PHP_SELF']);
                                exit();
                            } elseif (count($occupants) === 1) {
                                $occ = $occupants[0];
                                $occId = (int) $occ['id'];
                                $occNome = $occ['nome_participante'];

                              
                                $stmtTipoOcc = $pdo->prepare('SELECT tipo FROM new_table WHERE NOME = ?');
                                $stmtTipoOcc->execute([$occNome]);
                                $tipoOcc = $stmtTipoOcc->fetchColumn();

                                $occCompatibleWithSource = false;
                      
                                $isSourceNao = (mb_strpos($sourceFuncLower, 'não participarei') !== false || mb_strpos($sourceFuncLower, 'nao participarei') !== false);
                                if ($isSourceNao) $occCompatibleWithSource = true;
                                else {
                                    if ($tipoOcc === 'acolito' && (mb_strpos($sourceFuncLower, 'acólito') !== false || mb_strpos($sourceFuncLower, 'acolito') !== false)) $occCompatibleWithSource = true;
                                    if ($tipoOcc === 'coroinha' && mb_strpos($sourceFuncLower, 'coroinha') !== false) $occCompatibleWithSource = true;
                                }

                                if (!$occCompatibleWithSource) {
                                    $mensagem = 'Não é possível trocar: o participante atual do destino não é compatível com a função de origem.';
                                } else {
                                    
                                    $pdo->beginTransaction();
                                    try {
                                        $swapStmt = $pdo->prepare(
                                            'UPDATE participantes_funcoes SET id_funcao = CASE WHEN id = ? THEN ? WHEN id = ? THEN ? END WHERE id IN (?, ?)'
                                        );
                                        
                                        $swapStmt->execute([$partId, $funcId, $occId, $sourceFuncId, $partId, $occId]);

                                        $pdo->commit();
                                        header('Location: ' . $_SERVER['PHP_SELF']);
                                        exit();
                                    } catch (Exception $e) {
                                        if ($pdo->inTransaction()) $pdo->rollBack();
                                        $mensagem = 'Erro ao trocar participantes: ' . $e->getMessage();
                                    }
                                }
                            } else {
                                
                                $mensagem = 'Destino tem mais de um participante; operação não suportada.';
                            }
                        }
                    }
                }
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $mensagem = 'Erro na transferência: ' . $e->getMessage();
            }
        }
    }
}
?>

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

</script>

<?php

?>
</body>
</html>