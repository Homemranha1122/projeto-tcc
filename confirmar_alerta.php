<?php
session_start();
include 'conexao.php';

// Verifica se o ID do alerta foi enviado
if (isset($_POST['alerta_id'])) {
    $alertaId = (int)$_POST['alerta_id'];

    // Para evitar votos duplicados na mesma sessão
    if (!isset($_SESSION['voted_alerts'])) {
        $_SESSION['voted_alerts'] = [];
    }

    if (!in_array($alertaId, $_SESSION['voted_alerts'])) {
        // Incrementa o contador no banco de dados
        $stmt = $conn->prepare("UPDATE alertas SET confirmacoes = confirmacoes + 1 WHERE id = ?");
        $stmt->bind_param("i", $alertaId);
        $stmt->execute();
        
        // Marca que o usuário já votou neste alerta nesta sessão
        $_SESSION['voted_alerts'][] = $alertaId;

        // Pega o novo total de confirmações
        $res = $conn->query("SELECT confirmacoes FROM alertas WHERE id = $alertaId");
        $novoTotal = $res->fetch_assoc()['confirmacoes'];

        // Retorna o novo total em formato JSON
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'confirmacoes' => $novoTotal]);
    } else {
        // Usuário já votou
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Você já confirmou este alerta.']);
    }
} else {
    // ID do alerta não fornecido
    header('Content-Type: application/json');
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'ID do alerta não fornecido.']);
}
?>