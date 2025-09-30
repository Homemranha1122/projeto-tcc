<?php
/**
 * Bootstrap da aplicação
 * 
 * Carrega autoload do Composer, variáveis de ambiente, 
 * inicia sessão e registra helpers.
 * 
 * Este arquivo pode ser incluído em qualquer ponto de entrada
 * para habilitar a nova estrutura sem quebrar o código legado.
 */

// Define o diretório base do projeto
define('BASE_PATH', dirname(__DIR__));

// Carrega autoload do Composer (se disponível)
$autoloadPath = BASE_PATH . '/vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
}

// Carrega variáveis de ambiente (se .env existir)
if (class_exists('Dotenv\Dotenv')) {
    $dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
    if (file_exists(BASE_PATH . '/.env')) {
        $dotenv->load();
    }
}

// Configura timezone
$timezone = env('TIMEZONE', 'America/Sao_Paulo');
date_default_timezone_set($timezone);

// Inicia sessão se ainda não iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configura exibição de erros baseado em APP_DEBUG
if (env('APP_DEBUG', false)) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}
