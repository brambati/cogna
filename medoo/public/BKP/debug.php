<?php

/**
 * Debug - Testar conexão e criar usuário
 */

// Ativar error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    echo "1. Carregando autoloader...<br>";
    require_once __DIR__ . '/../vendor/autoload.php';
    echo "✅ Autoloader carregado<br>";
    
    echo "2. Carregando configuração...<br>";
    $config = require __DIR__ . '/../app/config/database.php';
    echo "✅ Config carregada<br>";
    
    echo "3. Testando conexão Medoo...<br>";
    $database = new Medoo\Medoo($config);
    echo "✅ Conexão Medoo estabelecida<br>";
    
    echo "4. Testando hash de senha...<br>";
    require_once __DIR__ . '/../app/security.php';
    $passwordHash = hashPassword('Admin123!');
    echo "✅ Hash gerado: " . substr($passwordHash, 0, 30) . "...<br>";
    
    echo "5. Verificando se usuário existe...<br>";
    $existingUser = $database->get('users', 'id', ['email' => 'admin@taskmanager.test']);
    
    if ($existingUser) {
        echo "✅ Usuário admin já existe! ID: $existingUser<br>";
    } else {
        echo "6. Inserindo novo usuário...<br>";
        
        $database->insert('users', [
            'username' => 'admin',
            'email' => 'admin@taskmanager.test',
            'password_hash' => $passwordHash,
            'first_name' => 'Admin',
            'last_name' => 'User',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        $userId = $database->id();
        if ($userId) {
            echo "✅ Usuário criado com sucesso! ID: $userId<br>";
        } else {
            echo "❌ Erro ao criar usuário<br>";
            print_r($database->error());
        }
    }
    
    echo "<br><strong>Credenciais:</strong><br>";
    echo "Email: admin@taskmanager.test<br>";
    echo "Senha: Admin123!<br><br>";
    echo "<a href='/login'>Ir para Login</a><br>";
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "<br>";
    echo "Arquivo: " . $e->getFile() . ":" . $e->getLine() . "<br>";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>";
}
?>