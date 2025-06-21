<?php

/**
 * Setup2 - Criar usuário admin (Medoo)
 */

require_once __DIR__ . '/../app/security.php';
require_once __DIR__ . '/../app/models/User.php';

try {
    $userModel = new User();
    
    // Verificar se admin já existe
    $config = require __DIR__ . '/../app/config/database.php';
    $database = new Medoo\Medoo($config);
    
    $existingUser = $database->get('users', 'id', ['email' => 'admin@taskmanager.test']);
    
    if ($existingUser) {
        echo "✅ Usuário admin já existe! ID: $existingUser<br>";
    } else {
        $userData = [
            'username' => 'admin',
            'email' => 'admin@taskmanager.test',
            'password' => 'Admin123!',
            'first_name' => 'Admin',
            'last_name' => 'User'
        ];
        
        $result = $userModel->create($userData);
        
        if ($result['success']) {
            echo "✅ Usuário admin criado com sucesso! ID: " . $result['user_id'] . "<br>";
        } else {
            echo "❌ Erro ao criar usuário:<br>";
            foreach ($result['errors'] as $error) {
                echo "- $error<br>";
            }
        }
    }
    
    echo "<br><strong>Credenciais para teste:</strong><br>";
    echo "Email: admin@taskmanager.test<br>";
    echo "Senha: Admin123!<br><br>";
    echo "<a href='/login'>Ir para Login</a><br>";
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "<br>";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>";
}
?>