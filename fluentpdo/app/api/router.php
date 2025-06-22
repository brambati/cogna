<?php
/**
 * API Router
 * Gerenciador centralizado de rotas da API
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Tratar requisições OPTIONS para CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../../vendor/autoload.php';
require_once '../helpers/security.php';

session_start();

// Obter informações da requisição
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$segments = explode('/', trim($path, '/'));

// Log da requisição para debug (opcional)
error_log("API Router - Method: {$method}, Path: {$path}");

try {
    // Aplicar rate limiting global
    $rate_limit = SecurityHelper::checkRateLimit('api_' . SecurityHelper::getClientIP(), 60, 5);
    if (!$rate_limit['allowed']) {
        http_response_code(429);
        echo json_encode([
            'error' => 'Muitas requisições. Limite de 60 por 5 minutos.',
            'reset_time' => $rate_limit['reset_time']
        ]);
        exit;
    }

    // Identificar o recurso sendo acessado
    $resource = null;
    $action = null;
    
    // Padrão esperado: /app/api/router.php/RESOURCE/ACTION
    if (count($segments) >= 4) {
        $resource = $segments[3] ?? null; // tasks, categories, user
        $action = $segments[4] ?? null;   // profile, change-password, etc.
    }
    
    // Se não encontrou na URL, verificar query parameters
    if (!$resource) {
        $resource = $_GET['resource'] ?? null;
        $action = $_GET['action'] ?? null;
    }
    
    if (!$resource) {
        http_response_code(400);
        echo json_encode([
            'error' => 'Recurso não especificado',
            'usage' => 'Use: /api/router.php/RESOURCE ou ?resource=RESOURCE'
        ]);
        exit;
    }
    
    // Verificar autenticação para recursos protegidos
    $public_resources = ['auth']; // Recursos que não precisam de autenticação
    
    if (!in_array($resource, $public_resources) && !isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Não autorizado. Faça login primeiro.']);
        exit;
    }
    
    // Roteamento baseado no recurso
    switch ($resource) {
        case 'tasks':
            // Delegar para o endpoint de tarefas
            $_SERVER['REQUEST_URI'] = str_replace('/router.php', '', $_SERVER['REQUEST_URI']);
            require_once 'tasks/index.php';
            break;
            
        case 'categories':
            // Delegar para o endpoint de categorias
            $_SERVER['REQUEST_URI'] = str_replace('/router.php', '', $_SERVER['REQUEST_URI']);
            require_once 'categories/index.php';
            break;
            
        case 'user':
            // Roteamento específico para endpoints de usuário
            switch ($action) {
                case 'profile':
                    $_SERVER['REQUEST_METHOD'] = $method;
                    require_once 'user/profile.php';
                    break;
                    
                case 'change-password':
                    if ($method !== 'POST') {
                        http_response_code(405);
                        echo json_encode(['error' => 'Método não permitido para alteração de senha']);
                        break;
                    }
                    require_once 'user/change-password.php';
                    break;
                    
                default:
                    // Se não especificou ação, assume profile
                    if (!$action) {
                        $_SERVER['REQUEST_METHOD'] = $method;
                        require_once 'user/profile.php';
                    } else {
                        http_response_code(404);
                        echo json_encode([
                            'error' => 'Ação de usuário não encontrada',
                            'available_actions' => ['profile', 'change-password']
                        ]);
                    }
                    break;
            }
            break;
            
        case 'auth':
            // Roteamento para autenticação
            switch ($action) {
                case 'login':
                    require_once 'auth/login.php';
                    break;
                case 'register':
                    require_once 'auth/register.php';
                    break;
                case 'logout':
                    require_once 'auth/logout.php';
                    break;
                case 'forgot-password':
                    require_once 'auth/forgot-password.php';
                    break;
                case 'reset-password':
                    require_once 'auth/reset-password.php';
                    break;
                case 'verify-token':
                    require_once 'auth/verify-token.php';
                    break;
                default:
                    http_response_code(404);
                    echo json_encode([
                        'error' => 'Endpoint de autenticação não encontrado',
                        'available_endpoints' => ['login', 'register', 'logout', 'forgot-password', 'reset-password', 'verify-token']
                    ]);
                    break;
            }
            break;
            
        default:
            http_response_code(404);
            echo json_encode([
                'error' => 'Recurso não encontrado',
                'available_resources' => ['tasks', 'categories', 'user', 'auth'],
                'requested_resource' => $resource
            ]);
            break;
    }
    
} catch (Exception $e) {
    error_log("Erro no API Router: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Erro interno do servidor',
        'message' => 'Falha no roteamento da API'
    ]);
}
?> 