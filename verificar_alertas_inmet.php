<?php
// Inclui os arquivos do PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'lib/phpmailer/Exception.php';
require 'lib/phpmailer/PHPMailer.php';
require 'lib/phpmailer/SMTP.php';

include_once 'conexao.php';

// Função para normalizar nomes de cidades para comparação
function normalizeCity($name) {
    $name = strtolower($name);
    $name = iconv('UTF-8', 'ASCII//TRANSLIT', $name);
    return $name;
}

echo "Iniciando verificação de alertas do INMET...\n";
$url_api_inmet = "https://apiprevmet.inmet.gov.br/avisos/ativos";

$ch = curl_init($url_api_inmet);
// ... (código cURL para buscar a API continua o mesmo) ...
$response = curl_exec($ch);
curl_close($ch);

$alertas_inmet = json_decode($response, true);
if (json_last_error() !== JSON_ERROR_NONE || !is_array($alertas_inmet)) {
    die("Erro ao decodificar a resposta da API do INMET.\n");
}

$novos_alertas_count = 0;

foreach ($alertas_inmet as $alerta) {
    $id_externo = $alerta['id_aviso'];
    $cidade_alerta = $alerta['cidade'];
    // ... (demais variáveis do alerta) ...

    $stmt_check = $conn->prepare("SELECT id FROM alertas_oficiais WHERE id_externo = ?");
    $stmt_check->bind_param("s", $id_externo);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows == 0) {
        // Alerta é novo, insere no banco
        // ... (código de inserção do alerta continua o mesmo) ...

        if ($stmt_insert->execute()) {
            $novos_alertas_count++;
            echo "Novo alerta para {$cidade_alerta} inserido. Procurando usuários para notificar...\n";

            // *** A MÁGICA ACONTECE AQUI ***
            // 1. Buscar usuários cuja cidade corresponde à cidade do alerta
            $stmt_users = $conn->prepare("SELECT email, nome FROM usuarios WHERE cidade = ?");
            $stmt_users->bind_param("s", $cidade_alerta);
            $stmt_users->execute();
            $result_users = $stmt_users->get_result();

            if ($result_users->num_rows > 0) {
                while ($user = $result_users->fetch_assoc()) {
                    echo "Enviando e-mail para: " . $user['email'] . "\n";
                    
                    // 2. Enviar o e-mail usando PHPMailer
                    $mail = new PHPMailer(true);
                    try {
                        // Configurações do Servidor SMTP (use um e-mail real seu, como do Gmail)
                        $mail->isSMTP();
                        $mail->Host       = 'smtp.gmail.com'; // Ex: smtp.gmail.com
                        $mail->SMTPAuth   = true;
                        $mail->Username   = 'seu-email@gmail.com'; // SEU E-MAIL
                        $mail->Password   = 'sua-senha-de-app';   // SUA SENHA DE APP (não a senha normal)
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                        $mail->Port       = 465;

                        // Remetente e Destinatário
                        $mail->setFrom('seu-email@gmail.com', 'Olimclima Alertas');
                        $mail->addAddress($user['email'], $user['nome']);

                        // Conteúdo
                        $mail->isHTML(true);
                        $mail->CharSet = 'UTF-8';
                        $mail->Subject = "ALERTA CLIMÁTICO: {$alerta['severidade']} para {$cidade_alerta}";
                        $mail->Body    = "<h3>Olá, {$user['nome']}!</h3><p>Um novo alerta climático foi emitido para a sua cidade, <b>{$cidade_alerta}</b>:</p><h4>{$alerta['aviso']}</h4><p><b>Severidade:</b> {$alerta['severidade']}</p><p><b>Descrição:</b> {$alerta['descricao']}</p><p><b>Válido até:</b> " . date('d/m/Y H:i', strtotime($alerta['data_fim_aviso'])) . "</p><p>Fique seguro!</p><p>--<br>Equipe Olimclima</p>";
                        $mail->AltBody = "Olá, {$user['nome']}!\nUm novo alerta climático foi emitido para a sua cidade, {$cidade_alerta}:\n\nTítulo: {$alerta['aviso']}\nSeveridade: {$alerta['severidade']}\nDescrição: {$alerta['descricao']}\n\nFique seguro!\n--\nEquipe Olimclima";

                        $mail->send();
                    } catch (Exception $e) {
                        echo "Erro ao enviar e-mail para {$user['email']}: {$mail->ErrorInfo}\n";
                    }
                }
            }
            $stmt_users->close();
        }
        $stmt_insert->close();
    }
    $stmt_check->close();
}

echo "Verificação concluída. {$novos_alertas_count} novos alertas foram processados.\n";
$conn->close();
?>