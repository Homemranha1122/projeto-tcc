# REFATORAÇÃO - Guia de Implementação

Este documento descreve as mudanças introduzidas nesta PR e como usar a nova estrutura.

## 🎯 Objetivo

Introduzir uma base sólida para refatoração gradual do código legado, mantendo **100% de compatibilidade** com o sistema existente.

## 📦 Arquivos Criados

### Estrutura Base

```
├── composer.json              # Gerenciador de dependências
├── .env.example               # Template de configuração
├── .gitignore                # Arquivos ignorados pelo Git
└── public/
    ├── bootstrap.php          # Inicialização da aplicação
    └── index_refatorado.php   # Exemplo da nova estrutura
```

### Nova Camada de Código (src/)

```
src/
├── Database/
│   └── Connection.php         # Singleton PDO com utf8mb4
├── Services/
│   └── WeatherService.php     # Serviço de previsão do tempo
├── Support/
│   └── helpers.php            # Funções auxiliares
└── View/
    └── Layout.php             # Layout básico separado
```

## 🔧 Ajustes em Arquivos Existentes

### conexao.php (Mudanças Mínimas)

**Linha 1-10**: Adicionado comentário explicando migração gradual

**Linha 12-15**: Tenta carregar `bootstrap.php` se existir (opcional, não quebra se não existir)

**Linha 26**: 
- ❌ Antes: `$conn->error`
- ✅ Depois: `$conn->connect_error` (correção de bug)

**Linha 46**:
- ❌ Antes: `$api_key = '5a3a2e0c72f5e5c8d2e2f3e2c6e2b7ac';`
- ✅ Depois: `$api_key = function_exists('env') ? env('OPENWEATHER_API_KEY', '...') : '...';`
- Usa variável de ambiente se disponível, senão usa valor hardcoded (compatibilidade)

**Importante**: Todo o resto do arquivo permanece **100% intocado**!

## 🚀 Como Usar

### Opção 1: Continuar Usando o Sistema Legado (Padrão)

Nada muda! O sistema continua funcionando exatamente como antes:

```
http://localhost/index.php        # Funciona normalmente
http://localhost/login.php        # Funciona normalmente
http://localhost/historico.php    # Funciona normalmente
```

### Opção 2: Habilitar Nova Estrutura (Opcional)

Para usar a nova estrutura com `.env` e classes refatoradas:

```bash
# 1. Instalar dependências
composer install

# 2. Configurar variáveis de ambiente
cp .env.example .env
nano .env  # Edite com suas configurações

# 3. Testar exemplo refatorado
http://localhost/public/index_refatorado.php
```

### Opção 3: Uso Híbrido (Recomendado)

Você pode usar ambos! O bootstrap é carregado automaticamente por `conexao.php` se existir:

1. Execute `composer install` e configure `.env`
2. Os arquivos legados automaticamente passarão a usar as variáveis de ambiente
3. Mantenha os arquivos antigos funcionando enquanto migra gradualmente

## 📚 Nova API - Exemplos de Uso

### Helpers de Segurança

```php
<?php
require_once 'public/bootstrap.php';

// Escape HTML para output
echo e($user_input);  // Seguro contra XSS

// Escape e remove tags
echo es($user_input);  // Ainda mais seguro

// Redirecionamento
redirect('/login.php');
```

### Variáveis de Ambiente

```php
<?php
require_once 'public/bootstrap.php';

// Obter variável de ambiente
$dbHost = env('DB_HOST', 'localhost');
$apiKey = env('OPENWEATHER_API_KEY');
$debug = env('APP_DEBUG', false);  // Retorna boolean
```

### Conexão PDO

```php
<?php
require_once 'public/bootstrap.php';

use App\Database\Connection;

// Obter conexão
$db = Connection::getInstance();

// Prepared statement (seguro contra SQL Injection)
$stmt = $db->prepare("SELECT * FROM usuarios WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();
```

### Serviço de Previsão do Tempo

