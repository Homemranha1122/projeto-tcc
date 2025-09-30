<?php
// get_evento.php - Retorna detalhes de um evento específico incluindo imagens

session_start();
include_once 'conexao.php';

header('Content-Type: application/json');

// Verifica se o ID foi fornecido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['error' => 'ID inválido']);
    exit;
}

$eventoId = (int)$_GET['id'];

// Busca os dados do evento com o nome do usuário
$sql = "SELECT e.*, u.nome as nome_usuario 
        FROM eventos e 
        LEFT JOIN usuarios u ON e.user_id = u.id 
        WHERE e.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $eventoId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['error' => 'Evento não encontrado']);
    exit;
}

$evento = $result->fetch_assoc();

// Busca as imagens associadas ao evento
$sqlImagens = "SELECT id, caminho_imagem FROM eventos_imagens WHERE evento_id = ?";
$stmtImagens = $conn->prepare($sqlImagens);
$stmtImagens->bind_param("i", $eventoId);
$stmtImagens->execute();
$resultImagens = $stmtImagens->get_result();

$imagens = [];
while ($img = $resultImagens->fetch_assoc()) {
    $imagens[] = $img;
}

// Adiciona as imagens ao array do evento
$evento['imagens'] = $imagens;

// Conta o número de verificações do evento
$sqlVerificacoes = "SELECT COUNT(*) as total FROM evento_verificacoes WHERE evento_id = ?";
$stmtVerif = $conn->prepare($sqlVerificacoes);
$stmtVerif->bind_param("i", $eventoId);
$stmtVerif->execute();
$resultVerif = $stmtVerif->get_result();
$verificacoes = $resultVerif->fetch_assoc();
$evento['verificacoes'] = $verificacoes['total'];

// Verifica se o usuário atual já verificou este evento
if (isset($_SESSION['user_id'])) {
    $sqlUserVerif = "SELECT tipo_verificacao FROM evento_verificacoes WHERE evento_id = ? AND user_id = ?";
    $stmtUserVerif = $conn->prepare($sqlUserVerif);
    $stmtUserVerif->bind_param("ii", $eventoId, $_SESSION['user_id']);
    $stmtUserVerif->execute();
    $resultUserVerif = $stmtUserVerif->get_result();
    
    if ($resultUserVerif->num_rows > 0) {
        $userVerif = $resultUserVerif->fetch_assoc();
        $evento['usuario_verificou'] = $userVerif['tipo_verificacao'];
    }
}

// Retorna os dados em formato JSON
echo json_encode($evento);

$conn->close();
?>
