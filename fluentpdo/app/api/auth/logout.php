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
    // Obter usuário do token
    $user = getUserFromToken();
    
    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'Token não fornecido ou inválido']);
        exit;
    }
    
    // Para JWT, não há necessidade de invalidar o token no servidor
    // Em uma implementação mais robusta, você poderia manter uma blacklist de tokens
    
    // Resposta de sucesso
    echo json_encode([
        'success' => true,
        'message' => 'Logout realizado com sucesso'
    ]);
    
} catch (Exception $e) {
    error_log('Logout API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno do servidor']);
} 