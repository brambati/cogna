<?php
/**
 * API Router Principal
 * Gerencia e direciona requisições para os endpoints corretos
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Lidar com requisições OPTIONS (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

session_start();

try {
    // Obter caminho da requisição
    $request_uri = $_SERVER['REQUEST_URI'];
    $base_path = '/medoo/app/api';
    
    // Remover base path e query string
    $path = str_replace($base_path, '', parse_url($request_uri, PHP_URL_PATH));
    $path = trim($path, '/');
    $segments = explode('/', $path);
    
    // Determinar o recurso solicitado
    $resource = $segments[0] ?? '';
    $id = $segments[1] ?? null;
    
    // Roteamento baseado no recurso
    switch ($resource) {
        case 'tasks':
            // Incluir endpoint de tarefas
            $_GET['id'] = $id; // Passar ID via GET para compatibilidade
            require_once __DIR__ . '/tasks/index.php';
            break;
            
        case 'categories':
            // Incluir endpoint de categorias
            $_GET['id'] = $id;
            require_once __DIR__ . '/categories/index.php';
            break;
            
        case 'user':
            // Roteamento para usuário
            $action = $id ?? 'profile';
            
            switch ($action) {
                case 'profile':
                    require_once __DIR__ . '/user/profile.php';
                    break;
                    
                case 'change-password':
                    require_once __DIR__ . '/user/change-password.php';
                    break;
                    
                default:
                    http_response_code(404);
                    echo json_encode(['error' => 'Endpoint de usuário não encontrado']);
                    break;
            }
            break;
            
        case 'auth':
            // Roteamento para autenticação
            $action = $id ?? '';
            
            switch ($action) {
                case 'login':
                    require_once __DIR__ . '/auth/login.php';
                    break;
                    
                case 'register':
                    require_once __DIR__ . '/auth/register.php';
                    break;
                    
                case 'logout':
                    require_once __DIR__ . '/auth/logout.php';
                    break;
                    
                case 'forgot-password':
                    require_once __DIR__ . '/auth/forgot-password.php';
                    break;
                    
                case 'reset-password':
                    require_once __DIR__ . '/auth/reset-password.php';
                    break;
                    
                case 'verify-token':
                    require_once __DIR__ . '/auth/verify-token.php';
                    break;
                    
                default:
                    http_response_code(404);
                    echo json_encode(['error' => 'Endpoint de autenticação não encontrado']);
                    break;
            }
            break;
            
        case 'stats':
            // Estatísticas (redirecionar para o endpoint público)
            require_once __DIR__ . '/../../public/api/stats.php';
            break;
            
        case '':
            // Endpoint raiz - informações da API
            echo json_encode([
                'success' => true,
                'message' => 'API do Sistema de Tarefas',
                'version' => '1.0.0',
                'endpoints' => [
                    'GET /tasks' => 'Listar tarefas',
                    'POST /tasks' => 'Criar tarefa',
                    'GET /tasks/{id}' => 'Obter tarefa específica',
                    'PUT /tasks/{id}' => 'Atualizar tarefa',
                    'DELETE /tasks/{id}' => 'Excluir tarefa',
                    'PATCH /tasks/{id}' => 'Atualizar status da tarefa',
                    
                    'GET /categories' => 'Listar categorias',
                    'POST /categories' => 'Criar categoria',
                    'GET /categories/{id}' => 'Obter categoria específica',
                    'PUT /categories/{id}' => 'Atualizar categoria',
                    'DELETE /categories/{id}' => 'Excluir categoria',
                    
                    'GET /user/profile' => 'Obter perfil do usuário',
                    'PUT /user/profile' => 'Atualizar perfil',
                    'POST /user/change-password' => 'Alterar senha',
                    
                    'POST /auth/login' => 'Fazer login',
                    'POST /auth/register' => 'Registrar usuário',
                    'POST /auth/logout' => 'Fazer logout',
                    'POST /auth/forgot-password' => 'Solicitar recuperação de senha',
                    'POST /auth/reset-password' => 'Redefinir senha',
                    'GET /auth/verify-token' => 'Verificar token',
                    
                    'GET /stats' => 'Obter estatísticas do usuário'
                ],
                'authentication' => 'Sessão baseada em cookies (exceto endpoints de auth)',
                'format' => 'JSON'
            ]);
            break;
            
        default:
            http_response_code(404);
            echo json_encode([
                'error' => 'Endpoint não encontrado',
                'requested' => $resource,
                'available_endpoints' => [
                    'tasks', 'categories', 'user', 'auth', 'stats'
                ]
            ]);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Erro interno do servidor',
        'message' => $e->getMessage()
    ]);
}
?> 