<?php
// get_alertas_oficiais.php

include_once 'conexao.php';

// Busca alertas que ainda estão ativos (data_fim maior que a data atual)
// Ordena pelos mais recentes primeiro
$sql = "SELECT cidade, estado, severidade, titulo, data_fim FROM alertas_oficiais WHERE data_fim > NOW() ORDER BY data_registro DESC";

$result = $conn->query($sql);

$alertas = [];

if ($result && $result->num_rows > 0) {
    $alertas = $result->fetch_all(MYSQLI_ASSOC);
}

header('Content-Type: application/json');
echo json_encode($alertas);

$conn->close();
?>