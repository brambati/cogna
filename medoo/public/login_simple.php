<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

// Se já está logado, redireciona para dashboard
if (isset($_SESSION['user_id'])) {
    echo "Já está logado, redirecionando...";
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $config = require '../app/config/database.php';
        $database = new Medoo\Medoo($config);
        
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            throw new Exception('Email e senha são obrigatórios');
        }
        
        // Buscar usuário por email
        $user = $database->get('users', [
            'id',
            'first_name',
            'last_name',
            'email',
            'password_hash'
        ], [
            'email' => $email
        ]);
        
        if (!$user) {
            throw new Exception('Credenciais inválidas - usuário não encontrado');
        }
        
        // Verificar senha
        if (!password_verify($password, $user['password_hash'])) {
            throw new Exception('Credenciais inválidas - senha incorreta');
        }
        
        // Login bem-sucedido
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['email'] = $user['email'];
        
        // Redirecionar
        header('Location: dashboard.php');
        exit;
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Simples - Sistema de Tarefas</title>
    <link rel="stylesheet" href="css/auth.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1>Entrar (Versão Simples)</h1>
                <p>Teste de login sem buffer</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form class="auth-form" method="POST">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required 
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                           placeholder="admin@taskmanager.test">
                </div>

                <div class="form-group">
                    <label for="password">Senha</label>
                    <input type="password" id="password" name="password" required
                           placeholder="Admin123!">
                </div>

                <button type="submit" class="btn-primary">Entrar</button>
            </form>

            <div class="auth-footer">
                <p><a href="login.php">← Voltar ao Login Original</a></p>
                <p><a href="debug_login.php">Debug Detalhado</a></p>
            </div>
        </div>
    </div>
</body>
</html>
