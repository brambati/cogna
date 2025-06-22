<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
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
    
    // Extrair ID da categoria se presente
    $category_id = null;
    if (isset($segments[4]) && is_numeric($segments[4])) {
        $category_id = (int)$segments[4];
    } elseif (isset($_GET['id']) && is_numeric($_GET['id'])) {
        $category_id = (int)$_GET['id'];
    }
    
    switch ($method) {
        case 'GET':
            if ($category_id) {
                // Obter categoria específica
                $category = $database->get('task_categories', [
                    'id', 'name', 'color', 'description', 'is_active',
                    'created_at', 'updated_at'
                ], [
                    'id' => $category_id,
                    'user_id' => $user_id
                ]);
                
                if (!$category) {
                    http_response_code(404);
                    echo json_encode(['error' => 'Categoria não encontrada']);
                    break;
                }
                
                // Adicionar contagem de tarefas
                $category['tasks_count'] = $database->count('tasks', [
                    'category_id' => $category_id,
                    'user_id' => $user_id
                ]);
                
                echo json_encode(['success' => true, 'data' => $category]);
            } else {
                // Listar todas as categorias
                $filters = [
                    'user_id' => $user_id,
                    'ORDER' => 'name ASC'
                ];
                
                // Filtro por ativo/inativo
                if (isset($_GET['active'])) {
                    $filters['is_active'] = $_GET['active'] ? 1 : 0;
                } else {
                    $filters['is_active'] = 1; // Por padrão, só categorias ativas
                }
                
                $categories = $database->select('task_categories', [
                    'id', 'name', 'color', 'description', 'is_active',
                    'created_at', 'updated_at'
                ], $filters);
                
                // Adicionar contagem de tarefas para cada categoria
                foreach ($categories as &$category) {
                    $category['tasks_count'] = $database->count('tasks', [
                        'category_id' => $category['id'],
                        'user_id' => $user_id
                    ]);
                }
                
                echo json_encode(['success' => true, 'data' => $categories]);
            }
            break;
            
        case 'POST':
            // Criar nova categoria
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (empty($input['name'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Nome da categoria é obrigatório']);
                break;
            }
            
            // Verificar se já existe categoria com o mesmo nome
            $existing = $database->get('task_categories', 'id', [
                'name' => trim($input['name']),
                'user_id' => $user_id,
                'is_active' => 1
            ]);
            
            if ($existing) {
                http_response_code(400);
                echo json_encode(['error' => 'Já existe uma categoria com este nome']);
                break;
            }
            
            $categoryData = [
                'name' => trim($input['name']),
                'description' => trim($input['description'] ?? ''),
                'color' => $input['color'] ?? '#6366f1',
                'is_active' => 1,
                'user_id' => $user_id,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $result = $database->insert('task_categories', $categoryData);
            
            if ($result->rowCount() > 0) {
                $new_id = $database->id();
                echo json_encode([
                    'success' => true,
                    'message' => 'Categoria criada com sucesso',
                    'data' => ['id' => $new_id]
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Erro ao criar categoria']);
            }
            break;
            
        case 'PUT':
            // Atualizar categoria
            if (!$category_id) {
                http_response_code(400);
                echo json_encode(['error' => 'ID da categoria é obrigatório']);
                break;
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (empty($input['name'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Nome da categoria é obrigatório']);
                break;
            }
            
            // Verificar se a categoria existe e pertence ao usuário
            $existing_category = $database->get('task_categories', 'id', [
                'id' => $category_id,
                'user_id' => $user_id
            ]);
            
            if (!$existing_category) {
                http_response_code(404);
                echo json_encode(['error' => 'Categoria não encontrada']);
                break;
            }
            
            // Verificar se já existe outra categoria com o mesmo nome
            $duplicate = $database->get('task_categories', 'id', [
                'name' => trim($input['name']),
                'user_id' => $user_id,
                'id[!]' => $category_id,
                'is_active' => 1
            ]);
            
            if ($duplicate) {
                http_response_code(400);
                echo json_encode(['error' => 'Já existe uma categoria com este nome']);
                break;
            }
            
            $updateData = [
                'name' => trim($input['name']),
                'description' => trim($input['description'] ?? ''),
                'color' => $input['color'] ?? '#6366f1',
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            if (isset($input['is_active'])) {
                $updateData['is_active'] = (int)$input['is_active'];
            }
            
            $result = $database->update('task_categories', $updateData, [
                'id' => $category_id,
                'user_id' => $user_id
            ]);
            
            if ($result->rowCount() > 0) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Categoria atualizada com sucesso'
                ]);
            } else {
                echo json_encode([
                    'success' => true,
                    'message' => 'Nenhuma alteração realizada'
                ]);
            }
            break;
            
        case 'DELETE':
            // Excluir categoria (soft delete)
            if (!$category_id) {
                http_response_code(400);
                echo json_encode(['error' => 'ID da categoria é obrigatório']);
                break;
            }
            
            // Verificar se a categoria existe e tem tarefas associadas
            $category = $database->get('task_categories', ['id', 'name'], [
                'id' => $category_id,
                'user_id' => $user_id
            ]);
            
            if (!$category) {
                http_response_code(404);
                echo json_encode(['error' => 'Categoria não encontrada']);
                break;
            }
            
            $tasks_count = $database->count('tasks', [
                'category_id' => $category_id,
                'user_id' => $user_id
            ]);
            
            if ($tasks_count > 0) {
                // Soft delete - marcar como inativa
                $result = $database->update('task_categories', [
                    'is_active' => 0,
                    'updated_at' => date('Y-m-d H:i:s')
                ], [
                    'id' => $category_id,
                    'user_id' => $user_id
                ]);
                
                if ($result->rowCount() > 0) {
                    echo json_encode([
                        'success' => true,
                        'message' => "Categoria '{$category['name']}' foi desativada (possui {$tasks_count} tarefas associadas)"
                    ]);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Erro ao desativar categoria']);
                }
            } else {
                // Hard delete - remover completamente
                $result = $database->delete('task_categories', [
                    'id' => $category_id,
                    'user_id' => $user_id
                ]);
                
                if ($result->rowCount() > 0) {
                    echo json_encode([
                        'success' => true,
                        'message' => "Categoria '{$category['name']}' foi excluída permanentemente"
                    ]);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Erro ao excluir categoria']);
                }
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