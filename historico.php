<?php
session_start();
include_once 'conexao.php';
function h($t) { return htmlspecialchars(isset($t) ? $t : '', ENT_QUOTES, 'UTF-8'); }
$eventos = $conn->query("SELECT * FROM eventos ORDER BY data_evento DESC")->fetch_all(MYSQLI_ASSOC);
$total_eventos = count($eventos);
$intensidade_mais_comum = 'N/A'; $cidade_mais_ocorrencias = 'N/A';
$eventos_por_intensidade = []; $eventos_por_mes = array_fill(0, 12, 0);
if ($total_eventos > 0) {
    $intensidades = array_count_values(array_column($eventos, 'intensidade'));
    arsort($intensidades); $intensidade_mais_comum = key($intensidades);
    $cidades = array_count_values(array_column($eventos, 'cidade'));
    arsort($cidades); $cidade_mais_ocorrencias = key($cidades);
    $eventos_por_intensidade = $intensidades;
    foreach ($eventos as $evento) {
        $mes = (int)date('m', strtotime($evento['data_evento'])) - 1;
        if ($mes >= 0 && $mes < 12) $eventos_por_mes[$mes]++;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Olimclima</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🌦️</text></svg>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/font-awesome@4.7.0/css/font-awesome.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --font-family: 'Poppins', sans-serif;
            --primary-color: #3a86ff; --secondary-color: #ffbe0b; --danger-color: #fb5607;
            --light-bg: #f8f9fa; --dark-bg: #121212; --light-card-bg: #ffffff;
            --dark-card-bg: #1e1e1e; --light-text: #212529; --dark-text: #e9ecef;
            --border-light: #dee2e6; --border-dark: #495057; --shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        body { margin: 0; font-family: var(--font-family); background-color: var(--light-bg); color: var(--light-text); transition: background-color 0.3s, color 0.3s; }
        body.dark-mode { background-color: var(--dark-bg); color: var(--dark-text); }
        .header { background: #1f2328; color: #fff; padding: 10px 25px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #333; position: sticky; top: 0; z-index: 1000; }
        .logo { display: flex; align-items: center; gap: 12px; text-decoration: none; color: #fff; }
        .logo svg { width: 40px; height: 40px; }
        .logo h2 { margin: 0; font-weight: 600; font-size: 1.5rem; }
        .nav { display: flex; gap: 1rem; align-items: center; }
        .nav a { color: #fff; text-decoration: none; font-weight: 500; padding: 8px 12px; border-radius: 6px; transition: background-color 0.2s; }
        .nav a:hover { background-color: rgba(255,255,255,0.1); }
        .nav > span { opacity: 0.9; }
        .theme-toggle { cursor: pointer; font-size: 1.4em; padding: 8px; border-radius: 50%; transition: background-color 0.2s; }
        .theme-toggle:hover { background-color: rgba(255,255,255,0.1); }
        .dashboard-container { padding: 30px; max-width: 1200px; margin: auto; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 25px; margin-bottom: 25px; }
        .stat-card { background: var(--light-card-bg); padding: 25px; border-radius: 12px; box-shadow: var(--shadow); text-align: center; border: 1px solid var(--border-light); }
        body.dark-mode .stat-card { background: var(--dark-card-bg); border-color: var(--border-dark); }
        .stat-card h3 { font-size: 1rem; color: #6c757d; margin: 0 0 10px; font-weight: 500; text-transform: uppercase; }
        body.dark-mode .stat-card h3 { color: #adb5bd; }
        .stat-card p { font-size: 2.5rem; color: var(--primary-color); margin: 0; font-weight: 700; }
        .charts-grid { display: grid; grid-template-columns: 1fr 1.5fr; gap: 25px; margin-bottom: 25px; }
        .chart-container { background: var(--light-card-bg); padding: 20px; border-radius: 12px; box-shadow: var(--shadow); height: 400px; border: 1px solid var(--border-light); }
        body.dark-mode .chart-container { background: var(--dark-card-bg); border-color: var(--border-dark); }
        .table-container { background: var(--light-card-bg); padding: 20px; border-radius: 12px; box-shadow: var(--shadow); overflow-x: auto; border: 1px solid var(--border-light); }
        body.dark-mode .table-container { background: var(--dark-card-bg); border-color: var(--border-dark); }
        #searchInput { width: 100%; box-sizing: border-box; padding: 12px; margin-bottom: 15px; border: 1px solid var(--border-light); border-radius: 6px; background-color: var(--light-bg); color: var(--light-text); font-family: var(--font-family); }
        body.dark-mode #searchInput { background-color: var(--dark-bg); color: var(--dark-text); border-color: var(--border-dark); }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 12px 15px; border-bottom: 1px solid var(--border-light); }
        body.dark-mode th, body.dark-mode td { border-bottom-color: var(--border-dark); }
        th { font-weight: 600; font-size: 0.85rem; text-transform: uppercase; }
        td a { color: var(--primary-color); text-decoration: none; font-weight: 500; }
        td a:hover { text-decoration: underline; }
        .export-btn { background-color: #28a745; color: white; padding: 10px 15px; border: none; border-radius: 6px; cursor: pointer; float: right; margin-bottom: 15px; font-weight: 500; font-family: var(--font-family); }
        @media (max-width: 992px) { .charts-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <header class="header">
        <a href="index.php" class="logo">
             <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="g1" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" style="stop-color:#3a86ff;stop-opacity:1" /><stop offset="100%" style="stop-color:#8338ec;stop-opacity:1" /></linearGradient></defs><path d="M73.2,8.6C63.2-1.5,47.9-2.7,36.8,5.4C25.7,13.5,20.2,28,24.2,40.9c-3.7-2.6-8.2-4.1-13-4.1C5.1,36.8-2,44,0.2,52.1 c1.5,5.6,6.3,9.5,12,9.5h63.5c8.2,0,15.1-6.8,15.1-15.1C90.8,30.3,84.1,21.3,73.2,8.6z" fill="url(#g1)"/><circle cx="28" cy="72" r="10" fill="#ffbe0b"/><path d="M50,65 l5,10 h-10 z" fill="#3a86ff" /><path d="M65,75 l5,10 h-10 z" fill="#3a86ff" /></svg>
            <h2>Olimclima</h2>
        </a>
        <nav class="nav">
            <a href="index.php">Mapa</a>
            <span class="theme-toggle" id="themeBtn" title="Alternar Tema"><i class="fa fa-moon-o"></i></span>
            <?php if (isset($_SESSION['user_id'])): ?>
                <span>Olá, <?= h($_SESSION['user_name']) ?></span>
                <a href="logout.php">Sair</a>
            <?php else: ?>
                <a href="login.php">Login</a>
            <?php endif; ?>
        </nav>
    </header>
    <div class="dashboard-container">
        <div class="stats-grid">
            <div class="stat-card"><h3>Total de Eventos</h3><p><?= $total_eventos ?></p></div>
            <div class="stat-card"><h3>Cidade com Mais Ocorrências</h3><p><?= h($cidade_mais_ocorrencias) ?></p></div>
            <div class="stat-card"><h3>Intensidade Mais Comum</h3><p><?= h($intensidade_mais_comum) ?></p></div>
        </div>
        <div class="charts-grid">
            <div class="chart-container"><canvas id="intensityChart"></canvas></div>
            <div class="chart-container"><canvas id="monthlyChart"></canvas></div>
        </div>
        <div class="table-container">
            <button onclick="exportTableToCSV('eventos_olimclima.csv')" class="export-btn">Exportar para CSV</button>
            <input type="text" id="searchInput" onkeyup="filterTable()" placeholder="Buscar em todos os campos...">
            <table id="eventsTable">
                <thead>
                    <tr><th>Local</th><th>Cidade</th><th>UF</th><th>Tipo</th><th>Intensidade</th><th>Data</th><th>Observações</th></tr>
                </thead>
                <tbody>
                    <?php if ($total_eventos > 0): foreach ($eventos as $evento): ?>
                    <tr>
                        <td><a href="evento.php?id=<?= $evento['id'] ?>"><?= h($evento['local']) ?></a></td>
                        <td><?= h($evento['cidade']) ?></td><td><?= h($evento['uf']) ?></td><td><?= h($evento['tipo']) ?></td>
                        <td><?= h($evento['intensidade']) ?></td><td><?= date('d/m/Y H:i', strtotime($evento['data_evento'])) ?></td>
                        <td><?= h($evento['observacoes']) ?></td>
                    </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="7" style="text-align: center;">Nenhum evento registrado ainda.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const themeBtn = document.getElementById('themeBtn');
            const body = document.body;
            let currentCharts = {};

            function createCharts(theme) {
                const isDarkMode = theme === 'dark';
                const textColor = isDarkMode ? '#e9ecef' : '#212529';
                const gridColor = isDarkMode ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.05)';
                Chart.defaults.color = textColor;
                Chart.defaults.font.family = "'Poppins', sans-serif";
                
                if (currentCharts.intensity) currentCharts.intensity.destroy();
                if (currentCharts.monthly) currentCharts.monthly.destroy();

                const intensityLabels = <?= json_encode(array_keys($eventos_por_intensidade)) ?>;
                const intensityData = <?= json_encode(array_values($eventos_por_intensidade)) ?>;
                if(intensityLabels.length > 0) {
                    const colorMap = { 'Forte': '#fb5607', 'Moderada': '#ffbe0b', 'Fraca': '#8ac926' };
                    const intensityColors = intensityLabels.map(label => colorMap[label] || '#6c757d');
                    const intensityCtx = document.getElementById('intensityChart').getContext('2d');
                    currentCharts.intensity = new Chart(intensityCtx, {
                        type: 'doughnut',
                        data: {
                            labels: intensityLabels,
                            datasets: [{ data: intensityData, backgroundColor: intensityColors, borderColor: isDarkMode ? '#1e1e1e' : '#fff', borderWidth: 4 }]
                        },
                        options: {
                            responsive: true, maintainAspectRatio: false,
                            plugins: { legend: { position: 'top', labels: {font: {size: 14}} }, title: { display: true, text: 'Eventos por Intensidade', font: {size: 16, weight: '600'}} }
                        }
                    });
                }

                const monthlyData = <?= json_encode($eventos_por_mes) ?>;
                const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
                currentCharts.monthly = new Chart(monthlyCtx, {
                    type: 'bar',
                    data: {
                        labels: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'],
                        datasets: [{ label: 'Nº de Eventos', data: monthlyData, backgroundColor: '#3a86ff', borderRadius: 4 }]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        plugins: { legend: { display: false }, title: { display: true, text: 'Eventos por Mês', font: {size: 16, weight: '600'}} },
                        scales: { y: { beginAtZero: true, grid: { color: gridColor }, ticks: { color: textColor } }, x: { grid: { color: gridColor }, ticks: { color: textColor } } }
                    }
                });
            }

            function applyTheme(theme) {
                document.body.classList.toggle('dark-mode', theme === 'dark');
                themeBtn.querySelector('i').className = theme === 'dark' ? 'fa fa-sun-o' : 'fa fa-moon-o';
                createCharts(theme);
            }

            themeBtn.addEventListener('click', () => {
                const newTheme = body.classList.contains('dark-mode') ? 'light' : 'dark';
                localStorage.setItem('theme', newTheme);
                applyTheme(newTheme);
            });

            applyTheme(localStorage.getItem('theme') || 'light');
        });

        function filterTable() {
            const filter = document.getElementById("searchInput").value.toUpperCase();
            const tr = document.querySelectorAll("#eventsTable tbody tr");
            tr.forEach(row => {
                const text = row.textContent || row.innerText;
                row.style.display = text.toUpperCase().indexOf(filter) > -1 ? "" : "none";
            });
        }

        function exportTableToCSV(filename) {
            let csv = [Array.from(document.querySelectorAll("#eventsTable th")).map(th => `"${th.innerText}"`).join(',')];
            document.querySelectorAll("#eventsTable tbody tr").forEach(tr => {
                csv.push(Array.from(tr.querySelectorAll("td")).map(td => `"${td.innerText}"`).join(','));
            });
            let downloadLink = document.createElement("a");
            downloadLink.href = URL.createObjectURL(new Blob([csv.join("\n")], {type: "text/csv"}));
            downloadLink.download = filename;
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
        }
    </script>
</body>
</html>