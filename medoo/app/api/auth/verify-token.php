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
require_once '../../models/User.php';
require_once '../../security.php';

try {
    // Obter token do header Authorization
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    
    if (empty($authHeader)) {
        http_response_code(401);
        echo json_encode(['error' => 'Token não fornecido']);
        exit;
    }
    
    // Extrair token (formato: "Bearer TOKEN")
    $token = str_replace('Bearer ', '', $authHeader);
    
    // Verificar se token é válido
    $payload = verifyJWT($token);
    
    if (!$payload) {
        http_response_code(401);
        echo json_encode(['error' => 'Token inválido ou expirado']);
        exit;
    }
    
    // Conectar ao banco para verificar se usuário ainda existe e está ativo
    $config = require '../../config/database.php';
    $database = new Medoo\Medoo($config);
    $userModel = new User($database);
    
    $user = $userModel->findById($payload['user_id']);
    
    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'Usuário não encontrado']);
        exit;
    }
    
    if ($user['status'] !== 'active') {
        http_response_code(403);
        echo json_encode(['error' => 'Usuário inativo']);
        exit;
    }
    
    // Calcular tempo restante do token
    $expiresIn = $payload['exp'] - time();
    
    if ($expiresIn <= 0) {
        http_response_code(401);
        echo json_encode(['error' => 'Token expirado']);
        exit;
    }
    
    // Token válido
    $response = [
        'success' => true,
        'message' => 'Token válido',
        'data' => [
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'created_at' => $user['created_at']
            ],
            'expires_in' => $expiresIn,
            'expires_at' => date('Y-m-d H:i:s', $payload['exp'])
        ]
    ];
    
    http_response_code(200);
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log('Verify Token API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno do servidor']);
}
