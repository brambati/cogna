<?php
header('Content-Type: application/json');
require_once '../../../vendor/autoload.php';
require_once '../../helpers/security.php';

session_start();

// Verificar autenticação
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

// Apenas aceitar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido']);
    exit;
}

try {
    // Rate limiting mais restritivo para mudança de senha
    $rate_limit = SecurityHelper::checkRateLimit('change_password_' . SecurityHelper::getClientIP(), 3, 30);
    if (!$rate_limit['allowed']) {
        http_response_code(429);
        echo json_encode([
            'error' => 'Muitas tentativas de alteração de senha. Tente novamente em 30 minutos.',
            'reset_time' => $rate_limit['reset_time']
        ]);
        exit;
    }
    
    // Conectar ao banco
    $config = require '../../config/database.php';
    $pdo = new PDO(
        "mysql:host={$config['host']};dbname={$config['database']};charset=utf8mb4",
        $config['username'],
        $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    $user_id = $_SESSION['user_id'];
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validar dados de entrada
    if (empty($input)) {
        http_response_code(400);
        echo json_encode(['error' => 'Dados não fornecidos']);
        exit;
    }
    
    // Campos obrigatórios
    $required_fields = ['current_password', 'new_password', 'confirm_password'];
    $missing_fields = [];
    
    foreach ($required_fields as $field) {
        if (empty($input[$field])) {
            $missing_fields[] = $field;
        }
    }
    
    if (!empty($missing_fields)) {
        http_response_code(422);
        echo json_encode([
            'error' => 'Campos obrigatórios não fornecidos',
            'missing_fields' => $missing_fields
        ]);
        exit;
    }
    
    // Verificar se nova senha e confirmação coincidem
    if ($input['new_password'] !== $input['confirm_password']) {
        http_response_code(422);
        echo json_encode(['error' => 'Nova senha e confirmação não coincidem']);
        exit;
    }
    
    // Validar força da nova senha
    $password_validation = SecurityHelper::validatePasswordStrength($input['new_password']);
    if (!$password_validation['valid']) {
        http_response_code(422);
        echo json_encode([
            'error' => 'Nova senha não atende aos critérios de segurança',
            'details' => $password_validation['errors'],
            'strength' => $password_validation['strength']
        ]);
        exit;
    }
    
    // Buscar usuário atual
    $stmt = $pdo->prepare("SELECT id, password FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        http_response_code(404);
        echo json_encode(['error' => 'Usuário não encontrado']);
        exit;
    }
    
    // Verificar senha atual
    if (!SecurityHelper::verifyPassword($input['current_password'], $user['password'])) {
        // Log tentativa de alteração com senha incorreta
        SecurityHelper::logSecurityEvent('password_change_failed', [
            'user_id' => $user_id,
            'reason' => 'incorrect_current_password'
        ]);
        
        http_response_code(401);
        echo json_encode(['error' => 'Senha atual incorreta']);
        exit;
    }
    
    // Verificar se nova senha é diferente da atual
    if (SecurityHelper::verifyPassword($input['new_password'], $user['password'])) {
        http_response_code(422);
        echo json_encode(['error' => 'A nova senha deve ser diferente da senha atual']);
        exit;
    }
    
    // Gerar hash da nova senha
    $new_password_hash = SecurityHelper::hashPassword($input['new_password']);
    
    // Atualizar senha no banco
    $stmt = $pdo->prepare("
        UPDATE users 
        SET password = ?, updated_at = NOW()
        WHERE id = ?
    ");
    
    $result = $stmt->execute([$new_password_hash, $user_id]);
    
    if ($result && $stmt->rowCount() > 0) {
        // Log da alteração bem-sucedida
        SecurityHelper::logSecurityEvent('password_changed', [
            'user_id' => $user_id,
            'password_strength' => $password_validation['strength']
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Senha alterada com sucesso',
            'password_strength' => $password_validation['strength']
        ]);
    } else {
        error_log("Falha ao atualizar senha para usuário {$user_id}");
        http_response_code(500);
        echo json_encode(['error' => 'Erro ao alterar senha']);
    }
    
} catch (Exception $e) {
    error_log("Erro na alteração de senha: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno do servidor']);
}
?> 