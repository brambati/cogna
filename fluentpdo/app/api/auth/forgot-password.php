<?php
/**
 * API de Esqueci Senha - POST /api/auth/forgot-password
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
    
    $email = trim($input['email'] ?? '');
    
    // Validações
    if (empty($email)) {
        http_response_code(422);
        echo json_encode(['error' => 'Email é obrigatório']);
        exit;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(422);
        echo json_encode(['error' => 'Email inválido']);
        exit;
    }
    
    // Conectar ao banco
    $config = require '../../config/database.php';
    $pdo = new PDO($config['dsn'], $config['username'], $config['password'], $config['options']);
    $fluent = new Envms\FluentPDO\Query($pdo);
    
    // Buscar usuário
    $user = $fluent->from('users')
        ->select(['id', 'email', 'first_name'])
        ->where('email = ?', $email)
        ->fetch();
    
    // Sempre retornar sucesso por segurança (não vazar se email existe)
    if (!$user) {
        echo json_encode([
            'success' => true,
            'message' => 'Se o email existir em nosso sistema, você receberá um link para redefinir sua senha'
        ]);
        exit;
    }
    
    // Gerar token de recuperação
    $token = generateToken();
    $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hora
    
    // Atualizar usuário com token
    $fluent->update('users')
        ->set([
            'reset_token' => $token,
            'reset_token_expires' => $expires,
            'updated_at' => date('Y-m-d H:i:s')
        ])
        ->where('id', $user['id'])
        ->execute();
    
    // Resposta de sucesso
    echo json_encode([
        'success' => true,
        'message' => 'Se o email existir em nosso sistema, você receberá um link para redefinir sua senha',
        'data' => [
            'reset_link' => '/reset-password.php?token=' . $token, // Para desenvolvimento
            'expires_in' => 3600
        ]
    ]);
    
} catch (Exception $e) {
    error_log('Forgot Password API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno do servidor']);
} 