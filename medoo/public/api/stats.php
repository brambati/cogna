<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

try {
    // Usar as mesmas credenciais que funcionaram na simple-update
    $host = 'mysql';
    $dbname = 'taskmanager';
    $username = 'taskuser';
    $password = 'taskpass';
    $port = 3306;
    
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $user_id = $_SESSION['user_id'];
    
    // Contar total de tarefas
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $total_tasks = $stmt->fetchColumn();
    
    // Contar por status
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE user_id = ? AND status = 'pending'");
    $stmt->execute([$user_id]);
    $pending_tasks = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE user_id = ? AND status = 'in_progress'");
    $stmt->execute([$user_id]);
    $in_progress_tasks = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE user_id = ? AND status = 'completed'");
    $stmt->execute([$user_id]);
    $completed_tasks = $stmt->fetchColumn();
    
    // Contar por prioridade
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE user_id = ? AND priority = 'high'");
    $stmt->execute([$user_id]);
    $high_priority = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE user_id = ? AND priority = 'urgent'");
    $stmt->execute([$user_id]);
    $urgent_priority = $stmt->fetchColumn();
    
    // Tarefas atrasadas
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE user_id = ? AND status != 'completed' AND due_date < CURDATE()");
    $stmt->execute([$user_id]);
    $overdue_tasks = $stmt->fetchColumn();
    
    // Tarefas para hoje
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE user_id = ? AND due_date = CURDATE()");
    $stmt->execute([$user_id]);
    $today_tasks = $stmt->fetchColumn();
    
    // Categorias ativas
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM task_categories WHERE user_id = ? AND is_active = 1");
    $stmt->execute([$user_id]);
    $active_categories = $stmt->fetchColumn();
    
    // Taxa de conclusão
    $completion_rate = $total_tasks > 0 ? round(($completed_tasks / $total_tasks) * 100, 1) : 0;
    
    echo json_encode([
        'success' => true,
        'data' => [
            'total_tasks' => (int)$total_tasks,
            'pending_tasks' => (int)$pending_tasks,
            'in_progress_tasks' => (int)$in_progress_tasks,
            'completed_tasks' => (int)$completed_tasks,
            'high_priority' => (int)$high_priority,
            'urgent_priority' => (int)$urgent_priority,
            'overdue_tasks' => (int)$overdue_tasks,
            'today_tasks' => (int)$today_tasks,
            'active_categories' => (int)$active_categories,
            'completion_rate' => $completion_rate
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Medoo Stats Error: ' . $e->getMessage()]);
}
?> 