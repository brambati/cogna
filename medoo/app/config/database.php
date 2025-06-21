<?php

/**
 * Configuração do Banco de Dados - Medoo
 */

// Incluir autoload do Composer
require_once __DIR__ . '/../../vendor/autoload.php';

return [
    'type' => 'mysql',
    'host' => $_ENV['DB_HOST'] ?? 'localhost',
    'database' => $_ENV['DB_NAME'] ?? 'taskmanager',
    'username' => $_ENV['DB_USER'] ?? 'taskuser',
    'password' => $_ENV['DB_PASS'] ?? 'taskpass',
    'charset' => 'utf8mb4',
    'port' => 3306,
    'prefix' => '',
    'logging' => true,
    'option' => [
        PDO::ATTR_CASE => PDO::CASE_NATURAL,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_STRINGIFY_FETCHES => false,
        PDO::ATTR_EMULATE_PREPARES => false
    ]
];