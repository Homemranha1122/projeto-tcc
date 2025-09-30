<?php
include 'conexao.php';
$locais = $conn->query("SELECT * FROM locais");
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Monitoramento em Tempo Real</title>
    <link href="https://fonts.googleapis.com/css?family=Roboto:400,700&display=swap" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
    <link rel="icon" href="assets/logo.svg">
    <style>
        body {
            min-height: 100vh;
            background: #f2f4f7;
            font-family: 'Roboto', Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        .header-bar {
            width: 100vw;
            background: #1976d2;
            color: #fff;
            box-shadow: 0 2px 12px rgba(25,118,210,0.09);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 24px;
            min-height: 54px;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .header-bar .logo-area {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .header-bar .logo-area img {
            width: 34px; height: 34px;
        }
        .header-bar .title {
            font-size: 1.18rem;
            font-weight: bold;
            letter-spacing: 1px;
        }
        .header-bar .nav-links {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .header-bar .nav-links a {
            color: #fff;
            font-weight: 500;
            text-decoration: none;
            font-size: 1.03rem;
            transition: color 0.2s;
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 6px 0;
        }
        .header-bar .nav-links a:hover {
            color: #bbdefb;
        }
        .header-bar .nav-links .icon {
            font-size: 1.17rem;
            margin-right: 1px;
        }
        .header-bar .login-btn {
            background: #fff;
            color: #1976d2;
            font-weight: bold;
            padding: 6px 18px;
            border-radius: 20px;
            border: none;
            font-size: 0.98rem;
            cursor: pointer;
            margin-left: 18px;
            transition: background 0.2s;
        }
        .header-bar .login-btn:hover {
            background: #bbdefb;
            color: #0d47a1;
        }
        .weather-card {
            max-width: 520px;
            margin: 44px auto 0 auto;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 4px 30px rgba(19,102,170,0.09);
            padding: 32px 26px 24px 26px;
            text-align: center;
            display: none;
        }
        .weather-title {
            font-size: 1.36rem;
            font-weight: 700;
            color: #1976d2;
            margin-bottom: 3px;
            letter-spacing: 1px;
        }
        .weather-icon {
            width: 52px;
            height: 52px;
            margin: 0 auto 8px auto;
            display: block;
        }
        #mapid {
            height: 150px;
            width: 98%;
            border-radius: 10px;
            box-shadow: 0 2px 10px #bbdefb;
            margin: 0 auto 16px auto;
            display: block;
        }
        .weather-panel {
            background: #e3f2fd;
            border-radius: 11px;
            box-shadow: 0 2px 8px rgba(25,118,210,0.10);
            padding: 12px 18px;
            text-align: left;
            font-size: 1.07rem;
            color: #1976d2;
            margin: 14px auto;
            width: 95%;
            display: none;
        }
        .weather-desc {
            flex: 1;
        }
        .forecast-panel {
            background: #e3f2fd;
            border-radius: 11px;
            box-shadow: 0 2px 8px rgba(25,118,210,0.10);
            padding: 12px 12px 10px 12px;
            margin: 16px 0 0 0;
            font-size: 1.01rem;
            display: none;
        }
        .forecast-title {
            font-weight: bold;
            color: #1976d2;
            font-size: 1.08rem;
            margin-bottom: 5px;
            text-align: left;
        }
        .forecast-list {
            display: flex;
            flex-wrap: wrap;
            gap: 9px;
            justify-content: center;
        }
        .day-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 1px 7px rgba(25,118,210,0.07);
            padding: 8px 10px;
            min-width: 82px;
            text-align: center;
            color: #1976d2;
            margin-bottom: 4px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 3px;
            font-size: 0.98rem;
        }
        .day-card strong {
            font-size: 0.98rem;
            margin-bottom: 2px;
            color: #11518b;
        }
        .day-card img {
            width: 24px; height: 24px; margin-bottom:2px;
        }
        .menu-btn {
            display: inline-block;
            margin: 20px auto 0 auto;
            padding: 9px 24px;
            background: #1976d2;
            color: #fff;
            border: none;
            border-radius: 7px;
            font-size: 0.97rem;
            font-weight: bold;
            text-decoration: none;
            cursor: pointer;
            box-shadow: 0 3px 12px rgba(19,102,170,0.10);
            transition: background 0.2s;
        }
        .menu-btn:hover { background: #1565c0; }
        .footer {
            margin-top: 36px;
            font-size: 0.95rem;
            color: #1976d2;
            text-align: center;
        }
        .alert-permissao {
            color: #c00;
            font-weight: bold;
            margin: 16px 0 0 0;
            text-align: center;
            background: #fff3f3;
            border-radius: 8px;
            padding: 8px;
            display: none;
        }
        .weather-card-permissao {
            max-width: 520px;
            margin: 44px auto 0 auto;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 4px 30px rgba(19,102,170,0.09);
            padding: 32px 26px 24px 26px;
            text-align: center;
            display: block;
        }
        @media (max-width: 700px) {
            .weather-card, .weather-card-permissao { max-width: 99vw; padding: 4vw 2vw; }
            #mapid { height: 100px; }
            .forecast-list { flex-direction: column; gap: 0; }
            .day-card { min-width: 98px; margin-bottom: 8px; }
            .header-bar { padding: 0 8px; font-size: 0.99rem; }
        }
    </style>
</head>
<body>
    <div class="header-bar">
      <div class="logo-area">
        <img src="assets/logo.svg" alt="Logo">
        <span class="title">Projeto Enchentes</span>
      </div>
      <div class="nav-links">
        <a href="index.php"><span class="icon"><i class="fa fa-home"></i></span>Início</a>
        <a href="suporte.php"><span class="icon"><i class="fa fa-life-ring"></i></span>Suporte</a>
        <a href="monitor.php"><span class="icon"><i class="fa fa-map-marker-alt"></i></span>Tempo Real</a>
        <a href="https://facebook.com" target="_blank"><span class="icon"><i class="fab fa-facebook-square"></i></span></a>
        <a href="https://instagram.com" target="_blank"><span class="icon"><i class="fab fa-instagram"></i></span></a>
        <a href="https://twitter.com" target="_blank"><span class="icon"><i class="fab fa-twitter"></i></span></a>
        <button class="login-btn"><i class="fa fa-user"></i> Login</button>
      </div>
    </div>
    <div class="weather-card" id="weatherCard">
        <div>
            <img id="wicon" class="weather-icon" src="https://cdn-icons-png.flaticon.com/512/1163/1163661.png" alt="Tempo">
            <div class="weather-title">Monitoramento em Tempo Real</div>
            <div id="cidadeNome" style="color:#11518b; font-weight:500; font-size:1.09rem; margin-bottom:8px;"></div>
        </div>
        <div id="mapid"></div>
        <div id="weather" class="weather-panel">
            <div class="weather-desc" id="weatherDesc"></div>
        </div>
        <div id="forecast" class="forecast-panel">
            <div class="forecast-title">Previsão para os próximos dias:</div>
            <div class="forecast-list" id="forecastList"></div>
        </div>
        <a href="index.php" class="menu-btn" style="margin-top:18px;">Voltar</a>
    </div>
    <div class="weather-card-permissao" id="cardPermissao">
        <img src="assets/logo.svg" alt="Logo" style="width:46px; margin-bottom:12px;">
        <div class="weather-title">Monitoramento em Tempo Real</div>
        <p style="margin-bottom:0;">Permita o acesso à sua localização para visualizar sua posição no mapa junto com os locais monitorados.</p>
        <div id="alertPermissao" class="alert-permissao"></div>
        <button id="btnDetectar" class="menu-btn" style="margin-bottom:6px;">Detectar minha localização</button>
    </div>
    <div class="footer">
        &copy; <?= date('Y') ?> Projeto Enchentes | Feito em PHP + HTML
    </div>
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script>
    let map;
    let jaMostrouMapa = false;
    let cidadeNome = "";

    // Ícones de clima bonitos
    function getWeatherIcon(code) {
        switch(code){
            case 0: return 'https://cdn-icons-png.flaticon.com/512/1163/1163661.png'; // Sol
            case 1: case 2: case 3: return 'https://cdn-icons-png.flaticon.com/512/1163/1163624.png'; // Nuvem
            case 45: case 48: return 'https://cdn-icons-png.flaticon.com/512/1163/1163665.png'; // Neblina
            case 51: case 53: case 55: return 'https://cdn-icons-png.flaticon.com/512/1146/1146863.png'; // Chuva fraca
            case 61: case 63: case 65: return 'https://cdn-icons-png.flaticon.com/512/1146/1146859.png'; // Chuva moderada
            case 80: case 81: case 82: return 'https://cdn-icons-png.flaticon.com/512/1146/1146861.png'; // Chuva intensa
            default: return 'https://cdn-icons-png.flaticon.com/512/1163/1163661.png';
        }
    }
    function getWeatherDesc(code) {
        switch(code){
            case 0: return 'Céu limpo';
            case 1: case 2: case 3: return 'Parcialmente nublado';
            case 45: case 48: return 'Neblina';
            case 51: case 53: case 55: return 'Chuva fraca';
            case 61: case 63: case 65: return 'Chuva moderada';
            case 80: case 81: case 82: return 'Chuva intensa';
            default: return 'Tempo indefinido';
        }
    }
    function marcarUsuario(lat, lon) {
        var userMarker = L.marker([lat, lon], {icon: L.icon({
            iconUrl: 'https://cdn-icons-png.flaticon.com/512/64/64113.png',
            iconSize: [32, 32],
            iconAnchor: [16, 32],
            popupAnchor: [0, -32]
        })}).addTo(map)
          .bindPopup('<b>Você está aqui!</b>').openPopup();
    }
    function mostrarMapa() {
        document.getElementById('weatherCard').style.display = 'block';
        document.getElementById('cardPermissao').style.display = 'none';
        if (!jaMostrouMapa) {
            map = L.map('mapid').setView([-21.5, -48.5], 7);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://openstreetmap.org">OpenStreetMap</a>'
            }).addTo(map);

            <?php
            $locais2 = $conn->query("SELECT * FROM locais");
            while($l = $locais2->fetch_assoc()) {
                $endereco = "{$l['nome']}, {$l['cidade']}, {$l['uf']}, Brasil";
                $url = "https://nominatim.openstreetmap.org/search?" . http_build_query([
                    "q" => $endereco,
                    "format" => "json",
                    "limit" => 1
                ]);
                $resp = @file_get_contents($url);
                $data = json_decode($resp, true);
                if(!empty($data)) {
                    $lat = $data[0]['lat'];
                    $lon = $data[0]['lon'];
                    $info = htmlspecialchars("{$l['nome']}<br>{$l['cidade']}, {$l['uf']}");
                    echo "L.marker([$lat, $lon]).addTo(map).bindPopup('<b>$info</b>');\n";
                }
            }
            ?>
            jaMostrouMapa = true;
        }
    }
    function mostrarTempo(lat, lon) {
        fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}`)
        .then(r=>r.json()).then(data=>{
            cidadeNome = data.address.city || data.address.town || data.address.village || "";
            if (cidadeNome)
                document.getElementById("cidadeNome").innerText = cidadeNome+", "+(data.address.state || "");
        });

        let url = `https://api.open-meteo.com/v1/forecast?latitude=${lat}&longitude=${lon}&current_weather=true&daily=temperature_2m_max,temperature_2m_min,weathercode&timezone=auto`;
        fetch(url)
        .then(resp => resp.json())
        .then(data => {
            if (data.current_weather) {
                let weatherDescDiv = document.getElementById('weatherDesc');
                let temp = data.current_weather.temperature;
                let wind = data.current_weather.windspeed;
                let weatherCode = data.current_weather.weathercode;
                let desc = getWeatherDesc(weatherCode);
                let icon = getWeatherIcon(weatherCode);
                document.getElementById("wicon").src = icon;
                weatherDescDiv.innerHTML = `
                    <img src="${icon}" alt="${desc}" style="width:24px;vertical-align:middle;margin-right:7px;">
                    <strong>Tempo agora:</strong> ${desc}<br>
                    <strong>Temperatura:</strong> ${temp}°C<br>
                    <strong>Vento:</strong> ${wind} km/h
                `;
                document.getElementById('weather').style.display = 'block';
            }
            if (data.daily && data.daily.time && data.daily.weathercode) {
                let forecastListDiv = document.getElementById('forecastList');
                forecastListDiv.innerHTML = '';
                let days = data.daily.time.length;
                for(let i=0; i < days; i++) {
                    let dia = new Date(data.daily.time[i]);
                    let displayDate = `${dia.getDate().toString().padStart(2,'0')}/${(dia.getMonth()+1).toString().padStart(2,'0')}`;
                    let code = data.daily.weathercode[i];
                    let desc = getWeatherDesc(code);
                    let icon = getWeatherIcon(code);
                    let tmax = data.daily.temperature_2m_max[i];
                    let tmin = data.daily.temperature_2m_min[i];
                    forecastListDiv.innerHTML += `
                        <div class="day-card">
                            <strong>${displayDate}</strong>
                            <img src="${icon}" alt="${desc}">
                            <div>${desc}</div>
                            <div>Max: ${tmax}°C</div>
                            <div>Min: ${tmin}°C</div>
                        </div>
                    `;
                }
                document.getElementById('forecast').style.display = 'block';
            }
        })
        .catch(()=>{});
    }
    function detectarLocalizacao() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                mostrarMapa();
                var userLat = position.coords.latitude;
                var userLon = position.coords.longitude;
                map.setView([userLat, userLon], 13);
                marcarUsuario(userLat, userLon);
                mostrarTempo(userLat, userLon);
                document.getElementById('alertPermissao').style.display = 'none';
            }, function(error) {
                mostrarMapa();
                let alertDiv = document.getElementById('alertPermissao');
                if (error.code === error.PERMISSION_DENIED) {
                    alertDiv.innerText = "Localização não permitida pelo navegador. Ative a permissão para mostrar sua posição!";
                } else {
                    alertDiv.innerText = "Não foi possível obter sua localização.";
                }
                alertDiv.style.display = 'block';
            });
        } else {
            mostrarMapa();
            let alertDiv = document.getElementById('alertPermissao');
            alertDiv.innerText = "Seu navegador não suporta localização.";
            alertDiv.style.display = 'block';
        }
    }
    document.getElementById('btnDetectar').onclick = detectarLocalizacao;
    </script>
</body>
</html>