<?php

/**
 * Página de Login - FluentPDO
 */

require_once __DIR__ . '/../app/helpers/security.php';

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
            require_once __DIR__ . '/../app/models/User.php';
            $userModel = new User();
            
            $result = $userModel->authenticate($email, $password);
            
            if ($result['success']) {
                header('Location: /dashboard');
                exit;
            } else {
                $errors = $result['errors'];
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
    <title>Login - Task Manager FluentPDO</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1>Task Manager</h1>
                <h2>FluentPDO</h2>
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
                    <input type="email" id="email" name="email" required 
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="password">Senha:</label>
                    <input type="password" id="password" name="password" required>
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