<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../../../vendor/autoload.php';

session_start();

// Verificar autenticação
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido']);
    exit;
}

try {
    // Conectar ao banco
    $config = require '../../config/database.php';
    $database = new Medoo\Medoo($config);
    
    $user_id = $_SESSION['user_id'];
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validar dados de entrada
    if (empty($input['current_password']) || empty($input['new_password']) || empty($input['confirm_password'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Todos os campos são obrigatórios']);
        exit;
    }
    
    if ($input['new_password'] !== $input['confirm_password']) {
        http_response_code(400);
        echo json_encode(['error' => 'Nova senha e confirmação não coincidem']);
        exit;
    }
    
    // Validar força da nova senha
    if (strlen($input['new_password']) < 6) {
        http_response_code(400);
        echo json_encode(['error' => 'Nova senha deve ter pelo menos 6 caracteres']);
        exit;
    }
    
    // Buscar senha atual do usuário
    $user = $database->get('users', [
        'id',
        'password_hash'
    ], [
        'id' => $user_id
    ]);
    
    if (!$user) {
        http_response_code(404);
        echo json_encode(['error' => 'Usuário não encontrado']);
        exit;
    }
    
    // Verificar senha atual
    if (!password_verify($input['current_password'], $user['password_hash'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Senha atual incorreta']);
        exit;
    }
    
    // Verificar se a nova senha é diferente da atual
    if (password_verify($input['new_password'], $user['password_hash'])) {
        http_response_code(400);
        echo json_encode(['error' => 'A nova senha deve ser diferente da senha atual']);
        exit;
    }
    
    // Criptografar nova senha
    $new_password_hash = password_hash($input['new_password'], PASSWORD_DEFAULT);
    
    // Atualizar senha no banco
    $result = $database->update('users', [
        'password_hash' => $new_password_hash,
        'updated_at' => date('Y-m-d H:i:s')
    ], [
        'id' => $user_id
    ]);
    
    if ($result->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Senha alterada com sucesso'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Erro ao alterar senha']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?> 