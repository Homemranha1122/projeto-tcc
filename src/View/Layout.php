<?php

namespace App\View;

/**
 * Classe básica para layout
 * 
 * Não substitui o layout gigante em conexao.php ainda.
 * Serve como exemplo de separação de responsabilidades
 * para migração gradual futura.
 */
class Layout
{
    /**
     * Renderiza o header básico
     * 
     * @param string $titulo
     * @param bool $incluirMapa
     * @return void
     */
    public static function header($titulo = 'Sistema de Monitoramento de Enchentes', $incluirMapa = false)
    {
        ?>
        <!DOCTYPE html>
        <html lang="pt-br">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?= e($titulo) ?></title>
            <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🌦️</text></svg>">
            
            <link rel="preconnect" href="https://fonts.googleapis.com">
            <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
            <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
            
            <?php if ($incluirMapa): ?>
            <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
            <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
            <?php endif; ?>
            
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body { 
                    font-family: 'Poppins', sans-serif; 
                    background: #f8f9fa;
                    color: #212529;
                    line-height: 1.6;
                }
                .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
                header { 
                    background: linear-gradient(135deg, #3a86ff 0%, #8338ec 100%);
                    color: white;
                    padding: 20px 0;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
                }
                header .container { display: flex; justify-content: space-between; align-items: center; }
                h1 { font-size: 1.5rem; }
                nav a { color: white; text-decoration: none; margin-left: 20px; font-weight: 500; }
                nav a:hover { text-decoration: underline; }
                .card { 
                    background: white; 
                    padding: 20px; 
                    border-radius: 8px; 
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                    margin-bottom: 20px;
                }
            </style>
        </head>
        <body>
            <header>
                <div class="container">
                    <h1><?= e($titulo) ?></h1>
                    <nav>
                        <a href="/index.php">Início</a>
                        <a href="/historico.php">Dashboard</a>
                        <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                            <a href="/admin/index.php">Admin</a>
                        <?php endif; ?>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <span>Olá, <?= e($_SESSION['user_name']) ?></span>
                            <a href="/logout.php">Sair</a>
                        <?php else: ?>
                            <a href="/login.php">Login</a>
                        <?php endif; ?>
                    </nav>
                </div>
            </header>
            <div class="container">
        <?php
    }

    /**
     * Renderiza o footer básico
     * 
     * @return void
     */
    public static function footer()
    {
        ?>
            </div>
            <footer style="text-align: center; padding: 20px; color: #6c757d; margin-top: 40px;">
                <p>&copy; <?= date('Y') ?> Olimclima - Sistema de Monitoramento Colaborativo de Enchentes</p>
            </footer>
        </body>
        </html>
        <?php
    }
}
