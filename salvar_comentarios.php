<?php
session_start();
include_once 'conexao.php';

if (!isset($_SESSION['user_id'])) { die('Acesso negado.'); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_evento = (int)$_POST['id_evento'];
    $id_usuario = (int)$_SESSION['user_id'];
    $comentario = trim($_POST['comentario']);

    if (!empty($comentario) && $id_evento > 0) {
        $stmt = $conn->prepare("INSERT INTO comentarios (id_evento, id_usuario, comentario) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $id_evento, $id_usuario, $comentario);
        $stmt->execute();

        // Notificar o dono do evento
        $res = $conn->query("SELECT id_usuario FROM eventos WHERE id = $id_evento")->fetch_assoc();
        $id_usuario_evento = $res['id_usuario'];
        if ($id_usuario_evento != $id_usuario) { // Não notificar a si mesmo
            $mensagem = $_SESSION['user_name'] . " comentou no seu evento.";
            $link = "evento.php?id=$id_evento";
            $stmt_notif = $conn->prepare("INSERT INTO notificacoes (id_usuario_destino, mensagem, link) VALUES (?, ?, ?)");
            $stmt_notif->bind_param("iss", $id_usuario_evento, $mensagem, $link);
            $stmt_notif->execute();
        }
    }
    header('Location: evento.php?id=' . $id_evento);
    exit();
}