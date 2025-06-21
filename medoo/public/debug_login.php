<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>Debug do Login</h2>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h3>POST Request Recebido</h3>";
    
    try {
        // Carregar configuração
        echo "<p>1. Carregando configuração...</p>";
        $config = require '../app/config/database.php';
        
        // Conectar ao banco
        echo "<p>2. Conectando ao banco...</p>";
        $database = new Medoo\Medoo($config);
        
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        echo "<p>3. Dados recebidos:</p>";
        echo "<ul>";
        echo "<li>Email: " . htmlspecialchars($email) . "</li>";
        echo "<li>Password length: " . strlen($password) . "</li>";
        echo "</ul>";
        
        if (empty($email) || empty($password)) {
            throw new Exception('Email e senha são obrigatórios');
        }
        
        echo "<p>4. Buscando usuário...</p>";
        $user = $database->get('users', [
            'id',
            'first_name',
            'last_name',
            'email',
            'password_hash',
            'is_active',
            'email_verified'
        ], [
            'email' => $email
        ]);
        
        echo "<p>5. Resultado da busca:</p>";
        if ($user) {
            echo "<pre>";
            print_r($user);
            echo "</pre>";
        } else {
            echo "<p style='color: red;'>Usuário não encontrado!</p>";
        }
        
        if (!$user) {
            throw new Exception('Credenciais inválidas - usuário não encontrado');
        }
        
        echo "<p>6. Verificando senha...</p>";
        echo "<p>Hash no banco: " . substr($user['password_hash'], 0, 20) . "...</p>";
        
        $password_check = password_verify($password, $user['password_hash']);
        echo "<p>Resultado da verificação: " . ($password_check ? 'TRUE' : 'FALSE') . "</p>";
        
        if (!$password_check) {
            throw new Exception('Credenciais inválidas - senha incorreta');
        }
        
        echo "<p>7. Criando sessão...</p>";
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['email'] = $user['email'];
        
        echo "<p>8. Sessão criada:</p>";
        echo "<pre>";
        print_r($_SESSION);
        echo "</pre>";
        
        echo "<p style='color: green;'>✅ Login bem-sucedido! Redirecionando...</p>";
        echo "<script>setTimeout(function(){ window.location.href = 'dashboard.php'; }, 2000);</script>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>Nenhum POST recebido ainda.</p>";
}

echo "<hr>";
echo "<h3>Estado Atual da Sessão</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

if (isset($_SESSION['user_id'])) {
    echo "<p style='color: green;'>✅ Usuário está logado!</p>";
    echo "<p><a href='dashboard.php'>Ir para Dashboard</a></p>";
} else {
    echo "<p>Usuário não está logado.</p>";
}

echo "<hr>";
echo "<form method='POST'>";
echo "<p>Email: <input type='email' name='email' value='admin@taskmanager.test' required></p>";
echo "<p>Senha: <input type='password' name='password' value='Admin123!' required></p>";
echo "<p><button type='submit'>Testar Login</button></p>";
echo "</form>";

echo "<p><a href='login.php'>← Voltar ao Login</a></p>";
?>
