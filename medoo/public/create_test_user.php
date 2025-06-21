<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "Creating test user...\n";

try {
    $config = require '../app/config/database.php';
    $database = new Medoo\Medoo($config);
    
    $testEmail = 'test@test.com';
    $testPassword = '123456';
    $testName = 'Test User';
    
    // Verificar se o usuário já existe
    $existingUser = $database->get('users', 'id', ['email' => $testEmail]);
    
    if ($existingUser) {
        echo "User already exists, updating password...\n";
        $database->update('users', [
            'password' => password_hash($testPassword, PASSWORD_DEFAULT)
        ], [
            'email' => $testEmail
        ]);
    } else {
        echo "Creating new user...\n";
        $database->insert('users', [
            'name' => $testName,
            'email' => $testEmail,
            'password' => password_hash($testPassword, PASSWORD_DEFAULT),
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    echo "Test user created/updated successfully!\n";
    echo "Email: $testEmail\n";
    echo "Password: $testPassword\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
