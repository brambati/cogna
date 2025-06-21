<?php

/**
 * Configuração do Banco de Dados - FluentPDO
 */

return [
    'host' => $_ENV['DB_HOST'] ?? 'mysql',
    'dbname' => $_ENV['DB_NAME'] ?? 'taskmanager',
    'username' => $_ENV['DB_USER'] ?? 'taskuser',
    'password' => $_ENV['DB_PASS'] ?? 'taskpass',
    'charset' => 'utf8mb4',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ]
];