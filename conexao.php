<?php
/**
 * NOTA: Este arquivo está em processo de migração gradual para nova estrutura.
 * 
 * A nova arquitetura (src/, Composer, PDO, .env) foi introduzida para melhorar
 * segurança e manutenibilidade sem quebrar o código existente.
 * 
 * Veja public/index_refatorado.php para exemplo da nova estrutura.
 * Migração completa será feita gradualmente em PRs futuras.
 */

// Tenta carregar bootstrap se disponível (habilita suporte a .env)
if (file_exists(__DIR__ . '/public/bootstrap.php')) {
    require_once __DIR__ . '/public/bootstrap.php';
}

// Configurações da conexão
$db_host = 'localhost';
$db_user = 'root'; 
$db_pass = ''; 
$db_name = 'enchentes';

// Conexão com o banco
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}
$conn->set_charset("utf8");

// Notificações (exemplo estático)
$notificacoes = [
    ["tipo" => "alerta", "msg" => "Nova enchente registrada ontem em Ribeirão Preto!", "tempo" => "3h atrás"],
    ["tipo" => "info", "msg" => "Novo local cadastrado: Centro de Barretos.", "tempo" => "5h atrás"],
    ["tipo" => "alerta", "msg" => "Alerta de chuvas intensas para região de Olímpia.", "tempo" => "Hoje, 10:25"],
];

// Funções úteis
function sanitize($input) {
    global $conn;
    return $conn->real_escape_string(htmlspecialchars(trim($input)));
}

// Função para obter previsão do tempo
function obterPrevisaoTempo($cidade, $uf = "") {
    // API Key: usar variável de ambiente se disponível (recomendado mover para .env)
    $api_key = function_exists('env') ? env('OPENWEATHER_API_KEY', '5a3a2e0c72f5e5c8d2e2f3e2c6e2b7ac') : '5a3a2e0c72f5e5c8d2e2f3e2c6e2b7ac';

    // Busca coordenadas via geocoding OpenWeatherMap
    $geo_url = "http://api.openweathermap.org/geo/1.0/direct?q=" . urlencode($cidade . ($uf ? ",$uf,BR" : ",BR")) . "&limit=1&appid=$api_key";
    $geo_json = @file_get_contents($geo_url);
    $geo = json_decode($geo_json, true);

    if (!$geo || !isset($geo[0]['lat']) || !isset($geo[0]['lon'])) {
        // fallback se não encontrar
        return [
            'erro' => true,
            'cidade' => $cidade,
            'uf' => $uf,
            'descricao' => 'Local não encontrado',
            'icone_url' => "https://openweathermap.org/img/wn/01d@2x.png"
        ];
    }

    $lat = $geo[0]['lat'];
    $lon = $geo[0]['lon'];

    // Busca previsão atual
    $weather_url = "https://api.openweathermap.org/data/2.5/weather?lat=$lat&lon=$lon&units=metric&lang=pt_br&appid=$api_key";
    $weather_json = @file_get_contents($weather_url);
    $w = json_decode($weather_json, true);

    if (!$w || !isset($w['main']) || !isset($w['weather'][0])) {
        return [
            'erro' => true,
            'cidade' => $cidade,
            'uf' => $uf,
            'descricao' => 'Previsão indisponível',
            'icone_url' => "https://openweathermap.org/img/wn/01d@2x.png"
        ];
    }

    return [
        'erro' => false,
        'cidade' => $cidade,
        'uf' => $uf,
        'lat' => $lat,
        'lng' => $lon,
        'temp' => round($w['main']['temp']),
        'umidade' => $w['main']['humidity'],
        'descricao' => ucfirst($w['weather'][0]['description']),
        'icone_url' => "https://openweathermap.org/img/wn/" . $w['weather'][0]['icon'] . "@2x.png",
        'vento' => round($w['wind']['speed'] * 3.6), // m/s para km/h
        'precipitacao' => isset($w['rain']['1h']) ? $w['rain']['1h'] : 0
    ];
}

