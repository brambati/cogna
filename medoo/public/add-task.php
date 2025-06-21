<?php
session_start();
require_once __DIR__ . '/../app/models/Task.php';
require_once __DIR__ . '/../vendor/autoload.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$errors = [];
$success = false;

// Buscar categorias do usuário
$categories = [];
try {
    $config = require __DIR__ . '/../app/config/database.php';
    $database = new Medoo\Medoo($config);
    
    $categories = $database->select('task_categories', [
        'id',
        'name',
        'color'
    ], [
        'user_id' => $_SESSION['user_id'],
        'is_active' => 1,
        'ORDER' => 'name'
    ]);
} catch (Exception $e) {
    // Em caso de erro, usar categorias padrão
    $categories = [];
}

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

// Função para validar dados do formulário
function validateTaskData($data) {
    $errors = [];
    
    // Validar título
    $title = trim($data['title'] ?? '');
    if (empty($title)) {
        $errors[] = 'Título é obrigatório.';
    } elseif (strlen($title) < 3) {
        $errors[] = 'Título deve ter pelo menos 3 caracteres.';
    } elseif (strlen($title) > 255) {
        $errors[] = 'Título deve ter no máximo 255 caracteres.';
    }
    
    // Validar categoria
    $category_id = trim($data['category_id'] ?? '');
    if (empty($category_id)) {
        $errors[] = 'Categoria é obrigatória.';
    } elseif (!is_numeric($category_id)) {
        $errors[] = 'Categoria inválida.';
    }
    
    // Validar prioridade
    $priority = trim($data['priority'] ?? '');
    if (empty($priority)) {
        $errors[] = 'Prioridade é obrigatória.';
    } else {
        $validPriorities = ['baixa', 'media', 'alta'];
        if (!in_array($priority, $validPriorities)) {
            $errors[] = 'Prioridade inválida.';
        }
    }
    
    // Validar descrição (opcional)
    $description = trim($data['description'] ?? '');
    if (!empty($description) && strlen($description) > 1000) {
        $errors[] = 'Descrição deve ter no máximo 1000 caracteres.';
    }
    
    // Validar data de vencimento (opcional)
    $dueDate = trim($data['due_date'] ?? '');
    if (!empty($dueDate)) {
        $today = date('Y-m-d');
        if ($dueDate < $today) {
            $errors[] = 'Data de vencimento não pode ser no passado.';
        }
        
        // Validar formato da data
        $dateTime = DateTime::createFromFormat('Y-m-d', $dueDate);
        if (!$dateTime || $dateTime->format('Y-m-d') !== $dueDate) {
            $errors[] = 'Data de vencimento inválida.';
        }
    }
    
    // Validar status
    $status = trim($data['status'] ?? 'pendente');
    $validStatuses = ['pendente', 'em_andamento'];
    if (!in_array($status, $validStatuses)) {
        $errors[] = 'Status inválido.';
    }
    
    return $errors;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validar dados do formulário
        $validationErrors = validateTaskData($_POST);
        
        if (!empty($validationErrors)) {
            $errors = array_merge($errors, $validationErrors);
        } else {
            // Conectar ao banco
            $config = require __DIR__ . '/../app/config/database.php';
            $database = new Medoo\Medoo($config);
            
            // Verificar e adicionar campo category se não existir
            $columns = $database->query("SHOW COLUMNS FROM tasks LIKE 'category'")->fetchAll();
            if (empty($columns)) {
                $database->query("ALTER TABLE tasks ADD COLUMN category VARCHAR(50) NULL AFTER description");
            }
            
            // Preparar dados para inserção
            $taskData = [
                'user_id' => $_SESSION['user_id'],
                'title' => trim($_POST['title']),
                'description' => trim($_POST['description'] ?? ''),
                'category_id' => !empty(trim($_POST['category_id'])) ? (int)trim($_POST['category_id']) : null,
                'status' => mapStatus(trim($_POST['status'] ?? 'pendente')),
                'priority' => mapPriority(trim($_POST['priority'])),
                'due_date' => !empty(trim($_POST['due_date'])) ? trim($_POST['due_date']) . ' 00:00:00' : null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            // Inserir no banco
            $result = $database->insert('tasks', $taskData);
            $insertId = $database->id();
            
            if ($insertId > 0) {
                $success = true;
                header('Location: dashboard.php');
                exit;
            } else {
                $errors[] = 'Erro ao criar tarefa. Tente novamente.';
            }
        }
        
    } catch (Exception $e) {
        $errors[] = 'Erro: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nova Tarefa - Sistema de Tarefas</title>
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
            <div class="page-header">
                <h2>Nova Tarefa</h2>
                <a href="dashboard.php" class="btn-secondary">← Voltar</a>
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
                <form class="task-form" method="POST" action="add-task.php">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Título da Tarefa *</label>
                            <input type="text" name="title" required maxlength="255" value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Descrição</label>
                            <textarea name="description" rows="4" placeholder="Descreva os detalhes da tarefa..."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Categoria *</label>
                            <select name="category_id" required>
                                <option value="">Selecione uma categoria</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" <?php echo ($_POST['category_id'] ?? '') == $category['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                                <?php if (empty($categories)): ?>
                                    <option value="" disabled>Nenhuma categoria encontrada</option>
                                <?php endif; ?>
                            </select>
                            <?php if (empty($categories)): ?>
                                <small style="color: #666;">
                                    <a href="categories.php">Criar categorias</a> antes de adicionar tarefas.
                                </small>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label>Prioridade *</label>
                            <select name="priority" required>
                                <option value="">Selecione a prioridade</option>
                                <option value="baixa" <?php echo ($_POST['priority'] ?? '') === 'baixa' ? 'selected' : ''; ?>>Baixa</option>
                                <option value="media" <?php echo ($_POST['priority'] ?? '') === 'media' ? 'selected' : ''; ?>>Média</option>
                                <option value="alta" <?php echo ($_POST['priority'] ?? '') === 'alta' ? 'selected' : ''; ?>>Alta</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Data de Vencimento</label>
                            <input type="date" name="due_date" min="<?php echo date('Y-m-d'); ?>" value="<?php echo htmlspecialchars($_POST['due_date'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label>Status Inicial</label>
                            <select name="status">
                                <option value="pendente" <?php echo ($_POST['status'] ?? 'pendente') === 'pendente' ? 'selected' : ''; ?>>Pendente</option>
                                <option value="em_andamento" <?php echo ($_POST['status'] ?? '') === 'em_andamento' ? 'selected' : ''; ?>>Em Andamento</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="button" onclick="history.back()" class="btn-secondary">Cancelar</button>
                        <button type="submit" class="btn-primary">Criar Tarefa</button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script src="js/main.js"></script>
</body>
</html>
