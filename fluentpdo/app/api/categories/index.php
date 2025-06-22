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
    $pdo = new PDO(
        "mysql:host={$config['host']};dbname={$config['database']};charset=utf8mb4",
        $config['username'],
        $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    $user_id = $_SESSION['user_id'];
    $method = $_SERVER['REQUEST_METHOD'];
    
    // Extrair ID da categoria se presente
    $category_id = null;
    if (isset($_GET['id']) && is_numeric($_GET['id'])) {
        $category_id = (int)$_GET['id'];
    }
    
    switch ($method) {
        case 'GET':
            if ($category_id) {
                // Obter categoria específica
                $stmt = $pdo->prepare("
                    SELECT tc.*, COUNT(t.id) as task_count
                    FROM task_categories tc
                    LEFT JOIN tasks t ON tc.id = t.category_id AND t.user_id = tc.user_id
                    WHERE tc.id = ? AND tc.user_id = ? AND tc.deleted_at IS NULL
                ");
                $stmt->execute([$category_id, $user_id]);
                $category = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$category) {
                    http_response_code(404);
                    echo json_encode(['error' => 'Categoria não encontrada']);
                    break;
                }
                
                echo json_encode(['success' => true, 'data' => $category]);
            } else {
                // Listar todas as categorias com contagem de tarefas
                $stmt = $pdo->prepare("
                    SELECT tc.*, COUNT(t.id) as task_count
                    FROM task_categories tc
                    LEFT JOIN tasks t ON tc.id = t.category_id AND t.user_id = tc.user_id
                    WHERE tc.user_id = ? AND tc.deleted_at IS NULL
                    GROUP BY tc.id
                    ORDER BY tc.name ASC
                ");
                $stmt->execute([$user_id]);
                $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
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
            
            // Verificar se já existe categoria com mesmo nome
            $stmt = $pdo->prepare("
                SELECT id FROM task_categories 
                WHERE name = ? AND user_id = ? AND deleted_at IS NULL
            ");
            $stmt->execute([trim($input['name']), $user_id]);
            
            if ($stmt->fetch()) {
                http_response_code(409);
                echo json_encode(['error' => 'Já existe uma categoria com este nome']);
                break;
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO task_categories (name, color, description, user_id, created_at, updated_at)
                VALUES (?, ?, ?, ?, NOW(), NOW())
            ");
            
            $result = $stmt->execute([
                trim($input['name']),
                $input['color'] ?? '#3B82F6',
                trim($input['description'] ?? ''),
                $user_id
            ]);
            
            if ($result) {
                $new_id = $pdo->lastInsertId();
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
            
            // Verificar se já existe categoria com mesmo nome (excluindo a atual)
            $stmt = $pdo->prepare("
                SELECT id FROM task_categories 
                WHERE name = ? AND user_id = ? AND id != ? AND deleted_at IS NULL
            ");
            $stmt->execute([trim($input['name']), $user_id, $category_id]);
            
            if ($stmt->fetch()) {
                http_response_code(409);
                echo json_encode(['error' => 'Já existe uma categoria com este nome']);
                break;
            }
            
            $stmt = $pdo->prepare("
                UPDATE task_categories 
                SET name = ?, color = ?, description = ?, updated_at = NOW()
                WHERE id = ? AND user_id = ? AND deleted_at IS NULL
            ");
            
            $result = $stmt->execute([
                trim($input['name']),
                $input['color'] ?? '#3B82F6',
                trim($input['description'] ?? ''),
                $category_id,
                $user_id
            ]);
            
            if ($stmt->rowCount() > 0) {
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
            
            // Verificar se categoria tem tarefas associadas
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as task_count FROM tasks 
                WHERE category_id = ? AND user_id = ?
            ");
            $stmt->execute([$category_id, $user_id]);
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['task_count'];
            
            if ($count > 0) {
                // Soft delete - apenas marcar como deletada
                $stmt = $pdo->prepare("
                    UPDATE task_categories 
                    SET deleted_at = NOW(), updated_at = NOW()
                    WHERE id = ? AND user_id = ? AND deleted_at IS NULL
                ");
                $result = $stmt->execute([$category_id, $user_id]);
                
                if ($stmt->rowCount() > 0) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Categoria removida com sucesso (tarefas mantidas)'  
                    ]);
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'Categoria não encontrada']);
                }
            } else {
                // Delete permanente se não há tarefas
                $stmt = $pdo->prepare("DELETE FROM task_categories WHERE id = ? AND user_id = ?");
                $result = $stmt->execute([$category_id, $user_id]);
                
                if ($stmt->rowCount() > 0) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Categoria excluída permanentemente'
                    ]);
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'Categoria não encontrada']);
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