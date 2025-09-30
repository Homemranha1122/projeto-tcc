<?php
// Define o cabeçalho para indicar que a resposta é JSON
header('Content-Type: application/json');

// Verifica se a latitude e a longitude foram passadas como parâmetros
if (!isset($_GET['lat']) || !isset($_GET['lon'])) {
    echo json_encode(['error' => 'Latitude e Longitude são obrigatórias.']);
    exit;
}

$lat = $_GET['lat'];
$lon = $_GET['lon'];

// Monta a URL da API Nominatim para geocodificação reversa
// O parâmetro 'format=jsonv2' retorna um JSON mais limpo e estruturado
// 'addressdetails=1' inclui os detalhes do endereço (rua, cidade, estado, etc.)
$url = "https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat={$lat}&lon={$lon}&addressdetails=1";

// Configura o contexto do stream para o cURL, incluindo um User-Agent
// Muitas APIs, incluindo a Nominatim, exigem um User-Agent para evitar bloqueios.
$opts = [
    "http" => [
        "method" => "GET",
        "header" => "User-Agent: MeuProjetoDeMonitoramento/1.0\r\n"
    ]
];
$context = stream_context_create($opts);

// Faz a requisição para a API e obtém a resposta
$response = @file_get_contents($url, false, $context);

// Verifica se a requisição falhou
if ($response === false) {
    echo json_encode(['error' => 'Não foi possível contatar o serviço de geolocalização.']);
    exit;
}

// Repassa a resposta da API diretamente para o cliente (JavaScript)
echo $response;
?>