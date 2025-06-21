<?php

/**
 * Página de Registro - FluentPDO
 */

require_once __DIR__ . '/../app/security.php';

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
        $username = sanitizeInput($_POST['username'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $firstName = sanitizeInput($_POST['first_name'] ?? '');
        $lastName = sanitizeInput($_POST['last_name'] ?? '');
        
        // Validações básicas
        if (empty($username) || empty($email) || empty($password) || empty($firstName) || empty($lastName)) {
            $errors[] = 'Todos os campos são obrigatórios';
        }
        
        if ($password !== $confirmPassword) {
            $errors[] = 'As senhas não coincidem';
        }
        
        if (empty($errors)) {
            require_once __DIR__ . '/../app/models/User.php';
            $userModel = new User();
            
            $userData = [
                'username' => $username,
                'email' => $email,
                'password' => $password,
                'first_name' => $firstName,
                'last_name' => $lastName
            ];
            
            $result = $userModel->create($userData);
            
            if ($result['success']) {
                $success = 'Usuário criado com sucesso! Faça login para continuar.';
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
    <title>Registro - Task Manager FluentPDO</title>
    <link rel="stylesheet" href="/style.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1>Task Manager</h1>
                <h2>FluentPDO</h2>
                <p>Criar nova conta</p>
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

            <form method="POST" class="auth-form" id="registerForm">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                
                <div class="row">
                    <div class="col-6">
                        <div class="form-group">
                            <label for="first_name">Nome:</label>
                            <input type="text" id="first_name" name="first_name" required class="form-control"
                                   value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-group">
                            <label for="last_name">Sobrenome:</label>
                            <input type="text" id="last_name" name="last_name" required class="form-control"
                                   value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" required class="form-control"
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required class="form-control"
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="password">Senha:</label>
                    <input type="password" id="password" name="password" required class="form-control">
                    <small class="text-muted">Mínimo 8 caracteres, com maiúscula, minúscula e número</small>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirmar Senha:</label>
                    <input type="password" id="confirm_password" name="confirm_password" required class="form-control">
                </div>

                <button type="submit" class="btn btn-primary btn-full">Criar Conta</button>
            </form>

            <div class="auth-footer">
                <p>Já tem uma conta? <a href="/login">Faça login aqui</a></p>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#registerForm').on('submit', function(e) {
                const password = $('#password').val();
                const confirmPassword = $('#confirm_password').val();
                
                if (password !== confirmPassword) {
                    e.preventDefault();
                    alert('As senhas não coincidem');
                    return false;
                }
                
                if (password.length < 8) {
                    e.preventDefault();
                    alert('A senha deve ter pelo menos 8 caracteres');
                    return false;
                }
            });
        });
    </script>
</body>
</html>