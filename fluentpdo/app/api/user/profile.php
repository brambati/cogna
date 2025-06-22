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

try {
    // Conectar ao banco
    $config = require '../../config/database.php';
    $pdo = new PDO(
        "mysql:host={$config['host']};dbname={$config['database']};charset=utf8mb4",
        $config['username'],
        $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    $user_id = $_SESSION['user_id'];
    $method = $_SERVER['REQUEST_METHOD'];
    
    // Rate limiting
    $rate_limit = SecurityHelper::checkRateLimit('profile_' . SecurityHelper::getClientIP(), 10, 10);
    if (!$rate_limit['allowed']) {
        http_response_code(429);
        echo json_encode([
            'error' => 'Muitas tentativas. Tente novamente em alguns minutos.',
            'reset_time' => $rate_limit['reset_time']
        ]);
        exit;
    }
    
    switch ($method) {
        case 'GET':
            // Obter perfil do usuário
            $stmt = $pdo->prepare("
                SELECT id, name, email, created_at, updated_at 
                FROM users 
                WHERE id = ?
            ");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                http_response_code(404);
                echo json_encode(['error' => 'Usuário não encontrado']);
                break;
            }
            
            // Buscar estatísticas do usuário
            $stats_stmt = $pdo->prepare("
                SELECT 
                    COUNT(*) as total_tasks,
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_tasks,
                    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_tasks,
                    COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as in_progress_tasks
                FROM tasks 
                WHERE user_id = ?
            ");
            $stats_stmt->execute([$user_id]);
            $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
            
            $user['stats'] = $stats;
            
            echo json_encode(['success' => true, 'data' => $user]);
            break;
            
        case 'PUT':
            // Atualizar perfil do usuário
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (empty($input)) {
                http_response_code(400);
                echo json_encode(['error' => 'Dados não fornecidos']);
                break;
            }
            
            // Validar dados
            $validation = SecurityHelper::validateUserInput($input, [
                'name' => 'required|min:2|max:100',
                'email' => 'required|email'
            ]);
            
            if (!$validation['valid']) {
                http_response_code(422);
                echo json_encode([
                    'error' => 'Dados inválidos',
                    'details' => $validation['errors']
                ]);
                break;
            }
            
            // Verificar se email já está em uso por outro usuário
            $stmt = $pdo->prepare("
                SELECT id FROM users 
                WHERE email = ? AND id != ?
            ");
            $stmt->execute([trim($input['email']), $user_id]);
            
            if ($stmt->fetch()) {
                http_response_code(409);
                echo json_encode(['error' => 'Este email já está sendo usado por outro usuário']);
                break;
            }
            
            // Atualizar dados do usuário
            $stmt = $pdo->prepare("
                UPDATE users 
                SET name = ?, email = ?, updated_at = NOW()
                WHERE id = ?
            ");
            
            $result = $stmt->execute([
                SecurityHelper::sanitizeInput($input['name']),
                filter_var($input['email'], FILTER_SANITIZE_EMAIL),
                $user_id
            ]);
            
            if ($stmt->rowCount() > 0) {
                // Atualizar dados na sessão
                $_SESSION['user_name'] = SecurityHelper::sanitizeInput($input['name']);
                $_SESSION['user_email'] = filter_var($input['email'], FILTER_SANITIZE_EMAIL);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Perfil atualizado com sucesso'
                ]);
                
                // Log do evento
                SecurityHelper::logSecurityEvent('profile_updated', [
                    'user_id' => $user_id,
                    'updated_fields' => ['name', 'email']
                ]);
            } else {
                echo json_encode([
                    'success' => true,
                    'message' => 'Nenhuma alteração foi realizada'
                ]);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Método não permitido']);
            break;
    }
    
} catch (Exception $e) {
    error_log("Erro no perfil do usuário: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno do servidor']);
}
?> 