// (Opcional, para o mapa dinâmico)
function obterLocaisComPrevisao($conn) {
    $locais = [];
    $res = $conn->query("SELECT * FROM locais WHERE latitude IS NOT NULL AND longitude IS NOT NULL");
    while ($row = $res->fetch_assoc()) {
        $prev = obterPrevisaoTempo($row['cidade'], $row['uf']);
        $locais[] = [
            'nome' => $row['nome'],
            'cidade' => $row['cidade'],
            'uf' => $row['uf'],
            'latitude' => $row['latitude'],
            'longitude' => $row['longitude'],
            'previsao' => $prev
        ];
    }
    return $locais;
}
// Função para carregar header padrão
function carregarHeader($titulo = "Sistema de Monitoramento de Enchentes", $incluirMapa = false) {
    global $notificacoes;
    
    // Verificar se usuário está logado
    if (!isset($_SESSION)) {
        session_start();
    }
    
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
    
    // Admin badge
    $isAdmin = isset($_SESSION['user_admin']) && $_SESSION['user_admin'];
    
    ?>
    <!DOCTYPE html>
    <html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= $titulo ?></title>
        <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
        <link rel="icon" href="assets/logo.svg">
        <?php if ($incluirMapa): ?>
        <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/mwasil/Leaflet.Rainviewer/leaflet.rainviewer.css"/>
        <?php endif; ?>
        <style>
            :root {
                /* Nova paleta de cores (cinza com azul como destaque) */
                --primary-color: #3498db;      /* Azul como destaque */
                --primary-dark: #2980b9;       /* Azul escuro */
                --primary-light: #b3e0ff;      /* Azul claro */
                --neutral-bg: #f5f5f5;         /* Fundo cinza claro */
                --neutral-dark: #333333;       /* Texto escuro */
                --neutral-medium: #757575;     /* Cinza médio */
                --neutral-light: #e0e0e0;      /* Cinza claro */
                --accent-color: #3498db;       /* Acento (azul) */
                --text-on-dark: #ffffff;       /* Texto sobre fundo escuro */
                --text-primary: #212121;       /* Texto primário */
                --text-secondary: #757575;     /* Texto secundário */
                --danger-color: #e74c3c;       /* Vermelho para alertas */
                --success-color: #2ecc71;      /* Verde para sucesso */
                --warning-color: #f39c12;      /* Laranja para avisos */
                --card-shadow: 0 4px 12px rgba(0,0,0,0.08);
                --card-radius: 8px;            /* Bordas arredondadas */
                --transition: all 0.3s ease;   /* Transição padrão */
            }
            
            * {
                box-sizing: border-box;
                margin: 0;
                padding: 0;
            }
            
            body {
                background: var(--neutral-bg);
                font-family: 'Roboto', Arial, sans-serif;
                color: var(--text-primary);
                line-height: 1.6;
                transition: var(--transition);
            }
            
            .header-bar {
                width: 100%;
                background: var(--neutral-dark);
                color: var(--text-on-dark);
                box-shadow: 0 2px 8px rgba(0,0,0,0.15);
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 0 24px;
                min-height: 60px;
                position: sticky;
                top: 0;
                z-index: 1000;
            }
            
            .logo-area {
                display: flex;
                align-items: center;
                gap: 12px;
            }
            
            .logo-area img {
                width: 34px; 
                height: 34px;
                filter: brightness(0) invert(1);
            }
            
            .title {
                font-size: 1.18rem;
                font-weight: bold;
                letter-spacing: 0.5px;
            }
            
            .nav-links {
                display: flex;
                align-items: center;
                gap: 16px;
                position: relative;
            }
            
            .nav-link, .nav-btn {
                color: var(--text-on-dark);
                font-weight: 500;
                text-decoration: none;
                font-size: 1rem;
                transition: var(--transition);
                display: flex;
                align-items: center;
                gap: 6px;
                padding: 7px 16px;
                border-radius: 20px;
                border: none;
                background: none;
                cursor: pointer;
                position: relative;
            }
            
            .nav-link:hover, .nav-btn:hover {
                background: rgba(255,255,255,0.1);
                color: var(--primary-light);
                transform: translateY(-2px);
            }
            
            .nav-link.active {
                background: rgba(255,255,255,0.15);
                box-shadow: 0 0 0 1px rgba(255,255,255,0.2);
            }
            
            .nav-btn.suporte {
                background: var(--primary-color);
                color: var(--text-on-dark);
                font-weight: bold;
                box-shadow: 0 1px 8px rgba(52, 152, 219, 0.3);
            }
            
            .nav-btn.suporte:hover {
                background: var(--primary-dark);
                transform: translateY(-2px);
            }
            
            .nav-btn.logout {
                background: transparent;
                color: var(--text-on-dark);
                font-weight: bold;
                border: 1px solid var(--text-on-dark);
            }
            
            .nav-btn.logout:hover {
                background: rgba(255,255,255,0.1);
                color: var(--primary-light);
            }
            
            .user-avatar {
                background: var(--primary-color);
                color: var(--text-on-dark);
                border-radius: 50%;
                width: 36px; height: 36px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: bold;
                font-size: 1.1rem;
                margin-left: 10px;
                border: 2px solid var(--text-on-dark);
                position: relative;
                transition: var(--transition);
            }
            
            .user-avatar:hover {
                transform: scale(1.1);
            }
            
            .badge-admin {
                background: var(--danger-color);
                color: var(--text-on-dark);
                font-size: 0.7rem;
                font-weight: bold;
                padding: 2px 6px;
                border-radius: 10px;
                position: absolute;
                top: -8px;
                right: -8px;
            }
            
            .notif-bell {
                position: relative;
                font-size: 1.5rem;
                color: var(--text-on-dark);
                cursor: pointer;
                margin-left: 10px;
                transition: var(--transition);
            }
            
            .notif-bell:hover {
                transform: scale(1.1);
            }
            
            .notif-bell .badge {
                position: absolute;
                top: -7px;
                right: -10px;
                background: var(--danger-color);
                color: var(--text-on-dark);
                font-size: 0.8rem;
                padding: 2px 7px;
                border-radius: 50px;
                font-weight: bold;
            }
            
            .notif-dropdown {
                display: none;
                position: fixed;
                top: 62px;
                right: 22px;
                background: #fff;
                color: var(--text-primary);
                min-width: 280px;
                box-shadow: var(--card-shadow);
                border-radius: var(--card-radius);
                z-index: 999;
                animation: fadeIn 0.2s ease;
            }
            
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(-10px); }
                to { opacity: 1; transform: translateY(0); }
            }
            
            .notif-bell.active ~ .notif-dropdown { display: block; }
            
            .notif-dropdown .notif-header {
                padding: 10px 15px;
                border-bottom: 1px solid var(--neutral-light);
                font-weight: bold;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            
            .notif-dropdown .notif-header .clear-all {
                font-size: 0.8rem;
                color: var(--primary-color);
                cursor: pointer;
            }
            
            .notif-dropdown .notif-item {
                padding: 12px 16px;
                border-bottom: 1px solid var(--neutral-light);
                font-size: 0.95rem;
                transition: var(--transition);
                display: flex;
                align-items: flex-start;
                gap: 10px;
            }
            
            .notif-dropdown .notif-item:hover {
                background: var(--neutral-bg);
            }
            
            .notif-dropdown .notif-item:last-child { 
                border-bottom: none;
                border-radius: 0 0 var(--card-radius) var(--card-radius);
            }
            
            .notif-dropdown .notif-item .icon {
                width: 32px;
                height: 32px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 1rem;
                flex-shrink: 0;
            }
            
            .notif-dropdown .notif-item.alerta .icon { 
                background: #ffebee;
                color: var(--danger-color); 
            }
            
            .notif-dropdown .notif-item.info .icon { 
                background: #e3f2fd;
                color: var(--primary-color); 
            }
            
            .notif-dropdown .notif-item .content {
                flex: 1;
            }
            
            .notif-dropdown .notif-item .time {
                font-size: 0.8rem;
                color: var(--text-secondary);
                margin-top: 3px;
            }
            
            .dark-toggle {
                background: rgba(255,255,255,0.1);
                color: var(--text-on-dark);
                border: none;
                border-radius: 20px;
                margin-left: 12px;
                padding: 7px 12px;
                cursor: pointer;
                font-weight: bold;
                transition: var(--transition);
            }
            
            .dark-toggle:hover {
                background: rgba(255,255,255,0.2);
                transform: translateY(-2px);
            }
            
            .container {
                max-width: 1000px;
                margin: 40px auto;
                padding: 30px;
                background: #fff;
                border-radius: var(--card-radius);
                box-shadow: var(--card-shadow);
            }
            
            .page-title {
                font-size: 1.8rem;
                font-weight: 700;
                color: var(--primary-color);
                margin-bottom: 20px;
                text-align: center;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 10px;
            }
            
            .page-subtitle {
                text-align: center;
                color: var(--text-secondary);
                margin-bottom: 30px;
            }
            
            .form-group {
                margin-bottom: 20px;
            }
            
            .form-label {
                display: block;
                margin-bottom: 8px;
                font-weight: 500;
                color: var(--neutral-dark);
            }
            
            .form-control {
                width: 100%;
                padding: 12px 15px;
                border: 1px solid var(--neutral-light);
                border-radius: var(--card-radius);
                font-size: 1rem;
                transition: var(--transition);
            }
            
            .form-control:focus {
                outline: none;
                border-color: var(--primary-color);
                box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
            }
            
            .form-select {
                appearance: none;
                background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%23333' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
                background-repeat: no-repeat;
                background-position: right 15px center;
                padding-right: 35px;
            }
            
            .form-text {
                font-size: 0.85rem;
                color: var(--text-secondary);
                margin-top: 5px;
            }
            
            .btn {
                display: inline-block;
                padding: 12px 24px;
                background: var(--primary-color);
                color: var(--text-on-dark);
                border: none;
                border-radius: var(--card-radius);
                font-size: 1rem;
                font-weight: 500;
                cursor: pointer;
                text-decoration: none;
                transition: var(--transition);
                text-align: center;
            }
            
            .btn:hover {
                background: var(--primary-dark);
                transform: translateY(-2px);
                box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            }
            
            .btn-success {
                background: var(--success-color);
            }
            
            .btn-success:hover {
                background: #27ae60;
            }
            
            .btn-danger {
                background: var(--danger-color);
            }
            
            .btn-danger:hover {
                background: #c0392b;
            }
            
            .btn-secondary {
                background: var(--text-secondary);
            }
            
            .btn-secondary:hover {
                background: #616161;
            }
            
            .btn-group {
                display: flex;
                gap: 15px;
                justify-content: center;
                margin-top: 30px;
            }
            
            .alert {
                padding: 15px 20px;
                border-radius: var(--card-radius);
                margin-bottom: 20px;
                display: flex;
                align-items: center;
                gap: 15px;
            }
            
            .alert-success {
                background-color: #d4edda;
                border-left: 4px solid var(--success-color);
                color: #155724;
            }
            
            .alert-danger {
                background-color: #f8d7da;
                border-left: 4px solid var(--danger-color);
                color: #721c24;
            }
            
            .alert-warning {
                background-color: #fff3cd;
                border-left: 4px solid var(--warning-color);
                color: #856404;
            }
            
            .alert .icon {
                font-size: 1.5rem;
                flex-shrink: 0;
            }
            
            .alert .content {
                flex: 1;
            }
            
            .table {
                width: 100%;
                border-collapse: collapse;
                margin: 25px 0;
                border-radius: var(--card-radius);
                overflow: hidden;
                box-shadow: var(--card-shadow);
            }
            
            .table th, 
            .table td {
                padding: 15px;
                text-align: left;
                border-bottom: 1px solid var(--neutral-light);
            }
            
            .table th {
                background-color: var(--neutral-bg);
                font-weight: 600;
                color: var(--neutral-dark);
            }
            
            .table tbody tr:last-child td {
                border-bottom: none;
            }
            
            .table tbody tr:hover td {
                background-color: rgba(0,0,0,0.01);
            }
            
            .badge {
                display: inline-block;
                padding: 4px 10px;
                font-size: 0.85rem;
                font-weight: 500;
                border-radius: 30px;
                color: white;
                text-align: center;
            }
            
            .badge-primary {
                background-color: var(--primary-color);
            }
            
            .badge-success {
                background-color: var(--success-color);
            }
            
            .badge-danger {
                background-color: var(--danger-color);
            }
            
            .badge-warning {
                background-color: var(--warning-color);
            }
            
            .footer {
                text-align: center;
                padding: 20px;
                color: var(--text-secondary);
                margin-top: 40px;
                font-size: 0.9rem;
            }
            
            .footer a {
                color: var(--primary-color);
                text-decoration: none;
            }
            
            .footer a:hover {
                text-decoration: underline;
            }
            
            #map {
                height: 400px;
                width: 100%;
                border-radius: var(--card-radius);
                margin-bottom: 20px;
                z-index: 1;
            }
            
            .weather-card {
                background: linear-gradient(135deg, #5d9cec, #3498db);
                color: var(--text-on-dark);
                border-radius: var(--card-radius);
                padding: 20px;
                display: flex;
                align-items: center;
                gap: 20px;
                margin-bottom: 20px;
                box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
            }
            
            .weather-card .weather-icon {
                width: 80px;
                height: 80px;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .weather-card .weather-icon img {
                width: 100%;
                height: 100%;
                object-fit: contain;
            }
            
            .weather-card .weather-info {
                flex: 1;
            }
            
            .weather-card h3 {
                margin: 0 0 5px 0;
                font-size: 1.3rem;
                font-weight: 600;
            }
            
            .weather-card .temp {
                font-size: 2rem;
                font-weight: 700;
                margin: 5px 0;
            }
            
            .weather-card .desc {
                font-size: 1.1rem;
                opacity: 0.9;
                margin: 5px 0;
            }
            
            .weather-card .extra-info {
                display: flex;
                gap: 20px;
                margin-top: 10px;
                font-size: 0.9rem;
            }
            
            .weather-card .extra-info div {
                display: flex;
                align-items: center;
                gap: 5px;
            }
            
            /* Animações */
            @keyframes fadeInUp {
                from {
                    opacity: 0;
                    transform: translateY(20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            .animate-fade-in-up {
                animation: fadeInUp 0.5s ease forwards;
            }
            
            /* Dark Mode */
            body.dark-mode {
                background: #1a1a1a;
                color: #e0e0e0;
            }
            
            body.dark-mode .header-bar {
                background: #121212;
            }
            
            body.dark-mode .container {
                background: #212121;
                box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            }
            
            body.dark-mode .page-title {
                color: #64b5f6;
            }
            
            body.dark-mode .page-subtitle {
                color: #b0bec5;
            }
            
            body.dark-mode .form-control,
            body.dark-mode .form-select {
                background-color: #333;
                border-color: #444;
                color: #e0e0e0;
            }
            
            body.dark-mode .form-label {
                color: #e0e0e0;
            }
            
            body.dark-mode .form-text {
                color: #b0bec5;
            }
            
            body.dark-mode .table th {
                background-color: #333;
                color: #e0e0e0;
            }
            
            body.dark-mode .table td {
                border-color: #444;
            }
            
            body.dark-mode .alert-success {
                background-color: #1e3a2d;
                color: #81c784;
            }
            
            body.dark-mode .alert-danger {
                background-color: #3b1a1d;
                color: #e57373;
            }
            
            body.dark-mode .alert-warning {
                background-color: #3b2e16;
                color: #ffcc80;
            }
            
            body.dark-mode .notif-dropdown {
                background: #333;
                box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            }
            
            body.dark-mode .notif-dropdown .notif-header {
                border-color: #444;
                color: #e0e0e0;
            }
            
            body.dark-mode .notif-dropdown .notif-item {
                border-color: #444;
                color: #e0e0e0;
            }
            
            body.dark-mode .notif-dropdown .notif-item:hover {
                background: #424242;
            }
            
            body.dark-mode .notif-dropdown .notif-item .time {
                color: #b0bec5;
            }
            
            body.dark-mode .footer {
                color: #b0bec5;
            }
            
            body.dark-mode .dark-toggle {
                color: #64b5f6;
            }
            
            /* Responsividade */
            @media (max-width: 768px) {
                .container {
                    margin: 20px;
                    padding: 20px;
                }
                
                .header-bar {
                    flex-direction: column;
                    padding: 10px;
                }
                
                .logo-area {
                    width: 100%;
                    justify-content: space-between;
                    margin-bottom: 10px;
                }
                
                .menu-toggle {
                    display: block;
                    font-size: 1.5rem;
                    color: var(--text-on-dark);
                    cursor: pointer;
                }
                
                .nav-links {
                    width: 100%;
                    overflow-x: auto;
                    padding-bottom: 5px;
                    justify-content: flex-start;
                    display: none;
                }
                
                .nav-links.active {
                    display: flex;
                    flex-wrap: wrap;
                }
                
                .nav-link, .nav-btn {
                    font-size: 0.9rem;
                    padding: 6px 12px;
                }
                
                .btn-group {
                    flex-direction: column;
                }
                
                .weather-card {
                    flex-direction: column;
                    text-align: center;
                }
                
                .weather-card .extra-info {
                    justify-content: center;
                }
            }
        </style>
    </head>
    <body>
        <div class="header-bar">
            <div class="logo-area">
                <img src="assets/logo.svg" alt="Logo">
                <span class="title">Projeto Enchentes</span>
                <div class="menu-toggle" id="menuToggle">
                    <i class="fa fa-bars"></i>
                </div>
            </div>
            <div class="nav-links" id="navLinks">
                <a href="index.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
                    <i class="fa fa-home"></i> Início
                </a>
                <a href="monitor.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'monitor.php' ? 'active' : '' ?>">
                    <i class="fa fa-map-marker-alt"></i> Tempo Real
                </a>
                <a href="historico.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'historico.php' ? 'active' : '' ?>">
                    <i class="fa fa-history"></i> Histórico
                </a>
                <a href="perfil.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'perfil.php' ? 'active' : '' ?>">
                    <i class="fa fa-user-circle"></i> Meu Perfil
                </a>
                <a href="sobre.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'sobre.php' ? 'active' : '' ?>">
                    <i class="fa fa-info-circle"></i> Sobre
                </a>
                <a href="ajuda.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'ajuda.php' ? 'active' : '' ?>">
                    <i class="fa fa-question-circle"></i> Ajuda
                </a>
                <a href="mailto:suporte@enchentes.com?subject=Suporte%20-%20Projeto%20Enchentes" class="nav-btn suporte">
                    <i class="fa fa-life-ring"></i> Suporte
                </a>
                <a href="logout.php" class="nav-btn logout">
                    <i class="fa fa-sign-out-alt"></i> Sair
                </a>
                
                <!-- Notificações -->
                <span class="notif-bell" id="notifBell">
                    <i class="fa fa-bell"></i>
                    <?php if (count($notificacoes) > 0): ?>
                        <span class="badge"><?= count($notificacoes) ?></span>
                    <?php endif; ?>
                </span>
                
                <!-- Dark mode -->
                <button class="dark-toggle" id="darkToggle">
                    <i class="fa fa-moon"></i>
                </button>
                
                <!-- Avatar -->
                <span class="user-avatar" title="<?= htmlspecialchars($_SESSION['user_nome'] ?? '') ?>">
                    <?= isset($_SESSION['user_nome']) ? strtoupper(substr($_SESSION['user_nome'], 0, 1)) : '?' ?>
                    <?php if ($isAdmin): ?>
                        <span class="badge-admin">ADMIN</span>
                    <?php endif; ?>
                </span>
            </div>
        </div>
        
        <!-- Dropdown de notificações -->
        <div class="notif-dropdown" id="notifDropdown">
            <div class="notif-header">
                <span>Notificações</span>
                <span class="clear-all">Limpar todas</span>
            </div>
            <?php foreach ($notificacoes as $n): ?>
                <div class="notif-item <?= $n['tipo'] ?>">
                    <div class="icon">
                        <i class="fa fa-<?= $n['tipo'] === 'alerta' ? 'exclamation-triangle' : 'info-circle' ?>"></i>
                    </div>
                    <div class="content">
                        <div><?= $n['msg'] ?></div>
                        <div class="time"><?= $n['tempo'] ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if (isset($_SESSION['mensagem'])): ?>
            <div class="container" style="margin-top: 20px; margin-bottom: -20px; padding: 15px;">
                <div class="alert alert-<?= $_SESSION['mensagem_tipo'] ?? 'warning' ?>">
                    <div class="icon">
                        <i class="fa fa-<?= $_SESSION['mensagem_tipo'] == 'success' ? 'check-circle' : ($_SESSION['mensagem_tipo'] == 'danger' ? 'exclamation-circle' : 'info-circle') ?>"></i>
                    </div>
                    <div class="content"><?= $_SESSION['mensagem'] ?></div>
                </div>
            </div>
            <?php 
            // Limpar mensagem após exibição
            unset($_SESSION['mensagem']); 
            unset($_SESSION['mensagem_tipo']);
            ?>
        <?php endif; ?>
    <?php
}

// Função para carregar footer padrão
function carregarFooter($incluirMapa = false) {
    ?>
        <div class="footer">
            &copy; <?= date('Y') ?> Projeto Enchentes | Desenvolvido por Homemranha1122
        </div>
        
        <?php if ($incluirMapa): ?>
        <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
        <script src="https://cdn.jsdelivr.net/gh/mwasil/Leaflet.Rainviewer/leaflet.rainviewer.js"></script>
        <?php endif; ?>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Menu mobile toggle
            const menuToggle = document.getElementById('menuToggle');
            const navLinks = document.getElementById('navLinks');
            
            if (menuToggle) {
                menuToggle.addEventListener('click', function() {
                    navLinks.classList.toggle('active');
                });
            }
            
            // Notificações dropdown
            const notifBell = document.getElementById('notifBell');
            const notifDropdown = document.getElementById('notifDropdown');
            
            if (notifBell && notifDropdown) {
                notifBell.addEventListener('click', function(e) {
                    e.stopPropagation();
                    notifBell.classList.toggle('active');
                });
                
                document.addEventListener('click', function(e) {
                    if (!notifBell.contains(e.target) && !notifDropdown.contains(e.target)) {
                        notifBell.classList.remove('active');
                    }
                });
            }
            
            // Dark mode toggle
            const darkToggle = document.getElementById('darkToggle');
            const storedTheme = localStorage.getItem('theme') || 'light';
            
            if (darkToggle) {
                // Aplicar tema salvo
                if (storedTheme === 'dark') {
                    document.body.classList.add('dark-mode');
                    darkToggle.innerHTML = '<i class="fa fa-sun"></i>';
                }
                
                darkToggle.addEventListener('click', function() {
                    document.body.classList.toggle('dark-mode');
                    
                    if (document.body.classList.contains('dark-mode')) {
                        localStorage.setItem('theme', 'dark');
                        darkToggle.innerHTML = '<i class="fa fa-sun"></i>';
                    } else {
                        localStorage.setItem('theme', 'light');
                        darkToggle.innerHTML = '<i class="fa fa-moon"></i>';
                    }
                });
            }
            
            // Auto-hide alerts after 5 seconds
            const alerts = document.querySelectorAll('.alert');
            if (alerts.length > 0) {
                setTimeout(function() {
                    alerts.forEach(function(alert) {
                        alert.style.opacity = '0';
                        alert.style.transition = 'opacity 0.5s ease';
                        
                        setTimeout(function() {
                            alert.style.display = 'none';
                        }, 500);
                    });
                }, 5000);
            }
            
            // Animate elements when scrolled into view
            const animateElements = document.querySelectorAll('.animate-fade-in-up');
            
            if ('IntersectionObserver' in window) {
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            entry.target.style.opacity = 1;
                            entry.target.style.transform = 'translateY(0)';
                            observer.unobserve(entry.target);
                        }
                    });
                }, { threshold: 0.1 });
                
                animateElements.forEach(el => {
                    el.style.opacity = 0;
                    el.style.transform = 'translateY(20px)';
                    el.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                    observer.observe(el);
                });
            } else {
                // Fallback for browsers that don't support IntersectionObserver
                animateElements.forEach(el => {
                    el.style.opacity = 1;
                    el.style.transform = 'translateY(0)';
                });
            }
        });
        </script>
    </body>
    </html>
    <?php
}
?>  