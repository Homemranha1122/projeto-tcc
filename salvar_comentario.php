<?php
session_start();
include_once 'conexao.php';

// Proteção: Apenas usuários logados podem comentar
if (!isset($_SESSION['user_id'])) {
    die("Acesso negado. Você precisa estar logado para comentar.");
}

// Verifica se os dados do formulário foram enviados
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comentario']) && isset($_POST['id_evento'])) {
    
    $comentario_texto = trim($_POST['comentario']);
    $id_evento = (int)$_POST['id_evento'];
    $id_usuario = (int)$_SESSION['user_id'];

    // Validação básica para não salvar comentários vazios
    if (!empty($comentario_texto) && $id_evento > 0) {
        
        $stmt = $conn->prepare("INSERT INTO comentarios (id_evento, id_usuario, comentario, data_comentario) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iis", $id_evento, $id_usuario, $comentario_texto);
        
        if ($stmt->execute()) {
            // Sucesso! Redireciona de volta para a página do evento
            header("Location: evento.php?id=" . $id_evento);
            exit();
        } else {
            // Falha ao executar a query
            die("Erro ao salvar o comentário. Por favor, tente novamente.");
        }
    } else {
        // Dados inválidos (comentário vazio ou ID do evento faltando)
        die("Dados inválidos para salvar o comentário.");
    }
} else {
    // Se alguém tentar acessar este arquivo diretamente
    header("Location: index.php");
    exit();
}
?>