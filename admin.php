<?php
session_start();
include_once('config.php');

$mensagem = '';

if (isset($_POST['submit'])) {
    $data_missa = $_POST['data_missa'] ?? '';
    $horario = $_POST['horario'] ?? '';
    $descricao = $_POST['descricao'] ?? '';
    $funcoes = $_POST['funcoes'] ?? [];

    if (!$data_missa || !$horario || !$descricao || empty($funcoes)) {
        $mensagem = "Preencha todos os campos e pelo menos uma função.";
    } else {
        // Inserir na tabela escalas
        $sqlEscala = "INSERT INTO escalas (data_missa, horario, descricao) VALUES (?, ?, ?)";
        $stmtEscala = $pdo->prepare($sqlEscala);
        $stmtEscala->execute([$data_missa, $horario, $descricao]);
        $id_escala = $pdo->lastInsertId();

        // Inserir as funções
        foreach ($funcoes as $funcao) {
            $sqlFuncao = "INSERT INTO funcoes (id_escala, funcao) VALUES (?, ?)";
            $stmtFuncao = $pdo->prepare($sqlFuncao);
            $stmtFuncao->execute([$id_escala, $funcao]);
        }

        $mensagem = "Escala cadastrada com sucesso!";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Cadastro de Escala</title>
  <style>
    <?php include 'style.css'; ?>
  </style>
</head>
<body>
  <div class="cadastro-box">
    <h1>Cadastrar Escala</h1>
    <?php if (!empty($mensagem)) echo "<p class='mensagem-erro'>$mensagem</p>"; ?>
    <form method="POST" action="">
      <input type="date" name="data_missa" placeholder="Data da Missa" required />
<input type="time" name="horario" placeholder="Horário" required />
<input type="text" name="descricao" placeholder="Descrição da Missa" required />

<input type="text" name="funcoes[]" value="Acólito" required />
<input type="text" name="funcoes[]" value="Acólito" />
<input type="text" name="funcoes[]" value="Acólito I" />
<input type="text" name="funcoes[]" value="Coroinha" />
<input type="text" name="funcoes[]" value="Coroinha" />
<input type="text" name="funcoes[]" value="Coroinha" />
<input type="text" name="funcoes[]" value="Coroinha I" />
<input type="text" name="funcoes[]" value="Coroinha I" />
<input type="text" name="funcoes[]" value="Coroinha I" />

<input type="text" name="funcoes[]" value="Não Participarei" />

      <button type="submit" name="submit">Cadastrar Escala</button>
    </form>
  </div>
</body>
</html>
