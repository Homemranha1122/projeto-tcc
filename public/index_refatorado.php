<?php
/**
 * Exemplo de uso da nova estrutura refatorada
 * 
 * Este arquivo demonstra como usar a nova camada de abstração
 * sem substituir o index.php original. Serve como referência
 * para migração gradual futura.
 * 
 * IMPORTANTE: Este arquivo não substitui index.php!
 * É apenas um exemplo da nova estrutura.
 */

// Carrega o bootstrap da aplicação
require_once __DIR__ . '/bootstrap.php';

use App\Database\Connection;
use App\Services\WeatherService;
use App\View\Layout;

// Verifica autenticação (simplificado para exemplo)
// Na versão completa, isto seria tratado por AuthService
if (!isset($_SESSION['user_id'])) {
    // Para exemplo, apenas redireciona
    redirect('/login.php');
}

// Inicia layout
Layout::header('Olimclima - Exemplo Refatorado', false);

?>

<div class="card">
    <h2>Bem-vindo ao Exemplo de Estrutura Refatorada!</h2>
    <p>Este é um exemplo de como a nova estrutura funciona. O site original continua funcionando normalmente em <code>index.php</code>.</p>
    
    <h3>Características da Nova Estrutura:</h3>
    <ul>
        <li>✅ Autoload PSR-4 com Composer</li>
        <li>✅ Variáveis de ambiente (.env)</li>
        <li>✅ Conexão PDO singleton com utf8mb4</li>
        <li>✅ Serviço de previsão do tempo com cache</li>
        <li>✅ Helpers de segurança (e(), es(), redirect())</li>
        <li>✅ Separação de responsabilidades</li>
    </ul>
</div>

<div class="card">
    <h3>Teste do Serviço de Previsão do Tempo</h3>
    <?php
    try {
        $weatherService = new WeatherService();
        $previsao = $weatherService->obterPrevisao('Olímpia', 'SP');
        
        if (!$previsao['erro']) {
            ?>
            <div style="display: flex; align-items: center; gap: 20px;">
                <img src="<?= e($previsao['icone_url']) ?>" alt="Ícone do tempo" style="width: 100px;">
                <div>
                    <h4><?= e($previsao['cidade']) ?>/<?= e($previsao['uf']) ?></h4>
                    <p><strong>Temperatura:</strong> <?= e($previsao['temp']) ?>°C</p>
                    <p><strong>Descrição:</strong> <?= e($previsao['descricao']) ?></p>
                    <p><strong>Umidade:</strong> <?= e($previsao['umidade']) ?>%</p>
                    <p><strong>Vento:</strong> <?= e($previsao['vento']) ?> km/h</p>
                </div>
            </div>
            <?php
        } else {
            echo '<p style="color: #dc3545;">Erro ao obter previsão: ' . e($previsao['descricao']) . '</p>';
        }
    } catch (Exception $e) {
        echo '<p style="color: #dc3545;">Erro: ' . e($e->getMessage()) . '</p>';
    }
    ?>
</div>

<div class="card">
    <h3>Teste da Conexão PDO</h3>
    <?php
    try {
        $db = Connection::getInstance();
        $stmt = $db->prepare("SELECT DATABASE() as db_name");
        $stmt->execute();
        $result = $stmt->fetch();
        
        echo '<p style="color: #28a745;">✓ Conexão PDO estabelecida com sucesso!</p>';
        echo '<p>Banco de dados: <strong>' . e($result['db_name']) . '</strong></p>';
    } catch (Exception $e) {
        echo '<p style="color: #dc3545;">Erro ao conectar: ' . e($e->getMessage()) . '</p>';
        echo '<p><small>Certifique-se de que o arquivo .env está configurado corretamente.</small></p>';
    }
    ?>
</div>

<div class="card">
    <h3>Próximos Passos</h3>
    <p>Esta estrutura permite migração gradual sem quebrar o site existente:</p>
    <ol>
        <li>Executar <code>composer install</code> para instalar dependências</li>
        <li>Copiar <code>.env.example</code> para <code>.env</code> e configurar</li>
        <li>Criar Router simples para rotas limpas</li>
        <li>Migrar autenticação para AuthService/AuthController</li>
        <li>Converter scripts salvar_* para Controllers</li>
        <li>Migrar queries mysqli para PDO gradualmente</li>
        <li>Separar CSS inline para arquivos assets</li>
        <li>Adicionar testes PHPUnit</li>
    </ol>
    
    <p style="margin-top: 20px;">
        <a href="/index.php" style="color: #3a86ff; font-weight: 600;">← Voltar para o site original</a>
    </p>
</div>

<?php

Layout::footer();
