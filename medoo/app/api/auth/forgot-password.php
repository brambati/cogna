<?php
/**
 * API de Esqueci a Senha - POST /api/auth/forgot-password
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
    
    $email = trim($input['email'] ?? '');
    
    // Validações
    $errors = [];
    
    if (empty($email)) {
        $errors['email'] = 'Email é obrigatório';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Email inválido';
    }
    
    if (!empty($errors)) {
        http_response_code(422);
        echo json_encode(['errors' => $errors]);
        exit;
    }
    
    // Conectar ao banco
    $config = require '../../config/database.php';
    $database = new Medoo\Medoo($config);
    $userModel = new User($database);
    
    // Buscar usuário por email
    $user = $userModel->findByEmail($email);
    
    // Sempre retornar sucesso por segurança (não revelar se email existe)
    $response = [
        'success' => true,
        'message' => 'Se o email estiver cadastrado, você receberá instruções para redefinir sua senha'
    ];
    
    // Se usuário existe, gerar token e "enviar" email
    if ($user) {
        // Gerar token de reset (válido por 1 hora)
        $resetToken = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 hora
        
        // Salvar token no banco
        $tokenData = [
            'user_id' => $user['id'],
            'token' => hash('sha256', $resetToken), // Guardar hash do token
            'type' => 'password_reset',
            'expires_at' => $expiresAt,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        // Aqui você salvaria o token na tabela password_reset_tokens
        // Por enquanto, vamos simular
        
        // Em produção, você enviaria um email real aqui
        // Exemplo de URL: https://seusite.com/reset-password.php?token=$resetToken
        
        // Log para desenvolvimento (remover em produção)
        error_log("Reset token for {$email}: {$resetToken}");
        error_log("Reset URL: http://localhost/reset-password.php?token={$resetToken}");
    }
    
    http_response_code(200);
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log('Forgot Password API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno do servidor']);
}
