<?php
/**
 * Verificador de Senhas - Ferramenta para testar hashes de senha
 */

require_once '../vendor/autoload.php';
require_once '../vendor/autoload.php';
require_once '../app/config/database.php';

$result = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if ($email && $password) {
        try {
            $config = require '../app/config/database.php';
            $pdo = new PDO($config['dsn'], $config['username'], $config['password'], $config['options']);
            $fluent = new Envms\FluentPDO\Query($pdo);
            
            $user = $fluent->from('users')
                ->select(['id', 'username', 'email', 'password_hash'])
                ->where('email = ?', $email)
                ->fetch();
            
            if ($user) {
                $isValid = password_verify($password, $user['password_hash']);
                
                if ($isValid) {
                    $result = "<p style='color: green;'>✓ Senha CORRETA para usuário: {$user['username']} (ID: {$user['id']})</p>";
                } else {
                    $result = "<p style='color: red;'>✗ Senha INCORRETA para usuário: {$user['username']}</p>";
                }
                
                $result .= "<p><strong>Hash armazenado:</strong><br><code>" . htmlspecialchars($user['password_hash']) . "</code></p>";
            } else {
                $result = "<p style='color: orange;'>Usuário não encontrado com o email: " . htmlspecialchars($email) . "</p>";
            }
            
        } catch (Exception $e) {
            $result = "<p style='color: red;'>Erro: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificar Senhas</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
        .container { background: #f9f9f9; padding: 20px; border-radius: 8px; }
        input, button { padding: 10px; margin: 5px 0; width: 100%; box-sizing: border-box; }
        button { background: #007bff; color: white; border: none; cursor: pointer; }
        button:hover { background: #0056b3; }
        code { background: #e9ecef; padding: 5px; border-radius: 3px; word-break: break-all; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Verificador de Senhas</h1>
        
        <form method="POST">
            <label for="email">Email do Usuário:</label>
            <input type="email" id="email" name="email" required>
            
            <label for="password">Senha para Testar:</label>
            <input type="password" id="password" name="password" required>
            
            <button type="submit">Verificar Senha</button>
        </form>
        
        <?php if ($result): ?>
            <hr>
            <div><?= $result ?></div>
        <?php endif; ?>
        
        <hr>
        <p><a href="debug_login.php">Debug Login</a> | <a href="create_user.php">Criar Usuário</a> | <a href="login.php">Login</a></p>
    </div>
</body>
</html> 