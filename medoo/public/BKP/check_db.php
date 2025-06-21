<?php

/**
 * Verificar estrutura do banco de dados
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once __DIR__ . '/../vendor/autoload.php';
    $config = require __DIR__ . '/../app/config/database.php';
    $database = new Medoo\Medoo($config);
    
    echo "<h3>Verificando estrutura do banco de dados</h3>";
    
    // Listar tabelas
    $tables = $database->query("SHOW TABLES")->fetchAll();
    echo "<h4>Tabelas encontradas:</h4>";
    foreach ($tables as $table) {
        $tableName = array_values($table)[0];
        echo "- $tableName<br>";
    }
    
    // Verificar estrutura da tabela users
    echo "<h4>Estrutura da tabela 'users':</h4>";
    $columns = $database->query("DESCRIBE users")->fetchAll();
    foreach ($columns as $column) {
        echo "- {$column['Field']} ({$column['Type']})<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "<br>";
}
?>