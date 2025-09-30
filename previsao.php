<?php
$endereco = $_GET['endereco'] ?? $_POST['endereco'] ?? null;

echo '<link rel="stylesheet" href="assets/style.css">';
echo '<div class="card">';

if (!$endereco) {
    echo "<h2>Consultar previsão do tempo</h2>
        <form method='get'>
            <input type='text' name='endereco' placeholder='Digite o endereço...' required>
            <button type='submit' class='form-btn'>Consultar</button>
        </form>
        <div style='margin-top:24px;'><a href='index.php' class='card-btn'>Voltar</a></div>";
    echo '</div>';
    exit;
}

$url = "https://nominatim.openstreetmap.org/search";
$params = [
    "q" => $endereco,
    "format" => "json",
    "limit" => 1
];
$url_completa = $url . "?" . http_build_query($params);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url_completa);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "User-Agent: meu-script-geocodificador"
]);
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);

echo "<h2>Previsão do tempo</h2>";
echo "<div class='info'><b>Endereço:</b> " . htmlspecialchars($endereco) . "</div>";

if (!empty($data)) {
    $lat = $data[0]["lat"];
    $lon = $data[0]["lon"];
    echo "<div class='info'><b>Latitude:</b> {$lat} | <b>Longitude:</b> {$lon}</div>";

    $url_previsao = "https://api.met.no/weatherapi/locationforecast/2.0/compact?lat={$lat}&lon={$lon}";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url_previsao);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "User-Agent: MeuAppPrevisao/1.0 gabriel@seudominio.com"
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);

    if (!empty($data['properties']['timeseries'])) {
        $proxima_previsao = $data['properties']['timeseries'][0];
        $hora = $proxima_previsao['time'];
        $detalhes = $proxima_previsao['data']['instant']['details'];

        echo "<div class='info'><b>Previsão para:</b> {$hora}</div>";
        echo "<div class='info'>🌡️ <b>Temperatura:</b> {$detalhes['air_temperature']} °C<br>
              💨 <b>Vento:</b> {$detalhes['wind_speed']} m/s<br>
              💧 <b>Umidade:</b> {$detalhes['relative_humidity']} %</div>";
    } else {
        echo "<div class='error'>Não foi possível obter a previsão.</div>";
    }
} else {
    echo "<div class='error'>Endereço não encontrado.</div>";
}

echo "<div style='margin-top:24px;'>
        <a href='previsao.php' class='card-btn'>Nova consulta</a> 
        <a href='index.php' class='card-btn'>Voltar</a>
    </div>
</div>";