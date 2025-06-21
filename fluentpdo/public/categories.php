<?php
session_start();
require_once '../vendor/autoload.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Inicializar conexão com o banco
try {
    $config = require '../app/config/database.php';
    $pdo = new PDO($config['dsn'], $config['username'], $config['password'], $config['options']);
    $fluent = new Envms\FluentPDO\Query($pdo);
} catch (Exception $e) {
    $error_message = 'Erro de conexão com o banco de dados.';
    $fluent = null;
}

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $fluent) {
    $action = $_POST['action'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $color = $_POST['color'] ?? '#3498db';
    $category_id = $_POST['id'] ?? null;

    // Validação apenas para ações que precisam do nome (add e edit)
    if (in_array($action, ['add', 'edit'])) {
        if (empty($name)) {
            $error_message = 'Nome da categoria é obrigatório.';
        } elseif (strlen($name) > 50) {
            $error_message = 'Nome da categoria deve ter no máximo 50 caracteres.';
        }
    }

    // Se não há erros de validação, processar a ação
    if (empty($error_message)) {
        try {
            if ($action === 'add') {
                // Verificar se já existe uma categoria com este nome
                $existing = $fluent->from('task_categories')
                    ->select('id')
                    ->where('user_id', $user_id)
                    ->where('name', $name)
                    ->where('is_active', 1)
                    ->fetch();
                
                if ($existing) {
                    $error_message = 'Já existe uma categoria com este nome.';
                } else {
                    $result = $fluent->insertInto('task_categories')
                        ->values([
                            'name' => $name,
                            'color' => $color,
                            'user_id' => $user_id
                        ])
                        ->execute();
                    
                    if ($result) {
                        $success_message = 'Categoria criada com sucesso!';
                    } else {
                        $error_message = 'Erro ao criar categoria.';
                    }
                }
            } elseif ($action === 'edit' && $category_id) {
                // Verificar se a categoria pertence ao usuário
                $existing = $fluent->from('task_categories')
                    ->select('id')
                    ->where('id', $category_id)
                    ->where('user_id', $user_id)
                    ->fetch();
                
                if (!$existing) {
                    $error_message = 'Categoria não encontrada.';
                } else {
                    // Verificar se já existe outra categoria com este nome
                    $duplicate = $fluent->from('task_categories')
                        ->select('id')
                        ->where('user_id', $user_id)
                        ->where('name', $name)
                        ->where('id != ?', $category_id)
                        ->where('is_active', 1)
                        ->fetch();
                    
                    if ($duplicate) {
                        $error_message = 'Já existe uma categoria com este nome.';
                    } else {
                        $result = $fluent->update('task_categories')
                            ->set([
                                'name' => $name,
                                'color' => $color
                            ])
                            ->where('id', $category_id)
                            ->where('user_id', $user_id)
                            ->execute();
                        
                        if ($result) {
                            $success_message = 'Categoria atualizada com sucesso!';
                        } else {
                            $error_message = 'Erro ao atualizar categoria.';
                        }
                    }
                }
            } elseif ($action === 'delete' && $category_id) {
                // Verificar se a categoria pertence ao usuário
                $existing = $fluent->from('task_categories')
                    ->select('id')
                    ->where('id', $category_id)
                    ->where('user_id', $user_id)
                    ->fetch();
                
                if (!$existing) {
                    $error_message = 'Categoria não encontrada.';
                } else {
                    // Marcar categoria como inativa (soft delete)
                    $result = $fluent->update('task_categories')
                        ->set(['is_active' => 0])
                        ->where('id', $category_id)
                        ->where('user_id', $user_id)
                        ->execute();
                    
                    if ($result) {
                        $success_message = 'Categoria excluída com sucesso!';
                    } else {
                        $error_message = 'Erro ao excluir categoria.';
                    }
                }
            }
        } catch (Exception $e) {
            $error_message = 'Erro ao processar solicitação. Tente novamente.';
        }
    }
}

// Buscar categorias do usuário
$categories = [];
if ($fluent) {
    try {
        // Buscar categorias básicas
        $categories = $fluent->from('task_categories')
            ->select('id, name, color, created_at')
            ->where('user_id', $user_id)
            ->where('is_active', 1)
            ->orderBy('name')
            ->fetchAll();
        
        // Adicionar contagem de tarefas para cada categoria
        foreach ($categories as &$category) {
            $task_count = $fluent->from('tasks')
                ->where('category_id', $category['id'])
                ->where('user_id', $user_id)
                ->count();
            $category['task_count'] = $task_count;
        }
        
    } catch (Exception $e) {
        $categories = [];
        if (empty($error_message)) {
            $error_message = 'Erro ao carregar categorias.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categorias - Sistema de Tarefas</title>
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>
    <header class="header">
        <div class="header-content">
            <h1>Sistema de Tarefas</h1>
            <nav class="nav">
                <a href="dashboard.php" class="nav-link">Dashboard</a>
                <a href="categories.php" class="nav-link active">Categorias</a>
                <a href="profile.php" class="nav-link">Perfil</a>
                <a href="logout.php" class="nav-link">Sair</a>
            </nav>
        </div>
    </header>

    <main class="main">
        <div class="container">
            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <div class="page-header">
                <h2>Gerenciar Categorias</h2>
                <button type="button" class="btn-primary" onclick="openAddCategoryModal()">
                    + Nova Categoria
                </button>
            </div>

            <div class="categories-grid">
                <?php if (empty($categories)): ?>
                    <div class="empty-state">
                        <p>Nenhuma categoria encontrada.</p>
                        <p>Clique em "Nova Categoria" para criar sua primeira categoria.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($categories as $category): ?>
                        <div class="category-card">
                            <div class="category-header">
                                <h3 class="category-name"><?php echo htmlspecialchars($category['name']); ?></h3>
                                <div class="category-actions">
                                    <button class="btn-secondary" onclick="editCategory(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['name']); ?>', '<?php echo htmlspecialchars($category['color']); ?>')">Editar</button>
                                    <button class="btn-danger" onclick="deleteCategory(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['name']); ?>')">Excluir</button>
                                </div>
                            </div>
                            <div class="category-info">
                                <div class="category-color" style="background-color: <?php echo htmlspecialchars($category['color']); ?>;"></div>
                                <span class="category-count"><?php echo $category['task_count']; ?> tarefas</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Modal para Adicionar/Editar Categoria -->
    <div id="categoryModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Nova Categoria</h3>
                <button type="button" class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            
            <form class="modal-form" id="categoryForm" method="POST" action="categories.php">
                <input type="hidden" id="categoryId" name="id" value="">
                <input type="hidden" id="formAction" name="action" value="add">
                
                <div class="form-group">
                    <label for="categoryName">Nome da Categoria</label>
                    <input type="text" id="categoryName" name="name" required maxlength="50">
                    <span class="help-text">Máximo 50 caracteres</span>
                </div>

                <div class="form-group">
                    <label for="categoryColor">Cor</label>
                    <div class="color-picker">
                        <input type="color" id="categoryColor" name="color" value="#3498db">
                        <div class="color-presets">
                            <button type="button" class="color-preset" style="background-color: #3498db;" onclick="setColor('#3498db')"></button>
                            <button type="button" class="color-preset" style="background-color: #e74c3c;" onclick="setColor('#e74c3c')"></button>
                            <button type="button" class="color-preset" style="background-color: #2ecc71;" onclick="setColor('#2ecc71')"></button>
                            <button type="button" class="color-preset" style="background-color: #f39c12;" onclick="setColor('#f39c12')"></button>
                            <button type="button" class="color-preset" style="background-color: #9b59b6;" onclick="setColor('#9b59b6')"></button>
                            <button type="button" class="color-preset" style="background-color: #1abc9c;" onclick="setColor('#1abc9c')"></button>
                        </div>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="closeModal()">Cancelar</button>
                    <button type="submit" class="btn-primary" id="submitBtn">Salvar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Formulário oculto para exclusão -->
    <form id="deleteForm" method="POST" action="categories.php" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="deleteId">
    </form>

    <div class="modal-overlay" id="modalOverlay" style="display: none;" onclick="closeModal()"></div>

    <script>
        function openAddCategoryModal() {
            document.getElementById('modalTitle').textContent = 'Nova Categoria';
            document.getElementById('categoryId').value = '';
            document.getElementById('formAction').value = 'add';
            document.getElementById('categoryName').value = '';
            document.getElementById('categoryColor').value = '#3498db';
            document.getElementById('submitBtn').textContent = 'Criar';
            document.getElementById('categoryModal').style.display = 'block';
            document.getElementById('modalOverlay').style.display = 'block';
        }

        function editCategory(id, name, color) {
            document.getElementById('modalTitle').textContent = 'Editar Categoria';
            document.getElementById('categoryId').value = id;
            document.getElementById('formAction').value = 'edit';
            document.getElementById('categoryName').value = name;
            document.getElementById('categoryColor').value = color;
            document.getElementById('submitBtn').textContent = 'Salvar';
            document.getElementById('categoryModal').style.display = 'block';
            document.getElementById('modalOverlay').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('categoryModal').style.display = 'none';
            document.getElementById('modalOverlay').style.display = 'none';
        }

        function setColor(color) {
            document.getElementById('categoryColor').value = color;
        }

        function deleteCategory(id, name) {
            if (confirm('Tem certeza que deseja excluir a categoria "' + name + '"? Esta ação não pode ser desfeita.')) {
                document.getElementById('deleteId').value = id;
                document.getElementById('deleteForm').submit();
            }
        }

        // Fechar modal com ESC
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeModal();
            }
        });

        // Auto-hide alerts
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.remove();
                }, 300);
            });
        }, 5000);
    </script>

    <style>
        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .category-card {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }

        .category-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }

        .category-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .category-name {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
        }

        .category-actions {
            display: flex;
            gap: 8px;
        }

        .category-actions button {
            padding: 4px 8px;
            font-size: 12px;
            border-radius: 4px;
        }

        .category-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .category-color {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            border: 2px solid #fff;
            box-shadow: 0 0 0 1px #ddd;
        }

        .category-count {
            color: #666;
            font-size: 14px;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #666;
            grid-column: 1 / -1;
        }

        .empty-state p {
            margin: 5px 0;
        }

        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }

        .modal-content {
            background: white;
            border-radius: 8px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
            z-index: 1001;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 20px 0;
        }

        .modal-header h3 {
            margin: 0;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #666;
        }

        .modal-form {
            padding: 20px;
        }

        .color-picker {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .color-presets {
            display: flex;
            gap: 8px;
        }

        .color-preset {
            width: 30px;
            height: 30px;
            border: 2px solid #fff;
            border-radius: 50%;
            cursor: pointer;
            box-shadow: 0 0 0 1px #ddd;
            transition: transform 0.2s;
        }

        .color-preset:hover {
            transform: scale(1.1);
        }

        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }

        .help-text {
            font-size: 12px;
            color: #666;
            margin-top: 4px;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 4px;
            margin-bottom: 20px;
            transition: opacity 0.3s;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</body>
</html> 