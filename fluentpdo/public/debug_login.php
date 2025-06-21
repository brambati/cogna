<?php
/**
 * Debug de Login - Arquivo para depuração de problemas de autenticação
 */

require_once '../vendor/autoload.php';
require_once '../app/config/database.php';
require_once '../app/helpers/security.php';

// Iniciar sessão
session_start();

echo "<h1>Debug de Login - Sistema FluentPDO</h1>";
echo "<hr>";

// Informações da sessão
echo "<h2>Informações da Sessão</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";
echo "<hr>";

// Testar conexão com banco
echo "<h2>Teste de Conexão com Banco</h2>";
try {
    $config = require '../app/config/database.php';
    $pdo = new PDO($config['dsn'], $config['username'], $config['password'], $config['options']);
    $fluent = new Envms\FluentPDO\Query($pdo);
    
    echo "<p style='color: green;'>✓ Conexão com banco estabelecida com sucesso!</p>";
    
    // Testar consulta simples
    $userCount = $fluent->from('users')->count();
    echo "<p>Total de usuários: <strong>$userCount</strong></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Erro na conexão: " . $e->getMessage() . "</p>";
}
echo "<hr>";

// Listar usuários
echo "<h2>Usuários no Sistema</h2>";
try {
    $users = $fluent->from('users')
        ->select(['id', 'username', 'email', 'first_name', 'last_name', 'created_at'])
        ->orderBy('created_at DESC')
        ->fetchAll();
    
    if (empty($users)) {
        echo "<p>Nenhum usuário encontrado.</p>";
    } else {
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Nome</th><th>Criado em</th></tr>";
        
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>{$user['id']}</td>";
            echo "<td>{$user['username']}</td>";
            echo "<td>{$user['email']}</td>";
            echo "<td>{$user['first_name']} {$user['last_name']}</td>";
            echo "<td>{$user['created_at']}</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Erro ao listar usuários: " . $e->getMessage() . "</p>";
}
echo "<hr>";

// Informações do servidor
echo "<h2>Informações do Servidor</h2>";
echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";
echo "<p><strong>Server Software:</strong> " . ($_SERVER['SERVER_SOFTWARE'] ?? 'N/A') . "</p>";
echo "<p><strong>Document Root:</strong> " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
echo "<p><strong>Script Name:</strong> " . $_SERVER['SCRIPT_NAME'] . "</p>";
echo "<hr>";

// Links úteis
echo "<h2>Links Úteis</h2>";
echo "<ul>";
echo "<li><a href='login.php'>Página de Login</a></li>";
echo "<li><a href='register.php'>Página de Registro</a></li>";
echo "<li><a href='dashboard.php'>Dashboard</a></li>";
echo "<li><a href='create_user.php'>Criar Usuário de Teste</a></li>";
echo "</ul>";
?> 