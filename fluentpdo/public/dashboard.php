<?php
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();

require_once __DIR__ . '/../app/models/Task.php';

// Verificar se está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_name = $_SESSION['name'];
$user_id = $_SESSION['user_id'];

// Função para traduzir prioridade
function translatePriority($priority) {
    $translations = [
        'low' => 'Baixa',
        'medium' => 'Média', 
        'high' => 'Alta',
        'urgent' => 'Urgente'
    ];
    return $translations[$priority] ?? ucfirst($priority);
}

// Função para traduzir status
function translateStatus($status) {
    $translations = [
        'pending' => 'Pendente',
        'in_progress' => 'Em Andamento',
        'completed' => 'Concluída',
        'cancelled' => 'Cancelada'
    ];
    return $translations[$status] ?? ucfirst($status);
}

// Buscar tarefas do usuário
$taskModel = new Task();
$tasksResult = $taskModel->getByUser($user_id);
$tasks = $tasksResult['tasks'] ?? [];

// Buscar categorias do usuário para o filtro
$categories = [];
try {
    // Usar as mesmas credenciais que funcionaram nas APIs
    $host = 'mysql';
    $dbname = 'taskmanager';
    $username = 'taskuser';
    $password = 'taskpass';
    $port = 3306;
    
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->prepare("SELECT id, name, color FROM task_categories WHERE user_id = ? AND is_active = 1 ORDER BY name");
    $stmt->execute([$user_id]);
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $categories = [];
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistema de Tarefas</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/dashboard-enhancements.css">
</head>
<body>    <header class="header">
        <div class="header-content">
            <h1>Sistema de Tarefas</h1>
            <div class="user-info">
                <span>Bem-vindo, <?php echo htmlspecialchars($user_name); ?></span>
            </div>
            <nav class="nav">
                <a href="dashboard.php" class="nav-link active">Dashboard</a>
                <a href="categories.php" class="nav-link">Categorias</a>
                <a href="profile.php" class="nav-link">Perfil</a>
                <a href="logout.php" class="nav-link">Sair</a>
            </nav>
        </div>
    </header>

    <main class="main">
        <div class="container">
            <div class="page-header">
                <h2>Minhas Tarefas</h2>
                <a href="add-task.php" class="btn-primary">+ Nova Tarefa</a>
            </div>

            <!-- Seção de Estatísticas -->
            <div class="stats-section">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">📊</div>
                        <div class="stat-content">
                            <h3 class="total-tasks">0</h3>
                            <p>Total de Tarefas</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">⏳</div>
                        <div class="stat-content">
                            <h3 class="pending-tasks">0</h3>
                            <p>Pendentes</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">🔄</div>
                        <div class="stat-content">
                            <h3 class="in-progress-tasks">0</h3>
                            <p>Em Andamento</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">✅</div>
                        <div class="stat-content">
                            <h3 class="completed-tasks">0</h3>
                            <p>Concluídas</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">🔥</div>
                        <div class="stat-content">
                            <h3 class="urgent-priority">0</h3>
                            <p>Urgentes</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">⚠️</div>
                        <div class="stat-content">
                            <h3 class="overdue-tasks">0</h3>
                            <p>Atrasadas</p>
                        </div>
                    </div>
                </div>
                
                <div class="progress-section">
                    <h4>Taxa de Conclusão: <span class="completion-rate">0%</span></h4>
                    <div class="progress">
                        <div class="progress-bar completion-progress" style="width: 0%"></div>
                    </div>
                </div>
                
                <button class="refresh-stats btn-secondary" style="margin-top: 15px;">
                    <i class="fas fa-sync-alt"></i> Atualizar Estatísticas
                </button>
            </div>

            <!-- Campo de Pesquisa -->
            <div class="search-section">
                <div class="search-input-container">
                    <input type="text" 
                           class="task-search" 
                           placeholder="🔍 Pesquisar tarefas..." 
                           style="width: 100%; padding: 12px 20px; border: 1px solid #ddd; border-radius: 8px; font-size: 16px; margin-bottom: 20px;">
                </div>
            </div>

            <div class="filters">
                <div class="filter-group">
                    <label for="status-filter">Status:</label>
                    <select id="status-filter" name="status">
                        <option value="">Todos</option>
                        <option value="pending">Pendente</option>
                        <option value="in_progress">Em Andamento</option>
                        <option value="completed">Concluída</option>
                        <option value="cancelled">Cancelada</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="category-filter">Categoria:</label>
                    <select id="category-filter" name="category">
                        <option value="">Todas</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo htmlspecialchars($category['name']); ?>">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="priority-filter">Prioridade:</label>
                    <select id="priority-filter" name="priority">
                        <option value="">Todas</option>
                        <option value="low">Baixa</option>
                        <option value="medium">Média</option>
                        <option value="high">Alta</option>
                        <option value="urgent">Urgente</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="sort-filter">Ordenar por:</label>
                    <select id="sort-filter" name="sort">
                        <option value="created_desc">Mais recentes</option>
                        <option value="created_asc">Mais antigas</option>
                        <option value="due_date_asc">Vencimento (próximo)</option>
                        <option value="priority_desc">Prioridade (alta)</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label>&nbsp;</label>
                    <button type="button" onclick="clearAllFilters()" class="btn-secondary" style="padding: 10px 16px;">🔄 Limpar Filtros</button>
                </div>
            </div>

            <div class="tasks-grid">
                <?php if (empty($tasks)): ?>
                    <div class="empty-state">
                        <h3>Nenhuma tarefa encontrada</h3>
                        <p>Crie sua primeira tarefa para começar!</p>
                        <a href="add-task.php" class="btn-primary">+ Nova Tarefa</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($tasks as $task): ?>
                        <div class="task-card" 
                             data-status="<?= htmlspecialchars($task['status']) ?>" 
                             data-priority="<?= htmlspecialchars($task['priority']) ?>"
                             data-category="<?= htmlspecialchars($task['category_name'] ?? '') ?>"
                             data-created="<?= htmlspecialchars($task['created_at']) ?>">
                            
                            <div class="task-header">
                                <div class="task-title-container" style="display: flex; align-items: center; gap: 10px;">
                                    <input type="checkbox" 
                                           class="task-status-checkbox" 
                                           data-task-id="<?= $task['id'] ?>"
                                           <?= $task['status'] === 'completed' ? 'checked' : '' ?>
                                           title="Marcar como <?= $task['status'] === 'completed' ? 'pendente' : 'concluída' ?>"
                                           style="width: 18px; height: 18px; cursor: pointer;">
                                    <h3 class="task-title" style="margin: 0; flex: 1;"><?= htmlspecialchars($task['title']) ?></h3>
                                </div>
                                <div class="task-actions">
                                    <a href="edit-task.php?id=<?= $task['id'] ?>" class="btn-secondary">Editar</a>
                                    <a href="delete-task.php?task_id=<?= $task['id'] ?>" class="btn-danger" onclick="return confirm('Tem certeza que deseja excluir esta tarefa?')">Excluir</a>
                                </div>
                            </div>
                            <p class="task-description"><?= htmlspecialchars($task['description']) ?></p>
                            <div class="task-meta">
                                <?php if (!empty($task['category_name'])): ?>
                                    <span class="task-category" style="color: <?= htmlspecialchars($task['category_color'] ?? '#666') ?>;">
                                        📁 <?= htmlspecialchars($task['category_name']) ?>
                                    </span>
                                <?php endif; ?>
                                <span class="task-priority priority-<?= htmlspecialchars($task['priority']) ?>">
                                    🔥 <?= translatePriority($task['priority']) ?>
                                </span>
                                <span class="task-status status-<?= htmlspecialchars($task['status']) ?>">
                                    📊 <?= translateStatus($task['status']) ?>
                                </span>
                                <?php if (!empty($task['due_date'])): ?>
                                    <span class="task-due-date">
                                        📅 <?= ($task['status'] === 'completed') ? 'Concluída em: ' : 'Vence em: ' ?>
                                        <?= date('d/m/Y', strtotime($task['due_date'])) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="empty-state" style="display: none;">
                <h3>Nenhuma tarefa encontrada</h3>
                <p>Crie sua primeira tarefa para começar!</p>
                <a href="add-task.php" class="btn-primary">+ Nova Tarefa</a>
            </div>
        </div>
    </main>

    <script src="js/main.js"></script>
    <script src="js/dashboard-counters.js"></script>
</body>
</html>
