<?php
/**
 * Teste de Conexão - FluentPDO
 */

echo "<h1>🔧 Teste de Conexão - FluentPDO</h1>";

try {
    // Carregar configuração
    $config = require __DIR__ . '/../app/config/database.php';
    
    echo "<h2>📋 Configuração do Banco:</h2>";
    echo "<ul>";
    echo "<li><strong>DSN:</strong> " . htmlspecialchars($config['dsn']) . "</li>";
    echo "<li><strong>Username:</strong> " . htmlspecialchars($config['username']) . "</li>";
    echo "<li><strong>Password:</strong> " . str_repeat('*', strlen($config['password'])) . "</li>";
    echo "<li><strong>Host:</strong> " . htmlspecialchars($config['host']) . "</li>";
    echo "<li><strong>Database:</strong> " . htmlspecialchars($config['dbname']) . "</li>";
    echo "<li><strong>Charset:</strong> " . htmlspecialchars($config['charset']) . "</li>";
    echo "</ul>";
    
    echo "<h2>🔌 Teste de Conexão PDO:</h2>";
    
    // Testar conexão PDO
    $pdo = new PDO($config['dsn'], $config['username'], $config['password'], $config['options']);
    echo "<p style='color: green;'>✅ <strong>Conexão PDO bem-sucedida!</strong></p>";
    
    echo "<h2>🔧 Teste FluentPDO:</h2>";
    
    // Testar FluentPDO
    require_once __DIR__ . '/../vendor/autoload.php';
    $fluent = new Envms\FluentPDO\Query($pdo);
    echo "<p style='color: green;'>✅ <strong>FluentPDO inicializado com sucesso!</strong></p>";
    
    echo "<h2>📊 Teste de Consulta:</h2>";
    
    // Testar consulta simples
    $userCount = $fluent->from('users')->count();
    echo "<p>👥 <strong>Total de usuários:</strong> $userCount</p>";
    
    $tableCount = $pdo->query("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = '{$config['dbname']}'")->fetch()['count'];
    echo "<p>📋 <strong>Total de tabelas:</strong> $tableCount</p>";
    
    echo "<h2>🏗️ Estrutura das Tabelas:</h2>";
    
    // Listar tabelas
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "<ul>";
    foreach ($tables as $table) {
        $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
        echo "<li><strong>$table:</strong> $count registros</li>";
    }
    echo "</ul>";
    
    echo "<p style='color: green; font-size: 18px; font-weight: bold;'>🎉 Tudo funcionando perfeitamente!</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ <strong>Erro:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Detalhes:</strong></p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "<hr>";
echo "<p><a href='login.php'>← Voltar para Login</a> | <a href='dashboard.php'>Dashboard</a> | <a href='layout_test.php'>Teste de Layout</a></p>";
?> 