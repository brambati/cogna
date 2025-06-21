<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($email && $password) {
        if ($email === 'admin@taskmanager.test' && $password === 'Admin123!') {
            $_SESSION['user_id'] = 1;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_name'] = 'Admin User';
            
            header('Location: /dashboard');
            exit;
        } else {
            $login_error = 'Email ou senha inválidos';
        }
    } else {
        $login_error = 'Email e senha são obrigatórios';
    }
}
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

            <?php if (!empty($login_error)): ?>
                <div class="alert alert-error">
                    <p><?php echo htmlspecialchars($login_error); ?></p>
                </div>
            <?php endif; ?>

            <form method="POST" class="auth-form">
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" value="admin@taskmanager.test" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Senha:</label>
                    <input type="password" id="password" name="password" value="Admin123!" class="form-control" required>
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
</body>
</html>