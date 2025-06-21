<?php
/**
 * API de Logout - POST /api/auth/logout
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Verificar se é POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido']);
    exit;
}

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
        echo json_encode(['error' => 'Token inválido']);
        exit;
    }
    
    // Para um logout simples, apenas retornamos sucesso
    // Em uma implementação mais robusta, você poderia:
    // 1. Manter uma blacklist de tokens
    // 2. Salvar tokens revogados no banco de dados
    // 3. Usar refresh tokens
    
    $response = [
        'success' => true,
        'message' => 'Logout realizado com sucesso'
    ];
    
    http_response_code(200);
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log('Logout API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno do servidor']);
}
