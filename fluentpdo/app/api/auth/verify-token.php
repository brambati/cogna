<?php
/**
 * API de Verificação de Token - GET /api/auth/verify-token
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Verificar se é GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido']);
    exit;
}

require_once '../../config/database.php';
require_once '../../security.php';

try {
    // Obter token da query string ou header
    $token = $_GET['token'] ?? null;
    
    if (!$token) {
        // Tentar obter do header Authorization
        $user = getUserFromToken();
        if ($user) {
            echo json_encode([
                'success' => true,
                'valid' => true,
                'data' => $user
            ]);
            exit;
        }
    }
    
    if (!$token) {
        http_response_code(400);
        echo json_encode(['error' => 'Token não fornecido']);
        exit;
    }
    
    // Conectar ao banco
    $config = require '../../config/database.php';
    $pdo = new PDO($config['dsn'], $config['username'], $config['password'], $config['options']);
    $fluent = new Envms\FluentPDO\Query($pdo);
    
    // Verificar se é um token de reset de senha
    $user = $fluent->from('users')
        ->select(['id', 'email', 'first_name', 'last_name', 'reset_token_expires'])
        ->where('reset_token = ?', $token)
        ->fetch();
    
    if ($user) {
        // Verificar se token expirou
        $isExpired = strtotime($user['reset_token_expires']) < time();
        
        echo json_encode([
            'success' => true,
            'valid' => !$isExpired,
            'type' => 'reset_token',
            'expired' => $isExpired,
            'data' => [
                'user_id' => $user['id'],
                'email' => $user['email'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name']
            ]
        ]);
        exit;
    }
    
    // Se não é token de reset, pode ser JWT
    $payload = verifyJWT($token);
    
    if ($payload) {
        echo json_encode([
            'success' => true,
            'valid' => true,
            'type' => 'jwt_token',
            'data' => $payload
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'valid' => false,
            'error' => 'Token inválido ou expirado'
        ]);
    }
    
} catch (Exception $e) {
    error_log('Verify Token API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno do servidor']);
} 