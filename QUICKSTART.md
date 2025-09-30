# Quick Start - Testando a Refatoração

Este guia permite testar rapidamente as alterações desta PR.

## ✅ Verificação Rápida (Sem Configuração)

### 1. Verificar que nada quebrou
```bash
# Site original deve funcionar normalmente
# Apenas abra no navegador:
http://localhost/index.php
http://localhost/login.php
http://localhost/historico.php
```

**Resultado esperado**: Tudo funciona como antes!

### 2. Verificar sintaxe PHP
```bash
cd /caminho/do/projeto
php -l conexao.php
php -l public/bootstrap.php
php -l src/Database/Connection.php
php -l src/Services/WeatherService.php
```

**Resultado esperado**: "No syntax errors detected"

## 🔧 Teste Completo (Com Configuração)

### 1. Instalar Dependências
```bash
composer install
```

Isso instalará:
- `vlucas/phpdotenv` - Gerenciamento de variáveis de ambiente

### 2. Configurar Variáveis de Ambiente
```bash
# Copiar exemplo
cp .env.example .env

# Editar com suas configurações
nano .env  # ou qualquer editor
```

Exemplo de `.env`:
```env
DB_HOST=localhost
DB_USER=root
DB_PASS=
DB_NAME=enchentes
OPENWEATHER_API_KEY=sua_chave_aqui
APP_DEBUG=true
TIMEZONE=America/Sao_Paulo
```

### 3. Testar Exemplo Refatorado
```
http://localhost/public/index_refatorado.php
```

**Resultado esperado**:
- ✅ Página carrega com sucesso
- ✅ Mostra previsão do tempo
- ✅ Mostra conexão PDO funcionando
- ✅ Lista próximos passos de migração

## 🐛 Verificação de Bug Corrigido

### Bug Original (conexao.php linha 11)

**Antes**:
```php
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->error);  // ❌ ERRADO
}
```

**Depois**:
```php
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);  // ✅ CORRETO
}
```

**Por que isso importa?**
- `$conn->error` pode estar vazio no contexto de erro de conexão
- `$conn->connect_error` é a propriedade correta para erros de conexão mysqli
- Isso evita mensagens de erro vagas quando a conexão falha

### Testar o Bug Fix

Se quiser forçar um erro de conexão para verificar:

```php
// Temporariamente edite conexao.php
$db_pass = 'senha_errada';  // Linha 20

// Acesse qualquer página
http://localhost/index.php

// Resultado esperado:
// "Falha na conexão: Access denied for user..."
// (mensagem clara, não vazia)
```

## 🔐 Verificação de Segurança

### 1. Variáveis de Ambiente Funcionando
```bash
# Teste helpers
php -r "
require_once 'public/bootstrap.php';
echo env('OPENWEATHER_API_KEY', 'fallback') . PHP_EOL;
"
```

### 2. Escape HTML Funcionando
```bash
php -r "
require_once 'public/bootstrap.php';
echo e('<script>alert(1)</script>') . PHP_EOL;
"
```

**Resultado esperado**: `&lt;script&gt;alert(1)&lt;/script&gt;`

### 3. PDO Preparado Statements
```bash
php -r "
require_once 'public/bootstrap.php';
use App\Database\Connection;
\$db = Connection::getInstance();
\$stmt = \$db->prepare('SELECT DATABASE() as nome');
\$stmt->execute();
var_dump(\$stmt->fetch());
"
```

## 📊 Checklist de Teste para Revisor

- [ ] Site legado funciona normalmente (index.php, login.php, etc)
- [ ] `composer install` executa sem erros
- [ ] `.env` pode ser configurado a partir de `.env.example`
- [ ] `public/index_refatorado.php` carrega com sucesso
- [ ] Nenhum erro de sintaxe PHP em arquivos novos/modificados
- [ ] Correção de `$conn->connect_error` está aplicada
- [ ] API key usa `env()` com fallback para hardcoded
- [ ] README.md está atualizado e claro
- [ ] REFATORACAO.md explica bem as mudanças
- [ ] Nenhum arquivo original foi removido

## 🎯 Casos de Teste Específicos

### Caso 1: Sistema Sem Composer (Ambiente Legado)
```
Cenário: Servidor sem Composer instalado
Ação: Acesse index.php normalmente
Resultado: Deve funcionar perfeitamente (bootstrap carrega helpers manualmente)
```

### Caso 2: Sistema Com Composer (Ambiente Moderno)
```
Cenário: Desenvolvedor local com Composer
Ação: Execute composer install e configure .env
Resultado: Pode usar nova estrutura + site legado simultaneamente
```

### Caso 3: Migração Gradual
```
Cenário: Migrar uma funcionalidade por vez
Ação: Copiar login.php → criar LoginController
Resultado: Ambas versões funcionam até migração completa
```

## 🚨 O que NÃO Testar (Não Implementado Ainda)

- ❌ Roteamento limpo (ainda usa arquivos .php diretos)
- ❌ Autenticação refatorada (ainda usa código em login.php)
- ❌ CSS separado (ainda inline nos arquivos)
- ❌ Todas queries em PDO (mysqli ainda funciona)
- ❌ Testes automatizados (PHPUnit não configurado)

Estes itens serão implementados em PRs futuras!

## 💡 Dicas para o Revisor

1. **Foque nas mudanças mínimas**: Esta PR é deliberadamente pequena
2. **Verifique compatibilidade**: Código legado deve continuar 100% funcional
3. **Teste o bug fix**: Linha 26 do conexao.php (connect_error vs error)
4. **Revise segurança**: .env.example não tem dados sensíveis reais
5. **Confirme .gitignore**: vendor/ e .env estão ignorados

## 📞 Problemas?

Se encontrar algum problema:

1. Verifique se `composer install` foi executado
2. Confirme que `.env` está configurado (copie de `.env.example`)
3. Verifique permissões de arquivos (`chmod 644 *.php`)
4. Teste em ambiente local antes de produção
5. Abra uma issue com detalhes do erro

---

**Tempo estimado de teste**: 10-15 minutos  
**Complexidade**: Baixa (mudanças mínimas e bem isoladas)  
**Risco**: Muito baixo (100% compatível com legado)
