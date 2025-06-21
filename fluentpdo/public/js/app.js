/**
 * Task Manager Application - FluentPDO
 */

class TaskManager {
    constructor() {
        this.csrfToken = $('meta[name="csrf-token"]').attr('content');
        this.currentTasks = [];
        this.currentFilters = {};
        
        this.initializeEventListeners();
        this.loadTasks();
        this.loadStats();
    }
    
    /**
     * Inicializar event listeners
     */
    initializeEventListeners() {
        // Filtros
        $('#search-tasks').on('input', this.debounce(() => {
            this.applyFilters();
        }, 300));
        
        $('#filter-status, #filter-priority').on('change', () => {
            this.applyFilters();
        });
        
        // Auto-refresh a cada 30 segundos
        setInterval(() => {
            this.loadTasks();
            this.loadStats();
        }, 30000);
    }
    
    /**
     * Carregar tarefas
     */
    loadTasks() {
        const params = new URLSearchParams(this.currentFilters);
        
        this.apiRequest(`/api/tasks?${params.toString()}`, 'GET')
            .done((response) => {
                if (response.success) {
                    this.currentTasks = response.tasks;
                    this.renderTasks(response.tasks);
                } else {
                    this.showAlert('Erro ao carregar tarefas', 'danger');
                }
            })
            .fail(() => {
                this.showAlert('Erro de conexão ao carregar tarefas', 'danger');
            });
    }
    
    /**
     * Carregar estatísticas
     */
    loadStats() {
        this.apiRequest('/api/stats', 'GET')
            .done((response) => {
                if (response.success) {
                    this.renderStats(response.stats);
                } else {
                    console.warn('Erro ao carregar estatísticas');
                }
            })
            .fail(() => {
                console.warn('Erro de conexão ao carregar estatísticas');
            });
    }
    
    /**
     * Aplicar filtros
     */
    applyFilters() {
        this.currentFilters = {
            search: $('#search-tasks').val().trim(),
            status: $('#filter-status').val(),
            priority: $('#filter-priority').val()
        };
        
        // Remover filtros vazios
        Object.keys(this.currentFilters).forEach(key => {
            if (!this.currentFilters[key]) {
                delete this.currentFilters[key];
            }
        });
        
        this.loadTasks();
    }
    
