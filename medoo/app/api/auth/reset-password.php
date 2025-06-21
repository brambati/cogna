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
    
    $token = trim($input['token'] ?? '');
    $password = $input['password'] ?? '';
    $confirmPassword = $input['confirm_password'] ?? '';
    
    // Validações
    $errors = [];
    
    if (empty($token)) {
        $errors['token'] = 'Token é obrigatório';
    }
    
    if (empty($password)) {
        $errors['password'] = 'Nova senha é obrigatória';
    } elseif (strlen($password) < 6) {
        $errors['password'] = 'Senha deve ter pelo menos 6 caracteres';
    } elseif (!preg_match('/^(?=.*[A-Za-z])(?=.*\d)/', $password)) {
        $errors['password'] = 'Senha deve conter pelo menos uma letra e um número';
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
    $userModel = new User($database);
    
    // Verificar token (em uma implementação real, você consultaria a tabela password_reset_tokens)
    // Por enquanto, vamos simular a validação
    
    // Hash do token para comparar com o banco
    $hashedToken = hash('sha256', $token);
    
    // Aqui você faria uma consulta real:
    // SELECT * FROM password_reset_tokens 
    // WHERE token = $hashedToken 
    //   AND expires_at > NOW() 
    //   AND used_at IS NULL
    
    // Simulação - assumindo que o token é válido se tiver 64 caracteres
    if (strlen($token) !== 64) {
        http_response_code(400);
        echo json_encode(['error' => 'Token inválido ou expirado']);
        exit;
    }
    
    // Simular busca do usuário pelo token
    // Em produção, você obteria o user_id da tabela de tokens
    // Por enquanto, vamos assumir que existe um usuário
    
    // Atualizar senha do usuário
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Aqui você faria:
    // 1. Atualizar a senha do usuário
    // 2. Marcar o token como usado
    // 3. Invalidar outros tokens do usuário
    
    // Simulação da atualização
    $updateData = [
        'password' => $hashedPassword,
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    // Em produção:
    // $userModel->update($userId, $updateData);
    // $this->markTokenAsUsed($hashedToken);
    
    $response = [
        'success' => true,
        'message' => 'Senha redefinida com sucesso'
    ];
    
    http_response_code(200);
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log('Reset Password API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno do servidor']);
}
