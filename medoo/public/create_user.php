<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>Criando Usuário Admin</h2>";

try {
    // Carregar configuração do banco
    $config = require '../app/config/database.php';
    
    // Conectar ao banco
    $database = new Medoo\Medoo($config);
    
    // Dados do usuário
    $email = 'admin@taskmanager.test';
    $password = 'Admin123!';
    $username = 'admin';
    $first_name = 'Administrador';
    $last_name = 'Sistema';
    
    // Verificar se usuário já existe
    $existing = $database->get('users', ['id', 'email'], [
        'email' => $email
    ]);
    
    if ($existing) {
        echo "<p style='color: orange;'>⚠️ Usuário já existe com email: " . $email . "</p>";
        echo "<p>ID: " . $existing['id'] . "</p>";
    } else {
        // Hash da senha
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        echo "<p>📝 Inserindo usuário...</p>";
        echo "<p>Email: " . $email . "</p>";
        echo "<p>Username: " . $username . "</p>";
        echo "<p>Nome: " . $first_name . " " . $last_name . "</p>";
        
        // Inserir usuário
        $result = $database->insert('users', [
            'username' => $username,
            'email' => $email,
            'password_hash' => $password_hash,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email_verified' => true,
            'is_active' => true
        ]);
        
        if ($result->rowCount() > 0) {
            $user_id = $database->id();
            echo "<p style='color: green;'>✅ Usuário criado com sucesso!</p>";
            echo "<p>ID do usuário: " . $user_id . "</p>";
            
            // Inserir algumas categorias padrão para o usuário
            echo "<p>📂 Criando categorias padrão...</p>";
            
            $categories = [
                ['name' => 'Trabalho', 'description' => 'Tarefas relacionadas ao trabalho', 'color' => '#007bff'],
                ['name' => 'Pessoal', 'description' => 'Tarefas pessoais', 'color' => '#28a745'],
                ['name' => 'Urgente', 'description' => 'Tarefas urgentes', 'color' => '#dc3545']
            ];
            
            foreach ($categories as $category) {
                $database->insert('task_categories', [
                    'name' => $category['name'],
                    'description' => $category['description'],
                    'color' => $category['color'],
                    'user_id' => $user_id
                ]);
            }
            
            echo "<p style='color: green;'>✅ Categorias criadas com sucesso!</p>";
            
        } else {
            echo "<p style='color: red;'>❌ Erro ao criar usuário</p>";
        }
    }
    
    // Verificar o usuário criado
    echo "<hr>";
    echo "<h3>Verificação do Usuário</h3>";
    
    $user = $database->get('users', [
        'id',
        'username', 
        'email',
        'first_name',
        'last_name',
        'email_verified',
        'is_active',
        'created_at'
    ], [
        'email' => $email
    ]);
    
    if ($user) {
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        foreach ($user as $key => $value) {
            echo "<tr><td><strong>$key</strong></td><td>$value</td></tr>";
        }
        echo "</table>";
        
        // Testar login
        echo "<hr>";
        echo "<h3>Teste de Login</h3>";
        if (password_verify($password, $database->get('users', 'password_hash', ['email' => $email]))) {
            echo "<p style='color: green;'>✅ Senha verificada com sucesso!</p>";
        } else {
            echo "<p style='color: red;'>❌ Erro na verificação da senha</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<p><a href='login.php'>← Voltar ao Login</a></p>";
echo "<p><a href='dashboard.php'>Ir para Dashboard</a></p>";
?>
