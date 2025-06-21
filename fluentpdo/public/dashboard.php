<?php

/**
 * Dashboard - FluentPDO
 */

require_once __DIR__ . '/../app/helpers/security.php';

// Verificar se está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

require_once __DIR__ . '/../app/models/User.php';
$userModel = new User();

// Verificar sessão válida
if (isset($_SESSION['session_token'])) {
    $sessionData = $userModel->verifySession($_SESSION['session_token']);
    if (!$sessionData) {
        session_destroy();
        header('Location: /login');
        exit;
    }
    $currentUser = $sessionData;
} else {
    header('Location: /login');
    exit;
}

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Task Manager FluentPDO</title>
    <link rel="stylesheet" href="/css/style.css">
    <meta name="csrf-token" content="<?php echo generateCSRFToken(); ?>">
</head>
<body class="dashboard">
    <!-- Navbar -->
    <nav class="navbar">
        <div class="container-fluid">
            <div class="row">
                <div class="col-6">
                    <a href="/" class="navbar-brand">Task Manager - FluentPDO</a>
                </div>
                <div class="col-6 text-right">
                    <ul class="navbar-nav">
                        <li class="nav-item">
                            <span class="nav-link">Olá, <?php echo htmlspecialchars($currentUser['first_name']); ?></span>
                        </li>
                        <li class="nav-item">
                            <a href="/logout" class="nav-link">Sair</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <div class="row">
                <!-- Sidebar -->
                <div class="col-3">
                    <div class="sidebar">
                        <h4>Filtros</h4>
                        
                        <div class="form-group">
                            <label for="search-tasks">Buscar:</label>
                            <input type="text" id="search-tasks" class="form-control" placeholder="Digite para buscar...">
                        </div>

                        <div class="form-group">
                            <label for="filter-status">Status:</label>
                            <select id="filter-status" class="form-control">
                                <option value="">Todos</option>
                                <option value="pending">Pendente</option>
                                <option value="in_progress">Em Progresso</option>
                                <option value="completed">Concluída</option>
                                <option value="cancelled">Cancelada</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="filter-priority">Prioridade:</label>
                            <select id="filter-priority" class="form-control">
                                <option value="">Todas</option>
                                <option value="low">Baixa</option>
                                <option value="medium">Média</option>
                                <option value="high">Alta</option>
                                <option value="urgent">Urgente</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="filter-category">Categoria:</label>
                            <select id="filter-category" class="form-control">
                                <option value="">Todas</option>
                                <!-- Categorias serão carregadas via AJAX -->
                            </select>
                        </div>

                        <button type="button" class="btn btn-primary btn-full" onclick="openTaskModal()">
                            Nova Tarefa
                        </button>
                    </div>
                </div>

                <!-- Content Area -->
                <div class="col-9">
                    <div class="content-area">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h2>Minhas Tarefas</h2>
                            <div id="task-stats" class="text-muted">
                                <!-- Estatísticas serão carregadas via AJAX -->
                            </div>
                        </div>

                        <!-- Alerts -->
                        <div id="alerts"></div>

                        <!-- Tasks Container -->
                        <div id="tasks-container">
                            <div class="text-center p-4">
                                <div class="loading"></div>
                                <p class="text-muted">Carregando tarefas...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Nova/Editar Tarefa -->
    <div id="taskModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modal-title">Nova Tarefa</h3>
                <button type="button" class="btn-close" onclick="closeTaskModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="taskForm">
                    <input type="hidden" id="task-id" name="id">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="form-group">
                        <label for="task-title">Título *</label>
                        <input type="text" id="task-title" name="title" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="task-description">Descrição</label>
                        <textarea id="task-description" name="description" class="form-control" rows="3"></textarea>
                    </div>

                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label for="task-priority">Prioridade</label>
                                <select id="task-priority" name="priority" class="form-control">
                                    <option value="low">Baixa</option>
                                    <option value="medium" selected>Média</option>
                                    <option value="high">Alta</option>
                                    <option value="urgent">Urgente</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label for="task-status">Status</label>
                                <select id="task-status" name="status" class="form-control">
                                    <option value="pending" selected>Pendente</option>
                                    <option value="in_progress">Em Progresso</option>
                                    <option value="completed">Concluída</option>
                                    <option value="cancelled">Cancelada</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="task-due-date">Data de Vencimento</label>
                        <input type="datetime-local" id="task-due-date" name="due_date" class="form-control">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeTaskModal()">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="saveTask()">Salvar</button>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="/js/app.js"></script>
    <script>
        // Funções específicas do dashboard
        function openTaskModal(taskId = null) {
            if (taskId) {
                $('#modal-title').text('Editar Tarefa');
                // Carregar dados da tarefa via AJAX
                window.taskManager.apiRequest(`/api/tasks/${taskId}`, 'GET')
                    .done(function(response) {
                        if (response.success) {
                            const task = response.task;
                            $('#task-id').val(task.id);
                            $('#task-title').val(task.title);
                            $('#task-description').val(task.description || '');
                            $('#task-priority').val(task.priority);
                            $('#task-status').val(task.status);
                            if (task.due_date) {
                                const date = new Date(task.due_date);
                                const formattedDate = date.toISOString().slice(0, 16);
                                $('#task-due-date').val(formattedDate);
                            }
                        }
                    });
            } else {
                $('#modal-title').text('Nova Tarefa');
                $('#taskForm')[0].reset();
                $('#task-id').val('');
            }
            $('#taskModal').show();
        }

        function closeTaskModal() {
            $('#taskModal').hide();
        }

        function saveTask() {
            if (!window.taskManager.validateForm('#taskForm')) return;

            const formData = new FormData(document.getElementById('taskForm'));
            const data = Object.fromEntries(formData.entries());
            
            const taskId = $('#task-id').val();
            const method = taskId ? 'PUT' : 'POST';
            const url = taskId ? `/api/tasks/${taskId}` : '/api/tasks';

            window.taskManager.apiRequest(url, method, data)
                .done(function(response) {
                    if (response.success) {
                        window.taskManager.showAlert('Tarefa salva com sucesso!', 'success');
                        closeTaskModal();
                        window.taskManager.loadTasks();
                        window.taskManager.loadStats();
                    } else {
                        window.taskManager.showAlert('Erro ao salvar tarefa', 'danger');
                    }
                });
        }

        // CSS para modal
        $('<style>').text(`
            .modal {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.5);
                z-index: 1000;
            }
            .modal-content {
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                background: white;
                border-radius: 8px;
                width: 90%;
                max-width: 600px;
                max-height: 90vh;
                overflow-y: auto;
            }
            .modal-header {
                padding: 20px;
                border-bottom: 1px solid #ddd;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .modal-body {
                padding: 20px;
            }
            .modal-footer {
                padding: 20px;
                border-top: 1px solid #ddd;
                text-align: right;
            }
            .btn-close {
                background: none;
                border: none;
                font-size: 24px;
                cursor: pointer;
            }
        `).appendTo('head');
    </script>
</body>
</html>