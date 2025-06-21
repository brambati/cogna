<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "Checking password hashes...\n";

try {
    $config = require '../app/config/database.php';
    $database = new Medoo\Medoo($config);
    
    $users = $database->select('users', ['id', 'name', 'email', 'password']);
    
    foreach ($users as $user) {
        echo "User: " . $user['name'] . " (" . $user['email'] . ")\n";
        echo "Password hash: " . substr($user['password'], 0, 20) . "...\n";
        echo "Hash length: " . strlen($user['password']) . "\n";
        echo "Is bcrypt hash: " . (password_get_info($user['password'])['algo'] ? 'Yes' : 'No') . "\n";
          // Testar senhas comuns
        $testPasswords = ['123456', 'password', 'admin', 'demo', 'teste', '123', 'admin123', 'demo123', 'teste123', '1234', 'secret', 'user123'];
        foreach ($testPasswords as $testPass) {
            if (password_verify($testPass, $user['password'])) {
                echo "*** FOUND PASSWORD: '$testPass' for user " . $user['email'] . " ***\n";
            }
        }
        echo "---\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
