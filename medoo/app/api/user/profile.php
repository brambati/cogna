<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, PUT');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../../../vendor/autoload.php';

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
    $database = new Medoo\Medoo($config);
    
    $user_id = $_SESSION['user_id'];
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            // Obter dados do perfil
            $user = $database->get('users', [
                'id',
                'first_name',
                'last_name',
                'email',
                'created_at',
                'updated_at'
            ], [
                'id' => $user_id
            ]);
            
            if (!$user) {
                http_response_code(404);
                echo json_encode(['error' => 'Usuário não encontrado']);
                break;
            }
            
            // Estatísticas do usuário
            $stats = [
                'total_tasks' => $database->count('tasks', ['user_id' => $user_id]),
                'completed_tasks' => $database->count('tasks', [
                    'user_id' => $user_id,
                    'status' => 'completed'
                ]),
                'pending_tasks' => $database->count('tasks', [
                    'user_id' => $user_id,
                    'status' => 'pending'
                ]),
                'categories_count' => $database->count('task_categories', [
                    'user_id' => $user_id,
                    'is_active' => 1
                ])
            ];
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'user' => $user,
                    'stats' => $stats
                ]
            ]);
            break;
            
        case 'PUT':
            // Atualizar perfil
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (empty($input['first_name']) || empty($input['last_name'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Nome e sobrenome são obrigatórios']);
                break;
            }
            
            // Validar email se foi alterado
            if (!empty($input['email'])) {
                if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Email inválido']);
                    break;
                }
                
                // Verificar se email já está em uso por outro usuário
                $existing = $database->get('users', 'id', [
                    'email' => $input['email'],
                    'id[!]' => $user_id
                ]);
                
                if ($existing) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Este email já está em uso']);
                    break;
                }
            }
            
            // Atualizar dados
            $updateData = [
                'first_name' => trim($input['first_name']),
                'last_name' => trim($input['last_name']),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            if (!empty($input['email'])) {
                $updateData['email'] = $input['email'];
            }
            
            $result = $database->update('users', $updateData, ['id' => $user_id]);
            
            if ($result->rowCount() > 0) {
                // Atualizar sessão se necessário
                if (!empty($input['email'])) {
                    $_SESSION['email'] = $input['email'];
                }
                $_SESSION['name'] = trim($input['first_name'] . ' ' . $input['last_name']);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Perfil atualizado com sucesso'
                ]);
            } else {
                echo json_encode([
                    'success' => true,
                    'message' => 'Nenhuma alteração realizada'
                ]);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Método não permitido']);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?> 