```php
<?php
require_once 'public/bootstrap.php';

use App\Services\WeatherService;

// Criar serviço
$weather = new WeatherService();

// Obter previsão (com cache automático)
$previsao = $weather->obterPrevisao('Olímpia', 'SP');

if (!$previsao['erro']) {
    echo "Temperatura: {$previsao['temp']}°C\n";
    echo "Descrição: {$previsao['descricao']}\n";
}
```

### Layout Separado

```php
<?php
require_once 'public/bootstrap.php';

use App\View\Layout;

Layout::header('Minha Página', false);
?>

<div class="card">
    <h2>Conteúdo</h2>
    <p>Seu conteúdo aqui...</p>
</div>

<?php
Layout::footer();
?>
```

## 🔐 Melhorias de Segurança

### Antes (Legado)
```php
// Credenciais expostas no código
$db_host = 'localhost';
$db_pass = 'senha123';
$api_key = 'abc123';

// Concatenação direta (SQL Injection)
$query = "SELECT * FROM users WHERE id = " . $_GET['id'];
```

### Depois (Nova Estrutura)
```php
// Credenciais em .env (gitignored)
$db_host = env('DB_HOST');
$db_pass = env('DB_PASS');
$api_key = env('OPENWEATHER_API_KEY');

// Prepared statements
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_GET['id']]);
```

## 🧪 Testes Realizados

- ✅ Todos os arquivos PHP sem erros de sintaxe
- ✅ Bootstrap carrega helpers corretamente (com e sem Composer)
- ✅ Função `env()` funciona com fallbacks
- ✅ Helpers de escape HTML (`e()`, `es()`) funcionam
- ✅ conexao.php mantém compatibilidade total com código legado
- ✅ Nenhum arquivo original foi removido

## 📋 Checklist de Migração (Futuro)

Esta PR é apenas o **primeiro passo**. Próximas etapas sugeridas:

### Fase 2 - Router e Controllers
- [ ] Criar Router simples
- [ ] AuthController para login/logout
- [ ] EventoController para cadastro de eventos
- [ ] LocalController para gerenciamento de locais

### Fase 3 - Migração de Queries
- [ ] Converter `salvar_evento.php` para usar PDO
- [ ] Converter `salvar_comentario.php` para usar PDO
- [ ] Converter `login.php` para usar PDO
- [ ] Converter `cadastro.php` para usar PDO

### Fase 4 - Front-end
- [ ] Extrair CSS inline para arquivos
- [ ] Criar sistema de assets (CSS/JS)
- [ ] Componentes reutilizáveis
- [ ] Melhorar UX mobile

### Fase 5 - Testes
- [ ] PHPUnit setup
- [ ] Testes de WeatherService
- [ ] Testes de autenticação
- [ ] Testes de integração

## ⚠️ Avisos Importantes

### O que NÃO foi alterado (propositalmente):
- ❌ Nenhum arquivo foi removido
- ❌ Layout gigante em `conexao.php` ainda existe
- ❌ Queries mysqli ainda funcionam normalmente
- ❌ CSS inline ainda presente nos arquivos
- ❌ Sistema de autenticação não foi modificado
- ❌ Scripts `salvar_*.php` ainda funcionam como antes

### Por quê?
Para permitir **migração gradual e segura** sem quebrar o sistema em produção!

## 🤝 Como Contribuir com a Migração

1. **Escolha uma funcionalidade pequena** (ex: login)
2. **Crie nova versão** usando as classes (ex: `AuthService`)
3. **Teste ambas versões** (legado e novo)
4. **Substitua gradualmente** após aprovação
5. **Remova código antigo** apenas após 100% de certeza

## 📞 Suporte

Se tiver dúvidas sobre a nova estrutura:

1. Veja `public/index_refatorado.php` - exemplo completo
2. Leia comentários no código - bem documentado
3. Consulte README.md - visão geral
4. Abra uma issue - estamos aqui para ajudar!

---

**Data**: Setembro 2024  
**Status**: ✅ Pronto para merge (sem breaking changes)
