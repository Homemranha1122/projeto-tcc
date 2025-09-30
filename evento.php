<?php
session_start();
include_once 'conexao.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$evento_id = $_GET['id'];
$stmt = $conn->prepare("SELECT e.*, u.nome as nome_usuario FROM eventos e LEFT JOIN usuarios u ON e.user_id = u.id WHERE e.id = ?");
$stmt->bind_param("i", $evento_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: index.php');
    exit();
}

$evento = $result->fetch_assoc();

// Verificar se o usuário atual já verificou este evento
$verificou = false;
$tipo_verificacao = '';
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT tipo_verificacao FROM evento_verificacoes WHERE evento_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $evento_id, $_SESSION['user_id']);
    $stmt->execute();
    $result_verificacao = $stmt->get_result();
    if ($result_verificacao->num_rows > 0) {
        $verificou = true;
        $tipo_verificacao = $result_verificacao->fetch_assoc()['tipo_verificacao'];
    }
}

// Contar verificações
$stmt = $conn->prepare("SELECT tipo_verificacao, COUNT(*) as total FROM evento_verificacoes WHERE evento_id = ? GROUP BY tipo_verificacao");
$stmt->bind_param("i", $evento_id);
$stmt->execute();
$result_contagem = $stmt->get_result();
$contagens = ['eu_vi' => 0, 'confirmo' => 0, 'nao_ocorreu' => 0];
while ($row = $result_contagem->fetch_assoc()) {
    $contagens[$row['tipo_verificacao']] = $row['total'];
}

// Processar verificação
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id']) && isset($_POST['verificacao'])) {
    $tipo_verificacao = $_POST['verificacao'];
    $comentario = isset($_POST['comentario']) ? $_POST['comentario'] : '';
    
    if ($verificou) {
        $stmt = $conn->prepare("UPDATE evento_verificacoes SET tipo_verificacao = ?, comentario = ? WHERE evento_id = ? AND user_id = ?");
        $stmt->bind_param("ssii", $tipo_verificacao, $comentario, $evento_id, $_SESSION['user_id']);
    } else {
        $stmt = $conn->prepare("INSERT INTO evento_verificacoes (evento_id, user_id, tipo_verificacao, comentario) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiss", $evento_id, $_SESSION['user_id'], $tipo_verificacao, $comentario);
    }
    
    if ($stmt->execute()) {
        header("Location: evento.php?id=" . $evento_id);
        exit();
    }
}

// Processar upload de mídias
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tipo_midia']) && isset($_FILES['midia'])) {
    $tipo_midia = $_POST['tipo_midia']; // 'foto' ou 'video'
    $upload_dir = 'uploads/eventos/';
    $file_name = basename($_FILES['midia']['name']);
    $file_path = $upload_dir . $file_name;

    // Verifica se o diretório de upload existe, caso contrário, cria
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Mover o arquivo para o diretório de uploads
    if (move_uploaded_file($_FILES['midia']['tmp_name'], $file_path)) {
        $stmt = $conn->prepare("INSERT INTO evento_midia (evento_id, tipo_midia, arquivo_path, data_upload) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iss", $evento_id, $tipo_midia, $file_path);
        $stmt->execute();
    }
}

// Buscar mídias do evento
$stmt = $conn->prepare("SELECT * FROM evento_midia WHERE evento_id = ?");
$stmt->bind_param("i", $evento_id);
$stmt->execute();
$result_midia = $stmt->get_result();
$midias = [];
while ($row = $result_midia->fetch_assoc()) {
    $midias[] = $row;
}

