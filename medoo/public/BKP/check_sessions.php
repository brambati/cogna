<?php

require_once __DIR__ . '/../vendor/autoload.php';
$config = require __DIR__ . '/../app/config/database.php';
$database = new Medoo\Medoo($config);

echo "<h4>Estrutura da tabela 'user_sessions':</h4>";
$columns = $database->query("DESCRIBE user_sessions")->fetchAll();
foreach ($columns as $column) {
    echo "- {$column['Field']} ({$column['Type']})<br>";
}

?>