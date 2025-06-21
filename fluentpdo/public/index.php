<?php

/**
 * Task Manager - FluentPDO
 * Ponto de entrada da aplicação
 */

// Configurações iniciais
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Headers de segurança
require_once __DIR__ . '/../app/helpers/security.php';
setSecurityHeaders();
forceHTTPS();

// Autoload do Composer
require_once __DIR__ . '/../vendor/autoload.php';

// Iniciar sessão (configuração para HTTP temporariamente)
session_start([
    'cookie_lifetime' => 86400,
    'cookie_secure' => false, // Desabilitado para HTTP
    'cookie_httponly' => true,
    'cookie_samesite' => 'Strict',
    'use_strict_mode' => true
]);

// Routing simples
$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);

// Remover query string para routing
$route = strtok($path, '?');

// API Routes
if (strpos($route, '/api/') === 0) {
    header('Content-Type: application/json');
    
    switch ($route) {
        case '/api/auth/login':
            require_once __DIR__ . '/../app/api/auth/login.php';
            break;
            
        case '/api/auth/register':
            require_once __DIR__ . '/../app/api/auth/register.php';
            break;
            
        case '/api/auth/logout':
            require_once __DIR__ . '/../app/api/auth/logout.php';
            break;
            
        case '/api/tasks':
            require_once __DIR__ . '/../app/api/tasks.php';
            break;
            
        case '/api/categories':
            require_once __DIR__ . '/../app/api/categories.php';
            break;
            
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint não encontrado']);
            break;
    }
    exit;
}

// Web Routes
switch ($route) {
    case '/':
    case '/dashboard':
        require_once __DIR__ . '/dashboard.php';
        break;
        
    case '/login':
        require_once __DIR__ . '/login.php';
        break;
        
    case '/register':
        require_once __DIR__ . '/register.php';
        break;
        
    case '/logout':
        require_once __DIR__ . '/logout.php';
        break;
        
    default:
        http_response_code(404);
        ?>
        <!DOCTYPE html>
        <html lang="pt-BR">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>404 - Página não encontrada</title>
            <link rel="stylesheet" href="/css/style.css">
        </head>
        <body>
            <div class="container">
                <div class="error-page">
                    <h1>404</h1>
                    <h2>Página não encontrada</h2>
                    <p>A página que você está procurando não existe.</p>
                    <a href="/" class="btn btn-primary">Voltar ao início</a>
                </div>
            </div>
        </body>
        </html>
        <?php
        break;
}