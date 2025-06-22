<?php
session_start();

// Verificar se est√° logado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$task_id = $_GET['id'] ?? null;

if (!$task_id) {
    header('Location: dashboard.php');
    exit;
}

// Buscar tarefa espec√≠fica
require_once __DIR__ . '/../app/models/Task.php';
$taskModel = new Task();
$taskResult = $taskModel->getById($task_id);

if (!$taskResult['success'] || empty($taskResult['task'])) {
    header('Location: dashboard.php');
    exit;
}

$task = $taskResult['task'];

// Verificar se a tarefa pertence ao usu√°rio
if ($task['user_id'] != $user_id) {
    header('Location: dashboard.php');
    exit;
}

function translatePriority($priority) {
    $translations = [
        'low' => 'Baixa',
        'medium' => 'M√©dia', 
        'high' => 'Alta',
        'urgent' => 'Urgente'
    ];
    return $translations[$priority] ?? ucfirst($priority);
}

function translateStatus($status) {
    $translations = [
        'pending' => 'Pendente',
        'in_progress' => 'Em Andamento',
        'completed' => 'Conclu√≠da',
        'cancelled' => 'Cancelada'
    ];
    return $translations[$status] ?? ucfirst($status);
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes da Tarefa - Sistema de Tarefas</title>
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>
    <header class="header">
        <div class="header-content">
            <h1>Sistema de Tarefas</h1>
            <nav class="nav">
                <a href="dashboard.php" class="nav-link">Dashboard</a>
                <a href="categories.php" class="nav-link">Categorias</a>
                <a href="profile.php" class="nav-link">Perfil</a>
                <a href="logout.php" class="nav-link">Sair</a>
            </nav>
        </div>
    </header>

    <main class="main">
        <div class="container">
            <h2>Detalhes da Tarefa</h2>
            
            <div class="task-card">
                <h3><?php echo htmlspecialchars($task['title']); ?></h3>
                
                <div class="task-meta">
                    <span class="task-status">Ì≥ä <?php echo translateStatus($task['status']); ?></span>
                    <span class="task-priority">Ì¥• <?php echo translatePriority($task['priority']); ?></span>
                    <?php if (!empty($task['category_name'])): ?>
                        <span class="task-category">Ì≥Å <?php echo htmlspecialchars($task['category_name']); ?></span>
                    <?php endif; ?>
                    <span class="task-date">Ì≥Ö <?php echo date('d/m/Y H:i', strtotime($task['created_at'])); ?></span>
                    <?php if (!empty($task['due_date'])): ?>
                        <span class="task-due-date">‚è∞ <?php echo date('d/m/Y', strtotime($task['due_date'])); ?></span>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($task['description'])): ?>
                <div class="task-description">
                    <h4>Descri√ß√£o:</h4>
                    <p><?php echo nl2br(htmlspecialchars($task['description'])); ?></p>
                </div>
                <?php endif; ?>
                
                <div class="task-actions">
                    <a href="dashboard.php" class="btn-secondary">‚Üê Voltar</a>
                    <a href="edit-task.php?id=<?php echo $task['id']; ?>" class="btn-primary">‚úèÔ∏è Editar</a>
                    <?php if ($task['status'] !== 'completed'): ?>
                        <button onclick="markAsCompleted(<?php echo $task['id']; ?>)" class="btn-secondary">‚úÖ Concluir</button>
                    <?php endif; ?>
                    <a href="delete-task.php?task_id=<?php echo $task['id']; ?>" 
                       onclick="return confirm('Tem certeza?')" 
                       class="btn-danger">Ì∑ëÔ∏è Excluir</a>
                </div>
            </div>
        </div>
    </main>

    <script src="js/api.js"></script>
    <script>
        async function markAsCompleted(taskId) {
            try {
                await api.updateTaskStatus(taskId, 'completed');
                api.showSuccess('Tarefa conclu√≠da!');
                setTimeout(() => window.location.reload(), 1000);
            } catch (error) {
                api.showError('Erro: ' + error.message);
            }
        }
    </script>
</body>
</html>
