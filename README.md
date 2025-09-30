# projeto-tcc

Sistema de Monitoramento Colaborativo de Enchentes - Olimclima

## 📋 Sobre o Projeto

Sistema web colaborativo para monitoramento e registro de eventos climáticos extremos, com foco em enchentes e chuvas intensas. Permite que usuários registrem eventos, visualizem previsões do tempo e acompanhem alertas oficiais.

## 🏗️ Arquitetura (Em Migração)

O projeto está em processo de refatoração gradual para uma arquitetura mais moderna e segura, mantendo total compatibilidade com o código existente.

### Estrutura Atual (Legacy)
- Arquivos PHP na raiz do projeto
- Conexão mysqli direta
- Credenciais hardcoded
- CSS inline nos arquivos

### Nova Estrutura (Em Desenvolvimento)
```
projeto-tcc/
├── src/
│   ├── Database/        # Conexões PDO
│   ├── Services/        # Serviços (Weather, etc)
│   ├── Support/         # Helpers
│   └── View/            # Layouts e componentes
├── public/              # Ponto de entrada
│   ├── bootstrap.php    # Inicialização
│   └── index_refatorado.php  # Exemplo da nova estrutura
├── vendor/              # Dependências Composer (gitignored)
├── .env                 # Variáveis de ambiente (gitignored)
├── .env.example         # Template de configuração
└── composer.json        # Gerenciamento de dependências
```

## 🚀 Instalação

### Requisitos
- PHP >= 7.4
- MySQL/MariaDB
- Composer (para nova estrutura)

### Setup Rápido (Site Original)
1. Configure o banco de dados em `conexao.php`
2. Importe o schema SQL
3. Acesse via navegador

### Setup Completo (Nova Estrutura)
```bash
# 1. Clone o repositório
git clone https://github.com/Homemranha1122/projeto-tcc.git
cd projeto-tcc

# 2. Instale as dependências
composer install

# 3. Configure variáveis de ambiente
cp .env.example .env
# Edite .env com suas configurações

# 4. Configure o banco de dados
# Importe o schema SQL

# 5. Acesse via navegador
# Site original: http://localhost/index.php
# Exemplo refatorado: http://localhost/public/index_refatorado.php
```

## 🔧 Configuração (.env)

```env
# Banco de Dados
DB_HOST=localhost
DB_USER=root
DB_PASS=
DB_NAME=enchentes

# API Keys
OPENWEATHER_API_KEY=sua_chave_aqui

# Aplicação
APP_DEBUG=true
TIMEZONE=America/Sao_Paulo
```

## 📚 Características da Nova Estrutura

- ✅ **Autoload PSR-4** com Composer
- ✅ **Variáveis de ambiente** (.env) para segurança
- ✅ **Conexão PDO** singleton com utf8mb4 e prepared statements
- ✅ **Serviço de Weather** com cache por request
- ✅ **Helpers de segurança** (e(), es(), redirect())
- ✅ **Separação de responsabilidades** (MVC básico)
- ✅ **Compatibilidade total** com código legado

## 🔄 Migração Gradual

A refatoração está sendo feita de forma incremental:

### ✅ Concluído (PR Atual)
- [x] Estrutura de pastas e autoload Composer
- [x] Variáveis de ambiente (.env)
- [x] Classe Connection PDO
- [x] WeatherService com cache
- [x] Helpers básicos
- [x] Layout básico separado
- [x] Exemplo funcional (index_refatorado.php)
- [x] Correção de bug em conexao.php (connect_error)

### 🔜 Próximas Etapas (PRs Futuras)
- [ ] Router simples para rotas limpas
- [ ] AuthService e AuthController
- [ ] Migrar scripts salvar_* para Controllers
- [ ] Converter queries mysqli para PDO
- [ ] Extrair CSS inline para arquivos
- [ ] Camada de notificações dinâmicas
- [ ] Testes PHPUnit

## 🛡️ Segurança

### Melhorias Implementadas
- Suporte a variáveis de ambiente para credenciais
- Helpers de escape HTML (e(), es())
- Preparação para prepared statements (PDO)

### Ainda Necessário (Legacy)
- Migrar todas as queries para prepared statements
- Remover credenciais hardcoded completamente
- Implementar CSRF protection
- Rate limiting em APIs

## 🤝 Contribuindo

Este projeto está em desenvolvimento ativo. Ao contribuir:
- Mantenha compatibilidade com código legado
- Faça mudanças incrementais
- Documente alterações
- Teste antes de submeter PR

## 📝 Licença

[Definir licença]

## 👥 Autores

[Informações dos autores]
