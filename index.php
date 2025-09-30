<?php
session_start();
include_once 'conexao.php';

function h($t) {
    return htmlspecialchars(isset($t) ? $t : '', ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Olimclima - Monitoramento Climático Colaborativo</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🌦️</text></svg>">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/font-awesome@4.7.0/css/font-awesome.min.css">
    
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        :root {
            --font-family: 'Poppins', sans-serif;
            --primary-color: #3a86ff; --secondary-color: #ffbe0b; --danger-color: #fb5607;
            --success-color: #8338ec; --light-bg: #f8f9fa; --dark-bg: #121212;
            --light-card-bg: #ffffff; --dark-card-bg: #1e1e1e; --light-text: #212529;
            --dark-text: #e9ecef; --border-light: #dee2e6; --border-dark: #495057;
            --shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        body { margin: 0; font-family: var(--font-family); background-color: var(--light-bg); color: var(--light-text); transition: background-color 0.3s, color 0.3s; }
        .header { background: #1f2328; color: #fff; padding: 10px 25px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #333; position: sticky; top: 0; z-index: 1100; }
        .logo { display: flex; align-items: center; gap: 12px; text-decoration: none; color: #fff; }
        .logo svg { width: 40px; height: 40px; }
        .logo h2 { margin: 0; font-weight: 600; font-size: 1.5rem; }
        .nav { display: flex; gap: 1rem; align-items: center; }
        .nav a { color: #fff; text-decoration: none; font-weight: 500; padding: 8px 12px; border-radius: 6px; transition: background-color 0.2s; }
        .nav a:hover { background-color: rgba(255,255,255,0.1); }
        .nav > span { opacity: 0.9; }
        .theme-toggle { cursor: pointer; font-size: 1.4em; padding: 8px; border-radius: 50%; transition: background-color 0.2s; }
        .theme-toggle:hover { background-color: rgba(255,255,255,0.1); }
        
        .main { display: flex; height: calc(100vh - 71px); }
        .sidebar { width: 360px; flex-shrink: 0; background: var(--light-card-bg); padding: 20px; overflow-y: auto; border-right: 1px solid var(--border-light); }
        .content { flex-grow: 1; position: relative; }
        #map { width: 100%; height: 100%; background: #ddd; }

        body.dark-mode .sidebar { background: var(--dark-card-bg); border-right: 1px solid var(--border-dark); }
        .dark-mode .leaflet-tile { filter: brightness(0.6) invert(1) contrast(3) hue-rotate(200deg) saturate(0.3) brightness(0.7); }
        .control-section { margin-bottom: 25px; }
        .control-section h4 { margin: 0 0 12px 0; font-weight: 600; display: flex; justify-content: space-between; align-items: center; }
        .clear-filter-btn { font-size: 0.8rem; font-weight: 500; color: var(--primary-color); cursor: pointer; display: none; }
        .input-group { display: flex; gap: 8px; }
        .input-group input { flex: 1; padding: 10px 12px; border: 1px solid var(--border-light); border-radius: 6px; font-family: var(--font-family); background-color: var(--light-bg); color: var(--light-text); }
        body.dark-mode .input-group input { border-color: var(--border-dark); background-color: var(--dark-bg); color: var(--dark-text); }
        .button-primary { padding: 10px 18px; border: none; background: var(--primary-color); color: #fff; border-radius: 6px; cursor: pointer; font-weight: 500; font-family: var(--font-family); transition: background-color 0.2s; }
        .button-primary:hover { background-color: #3278e2; }
        #event-list-content, #inmet-alerts-content { max-height: 200px; overflow-y: auto; padding-right: 5px; }
        .evento-recente, .alerta-oficial { background: var(--light-bg); border: 1px solid var(--border-light); padding: 12px 15px; margin-bottom: 10px; border-radius: 8px; transition: all 0.2s; }
        body.dark-mode .evento-recente, body.dark-mode .alerta-oficial { background: var(--dark-bg); border-color: var(--border-dark); }
        .evento-recente:hover { transform: translateY(-2px); box-shadow: var(--shadow); cursor: pointer; }
        .evento-recente strong, .alerta-oficial strong { font-weight: 600; }
        .evento-recente-detalhes, .alerta-detalhes { display: flex; justify-content: space-between; align-items: center; margin-top: 8px; font-size: 0.85rem; }
        .badge-intensidade, .badge-severidade { padding: 4px 10px; border-radius: 15px; color: white; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; font-size: 0.7rem; }
        .badge-intensidade.forte, .badge-severidade.perigo { background-color: var(--danger-color); }
        .badge-intensidade.moderada, .badge-severidade.perigo_potencial { background-color: var(--secondary-color); color: #000; }
        .badge-intensidade.fraca { background-color: #8ac926; }
        .badge-severidade.grande_perigo { background-color: #d00000; }
        .verification-count { display: inline-flex; align-items: center; gap: 5px; color: #4caf50; font-weight: 600; }
        .modal-container { 
            display: none; 
            position: fixed; 
            top: 0; 
            left: 0; 
            width: 100%; 
            height: 100%; 
            background: rgba(0,0,0,0.6); 
            z-index: 1200; 
            justify-content: center; 
            align-items: center; 
        }
        .modal-content { 
            background: var(--light-card-bg); 
            padding: 25px; 
            border-radius: 12px; 
            box-shadow: 0 5px 25px rgba(0,0,0,0.2); 
            min-width: 420px;
            max-width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        body.dark-mode .modal-content { background: var(--dark-card-bg); }
        .modal-content h3 { margin: 0 0 20px 0; font-weight: 600; }
        .modal-content input, .modal-content textarea, .modal-content select { 
            margin-bottom: 12px; 
            width: 100%; 
            box-sizing: border-box; 
            padding: 12px; 
            background: var(--light-bg); 
            color: var(--light-text); 
            border: 1px solid var(--border-light); 
            border-radius: 6px; 
            font-family: var(--font-family); 
        }
        body.dark-mode .modal-content input, 
        body.dark-mode .modal-content textarea, 
        body.dark-mode .modal-content select { 
            background: var(--dark-bg); 
            color: var(--dark-text); 
            border-color: var(--border-dark); 
        }
        .form-label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
        }
        .modal-actions { 
            display: flex; 
            justify-content: flex-end; 
            gap: 12px; 
            margin-top: 20px; 
        }
        .btn-cancelar { background: #6c757d; }
        .btn-cancelar:hover { background: #5a6268; }
        #weatherResult { margin-top: 15px; }
        .weather-box { background: var(--primary-color); color: #fff; padding: 15px; border-radius: 8px; margin-bottom: 10px; font-weight: 500; }
        .forecast-day { background: var(--light-bg); border: 1px solid var(--border-light); padding: 10px; border-radius: 6px; margin-bottom: 5px; }
        body.dark-mode .forecast-day { background: var(--dark-bg); border-color: var(--border-dark); }
        .image-upload-container { margin-bottom: 15px; }
        .image-upload-preview { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 10px; }
        .image-preview-item { width: 80px; height: 80px; border-radius: 4px; object-fit: cover; border: 1px solid var(--border-light); }
        .verification-buttons { display: flex; gap: 10px; margin-top: 10px; }
        .btn-verify { display: flex; align-items: center; gap: 5px; padding: 6px 12px; border-radius: 4px; font-size: 0.9rem; cursor: pointer; transition: all 0.2s; }
        .btn-verify.active { background: #4caf50; color: white; }
        .btn-verify:hover:not(.active) { background: rgba(76, 175, 80, 0.2); }
        .image-gallery { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 10px; }
        .image-gallery img { width: 100px; height: 100px; object-fit: cover; border-radius: 4px; cursor: pointer; }
        
        /* Estilos para a notificação de sucesso */
        .notification {
            position: fixed;
            top: 80px;
            right: 20px;
            background-color: #4caf50;
            color: white;
            padding: 15px 25px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            z-index: 1500;
            transform: translateY(-20px);
            opacity: 0;
            transition: transform 0.3s, opacity 0.3s;
        }
        .notification.show {
            transform: translateY(0);
            opacity: 1;
        }
    </style>
</head>
<body>
    <header class="header">
        <a href="index.php" class="logo">
            <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="g1" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" style="stop-color:#3a86ff;stop-opacity:1" /><stop offset="100%" style="stop-color:#8338ec;stop-opacity:1" /></linearGradient></defs><path d="M73.2,8.6C63.2-1.5,47.9-2.7,36.8,5.4C25.7,13.5,20.2,28,24.2,40.9c-3.7-2.6-8.2-4.1-13-4.1C5.1,36.8-2,44,0.2,52.1 c1.5,5.6,6.3,9.5,12,9.5h63.5c8.2,0,15.1-6.8,15.1-15.1C90.8,30.3,84.1,21.3,73.2,8.6z" fill="url(#g1)"/><circle cx="28" cy="72" r="10" fill="#ffbe0b"/><path d="M50,65 l5,10 h-10 z" fill="#3a86ff" /><path d="M65,75 l5,10 h-10 z" fill="#3a86ff" /></svg>
            <h2>Olimclima</h2>
        </a>
        <nav class="nav">
            <a href="historico.php">Dashboard</a>
            <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?><a href="admin/index.php">Admin</a><?php endif; ?>
            <span class="theme-toggle" id="themeBtn" title="Alternar Tema"><i class="fa fa-moon-o"></i></span>
            <?php if (isset($_SESSION['user_id'])): ?><span>Olá, <?= h($_SESSION['user_name']) ?></span><a href="logout.php">Sair</a><?php else: ?><a href="login.php">Login</a><?php endif; ?>
        </nav>
    </header>
    
    <!-- Notificação de sucesso -->
    <?php if (isset($_GET['evento']) && $_GET['evento'] == 'sucesso'): ?>
    <div class="notification" id="successNotification">
        Evento cadastrado com sucesso!
    </div>
    <?php endif; ?>
    
    <div class="main">
        <aside class="sidebar">
            <div id="inmet-alerts-section" class="control-section">
                <h4><i class="fa fa-bullhorn" style="color:var(--danger-color)"></i> Alertas Oficiais (INMET)</h4>
                <div id="inmet-alerts-content">Carregando alertas...</div>
            </div>

            <div class="control-section">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <button id="btn-add-event" class="button-primary" style="width: 100%; padding: 12px; font-size: 1rem;"><i class="fa fa-plus-circle"></i> Cadastrar Novo Evento</button>
                <?php else: ?>
                    <div style="text-align: center; padding: 10px; background: var(--light-bg); border-radius: 8px;"><a href="login.php" style="text-decoration:none; font-weight: 500; color: var(--primary-color);">Faça login para participar</a></div>
                <?php endif; ?>
            </div>
            
            <div class="control-section">
                <h4>Pesquisar Local</h4>
                <div class="input-group">
                    <input type="text" id="buscaLocal" placeholder="Digite cidade ou UF" aria-label="Pesquisar Local" onkeyup="if(event.key==='Enter') buscarLocal();">
                    <button onclick="buscarLocal()" class="button-primary"><i class="fa fa-search"></i></button>
                </div>
                <div id="weatherResult"></div>
            </div>

            <div class="control-section">
                <h4>Filtrar por Intensidade</h4>
                <div class="filter-group" id="intensity-filters">
                    <label><input type="checkbox" value="Forte" checked> Forte</label>
                    <label><input type="checkbox" value="Moderada" checked> Moderada</label>
                    <label><input type="checkbox" value="Fraca" checked> Fraca</label>
                </div>
            </div>

            <div class="control-section">
                <h4 id="event-list-title-container">
                    <span>Eventos Recentes</span>
                    <span id="clear-event-filter" class="clear-filter-btn" onclick="resetEventList()">Ver todos</span>
                </h4>
                <div id="event-list-content">Carregando eventos...</div>
            </div>
        </aside>
        <section class="content"><div id="map"></div></section>
    </div>

    <!-- Modal para cadastro de evento -->
    <div id="cadastroEventoModal" class="modal-container">
        <div class="modal-content">
            <h3>Cadastrar Novo Evento</h3>
            <p>Ponto selecionado: <span id="coordenadasPonto"></span></p>
            
            <form id="formCadastroEvento" action="salvar_evento.php" method="post" enctype="multipart/form-data">
                <input type="hidden" id="latitude" name="latitude">
                <input type="hidden" id="longitude" name="longitude">
                
                <label class="form-label" for="local">Local:</label>
                <input type="text" id="local" name="local" required placeholder="Nome do local">
                
                <label class="form-label" for="cidade">Cidade:</label>
                <input type="text" id="cidade" name="cidade" required>
                
                <label class="form-label" for="uf">UF:</label>
                <select id="uf" name="uf" required>
                    <option value="">Selecione...</option>
                    <option value="AC">AC</option>
                    <option value="AL">AL</option>
                    <option value="AP">AP</option>
                    <option value="AM">AM</option>
                    <option value="BA">BA</option>
                    <option value="CE">CE</option>
                    <option value="DF">DF</option>
                    <option value="ES">ES</option>
                    <option value="GO">GO</option>
                    <option value="MA">MA</option>
                    <option value="MT">MT</option>
                    <option value="MS">MS</option>
                    <option value="MG">MG</option>
                    <option value="PA">PA</option>
                    <option value="PB">PB</option>
                    <option value="PR">PR</option>
                    <option value="PE">PE</option>
                    <option value="PI">PI</option>
                    <option value="RJ">RJ</option>
                    <option value="RN">RN</option>
                    <option value="RS">RS</option>
                    <option value="RO">RO</option>
                    <option value="RR">RR</option>
                    <option value="SC">SC</option>
                    <option value="SP">SP</option>
                    <option value="SE">SE</option>
                    <option value="TO">TO</option>
                </select>
                
                <label class="form-label" for="tipo">Tipo:</label>
                <select id="tipo" name="tipo" required>
                    <option value="Chuva">Chuva</option>
                    <option value="Enchente">Enchente</option>
                    <option value="Alagamento">Alagamento</option>
                    <option value="Deslizamento">Deslizamento</option>
                </select>
                
                <label class="form-label" for="intensidade">Intensidade:</label>
                <select id="intensidade" name="intensidade" required>
                    <option value="Fraca">Fraca</option>
                    <option value="Moderada">Moderada</option>
                    <option value="Forte">Forte</option>
                </select>
                
                <label class="form-label" for="observacoes">Observações:</label>
                <textarea id="observacoes" name="observacoes" rows="3" placeholder="Detalhes adicionais sobre o evento..."></textarea>
                
                <div class="image-upload-container">
                    <label class="form-label" for="imagens">Imagens (opcional):</label>
                    <input type="file" id="imagens" name="imagens[]" multiple accept="image/*" onchange="previewImagens()">
                    <div class="image-upload-preview" id="imagemPreview"></div>
                </div>
                
                <label class="form-label" for="data">Data/Hora:</label>
                <input type="datetime-local" id="data" name="data" required>
                
                <div class="modal-actions">
                    <button type="button" class="button-primary btn-cancelar" onclick="fecharCadastroEventoModal()">Cancelar</button>
                    <button type="submit" class="button-primary">Cadastrar Evento</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal para detalhes do evento -->
    <div id="detalhesEventoModal" class="modal-container">
        <div class="modal-content">
            <h3 id="evento-titulo">Detalhes do Evento</h3>
            <div id="evento-detalhes"></div>
            <div class="image-gallery" id="evento-imagens"></div>
            
            <div id="verificacao-section">
                <h4>Verificação Comunitária</h4>
                <p>Este evento foi verificado por <span id="verificacao-count">0</span> pessoas.</p>
                
                <?php if (isset($_SESSION['user_id'])): ?>
                <div class="verification-buttons">
                    <button id="btn-eu-vi" class="btn-verify" onclick="verificarEvento('viu')"><i class="fa fa-eye"></i> Eu vi</button>
                    <button id="btn-confirmo" class="btn-verify" onclick="verificarEvento('confirma')"><i class="fa fa-check"></i> Confirmo</button>
                </div>
                <?php else: ?>
                <p><a href="login.php">Faça login</a> para verificar este evento.</p>
                <?php endif; ?>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="button-primary btn-cancelar" onclick="fecharDetalhesEventoModal()">Fechar</button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Mostrar a notificação de sucesso se existir
            const notification = document.getElementById('successNotification');
            if (notification) {
                notification.classList.add('show');
                setTimeout(() => {
                    notification.classList.remove('show');
                    // Após a animação de saída, remover o parâmetro da URL
                    setTimeout(() => {
                        let url = new URL(window.location.href);
                        url.searchParams.delete('evento');
                        history.replaceState(null, '', url);
                    }, 300);
                }, 3000);
            }

            // Configuração do mapa
            const map = L.map('map').setView([-15.78, -47.92], 5);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap', maxZoom: 19 }).addTo(map);
            const markerLayer = L.layerGroup().addTo(map);
            let tempEventMarker = null, userMarker = null, allEvents = [], currentEventId = null;

            // Definição de ícones personalizados para os marcadores
            const icons = {
                'Forte': L.icon({ iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-red.png', shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png', iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34], shadowSize: [41, 41] }),
                'Moderada': L.icon({ iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-orange.png', shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png', iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34], shadowSize: [41, 41] }),
                'Fraca': L.icon({ iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-yellow.png', shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png', iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34], shadowSize: [41, 41] })
            };

            // Função para normalização de texto para comparações
            function normalizeText(text) {
                if (!text) return '';
                return text.toString().toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
            }

            // Função para renderizar a lista de eventos
            function renderEventList(eventsToRender) {
                const listContent = document.getElementById('event-list-content');
                listContent.innerHTML = '';
                if (eventsToRender.length === 0) {
                    const cityFilter = document.getElementById('event-list-title-container').querySelector('span').dataset.city;
                    if (cityFilter) {
                        listContent.innerHTML = `<div style="padding:10px; text-align:center; opacity:0.7;">Nenhum evento recente para ${cityFilter}.</div>`;
                    } else {
                        listContent.innerHTML = '<div style="padding:10px; text-align:center; opacity:0.7;">Nenhum evento para exibir.</div>';
                    }
                    return;
                }
                const activeIntensities = Array.from(document.querySelectorAll('#intensity-filters input:checked')).map(cb => cb.value);
                const filteredByIntensity = eventsToRender.filter(ev => activeIntensities.includes(ev.intensidade));

                if (filteredByIntensity.length === 0) {
                     listContent.innerHTML = '<div style="padding:10px; text-align:center; opacity:0.7;">Nenhum evento corresponde aos filtros de intensidade.</div>';
                     return;
                }

                filteredByIntensity.forEach(ev => {
                    const el = document.createElement('div');
                    el.className = 'evento-recente';
                    el.dataset.id = ev.id;
                    el.dataset.lat = ev.latitude; el.dataset.lng = ev.longitude;
                    let verificationText = ev.verificacoes > 0 ? `<div class="verification-count"><i class="fa fa-check-circle"></i> ${ev.verificacoes}</div>` : '';
                    el.innerHTML = `<strong>${ev.tipo} em ${ev.cidade}</strong><div class="evento-recente-detalhes"><span class="badge-intensidade ${ev.intensidade.toLowerCase()}">${ev.intensidade}</span><small>${new Date(ev.data_evento).toLocaleString('pt-BR', {day:'2-digit', month:'2-digit', hour:'2-digit', minute:'2-digit'})}</small>${verificationText}</div>`;
                    el.addEventListener('click', () => {
                        map.setView([ev.latitude, ev.longitude], 15);
                        abrirDetalhesEvento(ev.id);
                    });
                    listContent.appendChild(el);
                });
            }

            // Função para atualizar os marcadores no mapa
            function updateMarkers() {
                markerLayer.clearLayers();
                const activeIntensities = Array.from(document.querySelectorAll('#intensity-filters input:checked')).map(cb => cb.value);
                const filteredEvents = allEvents.filter(ev => activeIntensities.includes(ev.intensidade));
                
                filteredEvents.forEach(ev => {
                    const icon = icons[ev.intensidade] || icons['Fraca'];
                    const marker = L.marker([ev.latitude, ev.longitude], { icon: icon })
                        .addTo(markerLayer)
                        .bindPopup(`<b>${ev.tipo} em ${ev.cidade}</b><br>${ev.local}<br><button class="popup-btn-detalhes" onclick="abrirDetalhesEvento(${ev.id})">Ver detalhes</button>`);
                    
                    // Armazena o ID do evento no marcador
                    marker._eventoId = ev.id;
                    
                    // Adiciona listener para abrir detalhes ao clicar
                    marker.on('click', function() {
                        setTimeout(() => {
                            const popupBtn = document.querySelector('.popup-btn-detalhes');
                            if (popupBtn) {
                                popupBtn.addEventListener('click', (e) => {
                                    e.preventDefault();
                                    abrirDetalhesEvento(marker._eventoId);
                                });
                            }
                        }, 100);
                    });
                });
            }
            
            // Função para mostrar preview das imagens
            window.previewImagens = function() {
                const input = document.getElementById('imagens');
                const preview = document.getElementById('imagemPreview');
                preview.innerHTML = '';
                
                if (input.files) {
                    Array.from(input.files).forEach(file => {
                        if (file.type.match('image.*')) {
                            const reader = new FileReader();
                            reader.onload = function(e) {
                                const img = document.createElement('img');
                                img.src = e.target.result;
                                img.className = 'image-preview-item';
                                preview.appendChild(img);
                            }
                            reader.readAsDataURL(file);
                        }
                    });
                }
            }
            
            // Função para abrir detalhes de um evento
            window.abrirDetalhesEvento = function(id) {
                fetch(`get_evento.php?id=${id}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data) {
                            currentEventId = data.id;
                            
                            document.getElementById('evento-titulo').textContent = `${data.tipo} em ${data.cidade}`;
                            
                            let detalhesHtml = `
                                <p><strong>Local:</strong> ${data.local}</p>
                                <p><strong>Data/Hora:</strong> ${new Date(data.data_evento).toLocaleString('pt-BR')}</p>
                                <p><strong>Intensidade:</strong> <span class="badge-intensidade ${data.intensidade.toLowerCase()}">${data.intensidade}</span></p>
                                <p><strong>Reportado por:</strong> ${data.nome_usuario}</p>
                            `;
                            
                            if (data.observacoes) {
                                detalhesHtml += `<p><strong>Observações:</strong> ${data.observacoes}</p>`;
                            }
                            
                            document.getElementById('evento-detalhes').innerHTML = detalhesHtml;
                            
                            // Carrega imagens se houver
                            const imagensContainer = document.getElementById('evento-imagens');
                            imagensContainer.innerHTML = '';
                            
                            if (data.imagens && data.imagens.length > 0) {
                                data.imagens.forEach(img => {
                                    const imgEl = document.createElement('img');
                                    imgEl.src = img.caminho_imagem;
                                    imgEl.alt = "Imagem do evento";
                                    imgEl.onclick = function() {
                                        window.open(img.caminho_imagem, '_blank');
                                    };
                                    imagensContainer.appendChild(imgEl);
                                });
                            } else {
                                imagensContainer.innerHTML = '<p>Nenhuma imagem disponível</p>';
                            }
                            
                            // Atualiza a contagem de verificações
                            document.getElementById('verificacao-count').textContent = data.verificacoes || '0';
                            
                            // Verifica se o usuário já verificou este evento
                            if (data.usuario_verificou) {
                                if (data.usuario_verificou.includes('viu')) {
                                    document.getElementById('btn-eu-vi').classList.add('active');
                                }
                                if (data.usuario_verificou.includes('confirma')) {
                                    document.getElementById('btn-confirmo').classList.add('active');
                                }
                            } else {
                                // Remove classes active se existirem
                                document.getElementById('btn-eu-vi')?.classList.remove('active');
                                document.getElementById('btn-confirmo')?.classList.remove('active');
                            }
                            
                            document.getElementById('detalhesEventoModal').style.display = 'flex';
                        }
                    })
                    .catch(error => console.error('Erro ao carregar detalhes:', error));
            }
            
            // Função para verificar um evento
            window.verificarEvento = function(tipo) {
                if (!currentEventId) return;
                
                const btn = tipo === 'viu' ? document.getElementById('btn-eu-vi') : document.getElementById('btn-confirmo');
                const isActive = btn.classList.contains('active');
                
                fetch('verificar_evento.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `id_evento=${currentEventId}&tipo=${tipo}&acao=${isActive ? 'remover' : 'adicionar'}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Toggle classe active no botão
                        if (isActive) {
                            btn.classList.remove('active');
                        } else {
                            btn.classList.add('active');
                        }
                        
                        // Atualiza a contagem de verificações
                        document.getElementById('verificacao-count').textContent = data.total_verificacoes;
                        
                        // Atualiza o evento na lista de allEvents
                        const eventIndex = allEvents.findIndex(e => e.id === currentEventId);
                        if (eventIndex !== -1) {
                            allEvents[eventIndex].verificacoes = data.total_verificacoes;
                            // Re-renderiza a lista e os marcadores
                            renderEventList(allEvents);
                            updateMarkers();
                        }
                    } else {
                        alert(data.message || 'Erro ao verificar evento');
                    }
                })
                .catch(error => console.error('Erro:', error));
            }
            
            // Função de geolocalização e filtro automático
            function autolocateAndFilter() {
                if (!navigator.geolocation) {
                    console.log("Geolocalização não é suportada por este navegador. Exibindo todos os eventos.");
                    renderEventList(allEvents);
                    return;
                }

                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        const { latitude, longitude } = position.coords;
                        
                        map.setView([latitude, longitude], 13);
                        if(userMarker) map.removeLayer(userMarker);
                        userMarker = L.marker([latitude, longitude]).addTo(map).bindPopup("Sua localização").openPopup();

                        fetch(`proxy_nominatim_reverse.php?lat=${latitude}&lon=${longitude}`)
                            .then(res => res.json())
                            .then(data => {
                                if (data.address) {
                                    const cityName = data.address.city || data.address.town || data.address.village;
                                    if (cityName) {
                                        filterEventsByCity(cityName);
                                        buscarPrevisaoTempo(latitude, longitude, cityName);
                                    } else {
                                        renderEventList(allEvents);
                                    }
                                }
                            })
                            .catch(error => {
                                console.error("Erro ao obter nome da cidade:", error);
                                renderEventList(allEvents);
                            });
                    },
                    (error) => {
                        console.error("Erro ao obter a localização: ", error.message);
                        renderEventList(allEvents);
                    }
                );
            }

            // Função para filtrar eventos por cidade
            function filterEventsByCity(cityName) {
                const normalizedCityName = normalizeText(cityName);
                const cityEvents = allEvents.filter(ev => normalizeText(ev.cidade) === normalizedCityName);
                
                const titleEl = document.getElementById('event-list-title-container').querySelector('span');
                titleEl.textContent = `Eventos em ${cityName}`;
                titleEl.dataset.city = cityName;
                document.getElementById('clear-event-filter').style.display = 'inline';
                
                renderEventList(cityEvents);
            }

            // Carrega todos os eventos e localiza o usuário
            fetch('get_eventos.php').then(r => r.json()).then(data => {
                allEvents = data;
                updateMarkers();
                autolocateAndFilter();
            });

            // Configuração do tema
            const themeBtn = document.getElementById('themeBtn');
            function applyTheme(theme) {
                document.body.classList.toggle('dark-mode', theme === 'dark');
                themeBtn.querySelector('i').className = theme === 'dark' ? 'fa fa-sun-o' : 'fa fa-moon-o';
            }
            themeBtn.addEventListener('click', () => {
                const newTheme = document.body.classList.contains('dark-mode') ? 'light' : 'dark';
                localStorage.setItem('theme', newTheme);
                applyTheme(newTheme);
            });
            applyTheme(localStorage.getItem('theme'));

            // Tratamento do botão de adicionar evento
            const btnAddEvent = document.getElementById('btn-add-event');
            if (btnAddEvent) {
                btnAddEvent.addEventListener('click', () => {
                    document.getElementById('map').style.cursor = 'crosshair';
                    map.once('click', handleMapClickForEvent);
                    
                    // Mostrar instrução ao usuário
                    alert('Clique no mapa para selecionar o local do evento.');
                });
            }

            // Função para lidar com o clique no mapa para adicionar evento
            function handleMapClickForEvent(e) {
    const lat = e.latlng.lat;
    const lng = e.latlng.lng;

    document.getElementById('latitude').value = lat;
    document.getElementById('longitude').value = lng;
    document.getElementById('coordenadasPonto').textContent = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;

    if (tempEventMarker) {
        map.removeLayer(tempEventMarker);
    }
    tempEventMarker = L.marker([lat, lng]).addTo(map);

    // Preenche a data atual no formulário
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    document.getElementById('data').value = `${year}-${month}-${day}T${hours}:${minutes}`;

    // Geocoding reverso para preencher local, cidade e UF automaticamente
    fetch(`proxy_nominatim_reverse.php?lat=${lat}&lon=${lng}`)
        .then(response => response.json())
        .then(data => {
            if (data && data.address) {
                // Cidade
                if (data.address.city) document.getElementById('cidade').value = data.address.city;
                else if (data.address.town) document.getElementById('cidade').value = data.address.town;
                else if (data.address.village) document.getElementById('cidade').value = data.address.village;

                // UF automático
                if (data.address.state) {
                    const stateName = data.address.state.trim().toLowerCase();
                    const stateMap = {
                        "acre": "AC", "alagoas": "AL", "amapá": "AP", "amazonas": "AM", "bahia": "BA", "ceará": "CE",
                        "distrito federal": "DF", "espírito santo": "ES", "goiás": "GO", "maranhão": "MA", "mato grosso": "MT",
                        "mato grosso do sul": "MS", "minas gerais": "MG", "pará": "PA", "paraíba": "PB", "paraná": "PR",
                        "pernambuco": "PE", "piauí": "PI", "rio de janeiro": "RJ", "rio grande do norte": "RN",
                        "rio grande do sul": "RS", "rondônia": "RO", "roraima": "RR", "santa catarina": "SC",
                        "são paulo": "SP", "sergipe": "SE", "tocantins": "TO"
                    };
                    let uf = stateMap[stateName];
                    if (!uf) uf = stateName.toUpperCase().slice(0,2);

                    const stateSelect = document.getElementById('uf');
                    for (let i = 0; i < stateSelect.options.length; i++) {
                        if (stateSelect.options[i].value === uf) {
                            stateSelect.selectedIndex = i;
                            break;
                        }
                    }
                }

                // Local
                if (data.address.road || data.address.neighbourhood) {
                    let local = ''; 
                    if (data.address.road) local += data.address.road;
                    if (data.address.neighbourhood) {
                        if (local) local += ', ';
                        local += data.address.neighbourhood;
                    }
                    document.getElementById('local').value = local;
                }
            }
        })
        .catch(error => console.error('Erro ao obter dados do local:', error));

    document.getElementById('cadastroEventoModal').style.display = 'flex';
    document.getElementById('map').style.cursor = '';
}
            // Função para fechar o modal de cadastro
            window.fecharCadastroEventoModal = function() {
                document.getElementById('cadastroEventoModal').style.display = 'none';
                
                // Remove o marcador temporário
                if (tempEventMarker) {
                    map.removeLayer(tempEventMarker);
                    tempEventMarker = null;
                }
                
                // Limpa o formulário
                document.getElementById('formCadastroEvento').reset();
                document.getElementById('imagemPreview').innerHTML = '';
            };
            
            // Função para fechar o modal de detalhes
            window.fecharDetalhesEventoModal = function() {
                document.getElementById('detalhesEventoModal').style.display = 'none';
                currentEventId = null;
            };

            // Funções para o clima
            function getWeatherIcon(code) {
                 if ([0, 1].includes(code)) return '☀️'; if ([2].includes(code)) return '⛅️'; if ([3].includes(code)) return '☁️'; if ([45, 48].includes(code)) return '🌫️'; if ([51, 53, 55, 61, 63, 65, 80, 81, 82].includes(code)) return '🌧️'; if ([66, 67].includes(code)) return '🌨️'; if ([71, 73, 75, 77, 85, 86].includes(code)) return '❄️'; if ([95, 96, 99].includes(code)) return '⛈️'; return '🌍';
            }

            // Função para buscar previsão do tempo
            function buscarPrevisaoTempo(lat, lon, locationName) {
                const url = `https://api.open-meteo.com/v1/forecast?latitude=${lat}&longitude=${lon}&current=temperature_2m,weather_code&daily=weather_code,temperature_2m_max,temperature_2m_min,precipitation_probability_max&timezone=auto&forecast_days=3`;
                fetch(url).then(resp => resp.json()).then(data => {
                    let currentHtml = `<div class='weather-box'><strong>Tempo em ${locationName}:</strong> ${getWeatherIcon(data.current.weather_code)} ${data.current.temperature_2m}°C</div>`;
                    let forecastHtml = ``;
                    data.daily.time.forEach((time, index) => {
                        const date = new Date(time + 'T00:00:00Z');
                        const day = date.toLocaleDateString('pt-BR', { weekday: 'short', timeZone: 'UTC' });
                        forecastHtml += `<div class='forecast-day'><b>${day.charAt(0).toUpperCase() + day.slice(1)}.</b>: ${getWeatherIcon(data.daily.weather_code[index])} ${data.daily.temperature_2m_min[index]}°C / ${data.daily.temperature_2m_max[index]}°C (chuva: ${data.daily.precipitation_probability_max[index]}%)</div>`;
                    });
                    document.getElementById('weatherResult').innerHTML = currentHtml + forecastHtml;
                }).catch(error => { 
                    document.getElementById('weatherResult').innerHTML = "<div class='forecast-day'>Não foi possível buscar a previsão do tempo.</div>";
                });
            }

            // Função para resetar a lista de eventos
            window.resetEventList = function() {
                const titleEl = document.getElementById('event-list-title-container').querySelector('span');
                titleEl.textContent = 'Eventos Recentes';
                titleEl.removeAttribute('data-city');
                document.getElementById('clear-event-filter').style.display = 'none';
                renderEventList(allEvents);
            }

            // Função para carregar alertas do INMET
            function loadInmetAlerts() {
                fetch('get_alertas_oficiais.php').then(response => response.json()).then(data => {
                    const alertsContainer = document.getElementById('inmet-alerts-content');
                    
                    if (data.length === 0) {
                        alertsContainer.innerHTML = '<div style="padding:10px; text-align:center; opacity:0.7;">Nenhum alerta oficial ativo.</div>';
                        return;
                    }
                    
                    let alertsHTML = '';
                    data.forEach(alerta => {
                        const severidadeClass = alerta.severidade.toLowerCase().replace(' ', '_');
                        const dataFim = new Date(alerta.data_fim);
                        alertsHTML += `
                            <div class="alerta-oficial">
                                <strong>${alerta.titulo}</strong>
                                <div class="alerta-detalhes">
                                    <span class="badge-severidade ${severidadeClass}">${alerta.severidade}</span>
                                    <small>Até ${dataFim.toLocaleDateString('pt-BR')}</small>
                                </div>
                            </div>
                        `;
                    });
                    alertsContainer.innerHTML = alertsHTML;
                }).catch(error => {
                    console.error('Erro ao carregar alertas:', error);
                    document.getElementById('inmet-alerts-content').innerHTML = '<div style="padding:10px; text-align:center; opacity:0.7;">Erro ao carregar alertas.</div>';
                });
            }

            // Inicializa a carga de alertas
            loadInmetAlerts();

            // Configuração dos filtros de intensidade
            document.querySelectorAll('#intensity-filters input').forEach(cb => {
                cb.addEventListener('change', () => {
                    updateMarkers();
                    const cityFilter = document.getElementById('event-list-title-container').querySelector('span').dataset.city;
                    if (cityFilter) {
                        filterEventsByCity(cityFilter);
                    } else {
                        renderEventList(allEvents);
                    }
                });
            });

            // Função para buscar local
            window.buscarLocal = function() {
                 const termo = document.getElementById('buscaLocal').value;
                 if (!termo) return;
                 document.getElementById('weatherResult').innerHTML = 'Buscando...';
                 
                 fetch(`proxy_nominatim.php?term=${encodeURIComponent(termo)}`)
                    .then(response => response.json())
                    .then(data => {
                        if(data && data.length > 0) {
                            const location = data[0];
                            const cityName = location.address?.city || location.address?.town || location.address?.village;
                            
                            map.setView([location.lat, location.lon], 13);
                            if(userMarker) map.removeLayer(userMarker);
                            userMarker = L.marker([location.lat, location.lon]).addTo(map).bindPopup(location.display_name).openPopup();
                            
                            buscarPrevisaoTempo(location.lat, location.lon, cityName || termo);

                            if (cityName) {
                                filterEventsByCity(cityName);
                            } else {
                                resetEventList();
                            }
                        } else {
                            document.getElementById('weatherResult').innerHTML = "<div class='forecast-day'>Local não encontrado.</div>";
                            resetEventList();
                        }
                    }).catch(error => {
                        console.error('Erro na busca de local:', error);
                        document.getElementById('weatherResult').innerHTML = "<div class='forecast-day'>Erro ao buscar local.</div>";
                        resetEventList();
                    });
            }
        });
    </script>
</body>
</html>