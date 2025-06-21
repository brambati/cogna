<?php
session_start();

/**
 * Página de Login - Medoo (Nova)
 */

// Funções necessárias
function generateCSRFToken(): string {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function sanitizeInput(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function verifyPassword(string $password, string $hash): bool {
    return password_verify($password, $hash);
}

// Se já estiver logado, redirecionar
if (isset($_SESSION['user_id'])) {
    header('Location: /dashboard');
    exit;
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar CSRF
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token de segurança inválido';
    } else {
        $email = sanitizeInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            $errors[] = 'Email e senha são obrigatórios';
        } else {
            try {
                require_once __DIR__ . '/../vendor/autoload.php';
                $config = require __DIR__ . '/../app/config/database.php';
                $database = new Medoo\Medoo($config);
                
                // Buscar usuário
                $user = $database->get('users', '*', ['email' => $email]);
                
                if ($user && verifyPassword($password, $user['password'])) {
                    // Login bem-sucedido
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_name'] = $user['name'];
                    
                    header('Location: /dashboard');
                    exit;
                } else {
                    $errors[] = 'Email ou senha inválidos';
                }
                
            } catch (Exception $e) {
                $errors[] = 'Erro interno do servidor';
                error_log("Erro no login: " . $e->getMessage());
            }
        }
    }
}

$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Task Manager Medoo</title>
    <link rel="stylesheet" href="/style.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1>Task Manager</h1>
                <h2>Medoo</h2>
                <p>Entre com suas credenciais</p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <p><?php echo htmlspecialchars($success); ?></p>
                </div>
            <?php endif; ?>

            <form method="POST" class="auth-form" id="loginForm">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required class="form-control"
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="password">Senha:</label>
                    <input type="password" id="password" name="password" required class="form-control">
                </div>

                <button type="submit" class="btn btn-primary btn-full">Entrar</button>
            </form>

            <div class="auth-footer">
                <p>Não tem uma conta? <a href="/register">Registre-se aqui</a></p>
                <p class="demo-info">
                    <strong>Demo:</strong> admin@taskmanager.test / Admin123!
                </p>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#loginForm').on('submit', function(e) {
                const email = $('#email').val().trim();
                const password = $('#password').val();
                
                if (!email || !password) {
                    e.preventDefault();
                    alert('Email e senha são obrigatórios');
                    return false;
                }
                
                if (!isValidEmail(email)) {
                    e.preventDefault();
                    alert('Por favor, insira um email válido');
                    return false;
                }
            });
            
            function isValidEmail(email) {
                const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return regex.test(email);
            }
        });
    </script>
</body>
</html>