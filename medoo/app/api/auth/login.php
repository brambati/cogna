<?php
/**
 * API de Login - POST /api/auth/login
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
    
    $login = trim($input['login'] ?? $input['email'] ?? '');
    $password = $input['password'] ?? '';
    
    // Validações
    if (empty($login)) {
        http_response_code(422);
        echo json_encode(['error' => 'Email ou usuário é obrigatório']);
        exit;
    }
    
    if (empty($password)) {
        http_response_code(422);
        echo json_encode(['error' => 'Senha é obrigatória']);
        exit;
    }
    
    // Conectar ao banco
    $config = require '../../config/database.php';
    $database = new Medoo\Medoo($config);
    
    // Buscar usuário por email ou username
    $user = $database->get('users', [
        'id',
        'username', 
        'email',
        'password_hash',
        'first_name',
        'last_name',
        'is_active'
    ], [
        'OR' => [
            'email' => $login,
            'username' => $login
        ],
        'is_active' => 1
    ]);
    
    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'Credenciais inválidas']);
        exit;
    }
    
    // Verificar senha
    if (!password_verify($password, $user['password_hash'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Credenciais inválidas']);
        exit;
    }
    
    // Gerar token JWT
    $token = generateJWT([
        'user_id' => $user['id'],
        'username' => $user['username'],
        'email' => $user['email'],
        'exp' => time() + 24 * 3600 // 1 dia
    ]);
    
    // Atualizar último login
    $database->update('users', [
        'updated_at' => date('Y-m-d H:i:s')
    ], [
        'id' => $user['id']
    ]);
    
    // Resposta de sucesso
    $response = [
        'success' => true,
        'message' => 'Login realizado com sucesso',
        'data' => [
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
                'email' => $user['email']
            ],
            'token' => $token,
            'expires_in' => 24 * 3600
        ]
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log('Login API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno do servidor']);
}
