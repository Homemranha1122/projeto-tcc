<?php
session_start();
require_once 'conexao.php';

// Verifica se usuário está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: cadastro_evento.php?erro=login_necessario');
    exit;
}

// Função util para resposta de erro
function fail($msg) {
    header('Location: cadastro_evento.php?erro=' . urlencode($msg));
    exit;
}

try {
    // Valida campos obrigatórios
    $camposObrig = ['tipo','intensidade','data_evento','latitude','longitude'];
    foreach ($camposObrig as $c) {
        if (empty($_POST[$c])) {
            fail("campos_obrigatorios");
        }
    }

    // Obter informações do local
    $local = '';
    $cidade = '';
    $uf = '';
    
    if (!empty($_POST['local_id'])) {
        $localId = (int)$_POST['local_id'];
        $stmt = $conn->prepare("SELECT nome, cidade, uf FROM locais WHERE id = ?");
        $stmt->bind_param("i", $localId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $local = $row['nome'];
            $cidade = $row['cidade'];
            $uf = $row['uf'];
        }
    }
    
    // Se não tiver local_id, usar coordenadas para determinar local
    if (empty($local)) {
        $local = 'Localização no mapa';
        $cidade = 'A definir';
        $uf = 'BR';
    }

    $tipo        = trim($_POST['tipo']);
    $intensidade = trim($_POST['intensidade']);
    $observacoes = isset($_POST['observacoes']) ? trim($_POST['observacoes']) : null;
    $dataEvento  = $_POST['data_evento']; // formato datetime-local (YYYY-MM-DDTHH:MM)
    $latitude    = (float)$_POST['latitude'];
    $longitude   = (float)$_POST['longitude'];
    $userId      = (int)$_SESSION['user_id'];

    // Normaliza data
    $dataEvento = str_replace('T', ' ', $dataEvento) . ':00';

    // Insere evento usando mysqli
    $sql = "INSERT INTO eventos (user_id, local, cidade, uf, tipo, intensidade, observacoes, latitude, longitude, data_evento)
            VALUES (?,?,?,?,?,?,?,?,?,?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issssssdds", $userId, $local, $cidade, $uf, $tipo, $intensidade, $observacoes, $latitude, $longitude, $dataEvento);
    $stmt->execute();
    $eventoId = $conn->insert_id;

    // Upload de imagens (múltiplas)
    if (!empty($_FILES['imagens']) && isset($_FILES['imagens']['name']) && $_FILES['imagens']['name'][0] !== '') {

        $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'];
        $uploadDir = __DIR__ . '/uploads';

        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0775, true)) {
                fail('Não foi possível criar diretório de upload.');
            }
        }
        if (!is_writable($uploadDir)) {
            fail('Diretório de upload sem permissão de escrita.');
        }

        foreach ($_FILES['imagens']['name'] as $i => $origName) {
            $error = $_FILES['imagens']['error'][$i];
            if ($error === UPLOAD_ERR_NO_FILE) continue;
            if ($error !== UPLOAD_ERR_OK) continue; // você pode mapear erros detalhadamente

            $tmpName = $_FILES['imagens']['tmp_name'][$i];
            $type    = mime_content_type($tmpName);

            if (!isset($allowed[$type])) {
                continue; // ignora tipos não permitidos
            }

            // Segurança: tamanho máximo 5MB
            if ($_FILES['imagens']['size'][$i] > 5 * 1024 * 1024) {
                continue;
            }

            $ext = $allowed[$type];
            $safeBase = bin2hex(random_bytes(8)); // nome aleatório
            $newFileName = $safeBase . '_' . time() . '.' . $ext;
            $destPath = $uploadDir . '/' . $newFileName;

            if (move_uploaded_file($tmpName, $destPath)) {
                // Caminho público relativo
                $publicPath = 'uploads/' . $newFileName;
                $stmtImg = $conn->prepare("INSERT INTO eventos_imagens (evento_id, caminho_imagem) VALUES (?,?)");
                $stmtImg->bind_param("is", $eventoId, $publicPath);
                $stmtImg->execute();
            }
        }
    }

    header('Location: index.php?evento=sucesso');
    exit;

} catch (Exception $e) {
    error_log('Erro salvar_evento: ' . $e->getMessage());
    fail('Erro ao salvar evento.');
}