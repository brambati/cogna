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
    $pdo = new PDO($config['dsn'], $config['username'], $config['password'], $config['options']);
    $fluent = new Envms\FluentPDO\Query($pdo);
    $user_id = $_SESSION['user_id'];
    $method = $_SERVER['REQUEST_METHOD'];
    
    // Roteamento simples
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $segments = explode('/', trim($path, '/'));
    $category_id = isset($segments[3]) ? (int)$segments[3] : null;
    
    switch ($method) {
        case 'GET':
            if ($category_id) {
                $category = $fluent->from('task_categories')
                    ->select('*')
                    ->where('id', $category_id)
                    ->where('user_id', $user_id)
                    ->where('is_active', 1)
                    ->fetch();
                
                echo json_encode(['success' => true, 'data' => $category]);
            } else {
                $categories = $fluent->from('task_categories')
                    ->leftJoin('tasks ON task_categories.id = tasks.category_id')
                    ->select('task_categories.id, task_categories.name, task_categories.color, task_categories.created_at, COUNT(tasks.id) AS task_count')
                    ->where('task_categories.user_id', $user_id)
                    ->where('task_categories.is_active', 1)
                    ->groupBy('task_categories.id')
                    ->orderBy('task_categories.name ASC')
                    ->fetchAll();
                
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
            
            $existing = $fluent->from('task_categories')
                ->select('id')
                ->where('name', $input['name'])
                ->where('user_id', $user_id)
                ->where('is_active', 1)
                ->fetch();
            
            if ($existing) {
                http_response_code(400);
                echo json_encode(['error' => 'Já existe uma categoria com esse nome']);
                break;
            }
            
            $result = $fluent->insertInto('task_categories')->values([
                'name' => $input['name'],
                'color' => $input['color'] ?? '#007bff',
                'user_id' => $user_id,
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ])->execute();
            
            echo json_encode(['success' => true, 'data' => ['id' => $pdo->lastInsertId()]]);
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
            
            $existing = $fluent->from('task_categories')
                ->select('id')
                ->where('name', $input['name'])
                ->where('user_id', $user_id)
                ->where('is_active', 1)
                ->where('id != ?', $category_id)
                ->fetch();
            
            if ($existing) {
                http_response_code(400);
                echo json_encode(['error' => 'Já existe uma categoria com esse nome']);
                break;
            }
            
            $result = $fluent->update('task_categories')->set([
                'name' => $input['name'],
                'color' => $input['color'] ?? '#007bff',
                'updated_at' => date('Y-m-d H:i:s')
            ])->where('id', $category_id)
              ->where('user_id', $user_id)
              ->where('is_active', 1)
              ->execute();
            
            echo json_encode(['success' => true, 'message' => 'Categoria atualizada']);
            break;
            
        case 'DELETE':
            if (!$category_id) {
                http_response_code(400);
                echo json_encode(['error' => 'ID obrigatório']);
                break;
            }
            
            $result = $fluent->update('task_categories')->set([
                'is_active' => 0,
                'updated_at' => date('Y-m-d H:i:s')
            ])->where('id', $category_id)
              ->where('user_id', $user_id)
              ->execute();
            
            $fluent->update('tasks')->set([
                'category_id' => null,
                'updated_at' => date('Y-m-d H:i:s')
            ])->where('category_id', $category_id)
              ->where('user_id', $user_id)
              ->execute();
            
            echo json_encode(['success' => true, 'message' => 'Categoria excluída']);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
