<?php
header('Content-Type: application/json');
require_once '../../vendor/autoload.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

try {
    $config = require '../../app/config/database.php';
    $database = new Medoo\Medoo($config);
    $user_id = $_SESSION['user_id'];
    $method = $_SERVER['REQUEST_METHOD'];
    
    // Roteamento simples
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $segments = explode('/', trim($path, '/'));
    $category_id = isset($segments[3]) ? (int)$segments[3] : null;
    
    switch ($method) {
        case 'GET':
            if ($category_id) {
                // Obter categoria específica
                $category = $database->get('task_categories', '*', [
                    'id' => $category_id,
                    'user_id' => $user_id,
                    'is_active' => 1
                ]);
                
                echo json_encode(['success' => true, 'data' => $category]);
            } else {
                // Listar categorias ativas
                $categories = $database->select('task_categories', [
                    '[>]tasks' => ['id' => 'category_id']
                ], [
                    'task_categories.id',
                    'task_categories.name',
                    'task_categories.color',
                    'task_categories.created_at',
                    'COUNT(tasks.id)(task_count)'
                ], [
                    'task_categories.user_id' => $user_id,
                    'task_categories.is_active' => 1,
                    'GROUP' => 'task_categories.id',
                    'ORDER' => 'task_categories.name ASC'
                ]);
                
                echo json_encode(['success' => true, 'data' => $categories]);
            }
            break;
            
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (empty($input['name'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Nome é obrigatório']);
                break;
            }
            
            // Verificar se já existe categoria com esse nome
            $existing = $database->get('task_categories', 'id', [
                'name' => $input['name'],
                'user_id' => $user_id,
                'is_active' => 1
            ]);
            
            if ($existing) {
                http_response_code(400);
                echo json_encode(['error' => 'Já existe uma categoria com esse nome']);
                break;
            }
            
            $result = $database->insert('task_categories', [
                'name' => $input['name'],
                'color' => $input['color'] ?? '#007bff',
                'user_id' => $user_id,
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            echo json_encode(['success' => true, 'data' => ['id' => $database->id()]]);
            break;
            
        case 'PUT':
            if (!$category_id) {
                http_response_code(400);
                echo json_encode(['error' => 'ID obrigatório']);
                break;
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (empty($input['name'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Nome é obrigatório']);
                break;
            }
            
            // Verificar se existe outra categoria com esse nome
            $existing = $database->get('task_categories', 'id', [
                'name' => $input['name'],
                'user_id' => $user_id,
                'is_active' => 1,
                'id[!]' => $category_id
            ]);
            
            if ($existing) {
                http_response_code(400);
                echo json_encode(['error' => 'Já existe uma categoria com esse nome']);
                break;
            }
            
            $result = $database->update('task_categories', [
                'name' => $input['name'],
                'color' => $input['color'] ?? '#007bff',
                'updated_at' => date('Y-m-d H:i:s')
            ], [
                'id' => $category_id,
                'user_id' => $user_id,
                'is_active' => 1
            ]);
            
            echo json_encode(['success' => true, 'message' => 'Categoria atualizada']);
            break;
            
        case 'DELETE':
            if (!$category_id) {
                http_response_code(400);
                echo json_encode(['error' => 'ID obrigatório']);
                break;
            }
            
            // Soft delete - marcar como inativa
            $result = $database->update('task_categories', [
                'is_active' => 0,
                'updated_at' => date('Y-m-d H:i:s')
            ], [
                'id' => $category_id,
                'user_id' => $user_id
            ]);
            
            // Remover categoria das tarefas
            $database->update('tasks', [
                'category_id' => null,
                'updated_at' => date('Y-m-d H:i:s')
            ], [
                'category_id' => $category_id,
                'user_id' => $user_id
            ]);
            
            echo json_encode(['success' => true, 'message' => 'Categoria excluída']);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?> 