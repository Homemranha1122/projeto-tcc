<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    http_response_code(403);
    echo json_encode(["error" => "Não autorizado"]);
    exit;
}

require_once "conexao.php";

// Obter dados climáticos para o mapa
$dados = obterDadosMapaClimatico();

// Retornar como JSON
header("Content-Type: application/json");
echo json_encode($dados);
?>