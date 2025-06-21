<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Verificar se o ID da tarefa foi fornecido (GET ou POST)
$taskId = $_GET['task_id'] ?? $_POST['task_id'] ?? null;
if (!$taskId || !is_numeric($taskId)) {
    header('Location: dashboard.php');
    exit;
}

try {
    // Conectar ao banco usando FluentPDO
    $config = require __DIR__ . '/../app/config/database.php';
    $pdo = new PDO(
        $config['dsn'],
        $config['username'],
        $config['password'],
        $config['options']
    );
    $fluent = new Envms\FluentPDO\Query($pdo);
    
    // Verificar se a tarefa existe e pertence ao usuário logado
    $task = $fluent->from('tasks')
        ->where('id', $taskId)
        ->where('user_id', $_SESSION['user_id'])
        ->fetch();
    
    if (!$task) {
        header('Location: dashboard.php');
        exit;
    }
    
    // Excluir a tarefa
    $result = $fluent->deleteFrom('tasks')
        ->where('id', $taskId)
        ->where('user_id', $_SESSION['user_id'])
        ->execute();
    
    if ($result) {
        // Sucesso - redirecionar para dashboard
        header('Location: dashboard.php?success=task_deleted');
        exit;
    } else {
        // Erro - redirecionar para dashboard
        header('Location: dashboard.php?error=delete_failed');
        exit;
    }
    
} catch (Exception $e) {
    // Erro - redirecionar para dashboard
    header('Location: dashboard.php?error=server_error');
    exit;
}
?>
