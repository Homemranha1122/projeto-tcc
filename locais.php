<?php include 'includes/header.php'; ?>
<?php
include 'conexao.php';
$res = $conn->query("SELECT * FROM locais ORDER BY nome");
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Locais Monitorados</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="container">
    <h2>Locais Monitorados</h2>
    <table>
        <tr>
            <th>Nome</th>
            <th>Cidade</th>
            <th>UF</th>
            <th>Previsão do tempo</th>
        </tr>
        <?php while($l = $res->fetch_assoc()) { 
            $endereco = "{$l['nome']}, {$l['cidade']}, {$l['uf']}, Brasil";
        ?>
        <tr>
            <td><?= htmlspecialchars($l['nome']) ?></td>
            <td><?= htmlspecialchars($l['cidade']) ?></td>
            <td><?= htmlspecialchars($l['uf']) ?></td>
            <td>
                <a href="previsao.php?endereco=<?= urlencode($endereco) ?>" class="menu-btn" style="padding:4px 10px;font-size:0.95rem;">Ver previsão</a>
            </td>
        </tr>
        <?php } ?>
    </table>
    <a href="index.php" class="menu-btn">Voltar</a>
</div>
</body>
</html>
<?php include 'includes/footer.php'; ?>