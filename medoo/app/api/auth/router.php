<?php
/**
 * Roteador simples para APIs de autenticação
 * 
 * Uso: Redirecione todas as requisições /api/auth/* para este arquivo
 * 
 * Configuração no .htaccess:
 * RewriteEngine On
 * RewriteRule ^api/auth/(.*)$ app/api/auth/router.php [QSA,L]
 */

// Obter o caminho da requisição
$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Remover query string
$path = parse_url($requestUri, PHP_URL_PATH);

// Extrair o endpoint da URL
$pathParts = explode('/', trim($path, '/'));
$endpoint = end($pathParts);

// Definir os endpoints disponíveis
$endpoints = [
    'login' => 'login.php',
    'register' => 'register.php',
    'logout' => 'logout.php',
    'forgot-password' => 'forgot-password.php',
    'reset-password' => 'reset-password.php',
    'verify-token' => 'verify-token.php'
];

// Verificar se o endpoint existe
if (!isset($endpoints[$endpoint]) || !file_exists(__DIR__ . '/' . $endpoints[$endpoint])) {
    http_response_code(404);
    echo json_encode(['error' => 'Endpoint não encontrado']);
    exit;
}

// Incluir o arquivo do endpoint
require_once $endpoints[$endpoint];
