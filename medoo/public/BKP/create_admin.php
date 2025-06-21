<?php

/**
 * Criar usuário admin com estrutura correta
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once __DIR__ . '/../vendor/autoload.php';
    require_once __DIR__ . '/../app/security.php';
    
    $config = require __DIR__ . '/../app/config/database.php';
    $database = new Medoo\Medoo($config);
    
    echo "Verificando se usuário admin existe...<br>";
    $existingUser = $database->get('users', 'id', ['email' => 'admin@taskmanager.test']);
    
    if ($existingUser) {
        echo "✅ Usuário admin já existe! ID: $existingUser<br>";
    } else {
        echo "Criando usuário admin...<br>";
        
        // Hash da senha
        $passwordHash = hashPassword('Admin123!');
        
        // Usar a estrutura atual da tabela (name ao invés de first_name/last_name, password ao invés de password_hash)
        $database->insert('users', [
            'name' => 'Admin User',
            'email' => 'admin@taskmanager.test',
            'password' => $passwordHash,
            'email_verified_at' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        $userId = $database->id();
        if ($userId) {
            echo "✅ Usuário admin criado com sucesso! ID: $userId<br>";
        } else {
            echo "❌ Erro ao criar usuário<br>";
            print_r($database->error());
        }
    }
    
    echo "<br><strong>Credenciais para login:</strong><br>";
    echo "Email: admin@taskmanager.test<br>";
    echo "Senha: Admin123!<br><br>";
    echo "<a href='/login'>Ir para Login</a><br>";
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "<br>";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>";
}
?>