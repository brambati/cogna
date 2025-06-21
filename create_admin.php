<?php

/**
 * Script para criar usuário admin
 */

require_once __DIR__ . '/fluentpdo/app/helpers/security.php';
require_once __DIR__ . '/fluentpdo/app/models/User.php';

try {
    $userModel = new User();
    
    $userData = [
        'username' => 'admin',
        'email' => 'admin@taskmanager.test',
        'password' => 'admin123',
        'first_name' => 'Admin',
        'last_name' => 'User'
    ];
    
    $result = $userModel->create($userData);
    
    if ($result['success']) {
        echo "✅ Usuário admin criado com sucesso!\n";
        echo "Email: admin@taskmanager.test\n";
        echo "Senha: admin123\n";
    } else {
        echo "❌ Erro ao criar usuário:\n";
        foreach ($result['errors'] as $error) {
            echo "- $error\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}
?>