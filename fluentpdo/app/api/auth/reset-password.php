<?php
/**
 * API de Reset de Senha - POST /api/auth/reset-password
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

require_once '../../config/database.php';
require_once '../../security.php';

try {
    // Obter dados JSON
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['error' => 'Dados inválidos']);
        exit;
    }
    
    $token = trim($input['token'] ?? '');
    $password = $input['password'] ?? '';
    $confirmPassword = $input['confirm_password'] ?? '';
    
    // Validações
    if (empty($token)) {
        http_response_code(422);
        echo json_encode(['error' => 'Token é obrigatório']);
        exit;
    }
    
    if (empty($password)) {
        http_response_code(422);
        echo json_encode(['error' => 'Nova senha é obrigatória']);
        exit;
    }
    
    $passwordErrors = validatePasswordStrength($password);
    if (!empty($passwordErrors)) {
        http_response_code(422);
        echo json_encode(['error' => implode(', ', $passwordErrors)]);
        exit;
    }
    
    if ($password !== $confirmPassword) {
        http_response_code(422);
        echo json_encode(['error' => 'Senhas não conferem']);
        exit;
    }
    
    // Conectar ao banco
    $config = require '../../config/database.php';
    $pdo = new PDO($config['dsn'], $config['username'], $config['password'], $config['options']);
    $fluent = new Envms\FluentPDO\Query($pdo);
    
    // Buscar usuário pelo token
    $user = $fluent->from('users')
        ->select(['id', 'email', 'reset_token_expires'])
        ->where('reset_token = ?', $token)
        ->fetch();
    
    if (!$user) {
        http_response_code(400);
        echo json_encode(['error' => 'Token inválido']);
        exit;
    }
    
    // Verificar se token expirou
    if (strtotime($user['reset_token_expires']) < time()) {
        http_response_code(400);
        echo json_encode(['error' => 'Token expirado']);
        exit;
    }
    
    // Atualizar senha e limpar token
    $fluent->update('users')
        ->set([
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'reset_token' => null,
            'reset_token_expires' => null,
            'updated_at' => date('Y-m-d H:i:s')
        ])
        ->where('id', $user['id'])
        ->execute();
    
    // Resposta de sucesso
    echo json_encode([
        'success' => true,
        'message' => 'Senha redefinida com sucesso'
    ]);
    
} catch (Exception $e) {
    error_log('Reset Password API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno do servidor']);
} 