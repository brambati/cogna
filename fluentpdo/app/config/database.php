<?php

/**
 * Configuração do Banco de Dados - FluentPDO
 */

// Incluir autoload do Composer
require_once __DIR__ . '/../../vendor/autoload.php';

$host = $_ENV['DB_HOST'] ?? 'localhost';
$dbname = $_ENV['DB_NAME'] ?? 'taskmanager';
$username = $_ENV['DB_USER'] ?? 'taskuser';
$password = $_ENV['DB_PASS'] ?? 'taskpass';
$charset = 'utf8mb4';
$port = $_ENV['DB_PORT'] ?? 3306;

return [
    'dsn' => "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}",
    'username' => $username,
    'password' => $password,
    'host' => $host,
    'dbname' => $dbname,
    'charset' => $charset,
    'port' => $port,
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
        PDO::ATTR_STRINGIFY_FETCHES => false
    ]
];