
<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include_once('config.php');
session_start();

// Apenas coordenação pode acessar
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    echo '<p style="color:red;">Acesso negado. Faça login como administrador.</p>';
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listar Participantes</title>
    <link rel="stylesheet" href="style.css">
    <style>
    /* Garante cursor de digitação em todos os inputs do modal */
    #modal-editar input[type="text"],
    #modal-editar input[type="email"],
    #modal-editar input[type="date"] {
        cursor: text;
    }
    </style>
</head>
<body>
<header>
    <div class="header-content">
    <a href="index.html">Início</a>
    <a href="admin.php">Cadst Escalas</a>
    <a href="cadastro.php">Cadst Participantes</a>
    <a href="logout.php">Sair</a>
</header>

<div class="tabela-box">
    <h1>Participantes Cadastrados</h1>
    <?php
    try {
        $sql = "SELECT id, NOME, EMAIL, TELEFONE, NASCIMENTO, ENDERECO, tipo FROM new_table ORDER BY NOME ASC";
        $stmt = $pdo->query($sql);
        $registros = $stmt ? $stmt->rowCount() : 0;
        echo "<p style='text-align:center;'>Registros encontrados: $registros</p>";

        if ($stmt && $registros > 0) {
            echo "<table border='1' cellpadding='5' cellspacing='0' style='margin:auto;'>";
            echo "<tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>E-mail</th>
                    <th>Telefone</th>
                    <th>Nascimento</th>
                    <th>Endereço</th>
                    <th>Tipo</th>
                    <th>Ações</th>
                  </tr>";
            foreach ($stmt as $row) {
                $id = htmlspecialchars($row['id'] ?? '');
                $nome = htmlspecialchars($row['NOME'] ?? '');
                $email = htmlspecialchars($row['EMAIL'] ?? '');
                $telefone = htmlspecialchars($row['TELEFONE'] ?? '');
                $nascimento = htmlspecialchars($row['NASCIMENTO'] ?? '');
                $endereco = htmlspecialchars($row['ENDERECO'] ?? '');
                $tipo = htmlspecialchars($row['tipo'] ?? '');
                echo "<tr>";
                echo "<td>$id</td>";
                echo "<td>$nome</td>";
                echo "<td>$email</td>";
                echo "<td>$telefone</td>";
                echo "<td>$nascimento</td>";
                echo "<td>$endereco</td>";
                echo "<td>$tipo</td>";
                echo "<td><button type='button' class='editar-btn' 
                    data-id='$id' data-nome='$nome' data-email='$email' data-telefone='$telefone' data-nascimento='$nascimento' data-endereco='$endereco' data-tipo='$tipo' style='background:#f5f5f5;color:#333;border:1px solid #bbb;padding:3px 10px;border-radius:5px;font-size:13px;cursor:pointer;transition:background 0.2s;'>✏️ Editar</button></td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p class='mensagem-erro'>Nenhum participante encontrado no banco de dados.</p>";
        }
    } catch (Exception $e) {
        echo "<p class='mensagem-erro'>Erro ao carregar participantes: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    ?>
</div>
</div>

<!-- Modal de edição -->


<div id="modal-editar" style="display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.4);align-items:center;justify-content:center;z-index:9999;">
    <div style="background:#fff;padding:12px 10px 12px 10px;border-radius:10px;min-width:220px;max-width:600px;width:96vw;position:relative;display:flex;flex-direction:row;gap:18px;align-items:flex-start;box-sizing:border-box;">
        <form id="form-editar" method="POST" action="" style="flex:1;min-width:0;">
            <input type="hidden" name="editar_id" id="editar_id">
            <div style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;justify-content:center;">
                <div style="flex:1 1 180px;min-width:120px;">
                    <label style="font-size:13px;">Nome:<br><input type="text" name="editar_nome" id="editar_nome" required style="width:100%;margin-bottom:4px;font-size:13px;"></label>
                </div>
                <div style="flex:1 1 140px;min-width:100px;">
                      <label style="font-size:13px;">Email:<br><input type="email" name="editar_email" id="editar_email" required style="width:100%;margin-bottom:4px;font-size:13px;"></label>
                </div>
                <div style="flex:1 1 110px;min-width:80px;">
                    <label style="font-size:13px;">Telefone:<br><input type="text" name="editar_telefone" id="editar_telefone" required style="width:100%;margin-bottom:4px;font-size:13px;"></label>
                </div>
                <div style="flex:1 1 110px;min-width:80px;">
                    <label style="font-size:13px;">Nascimento:<br><input type="date" name="editar_nascimento" id="editar_nascimento" required style="width:100%;margin-bottom:4px;font-size:13px;"></label>
                </div>
                <div style="flex:1 1 140px;min-width:100px;">
                    <label style="font-size:13px;">Endereço:<br><input type="text" name="editar_endereco" id="editar_endereco" required style="width:100%;margin-bottom:4px;font-size:13px;"></label>
                </div>
                <div style="flex:1 1 90px;min-width:70px;">
                    <label style="font-size:13px;">Tipo:<br><input type="text" name="editar_tipo" id="editar_tipo" style="width:100%;margin-bottom:4px;font-size:13px;"></label>
                </div>
            </div>
            <button type="submit" name="salvar_edicao" style="width:100%;background:#28a745;color:white;padding:6px 0;border:none;border-radius:6px;font-size:13px;margin-top:10px;">Salvar Alterações</button>
        </form>
        <button id="fechar-modal" style="position:absolute;top:6px;right:10px;font-size:20px;background:none;border:none;cursor:pointer;line-height:1;">&times;</button>
    </div>
</div>

<script>
// Abrir modal e preencher campos
document.querySelectorAll('.editar-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('modal-editar').style.display = 'flex';
        document.getElementById('editar_id').value = this.dataset.id;
        document.getElementById('editar_nome').value = this.dataset.nome;
        document.getElementById('editar_email').value = this.dataset.email;
        document.getElementById('editar_telefone').value = this.dataset.telefone;
        document.getElementById('editar_nascimento').value = this.dataset.nascimento;
        document.getElementById('editar_endereco').value = this.dataset.endereco;
        document.getElementById('editar_tipo').value = this.dataset.tipo;
        // Foco automático no campo Nome
        setTimeout(function() {
            document.getElementById('editar_nome').focus();
        }, 100);
    });
});
document.getElementById('fechar-modal').onclick = function() {
    document.getElementById('modal-editar').style.display = 'none';
};
// Fechar modal ao clicar fora
document.getElementById('modal-editar').onclick = function(e) {
    if (e.target === this) this.style.display = 'none';
};
</script>

<?php
// Processar edição
if (isset($_POST['salvar_edicao'])) {
        $id = $_POST['editar_id'] ?? '';
        $nome = $_POST['editar_nome'] ?? '';
        $email = $_POST['editar_email'] ?? '';
        $telefone = $_POST['editar_telefone'] ?? '';
        $nascimento = $_POST['editar_nascimento'] ?? '';
        $endereco = $_POST['editar_endereco'] ?? '';
        $tipo = $_POST['editar_tipo'] ?? '';
        if ($id && $nome && $email && $telefone && $nascimento && $endereco) {
                try {
                        $sql = "UPDATE new_table SET NOME=?, EMAIL=?, TELEFONE=?, NASCIMENTO=?, ENDERECO=?, tipo=? WHERE id=?";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([$nome, $email, $telefone, $nascimento, $endereco, $tipo, $id]);
                        echo "<script>alert('Participante atualizado com sucesso!');window.location.href=window.location.href;</script>";
                        exit();
                } catch (Exception $e) {
                        echo "<script>alert('Erro ao atualizar participante: ".addslashes($e->getMessage())."');</script>";
                }
        } else {
                echo "<script>alert('Preencha todos os campos obrigatórios!');</script>";
        }
}
?>
</body>
</html>
