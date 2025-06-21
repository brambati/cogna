<?php
/**
 * API de Registro - POST /api/auth/register
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
    
    $username = trim($input['username'] ?? '');
    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';
    $confirmPassword = $input['confirm_password'] ?? '';
    $firstName = trim($input['first_name'] ?? '');
    $lastName = trim($input['last_name'] ?? '');
    
    // Validações
    $errors = [];
    
    if (empty($username)) {
        $errors[] = 'Nome de usuário é obrigatório';
    } elseif (strlen($username) < 3) {
        $errors[] = 'Nome de usuário deve ter pelo menos 3 caracteres';
    }
    
    if (empty($email)) {
        $errors[] = 'Email é obrigatório';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email inválido';
    }
    
    if (empty($password)) {
        $errors[] = 'Senha é obrigatória';
    } else {
        $passwordErrors = validatePasswordStrength($password);
        $errors = array_merge($errors, $passwordErrors);
    }
    
    if ($password !== $confirmPassword) {
        $errors[] = 'Senhas não conferem';
    }
    
    if (!empty($errors)) {
        http_response_code(422);
        echo json_encode(['error' => implode(', ', $errors)]);
        exit;
    }
    
    // Conectar ao banco
    $config = require '../../config/database.php';
    $pdo = new PDO($config['dsn'], $config['username'], $config['password'], $config['options']);
    $fluent = new Envms\FluentPDO\Query($pdo);
    
    // Verificar se usuário já existe
    $existingUser = $fluent->from('users')
        ->where('email = ? OR username = ?', $email, $username)
        ->fetch();
    
    if ($existingUser) {
        http_response_code(409);
        echo json_encode(['error' => 'Email ou nome de usuário já está em uso']);
        exit;
    }
    
    // Criar usuário
    $userId = $fluent->insertInto('users')->values([
        'username' => $username,
        'email' => $email,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'first_name' => $firstName,
        'last_name' => $lastName,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ])->execute();
    
    if (!$userId) {
        http_response_code(500);
        echo json_encode(['error' => 'Erro ao criar usuário']);
        exit;
    }
    
    // Gerar token JWT
    $token = generateJWT([
        'user_id' => $userId,
        'username' => $username,
        'email' => $email,
        'exp' => time() + 24 * 3600 // 1 dia
    ]);
    
    // Resposta de sucesso
    $response = [
        'success' => true,
        'message' => 'Usuário criado com sucesso',
        'data' => [
            'user' => [
                'id' => $userId,
                'username' => $username,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email
            ],
            'token' => $token,
            'expires_in' => 24 * 3600
        ]
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log('Register API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno do servidor']);
} 