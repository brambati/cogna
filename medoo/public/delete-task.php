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
    // Conectar ao banco
    $config = require __DIR__ . '/../app/config/database.php';
    $database = new Medoo\Medoo($config);
    
    // Verificar se a tarefa existe e pertence ao usuário logado
    $task = $database->get('tasks', '*', [
        'id' => $taskId,
        'user_id' => $_SESSION['user_id']
    ]);
    
    if (!$task) {
        header('Location: dashboard.php');
        exit;
    }
    
    // Excluir a tarefa
    $result = $database->delete('tasks', [
        'id' => $taskId,
        'user_id' => $_SESSION['user_id']
    ]);
    
    if ($result->rowCount() > 0) {
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