    /**
     * Renderizar tarefas
     */
    renderTasks(tasks) {
        const container = $('#tasks-container');
        
        if (tasks.length === 0) {
            container.html(`
                <div class="text-center p-4">
                    <p class="text-muted">Nenhuma tarefa encontrada.</p>
                    <button class="btn btn-primary" onclick="openTaskModal()">Criar primeira tarefa</button>
                </div>
            `);
            return;
        }
        
        let html = '<div class="tasks-grid">';
        
        tasks.forEach(task => {
            const priorityClass = this.getPriorityClass(task.priority);
            const statusClass = this.getStatusClass(task.status);
            const dueDate = task.due_date ? new Date(task.due_date).toLocaleString('pt-BR') : 'Sem prazo';
            const isOverdue = task.due_date && new Date(task.due_date) < new Date() && 
                            !['completed', 'cancelled'].includes(task.status);
            
            html += `
                <div class="task-card ${isOverdue ? 'overdue' : ''}">
                    <div class="task-header">
                        <h4 class="task-title">${this.escapeHtml(task.title)}</h4>
                        <div class="task-actions">
                            <button class="btn btn-sm btn-outline-primary" onclick="openTaskModal(${task.id})" title="Editar">
                                <i class="icon-edit">✏️</i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="TaskManager.deleteTask(${task.id})" title="Excluir">
                                <i class="icon-delete">🗑️</i>
                            </button>
                        </div>
                    </div>
                    
                    ${task.description ? `<p class="task-description">${this.escapeHtml(task.description)}</p>` : ''}
                    
                    <div class="task-meta">
                        <span class="badge badge-${priorityClass}">${this.getPriorityLabel(task.priority)}</span>
                        <span class="badge badge-${statusClass}">${this.getStatusLabel(task.status)}</span>
                    </div>
                    
                    <div class="task-footer">
                        <small class="text-muted">
                            <strong>Prazo:</strong> ${dueDate}
                            ${isOverdue ? '<span class="text-danger"> (Em atraso)</span>' : ''}
                        </small>
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        container.html(html);
    }
    
    /**
     * Renderizar estatísticas
     */
    renderStats(stats) {
        const statsHtml = `
            <div class="stats-summary">
                <span class="stat-item">Total: <strong>${stats.total}</strong></span>
                <span class="stat-item">Pendentes: <strong>${stats.pending}</strong></span>
                <span class="stat-item">Em Progresso: <strong>${stats.in_progress}</strong></span>
                <span class="stat-item">Concluídas: <strong>${stats.completed}</strong></span>
                ${stats.overdue > 0 ? `<span class="stat-item text-danger">Em Atraso: <strong>${stats.overdue}</strong></span>` : ''}
            </div>
        `;
        $('#task-stats').html(statsHtml);
    }
    
    /**
     * Deletar tarefa
     */
    static deleteTask(taskId) {
        if (!confirm('Tem certeza que deseja excluir esta tarefa?')) {
            return;
        }
        
        const manager = window.taskManager;
        manager.apiRequest(`/api/tasks/${taskId}`, 'DELETE')
            .done((response) => {
                if (response.success) {
                    manager.showAlert('Tarefa excluída com sucesso!', 'success');
                    manager.loadTasks();
                    manager.loadStats();
                } else {
                    manager.showAlert('Erro ao excluir tarefa', 'danger');
                }
            });
    }
    
    /**
     * Fazer requisição à API
     */
    apiRequest(url, method = 'GET', data = null) {
        const config = {
            url: url,
            method: method,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        };
        
        if (data) {
            if (typeof data === 'object') {
                config.data = JSON.stringify(data);
                config.contentType = 'application/json';
            } else {
                config.data = data;
            }
        }
        
        return $.ajax(config);
    }
    
    /**
     * Mostrar alerta
     */
    showAlert(message, type = 'info') {
        const alertClass = type === 'danger' ? 'alert-error' : `alert-${type}`;
        const alertHtml = `
            <div class="alert ${alertClass} alert-dismissible">
                ${message}
                <button type="button" class="btn-close" onclick="this.parentElement.remove()">&times;</button>
            </div>
        `;
        
        $('#alerts').html(alertHtml);
        
        // Auto-remove após 5 segundos
        setTimeout(() => {
            $('#alerts .alert').fadeOut();
        }, 5000);
    }
    
    /**
     * Validar formulário
     */
    validateForm(formSelector) {
        const form = $(formSelector);
        let isValid = true;
        
        form.find('[required]').each(function() {
            const input = $(this);
            if (!input.val().trim()) {
                input.addClass('is-invalid');
                isValid = false;
            } else {
                input.removeClass('is-invalid');
            }
        });
        
        return isValid;
    }
    
    /**
     * Utility functions
     */
    getPriorityClass(priority) {
        const classes = {
            'low': 'success',
            'medium': 'warning',
            'high': 'danger',
            'urgent': 'dark'
        };
        return classes[priority] || 'secondary';
    }
    
    getPriorityLabel(priority) {
        const labels = {
            'low': 'Baixa',
            'medium': 'Média',
            'high': 'Alta',
            'urgent': 'Urgente'
        };
        return labels[priority] || priority;
    }
    
    getStatusClass(status) {
        const classes = {
            'pending': 'warning',
            'in_progress': 'info',
            'completed': 'success',
            'cancelled': 'secondary'
        };
        return classes[status] || 'secondary';
    }
    
    getStatusLabel(status) {
        const labels = {
            'pending': 'Pendente',
            'in_progress': 'Em Progresso',
            'completed': 'Concluída',
            'cancelled': 'Cancelada'
        };
        return labels[status] || status;
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
}

// Inicializar aplicação quando DOM estiver pronto
$(document).ready(function() {
    window.taskManager = new TaskManager();
    
    // CSS adicional
    $('<style>').text(`
        .tasks-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .task-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            background: white;
            transition: all 0.3s ease;
        }
        
        .task-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        
        .task-card.overdue {
            border-left: 4px solid #dc3545;
        }
        
        .task-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
        }
        
        .task-title {
            margin: 0;
            font-size: 1.1em;
            font-weight: 600;
            color: #333;
            flex: 1;
            margin-right: 10px;
        }
        
        .task-actions {
            display: flex;
            gap: 5px;
        }
        
        .task-description {
            color: #666;
            margin-bottom: 15px;
            line-height: 1.4;
        }
        
        .task-meta {
            display: flex;
            gap: 8px;
            margin-bottom: 15px;
        }
        
        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            font-weight: 500;
        }
        
        .badge-success { background-color: #d4edda; color: #155724; }
        .badge-warning { background-color: #fff3cd; color: #856404; }
        .badge-danger { background-color: #f8d7da; color: #721c24; }
        .badge-info { background-color: #d1ecf1; color: #0c5460; }
        .badge-dark { background-color: #d6d8db; color: #1b1e21; }
        .badge-secondary { background-color: #e2e3e5; color: #383d41; }
        
        .task-footer {
            border-top: 1px solid #eee;
            padding-top: 10px;
            margin-top: 15px;
        }
        
        .stats-summary {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .stat-item {
            font-size: 0.9em;
        }
        
        .alert {
            padding: 12px 20px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
            position: relative;
        }
        
        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }
        
        .alert-error {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
        
        .alert-info {
            color: #0c5460;
            background-color: #d1ecf1;
            border-color: #bee5eb;
        }
        
        .alert-dismissible .btn-close {
            position: absolute;
            top: 8px;
            right: 15px;
            background: none;
            border: none;
            font-size: 18px;
            cursor: pointer;
            opacity: 0.7;
        }
        
        .alert-dismissible .btn-close:hover {
            opacity: 1;
        }
        
        .is-invalid {
            border-color: #dc3545;
        }
        
        .loading {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #3498db;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    `).appendTo('head');
});