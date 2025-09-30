<?php
include 'conexao.php';
$locais = $conn->query("SELECT * FROM locais");
?>
<?php include 'includes/header.php'; ?>
<div class="container" style="max-width:1000px;">
    <h2>Mapa dos Locais Monitorados</h2>
    <div id="mapid" style="height:480px; width:100%; border-radius:12px; box-shadow:0 4px 20px #b2c2d6;"></div>
    <p>Veja todos os locais monitorados em tempo real.</p>
    <a href="index.php" class="menu-btn">Voltar</a>
</div>
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
<script>
var map = L.map('mapid').setView([-21.5, -48.5], 7); // Região SP
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; <a href="https://openstreetmap.org">OpenStreetMap</a>'
}).addTo(map);

<?php
while($l = $locais->fetch_assoc()) {
    $endereco = "{$l['nome']}, {$l['cidade']}, {$l['uf']}, Brasil";
    $url = "https://nominatim.openstreetmap.org/search?" . http_build_query([
        "q" => $endereco,
        "format" => "json",
        "limit" => 1
    ]);
    $resp = file_get_contents($url);
    $data = json_decode($resp, true);
    if(!empty($data)) {
        $lat = $data[0]['lat'];
        $lon = $data[0]['lon'];
        $info = htmlspecialchars("{$l['nome']}<br>{$l['cidade']}, {$l['uf']}");
        echo "L.marker([$lat, $lon]).addTo(map)
            .bindPopup('<b>$info</b>');\n";
    }
}
?>
</script>
<?php include 'includes/footer.php'; ?>