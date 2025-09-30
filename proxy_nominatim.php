<?php
// Define um User-Agent, pois a API do Nominatim exige isso.
ini_set('user_agent', 'ProjetoEnchentes/1.0 (https://seusite.com; email@seusite.com)');

header('Content-Type: application/json');

if (empty($_GET['term'])) {
    echo json_encode([]);
    exit;
}

$term = urlencode($_GET['term']);
$url = "https://nominatim.openstreetmap.org/search?q={$term}&format=json&addressdetails=1&accept-language=pt-BR";

// file_get_contents é mais simples para este caso.
$response = @file_get_contents($url);

if ($response === FALSE) {
    // Se houver um erro na requisição, retorna um array vazio.
    echo json_encode([]);
} else {
    echo $response;
}
?>