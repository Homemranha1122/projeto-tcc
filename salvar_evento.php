<?php
session_start();
require_once 'conexao.php';

// Verifica se usuário está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Função util para resposta de erro
function fail($msg) {
    $_SESSION['flash_error'] = $msg;
    header('Location: index.php');
    exit;
}

try {
    // Valida campos obrigatórios
    $camposObrig = ['local','cidade','uf','tipo','intensidade','data','latitude','longitude'];
    foreach ($camposObrig as $c) {
        if (empty($_POST[$c])) {
            fail("Campo obrigatório ausente: $c");
        }
    }

    $local       = trim($_POST['local']);
    $cidade      = trim($_POST['cidade']);
    $uf          = strtoupper(trim($_POST['uf']));
    $tipo        = trim($_POST['tipo']);
    $intensidade = trim($_POST['intensidade']);
    $observacoes = isset($_POST['observacoes']) ? trim($_POST['observacoes']) : null;
    $dataEvento  = $_POST['data']; // formato datetime-local (YYYY-MM-DDTHH:MM)
    $latitude    = (float)$_POST['latitude'];
    $longitude   = (float)$_POST['longitude'];
    $userId      = (int)$_SESSION['user_id'];

    // Normaliza data
    $dataEvento = str_replace('T', ' ', $dataEvento) . ':00';

    // Insere evento
    $sql = "INSERT INTO eventos (user_id, local, cidade, uf, tipo, intensidade, observacoes, latitude, longitude, data_evento)
            VALUES (?,?,?,?,?,?,?,?,?,?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId, $local, $cidade, $uf, $tipo, $intensidade, $observacoes, $latitude, $longitude, $dataEvento]);
    $eventoId = $pdo->lastInsertId();

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
                $stmtImg = $pdo->prepare("INSERT INTO eventos_imagens (evento_id, caminho_imagem) VALUES (?,?)");
                $stmtImg->execute([$eventoId, $publicPath]);
            }
        }
    }

    header('Location: index.php?evento=sucesso');
    exit;

} catch (Exception $e) {
    error_log('Erro salvar_evento: ' . $e->getMessage());
    fail('Erro ao salvar evento.');
}