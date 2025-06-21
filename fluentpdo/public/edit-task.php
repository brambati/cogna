<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$errors = [];
$success = false;
$task = null;
$categories = [];

// Mapeamento dos valores do formulário para os valores do banco
function mapStatus($status) {
    $map = [
        'pendente' => 'pending',
        'em_andamento' => 'in_progress',
        'concluida' => 'completed',
        'cancelada' => 'cancelled',
    ];
    return $map[$status] ?? 'pending';
}

function mapPriority($priority) {
    $map = [
        'baixa' => 'low',
        'media' => 'medium',
        'alta' => 'high',
        'urgente' => 'urgent',
    ];
    return $map[$priority] ?? 'medium';
}

// Mapeamento reverso para exibir no formulário
function mapStatusReverse($status) {
    $map = [
        'pending' => 'pendente',
        'in_progress' => 'em_andamento',
        'completed' => 'concluida',
        'cancelled' => 'cancelada',
    ];
    return $map[$status] ?? 'pendente';
}

function mapPriorityReverse($priority) {
    $map = [
        'low' => 'baixa',
        'medium' => 'media',
        'high' => 'alta',
        'urgent' => 'urgente',
    ];
    return $map[$priority] ?? 'media';
}

// Verificar se ID foi fornecido
$taskId = $_GET['id'] ?? $_POST['id'] ?? null;
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
    
    // Buscar categorias do usuário
    $categories = $fluent->from('task_categories')
        ->select('id, name, color')
        ->where('user_id', $_SESSION['user_id'])
        ->where('is_active', 1)
        ->orderBy('name')
        ->fetchAll();
    
    // Buscar tarefa
    $task = $fluent->from('tasks')
        ->where('id', $taskId)
        ->where('user_id', $_SESSION['user_id'])
        ->fetch();
    
    if (!$task) {
        header('Location: dashboard.php');
        exit;
    }
    
    // Processar formulário se for POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Validar dados básicos
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $category_id = trim($_POST['category_id'] ?? '');
        $priority = trim($_POST['priority'] ?? '');
        $status = trim($_POST['status'] ?? '');
        $dueDate = trim($_POST['due_date'] ?? '');
        
        if (empty($title)) {
            $errors[] = 'Título é obrigatório.';
        }
        
        if (empty($category_id)) {
            $errors[] = 'Categoria é obrigatória.';
        } elseif (!is_numeric($category_id)) {
            $errors[] = 'Categoria inválida.';
        }
        
        if (empty($priority)) {
            $errors[] = 'Prioridade é obrigatória.';
        }
        
        if (empty($errors)) {
            // Atualizar tarefa
            $updateData = [
                'title' => $title,
                'description' => $description,
                'category_id' => !empty($category_id) ? (int)$category_id : null,
                'status' => mapStatus($status),
                'priority' => mapPriority($priority),
                'due_date' => !empty($dueDate) ? $dueDate . ' 00:00:00' : null,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $result = $fluent->update('tasks')
                ->set($updateData)
                ->where('id', $taskId)
                ->where('user_id', $_SESSION['user_id'])
                ->execute();
            
            if ($result) {
                header('Location: dashboard.php?success=task_updated');
                exit;
            } else {
                $errors[] = 'Erro ao atualizar tarefa.';
            }
        }
    }
    
} catch (Exception $e) {
    $errors[] = 'Erro: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Tarefa - Sistema de Tarefas FluentPDO</title>
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>
    <header class="header">
        <div class="header-content">
            <h1>Sistema de Tarefas - FluentPDO</h1>
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
            <div class="page-header">
                <h2>Editar Tarefa</h2>
                <div class="header-actions">
                    <a href="dashboard.php" class="btn-secondary">← Voltar</a>
                    <a href="delete-task.php?task_id=<?php echo $task['id']; ?>" class="btn-danger" onclick="return confirm('Tem certeza que deseja excluir esta tarefa?')">
                        Excluir Tarefa
                    </a>
                </div>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="form-container">
                <form class="task-form" method="POST" action="edit-task.php">
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($task['id']); ?>">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Título da Tarefa *</label>
                            <input type="text" name="title" required maxlength="255" 
                                   value="<?php echo htmlspecialchars($_POST['title'] ?? $task['title']); ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Descrição</label>
                            <textarea name="description" rows="4" 
                                      placeholder="Descreva os detalhes da tarefa..."><?php echo htmlspecialchars($_POST['description'] ?? $task['description']); ?></textarea>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Categoria *</label>
                            <select name="category_id" required>
                                <option value="">Selecione uma categoria</option>
                                <?php
                                $currentCategoryId = $_POST['category_id'] ?? $task['category_id'] ?? '';
                                foreach ($categories as $category):
                                ?>
                                    <option value="<?php echo $category['id']; ?>" <?php echo $currentCategoryId == $category['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                                <?php if (empty($categories)): ?>
                                    <option value="" disabled>Nenhuma categoria encontrada</option>
                                <?php endif; ?>
                            </select>
                            <?php if (empty($categories)): ?>
                                <small style="color: #666;">
                                    <a href="categories.php">Criar categorias</a> antes de editar tarefas.
                                </small>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label>Prioridade *</label>
                            <select name="priority" required>
                                <option value="">Selecione a prioridade</option>
                                <?php
                                $currentPriority = $_POST['priority'] ?? mapPriorityReverse($task['priority']);
                                $priorities = ['baixa' => 'Baixa', 'media' => 'Média', 'alta' => 'Alta'];
                                foreach ($priorities as $value => $label):
                                ?>
                                    <option value="<?= $value ?>" <?= $currentPriority === $value ? 'selected' : '' ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Data de Vencimento</label>
                            <input type="date" name="due_date" 
                                   value="<?php echo $_POST['due_date'] ?? ($task['due_date'] ? date('Y-m-d', strtotime($task['due_date'])) : ''); ?>" 
                                   min="<?php echo date('Y-m-d'); ?>">
                        </div>

                        <div class="form-group">
                            <label>Status</label>
                            <select name="status">
                                <?php
                                $currentStatus = $_POST['status'] ?? mapStatusReverse($task['status']);
                                $statuses = ['pendente' => 'Pendente', 'em_andamento' => 'Em Andamento', 'concluida' => 'Concluída'];
                                foreach ($statuses as $value => $label):
                                ?>
                                    <option value="<?= $value ?>" <?= $currentStatus === $value ? 'selected' : '' ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="task-history">
                        <h3>Informações</h3>
                        <div class="history-item">
                            <span class="history-date">Criada em: <?php echo date('d/m/Y H:i', strtotime($task['created_at'])); ?></span>
                        </div>
                        <?php if ($task['updated_at'] !== $task['created_at']): ?>
                        <div class="history-item">
                            <span class="history-date">Última atualização: <?php echo date('d/m/Y H:i', strtotime($task['updated_at'])); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-actions">
                        <button type="button" onclick="history.back()" class="btn-secondary">Cancelar</button>
                        <button type="submit" class="btn-primary">Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script src="js/main.js"></script>
</body>
</html>
