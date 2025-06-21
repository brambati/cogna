<?php

/**
 * Criar usuário admin FluentPDO
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once __DIR__ . '/../vendor/autoload.php';
    require_once __DIR__ . '/../app/helpers/security.php';
    
    $config = require __DIR__ . '/../app/config/database.php';
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
    
    echo "Verificando se usuário admin existe...<br>";
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute(['admin@taskmanager.test']);
    $existingUser = $stmt->fetch();
    
    if ($existingUser) {
        echo "✅ Usuário admin já existe! ID: {$existingUser['id']}<br>";
    } else {
        echo "Criando usuário admin...<br>";
        
        // Hash da senha
        $passwordHash = hashPassword('Admin123!');
        
        // Verificar estrutura da tabela primeiro
        $stmt = $pdo->query("DESCRIBE users");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (in_array('username', $columns)) {
            // Estrutura customizada
            $stmt = $pdo->prepare("
                INSERT INTO users (username, email, password_hash, first_name, last_name, is_active, email_verified, created_at) 
                VALUES (?, ?, ?, ?, ?, 1, 1, NOW())
            ");
            $stmt->execute(['admin', 'admin@taskmanager.test', $passwordHash, 'Admin', 'User']);
        } else {
            // Estrutura Laravel
            $stmt = $pdo->prepare("
                INSERT INTO users (name, email, password, email_verified_at, created_at, updated_at) 
                VALUES (?, ?, ?, NOW(), NOW(), NOW())
            ");
            $stmt->execute(['Admin User', 'admin@taskmanager.test', $passwordHash]);
        }
        
        $userId = $pdo->lastInsertId();
        if ($userId) {
            echo "✅ Usuário admin criado com sucesso! ID: $userId<br>";
        } else {
            echo "❌ Erro ao criar usuário<br>";
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