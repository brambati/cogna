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
require_once '../../models/User.php';
require_once '../../security.php';

try {
    // Obter dados JSON
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['error' => 'Dados inválidos']);
        exit;
    }
      $first_name = trim($input['first_name'] ?? '');
    $last_name = trim($input['last_name'] ?? '');
    $username = trim($input['username'] ?? '');
    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';
    $confirmPassword = $input['confirm_password'] ?? '';
    
    // Validações
    $errors = [];
    
    if (empty($first_name)) {
        $errors['first_name'] = 'Primeiro nome é obrigatório';
    } elseif (strlen($first_name) < 2) {
        $errors['first_name'] = 'Primeiro nome deve ter pelo menos 2 caracteres';
    } elseif (strlen($first_name) > 50) {
        $errors['first_name'] = 'Primeiro nome deve ter no máximo 50 caracteres';
    }
    
    if (empty($last_name)) {
        $errors['last_name'] = 'Sobrenome é obrigatório';
    } elseif (strlen($last_name) < 2) {
        $errors['last_name'] = 'Sobrenome deve ter pelo menos 2 caracteres';
    } elseif (strlen($last_name) > 50) {
        $errors['last_name'] = 'Sobrenome deve ter no máximo 50 caracteres';
    }
    
    if (empty($username)) {
        $errors['username'] = 'Nome de usuário é obrigatório';
    } elseif (strlen($username) < 3) {
        $errors['username'] = 'Nome de usuário deve ter pelo menos 3 caracteres';
    } elseif (strlen($username) > 50) {
        $errors['username'] = 'Nome de usuário deve ter no máximo 50 caracteres';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors['username'] = 'Nome de usuário deve conter apenas letras, números e underscore';
    }
    
    if (empty($email)) {
        $errors['email'] = 'Email é obrigatório';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Email inválido';
    } elseif (strlen($email) > 100) {
        $errors['email'] = 'Email deve ter no máximo 100 caracteres';
    }
      if (empty($password)) {
        $errors['password'] = 'Senha é obrigatória';
    } elseif (strlen($password) < 6) {
        $errors['password'] = 'Senha deve ter pelo menos 6 caracteres';
    }
    
    if (empty($confirmPassword)) {
        $errors['confirm_password'] = 'Confirmação de senha é obrigatória';
    } elseif ($password !== $confirmPassword) {
        $errors['confirm_password'] = 'Senhas não conferem';
    }
    
    if (!empty($errors)) {
        http_response_code(422);
        echo json_encode(['errors' => $errors]);
        exit;
    }
    
    // Conectar ao banco
    $config = require '../../config/database.php';
    $database = new Medoo\Medoo($config);
    
    // Verificar se username já existe
    $existingUsername = $database->get('users', 'id', ['username' => $username]);
    if ($existingUsername) {
        http_response_code(409);
        echo json_encode(['error' => 'Nome de usuário já está em uso']);
        exit;
    }
    
    // Verificar se email já existe
    $existingEmail = $database->get('users', 'id', ['email' => $email]);
    if ($existingEmail) {
        http_response_code(409);
        echo json_encode(['error' => 'Email já está em uso']);
        exit;
    }
    
    // Criar usuário
    $userData = [
        'username' => $username,
        'email' => $email,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'first_name' => $first_name,
        'last_name' => $last_name,
        'is_active' => 1,
        'email_verified' => 0,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    $userId = $database->insert('users', $userData);
    
    if (!$userId) {
        http_response_code(500);
        echo json_encode(['error' => 'Erro ao criar usuário']);
        exit;
    }
    
    // Gerar token JWT
    $token = generateJWT([
        'user_id' => $userId,
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
                'first_name' => $first_name,
                'last_name' => $last_name,
                'email' => $email,
                'created_at' => $userData['created_at']
            ],
            'token' => $token,
            'expires_in' => 24 * 3600
        ]
    ];
    
    http_response_code(201);
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log('Register API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno do servidor']);
}
