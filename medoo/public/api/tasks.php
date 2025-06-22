<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Incluir autoload e configurações
require_once '../../vendor/autoload.php';

session_start();

// Verificar autenticação
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

try {
    // Conectar ao banco
    $config = require '../../app/config/database.php';
    $database = new Medoo\Medoo($config);
    
    $user_id = $_SESSION['user_id'];
    $method = $_SERVER['REQUEST_METHOD'];
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $segments = explode('/', trim($path, '/'));
    
    // Aceitar ID tanto por URL path quanto por query parameter
    $task_id = null;
    if (isset($segments[3]) && is_numeric($segments[3])) {
        $task_id = (int)$segments[3];
    } elseif (isset($_GET['id']) && is_numeric($_GET['id'])) {
        $task_id = (int)$_GET['id'];
    }
    
    // Detectar ação especial via query parameter
    $action = $_GET['action'] ?? null;
    
    // Se for POST com action=patch, tratar como PATCH
    if ($method === 'POST' && $action === 'patch') {
        $method = 'PATCH';
    }
    
    switch ($method) {
        case 'GET':
            if ($task_id) {
                // Obter tarefa específica
                $task = $database->get('tasks', [
                    '[>]task_categories' => ['category_id' => 'id']
                ], [
                    'tasks.id', 'tasks.title', 'tasks.description',
                    'tasks.status', 'tasks.priority', 'tasks.due_date',
                    'task_categories.name(category_name)'
                ], ['tasks.id' => $task_id, 'tasks.user_id' => $user_id]);
                
                echo json_encode(['success' => true, 'data' => $task]);
            } else {
                // Listar tarefas com filtros
                $filters = ['tasks.user_id' => $user_id];
                
                if (!empty($_GET['status'])) $filters['tasks.status'] = $_GET['status'];
                if (!empty($_GET['priority'])) $filters['tasks.priority'] = $_GET['priority'];
                if (!empty($_GET['search'])) {
                    $search = '%' . $_GET['search'] . '%';
                    $filters['OR'] = [
                        'tasks.title[~]' => $search,
                        'tasks.description[~]' => $search
                    ];
                }
                
                $tasks = $database->select('tasks', [
                    '[>]task_categories' => ['category_id' => 'id']
                ], [
                    'tasks.id', 'tasks.title', 'tasks.description',
                    'tasks.status', 'tasks.priority', 'tasks.due_date',
                    'task_categories.name(category_name)'
                ], $filters);
                
                echo json_encode(['success' => true, 'data' => $tasks]);
            }
            break;
            
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (empty($input['title'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Título é obrigatório']);
                break;
            }
            
            $result = $database->insert('tasks', [
                'title' => $input['title'],
                'description' => $input['description'] ?? '',
                'category_id' => $input['category_id'] ?? null,
                'priority' => $input['priority'] ?? 'medium',
                'status' => $input['status'] ?? 'pending',
                'due_date' => $input['due_date'] ?? null,
                'user_id' => $user_id,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            echo json_encode(['success' => true, 'data' => ['id' => $database->id()]]);
            break;
            
        case 'PUT':
            if (!$task_id) {
                http_response_code(400);
                echo json_encode(['error' => 'ID obrigatório']);
                break;
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            $result = $database->update('tasks', [
                'title' => $input['title'],
                'description' => $input['description'] ?? '',
                'category_id' => $input['category_id'] ?? null,
                'priority' => $input['priority'] ?? 'medium',
                'status' => $input['status'] ?? 'pending',
                'due_date' => $input['due_date'] ?? null,
                'updated_at' => date('Y-m-d H:i:s')
            ], ['id' => $task_id, 'user_id' => $user_id]);
            
            echo json_encode(['success' => true, 'message' => 'Atualizado']);
            break;
            
        case 'DELETE':
            if (!$task_id) {
                http_response_code(400);
                echo json_encode(['error' => 'ID obrigatório']);
                break;
            }
            
            $database->delete('tasks', ['id' => $task_id, 'user_id' => $user_id]);
            echo json_encode(['success' => true, 'message' => 'Excluído']);
            break;
            
        case 'PATCH':
            if (!$task_id) {
                http_response_code(400);
                echo json_encode(['error' => 'ID obrigatório']);
                break;
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            $database->update('tasks', [
                'status' => $input['status'],
                'updated_at' => date('Y-m-d H:i:s')
            ], ['id' => $task_id, 'user_id' => $user_id]);
            
            echo json_encode(['success' => true, 'message' => 'Status atualizado']);
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