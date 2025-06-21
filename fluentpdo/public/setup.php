<?php

/**
 * Setup - Criar usuário admin
 */

require_once __DIR__ . '/../app/helpers/security.php';
require_once __DIR__ . '/../app/models/User.php';

// Só permitir uma vez
if (file_exists(__DIR__ . '/setup_done.txt')) {
    die('Setup já foi executado!');
}

try {
    $userModel = new User();
    
    // Verificar se admin já existe
    $config = require __DIR__ . '/../app/config/database.php';
    $dsn = $config['dsn'];
    $pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
    
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute(['admin@taskmanager.test']);
    $existingUser = $stmt->fetch();
    
    if ($existingUser) {
        echo "✅ Usuário admin já existe!<br>";
    } else {
        $userData = [
            'username' => 'admin',
            'email' => 'admin@taskmanager.test',
            'password' => 'admin123',
            'first_name' => 'Admin',
            'last_name' => 'User'
        ];
        
        $result = $userModel->create($userData);
        
        if ($result['success']) {
            echo "✅ Usuário admin criado com sucesso!<br>";
        } else {
            echo "❌ Erro ao criar usuário:<br>";
            foreach ($result['errors'] as $error) {
                echo "- $error<br>";
            }
        }
    }
    
    echo "<br><strong>Credenciais:</strong><br>";
    echo "Email: admin@taskmanager.test<br>";
    echo "Senha: admin123<br><br>";
    echo "<a href='/login'>Ir para Login</a><br>";
    
    // Marcar como executado
    file_put_contents(__DIR__ . '/setup_done.txt', 'Setup executado em ' . date('Y-m-d H:i:s'));
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "<br>";
}
?>