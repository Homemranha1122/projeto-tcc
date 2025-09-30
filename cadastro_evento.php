<?php
session_start();
include_once 'conexao.php';

// Se o usuário não estiver logado, redireciona para o login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?erro=login_necessario');
    exit;
}

// Busca locais para preencher o <select>
$locais = $conn->query("SELECT id, nome, cidade, uf, latitude, longitude FROM locais ORDER BY nome");
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Novo Evento - Olimclima</title>
    
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Poppins', sans-serif; margin: 0; background-color: #f4f4f9; }
        .container { max-width: 800px; margin: 2rem auto; padding: 2rem; background: white; border-radius: 8px; box-shadow: 0 0 15px rgba(0,0,0,0.07); }
        h1 { text-align: center; color: #333; }
        .form-group { margin-bottom: 1.5rem; }
        label { display: block; margin-bottom: 0.5rem; font-weight: 500; color: #555; }
        input, select, textarea { width: 100%; padding: 0.8rem; border: 1px solid #ccc; border-radius: 6px; box-sizing: border-box; }
        .button { width: 100%; padding: 1rem; border: none; background: #3a86ff; color: white; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 1rem; transition: background-color 0.2s; }
        .button:hover { background-color: #3278e2; }
        .alert-danger { padding: 1rem; margin-bottom: 1.5rem; border-radius: 6px; background-color: #f8d7da; color: #721c24; text-align: center; }
        #map { height: 350px; width: 100%; margin-bottom: 1.5rem; border-radius: 8px; border: 1px solid #ccc; }
    </style>
</head>
<body>

    <?php include 'assets/header.php'; // CORRIGIDO AQUI! ?>

    <div class="container">
        <h1>Cadastrar Novo Evento</h1>
        
        <?php if (isset($_GET['erro']) && $_GET['erro'] == 'campos_obrigatorios'): ?>
            <div class="alert-danger">
                Todos os campos obrigatórios devem ser preenchidos.
            </div>
        <?php endif; ?>

        <div id="map"></div>
        
        <form action="salvar_evento.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="latitude" id="latitude">
            <input type="hidden" name="longitude" id="longitude">

            <div class="form-group">
                <label for="local_id">Local (selecione na lista ou clique no mapa)</label>
                <select name="local_id" id="local_id">
                    <option value="">Selecione um local da lista</option>
                    <?php while ($local = $locais->fetch_assoc()): ?>
                        <option value="<?= $local['id'] ?>" data-lat="<?= $local['latitude'] ?>" data-lng="<?= $local['longitude'] ?>">
                            <?= htmlspecialchars($local['nome'] . ' - ' . $local['cidade'] . '/' . $local['uf']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="tipo">Tipo de evento</label>
                <select name="tipo" id="tipo" required>
                    <option value="">Selecione</option>
                    <option value="Chuva">Chuva</option>
                    <option value="Alagamento">Alagamento</option>
                    <option value="Enchente">Enchente</option>
                    <option value="Deslizamento">Deslizamento</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="intensidade">Intensidade</label>
                <select name="intensidade" id="intensidade" required>
                    <option value="">Selecione</option>
                    <option value="Fraca">Fraca</option>
                    <option value="Moderada">Moderada</option>
                    <option value="Forte">Forte</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="data_evento">Data e hora</label>
                <input type="datetime-local" name="data_evento" id="data_evento" required>
            </div>
            
            <div class="form-group">
                <label for="observacoes">Observações</label>
                <textarea name="observacoes" id="observacoes" rows="4" placeholder="Descreva mais detalhes sobre o evento..."></textarea>
            </div>
            
            <div class="form-group">
                <label for="imagens">Imagens e Vídeos</label>
                <input type="file" name="midia[]" id="midia" accept="image/*,video/*" multiple>
            </div>
            
            <button type="submit" class="button">Cadastrar Evento</button>
        </form>
    </div>
    
    <script>
        const map = L.map('map').setView([-15.78, -47.92], 4);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
        
        let marker;

        function updateMarker(lat, lng) {
            if (marker) map.removeLayer(marker);
            marker = L.marker([lat, lng]).addTo(map);
            map.setView([lat, lng], 15);
            document.getElementById('latitude').value = lat;
            document.getElementById('longitude').value = lng;
        }
        
        map.on('click', e => updateMarker(e.latlng.lat, e.latlng.lng));

        document.getElementById('local_id').addEventListener('change', function() {
            const option = this.options[this.selectedIndex];
            if (option.value) {
                const lat = parseFloat(option.dataset.lat);
                const lng = parseFloat(option.dataset.lng);
                if (!isNaN(lat) && !isNaN(lng)) updateMarker(lat, lng);
            }
        });
    </script>
</body>
</html>