// Buscar comentários/verificações
$stmt = $conn->prepare("SELECT v.*, u.nome FROM evento_verificacoes v JOIN usuarios u ON v.user_id = u.id WHERE v.evento_id = ? ORDER BY v.data_verificacao DESC");
$stmt->bind_param("i", $evento_id);
$stmt->execute();
$result_comentarios = $stmt->get_result();
$comentarios = [];
while ($row = $result_comentarios->fetch_assoc()) {
    $comentarios[] = $row;
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes do Evento - Olimclima</title>
    <!-- Incluir os mesmos estilos do index.php -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/font-awesome@4.7.0/css/font-awesome.min.css">
    <style>
        /* Estilos copiados do index.php + novos estilos */
        .verificacao-buttons {
            display: flex;
            gap: 10px;
            margin: 20px 0;
        }
        .btn-verificacao {
            flex: 1;
            padding: 10px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 500;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 5px;
        }
        .btn-eu-vi {
            background-color: #4caf50;
            color: white;
        }
        .btn-confirmo {
            background-color: #2196F3;
            color: white;
        }
        .btn-nao-ocorreu {
            background-color: #f44336;
            color: white;
        }
        .btn-selected {
            box-shadow: 0 0 0 3px #fff, 0 0 0 6px currentColor;
        }
        .contagem {
            font-size: 0.9rem;
            margin-top: 3px;
        }
        .media-gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
            margin: 20px 0;
        }
        .media-item {
            border-radius: 8px;
            overflow: hidden;
            height: 200px;
        }
        .media-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .media-item video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .comentarios-section {
            margin-top: 30px;
        }
        .comentario {
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        .comentario-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        .comentario-tipo {
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            color: white;
        }
        .tipo-eu-vi { background-color: #4caf50; }
        .tipo-confirmo { background-color: #2196F3; }
        .tipo-nao-ocorreu { background-color: #f44336; }
    </style>
</head>
<body>
    <header class="header">
        <a href="index.php" class="logo">
            <h2>Olimclima</h2>
        </a>
        <nav class="nav">
            <a href="historico.php">Dashboard</a>
            <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?><a href="admin/index.php">Admin</a><?php endif; ?>
            <?php if (isset($_SESSION['user_id'])): ?><span>Olá, <?= htmlspecialchars($_SESSION['user_name']) ?></span><a href="logout.php">Sair</a><?php else: ?><a href="login.php">Login</a><?php endif; ?>
        </nav>
    </header>
    
    <div class="container" style="max-width: 1200px; margin: 20px auto; padding: 0 20px;">
        <h1><?= htmlspecialchars($evento['tipo']) ?> em <?= htmlspecialchars($evento['cidade']) ?>/<?= htmlspecialchars($evento['uf']) ?></h1>
        <p><strong>Local:</strong> <?= htmlspecialchars($evento['local']) ?></p>
        <p><strong>Intensidade:</strong> <?= htmlspecialchars($evento['intensidade']) ?></p>
        <p><strong>Data:</strong> <?= date('d/m/Y H:i', strtotime($evento['data_evento'])) ?></p>
        <p><strong>Relatado por:</strong> <?= htmlspecialchars($evento['nome_usuario']) ?></p>
        <?php if (!empty($evento['observacoes'])): ?>
            <div class="observacoes">
                <h3>Observações:</h3>
                <p><?= nl2br(htmlspecialchars($evento['observacoes'])) ?></p>
            </div>
        <?php endif; ?>
        
        <div id="map" style="height: 300px; margin: 20px 0; border-radius: 8px;"></div>
        
        <h3>Enviar Mídias</h3>
        <form action="" method="POST" enctype="multipart/form-data">
            <label for="tipo_midia">Tipo de mídia:</label>
            <select id="tipo_midia" name="tipo_midia" required>
                <option value="foto">Imagem</option>
                <option value="video">Vídeo</option>
            </select>
            <label for="midia">Arquivo:</label>
            <input type="file" id="midia" name="midia" accept="image/*,video/*" required>
            <button type="submit">Enviar Mídia</button>
        </form>
        
        <?php if (count($midias) > 0): ?>
            <h3>Fotos e Vídeos</h3>
            <div class="media-gallery">
                <?php foreach ($midias as $midia): ?>
                    <div class="media-item">
                        <?php if ($midia['tipo_midia'] == 'foto'): ?>
                            <img src="<?= htmlspecialchars($midia['arquivo_path']) ?>" alt="Foto do evento">
                        <?php else: ?>
                            <video controls>
                                <source src="<?= htmlspecialchars($midia['arquivo_path']) ?>" type="video/mp4">
                                Seu navegador não suporta vídeos HTML5.
                            </video>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if (count($comentarios) > 0): ?>
            <div class="comentarios-section">
                <h3>Comentários da Comunidade</h3>
                <?php foreach ($comentarios as $comentario): ?>
                    <div class="comentario">
                        <div class="comentario-header">
                            <strong><?= htmlspecialchars($comentario['nome']) ?></strong>
                            <div>
                                <span class="comentario-tipo tipo-<?= $comentario['tipo_verificacao'] ?>">
                                    <?php 
                                    switch($comentario['tipo_verificacao']) {
                                        case 'eu_vi': echo 'Eu Vi'; break;
                                        case 'confirmo': echo 'Confirmo'; break;
                                        case 'nao_ocorreu': echo 'Não Ocorreu'; break;
                                    } 
                                    ?>
                                </span>
                                <small><?= date('d/m/Y H:i', strtotime($comentario['data_verificacao'])) ?></small>
                            </div>
                        </div>
                        <?php if (!empty($comentario['comentario'])): ?>
                            <p><?= nl2br(htmlspecialchars($comentario['comentario'])) ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar o mapa
            const map = L.map('map').setView([<?= $evento['latitude'] ?>, <?= $evento['longitude'] ?>], 15);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap'
            }).addTo(map);
            
            // Adicionar marcador do evento
            L.marker([<?= $evento['latitude'] ?>, <?= $evento['longitude'] ?>]).addTo(map)
                .bindPopup("<?= htmlspecialchars($evento['tipo']) ?> - <?= htmlspecialchars($evento['local']) ?>");
        });
    </script>
</body>
</html>