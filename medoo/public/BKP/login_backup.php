<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($email && $password) {
        try {
            require_once __DIR__ . '/../vendor/autoload.php';
            $config = require __DIR__ . '/../app/config/database.php';
            $database = new Medoo\Medoo($config);
            
            $user = $database->get('users', '*', ['email' => $email]);
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_name'] = $user['name'];
                
                echo "✅ Login bem-sucedido!<br>";
                echo "<a href='/dashboard'>Ir para Dashboard</a>";
                exit;
            } else {
                echo "❌ Email ou senha inválidos<br>";
            }
        } catch (Exception $e) {
            echo "❌ Erro: " . $e->getMessage() . "<br>";
        }
    } else {
        echo "❌ Email e senha são obrigatórios<br>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login Simples - Medoo</title>
    <link rel="stylesheet" href="/style.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <h2>Login Simples - Medoo</h2>
            
            <form method="POST">
                <div class="form-group">
                    <label>Email:</label>
                    <input type="email" name="email" value="admin@taskmanager.test" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>Senha:</label>
                    <input type="password" name="password" value="Admin123!" class="form-control" required>
                </div>
                
                <button type="submit" class="btn btn-primary">Login</button>
            </form>
            
            <br><a href="/login">Voltar ao Login Normal</a>
        </div>
    </div>
</body>
</html>