<?php

/**
 * API Stats - FluentPDO
 */

require_once __DIR__ . '/../../app/helpers/security.php';
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
        
        // Verificar token na base de dados
        $config = require __DIR__ . '/../../app/config/database.php';
        $dsn = $config['dsn'];
        $pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
        
        $stmt = $pdo->prepare("
            SELECT us.user_id, u.username, u.email 
            FROM user_sessions us 
            JOIN users u ON us.user_id = u.id 
            WHERE us.api_token = ? AND us.is_active = 1 AND us.expires_at > NOW() AND u.is_active = 1
        ");
        $stmt->execute([$apiToken]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            return $result;
        }
    }
    
    return null;
}