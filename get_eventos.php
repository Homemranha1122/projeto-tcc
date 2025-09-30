<?php
// get_eventos.php

// Inclui o arquivo de conexão com o banco de dados
include_once 'conexao.php';

// Prepara a consulta SQL para buscar todos os eventos.
// Ordenar por data aqui é uma boa prática para garantir que os dados cheguem de forma consistente.
$sql = "SELECT * FROM eventos ORDER BY data_evento DESC";

// Executa a consulta
$result = $conn->query($sql);

// Cria um array para armazenar os eventos que serão enviados
$eventos = [];

// Verifica se a consulta retornou algum resultado
if ($result && $result->num_rows > 0) {
    // Coleta todos os resultados da consulta em um array associativo
    // MYSQLI_ASSOC garante que teremos um array de 'nome_da_coluna' => 'valor'
    $eventos = $result->fetch_all(MYSQLI_ASSOC);
}

// Define o cabeçalho da resposta HTTP para indicar que o conteúdo é JSON.
// Isso é crucial para que o navegador e o JavaScript entendam o formato dos dados.
header('Content-Type: application/json');

// Converte o array de eventos para o formato JSON e o imprime na saída.
// É isso que o `fetch()` no seu JavaScript vai receber.
echo json_encode($eventos);

// Fecha a conexão com o banco de dados para liberar recursos.
$conn->close();
?>