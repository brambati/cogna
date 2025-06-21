<?php
echo "1. Teste de PHP básico - OK<br>";

try {
    echo "2. Iniciando sessão...<br>";
    session_start();
    echo "3. Sessão iniciada - OK<br>";
} catch (Exception $e) {
    echo "3. Erro na sessão: " . $e->getMessage() . "<br>";
}

try {
    echo "4. Carregando autoload...<br>";
    require_once __DIR__ . '/../vendor/autoload.php';
    echo "5. Autoload carregado - OK<br>";
} catch (Exception $e) {
    echo "5. Erro no autoload: " . $e->getMessage() . "<br>";
}

try {
    echo "6. Configurando banco...<br>";
    $config = [
        'type' => 'mysql',
        'host' => 'mysql',
        'database' => 'taskmanager',
        'username' => 'taskuser',
        'password' => 'taskpass',
        'charset' => 'utf8mb4',
        'port' => 3306
    ];
    echo "7. Config criada - OK<br>";
    
    $database = new Medoo\Medoo($config);
    echo "8. Conexão Medoo criada - OK<br>";
    
    $user = $database->get('users', '*', ['email' => 'admin@taskmanager.test']);
    echo "9. Query executada - OK<br>";
    
    if ($user) {
        echo "10. Usuário encontrado: " . $user['name'] . "<br>";
    } else {
        echo "10. Usuário não encontrado<br>";
    }
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "<br>";
    echo "Código: " . $e->getCode() . "<br>";
    echo "Arquivo: " . $e->getFile() . ":" . $e->getLine() . "<br>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Teste de Login</title>
    <link rel="stylesheet" href="/style.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <h2>Teste de Login - Medoo</h2>
            
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                
                <div class="form-group">
                    <label>Email:</label>
                    <input type="email" name="email" value="admin@taskmanager.test" class="form-control">
                </div>
                
                <div class="form-group">
                    <label>Senha:</label>
                    <input type="password" name="password" value="Admin123!" class="form-control">
                </div>
                
                <button type="submit" class="btn btn-primary">Testar Login</button>
            </form>
            
            <br><a href="/login">Voltar ao Login Normal</a>
        </div>
    </div>
</body>
</html>