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
    $pdo = new PDO(
        "mysql:host={$config['host']};dbname={$config['database']};charset=utf8mb4",
        $config['username'],
        $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
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
                $stmt = $pdo->prepare("
                    SELECT t.*, tc.name as category_name, tc.color as category_color
                    FROM tasks t
                    LEFT JOIN task_categories tc ON t.category_id = tc.id
                    WHERE t.id = ? AND t.user_id = ?
                ");
                $stmt->execute([$task_id, $user_id]);
                $task = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$task) {
                    http_response_code(404);
                    echo json_encode(['error' => 'Tarefa não encontrada']);
                    break;
                }
                
                echo json_encode(['success' => true, 'data' => $task]);
            } else {
                // Listar tarefas com filtros
                $where_conditions = ['t.user_id = ?'];
                $params = [$user_id];
                
                // Aplicar filtros se fornecidos
                if (!empty($_GET['status'])) {
                    $where_conditions[] = 't.status = ?';
                    $params[] = $_GET['status'];
                }
                if (!empty($_GET['priority'])) {
                    $where_conditions[] = 't.priority = ?';
                    $params[] = $_GET['priority'];
                }
                if (!empty($_GET['category_id'])) {
                    $where_conditions[] = 't.category_id = ?';
                    $params[] = $_GET['category_id'];
                }
                if (!empty($_GET['search'])) {
                    $search = '%' . $_GET['search'] . '%';
                    $where_conditions[] = '(t.title LIKE ? OR t.description LIKE ?)';
                    $params[] = $search;
                    $params[] = $search;
                }
                
                // Ordenação
                $order = 't.created_at DESC';
                if (!empty($_GET['sort'])) {
                    switch ($_GET['sort']) {
                        case 'title_asc':
                            $order = 't.title ASC';
                            break;
                        case 'title_desc':
                            $order = 't.title DESC';
                            break;
                        case 'due_date_asc':
                            $order = 't.due_date ASC';
                            break;
                        case 'due_date_desc':
                            $order = 't.due_date DESC';
                            break;
                        case 'priority_desc':
                            $order = 'FIELD(t.priority, "urgent", "high", "medium", "low")';
                            break;
                    }
                }
                
                $sql = "
                    SELECT t.*, tc.name as category_name, tc.color as category_color
                    FROM tasks t
                    LEFT JOIN task_categories tc ON t.category_id = tc.id
                    WHERE " . implode(' AND ', $where_conditions) . "
                    ORDER BY {$order}
                ";
                
                // Limite de resultados
                if (!empty($_GET['limit'])) {
                    $sql .= ' LIMIT ' . min((int)$_GET['limit'], 100);
                }
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
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
            
            $stmt = $pdo->prepare("
                INSERT INTO tasks (title, description, category_id, priority, status, due_date, user_id, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            
            $result = $stmt->execute([
                trim($input['title']),
                trim($input['description'] ?? ''),
                $input['category_id'] ?? null,
                $input['priority'] ?? 'medium',
                $input['status'] ?? 'pending',
                $input['due_date'] ?? null,
                $user_id
            ]);
            
            if ($result) {
                $new_id = $pdo->lastInsertId();
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
            
            $stmt = $pdo->prepare("
                UPDATE tasks 
                SET title = ?, description = ?, category_id = ?, priority = ?, status = ?, due_date = ?, updated_at = NOW()
                WHERE id = ? AND user_id = ?
            ");
            
            $result = $stmt->execute([
                trim($input['title']),
                trim($input['description'] ?? ''),
                $input['category_id'] ?? null,
                $input['priority'] ?? 'medium',
                $input['status'] ?? 'pending',
                $input['due_date'] ?? null,
                $task_id,
                $user_id
            ]);
            
            if ($stmt->rowCount() > 0) {
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
            
            $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ? AND user_id = ?");
            $result = $stmt->execute([$task_id, $user_id]);
            
            if ($stmt->rowCount() > 0) {
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
            
            $stmt = $pdo->prepare("
                UPDATE tasks 
                SET status = ?, updated_at = NOW()
                WHERE id = ? AND user_id = ?
            ");
            
            $result = $stmt->execute([$input['status'], $task_id, $user_id]);
            
            if ($stmt->rowCount() > 0) {
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