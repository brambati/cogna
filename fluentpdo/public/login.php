<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();

ob_start();

/**
 * Página de Login - FluentPDO
 */

// Se já está logado, redireciona para dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Carregar autoload do Composer
        require_once '../vendor/autoload.php';
        
        // Carregar configuração do banco
        $config = require '../app/config/database.php';
        $pdo = new PDO($config['dsn'], $config['username'], $config['password'], $config['options']);
        $fluent = new Envms\FluentPDO\Query($pdo);
        
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            throw new Exception('Email e senha são obrigatórios');
        }
        
        // Buscar usuário por email
        $user = $fluent->from('users')
            ->select(['id', 'first_name', 'last_name', 'email', 'password_hash'])
            ->where('email = ?', $email)
            ->fetch();
        
        if (!$user) {
            throw new Exception('Email ou senha inválidos');
        }
        
        // Verificar senha
        if (!password_verify($password, $user['password_hash'])) {
            throw new Exception('Email ou senha inválidos');
        }
        
        // Login bem-sucedido - criar sessões
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = trim($user['first_name'] . ' ' . $user['last_name']);
        $_SESSION['email'] = $user['email'];
        
        $success = true;
        
        // Redirecionar para dashboard
        header('Location: dashboard.php');
        exit;
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

ob_end_flush();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Tarefas</title>
    <link rel="stylesheet" href="css/auth.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1>Entrar</h1>
                <p>Acesse sua conta para gerenciar suas tarefas</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    Login realizado com sucesso! Redirecionando...
                </div>
            <?php endif; ?>

            <form class="auth-form" id="loginForm" method="POST" action="login.php">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required 
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    <span class="error-message" id="emailError"></span>
                </div>

                <div class="form-group">
                    <label for="password">Senha</label>
                    <input type="password" id="password" name="password" required>
                    <span class="error-message" id="passwordError"></span>
                </div>

                <div class="form-options">
                    <label class="checkbox">
                        <input type="checkbox" name="remember" id="remember">
                        <span class="checkmark"></span>
                        Lembrar de mim
                    </label>
                </div>

                <button type="submit" class="btn-primary">Entrar</button>

                <div class="auth-links">
                    <a href="forgot-password.php">Esqueci minha senha</a>
                </div>
            </form>

            <div class="auth-footer">
                <p>Não tem uma conta? <a href="register.php">Criar conta</a></p>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('loginForm');
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');
            
            if (form) {
                form.addEventListener('submit', function(e) {
                    // Validação básica
                    if (!emailInput.value.trim()) {
                        alert('Por favor, digite seu email');
                        e.preventDefault();
                        return;
                    }
                    
                    if (!passwordInput.value) {
                        alert('Por favor, digite sua senha');
                        e.preventDefault();
                        return;
                    }
                    
                    console.log('Enviando login para:', emailInput.value);
                });
            }
        });
    </script>
</body>
</html>