<?php

/**
 * API Stats - Medoo
 */

require_once __DIR__ . '/../../app/security.php';
require_once __DIR__ . '/../../app/models/User.php';
require_once __DIR__ . '/../../app/models/Task.php';

// Headers CORS e JSON
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // Apenas GET é permitido
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['success' => false, 'errors' => ['Método não permitido']]);
        exit();
    }
    
    // Verificar autenticação
    $user = verifyApiAuthentication();
    if (!$user) {
        http_response_code(401);
        echo json_encode(['success' => false, 'errors' => ['Não autorizado']]);
        exit();
    }
    
    $taskModel = new Task();
    $result = $taskModel->getStats($user['user_id']);
    
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log("Erro na API de estatísticas: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'errors' => ['Erro interno do servidor']]);
}

/**
 * Verificar autenticação da API
 */
function verifyApiAuthentication(): ?array {
    // Tentar autenticação por sessão primeiro
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (isset($_SESSION['user_id']) && isset($_SESSION['session_token'])) {
        $userModel = new User();
        $sessionData = $userModel->verifySession($_SESSION['session_token']);
        
        if ($sessionData) {
            return [
                'user_id' => $sessionData['user_id'],
                'username' => $sessionData['username'],
                'email' => $sessionData['email']
            ];
        }
    }
    
    // Tentar autenticação por API token
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    
    if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        $apiToken = $matches[1];
        
        // Verificar token na base de dados usando Medoo
        $config = require __DIR__ . '/../../app/config/database.php';
        $database = new Medoo\Medoo($config);
        
        $result = $database->get('user_sessions', [
            '[>]users' => ['user_id' => 'id']
        ], [
            'user_sessions.user_id',
            'users.username',
            'users.email'
        ], [
            'user_sessions.api_token' => $apiToken,
            'user_sessions.is_active' => 1,
            'user_sessions.expires_at[>]' => date('Y-m-d H:i:s'),
            'users.is_active' => 1
        ]);
        
        if ($result) {
            return $result;
        }
    }
    
    return null;
}