<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH');
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
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $segments = explode('/', trim($path, '/'));
    
    // Extrair ID da tarefa se presente
    $task_id = null;
    if (isset($segments[4]) && is_numeric($segments[4])) {
        $task_id = (int)$segments[4];
    } elseif (isset($_GET['id']) && is_numeric($_GET['id'])) {
        $task_id = (int)$_GET['id'];
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
                    'tasks.created_at', 'tasks.updated_at',
                    'task_categories.name(category_name)',
                    'task_categories.color(category_color)'
                ], [
                    'tasks.id' => $task_id, 
                    'tasks.user_id' => $user_id
                ]);
                
                if (!$task) {
                    http_response_code(404);
                    echo json_encode(['error' => 'Tarefa não encontrada']);
                    break;
                }
                
                echo json_encode(['success' => true, 'data' => $task]);
            } else {
                // Listar todas as tarefas com filtros
                $filters = ['tasks.user_id' => $user_id];
                
                // Aplicar filtros se fornecidos
                if (!empty($_GET['status'])) {
                    $filters['tasks.status'] = $_GET['status'];
                }
                if (!empty($_GET['priority'])) {
                    $filters['tasks.priority'] = $_GET['priority'];
                }
                if (!empty($_GET['category_id'])) {
                    $filters['tasks.category_id'] = $_GET['category_id'];
                }
                if (!empty($_GET['search'])) {
                    $search = '%' . $_GET['search'] . '%';
                    $filters['OR'] = [
                        'tasks.title[~]' => $search,
                        'tasks.description[~]' => $search
                    ];
                }
                
                // Ordenação
                $order = 'tasks.created_at DESC';
                if (!empty($_GET['sort'])) {
                    switch ($_GET['sort']) {
                        case 'title_asc':
                            $order = 'tasks.title ASC';
                            break;
                        case 'title_desc':
                            $order = 'tasks.title DESC';
                            break;
                        case 'due_date_asc':
                            $order = 'tasks.due_date ASC';
                            break;
                        case 'due_date_desc':
                            $order = 'tasks.due_date DESC';
                            break;
                        case 'priority_desc':
                            $order = 'FIELD(tasks.priority, "urgent", "high", "medium", "low")';
                            break;
                    }
                }
                
                $filters['ORDER'] = $order;
                
                // Limite de resultados
                if (!empty($_GET['limit'])) {
                    $filters['LIMIT'] = min((int)$_GET['limit'], 100);
                }
                
                $tasks = $database->select('tasks', [
                    '[>]task_categories' => ['category_id' => 'id']
                ], [
                    'tasks.id', 'tasks.title', 'tasks.description',
                    'tasks.status', 'tasks.priority', 'tasks.due_date',
                    'tasks.created_at', 'tasks.updated_at',
                    'task_categories.name(category_name)',
                    'task_categories.color(category_color)'
                ], $filters);
                
                echo json_encode(['success' => true, 'data' => $tasks]);
            }
            break;
            
        case 'POST':
            // Criar nova tarefa
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (empty($input['title'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Título é obrigatório']);
                break;
            }
            
            $taskData = [
                'title' => trim($input['title']),
                'description' => trim($input['description'] ?? ''),
                'category_id' => $input['category_id'] ?? null,
                'priority' => $input['priority'] ?? 'medium',
                'status' => $input['status'] ?? 'pending',
                'due_date' => $input['due_date'] ?? null,
                'user_id' => $user_id,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $result = $database->insert('tasks', $taskData);
            
            if ($result->rowCount() > 0) {
                $new_id = $database->id();
                echo json_encode([
                    'success' => true, 
                    'message' => 'Tarefa criada com sucesso',
                    'data' => ['id' => $new_id]
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Erro ao criar tarefa']);
            }
            break;
            
        case 'PUT':
            // Atualizar tarefa
            if (!$task_id) {
                http_response_code(400);
                echo json_encode(['error' => 'ID da tarefa é obrigatório']);
                break;
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (empty($input['title'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Título é obrigatório']);
                break;
            }
            
            $updateData = [
                'title' => trim($input['title']),
                'description' => trim($input['description'] ?? ''),
                'category_id' => $input['category_id'] ?? null,
                'priority' => $input['priority'] ?? 'medium',
                'status' => $input['status'] ?? 'pending',
                'due_date' => $input['due_date'] ?? null,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $result = $database->update('tasks', $updateData, [
                'id' => $task_id,
                'user_id' => $user_id
            ]);
            
            if ($result->rowCount() > 0) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Tarefa atualizada com sucesso'
                ]);
            } else {
                echo json_encode([
                    'success' => true,
                    'message' => 'Nenhuma alteração realizada'
                ]);
            }
            break;
            
        case 'DELETE':
            // Excluir tarefa
            if (!$task_id) {
                http_response_code(400);
                echo json_encode(['error' => 'ID da tarefa é obrigatório']);
                break;
            }
            
            $result = $database->delete('tasks', [
                'id' => $task_id,
                'user_id' => $user_id
            ]);
            
            if ($result->rowCount() > 0) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Tarefa excluída com sucesso'
                ]);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Tarefa não encontrada']);
            }
            break;
            
        case 'PATCH':
            // Atualizar status da tarefa
            if (!$task_id) {
                http_response_code(400);
                echo json_encode(['error' => 'ID da tarefa é obrigatório']);
                break;
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (empty($input['status'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Status é obrigatório']);
                break;
            }
            
            $result = $database->update('tasks', [
                'status' => $input['status'],
                'updated_at' => date('Y-m-d H:i:s')
            ], [
                'id' => $task_id,
                'user_id' => $user_id
            ]);
            
            if ($result->rowCount() > 0) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Status atualizado com sucesso'
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