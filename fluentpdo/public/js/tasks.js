/**
 * Tasks Specific Functions
 * Funções específicas para manipulação de tarefas
 */

class TaskManager {
    constructor() {
        this.tasks = [];
        this.filteredTasks = [];
        this.filters = {
            status: '',
            category: '',
            priority: '',
            sort: 'created_desc'
        };
        
        this.debug = false; // Debug desabilitado para produção
        this.log('TaskManager inicializando...');
        this.init();
    }

    /**
     * Log de debug
     */
    log(message, data = null) {
        if (this.debug) {
            console.log(`[TaskManager] ${message}`, data || '');
        }
    }

    /**
     * Inicializar o gerenciador de tarefas
     */
    init() {
        this.log('Inicializando eventos e carregando tarefas...');
        this.bindEvents();
        this.loadTasks();
    }

    /**
     * Vincular eventos
     */
    bindEvents() {
        this.log('Vinculando eventos...');
        
        // Filtros
        const statusFilter = document.getElementById('status-filter');
        const categoryFilter = document.getElementById('category-filter');
        const priorityFilter = document.getElementById('priority-filter');
        const sortFilter = document.getElementById('sort-filter');
        
        if (statusFilter) {
            statusFilter.addEventListener('change', (e) => {
                this.log('Filtro status alterado:', e.target.value);
                this.filters.status = e.target.value;
                this.applyFilters();
            });
        }

        if (categoryFilter) {
            categoryFilter.addEventListener('change', (e) => {
                this.log('Filtro categoria alterado:', e.target.value);
                this.filters.category = e.target.value;
                this.applyFilters();
            });
        }

        if (priorityFilter) {
            priorityFilter.addEventListener('change', (e) => {
                this.log('Filtro prioridade alterado:', e.target.value);
                this.filters.priority = e.target.value;
                this.applyFilters();
            });
        }

        if (sortFilter) {
            sortFilter.addEventListener('change', (e) => {
                this.log('Ordenação alterada:', e.target.value);
                this.filters.sort = e.target.value;
                this.applyFilters();
            });
        }



        // Checkboxes de status
        document.addEventListener('change', (e) => {
            if (e.target.classList.contains('task-status-checkbox')) {
                this.toggleTaskStatus(e.target);
            }
        });

        // Botão de atualizar estatísticas
        const refreshBtn = document.querySelector('.refresh-stats');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => {
                this.log('Atualizando estatísticas...');
                this.updateStats();
            });
        }
        
        this.log('Eventos vinculados com sucesso');
    }

    /**
     * Carregar todas as tarefas
     */
    async loadTasks() {
        this.log('Carregando tarefas...');
        
        try {
            if (typeof window.api !== 'undefined') {
                this.log('Usando API para carregar tarefas...');
                const response = await window.api.getTasks();
                this.tasks = response.data || [];
                this.log('Tarefas carregadas via API:', this.tasks.length);
            } else {
                // Fallback: buscar do DOM se API não estiver disponível
                this.log('API não disponível, carregando do DOM...');
                this.tasks = this.getTasksFromDOM();
                this.log('Tarefas carregadas do DOM:', this.tasks.length);
            }
            
            this.log('Lista de tarefas:', this.tasks);
            this.filteredTasks = [...this.tasks];
            this.applyFilters();
            this.updateStats();
        } catch (error) {
            this.log('Erro ao carregar tarefas:', error);
            this.tasks = this.getTasksFromDOM();
            this.filteredTasks = [...this.tasks];
            this.applyFilters();
        }
    }

    /**
     * Obter tarefas do DOM (fallback)
     */
    getTasksFromDOM() {
        this.log('Extraindo tarefas do DOM...');
        const taskCards = document.querySelectorAll('.task-card');
        this.log('Cards de tarefa encontrados:', taskCards.length);
        const tasks = [];

        taskCards.forEach((card, index) => {
            const checkbox = card.querySelector('.task-status-checkbox');
            const titleElement = card.querySelector('.task-title');
            const descElement = card.querySelector('.task-description');
            
            const task = {
                id: checkbox?.dataset.taskId || index,
                title: titleElement?.textContent?.trim() || '',
                description: descElement?.textContent?.trim() || '',
                status: card.dataset.status || 'pending',
                priority: card.dataset.priority || 'medium',
                category: card.dataset.category || '',
                category_name: card.dataset.categoryName || '',
                created_at: card.dataset.created || new Date().toISOString()
            };
            
            this.log(`Tarefa ${index + 1}:`, task);
            tasks.push(task);
        });

        this.log('Total de tarefas extraídas:', tasks.length);
        return tasks;
    }

    /**
     * Aplicar filtros
     */
    applyFilters() {
        this.log('Aplicando filtros:', this.filters);
        let filtered = [...this.tasks];
        const originalCount = filtered.length;

        // Filtro por status
        if (this.filters.status) {
            const beforeFilter = filtered.length;
            filtered = filtered.filter(task => task.status === this.filters.status);
            this.log(`Filtro status "${this.filters.status}": ${beforeFilter} -> ${filtered.length}`);
        }

        // Filtro por categoria
        if (this.filters.category) {
            const beforeFilter = filtered.length;
            filtered = filtered.filter(task => 
                task.category_name === this.filters.category || 
                task.category === this.filters.category
            );
            this.log(`Filtro categoria "${this.filters.category}": ${beforeFilter} -> ${filtered.length}`);
        }

        // Filtro por prioridade
        if (this.filters.priority) {
            const beforeFilter = filtered.length;
            filtered = filtered.filter(task => task.priority === this.filters.priority);
            this.log(`Filtro prioridade "${this.filters.priority}": ${beforeFilter} -> ${filtered.length}`);
        }



        // Ordenação
        filtered = this.sortTasks(filtered, this.filters.sort);

        this.filteredTasks = filtered;
        this.log(`Resultado final: ${originalCount} -> ${filtered.length} tarefas`);
        this.renderTasks();
    }

    /**
     * Ordenar tarefas
     */
    sortTasks(tasks, sortType) {
        return tasks.sort((a, b) => {
            switch (sortType) {
                case 'created_desc':
                    return new Date(b.created_at) - new Date(a.created_at);
                case 'created_asc':
                    return new Date(a.created_at) - new Date(b.created_at);
                case 'due_date_asc':
                    if (!a.due_date && !b.due_date) return 0;
                    if (!a.due_date) return 1;
                    if (!b.due_date) return -1;
                    return new Date(a.due_date) - new Date(b.due_date);
                case 'priority_desc':
                    const priorityOrder = { urgent: 4, high: 3, medium: 2, low: 1 };
                    return (priorityOrder[b.priority] || 0) - (priorityOrder[a.priority] || 0);
                default:
                    return 0;
            }
        });
    }

    /**
     * Renderizar tarefas filtradas
     */
    renderTasks() {
        this.log('Renderizando tarefas...');
        const tasksContainer = document.querySelector('.tasks-grid');
        const emptyState = document.querySelector('.empty-state');
        
        if (!tasksContainer) {
            this.log('ERRO: Container .tasks-grid não encontrado!');
            return;
        }

        // Esconder/mostrar cards baseado nos filtros
        const allCards = tasksContainer.querySelectorAll('.task-card');
        this.log('Cards no DOM:', allCards.length);
        
        const filteredIds = this.filteredTasks.map(task => String(task.id));
        this.log('IDs filtrados:', filteredIds);

        let visibleCount = 0;
        allCards.forEach((card, index) => {
            const checkbox = card.querySelector('.task-status-checkbox');
            const taskId = String(checkbox?.dataset.taskId || index);
            const shouldShow = filteredIds.includes(taskId);
            
            card.style.display = shouldShow ? 'block' : 'none';
            if (shouldShow) visibleCount++;
            
            this.log(`Card ${index}: ID="${taskId}", Mostrar=${shouldShow}`);
        });

        this.log(`Cards visíveis: ${visibleCount}/${allCards.length}`);

        // Mostrar/esconder estado vazio
        if (emptyState) {
            const showEmpty = visibleCount === 0;
            emptyState.style.display = showEmpty ? 'block' : 'none';
            this.log('Estado vazio:', showEmpty ? 'mostrado' : 'escondido');
        }
    }

    /**
     * Alternar status da tarefa
     */
    async toggleTaskStatus(checkbox) {
        const taskId = checkbox.dataset.taskId;
        const currentStatus = checkbox.checked ? 'completed' : 'pending';
        
        this.log(`Alterando status da tarefa ${taskId} para: ${currentStatus}`);
        
        try {
            if (typeof window.api !== 'undefined') {
                await window.api.updateTaskStatus(taskId, currentStatus);
                window.api.showSuccess(`Tarefa marcada como ${currentStatus === 'completed' ? 'concluída' : 'pendente'}!`);
            }
            
            // Atualizar tarefa local
            const taskIndex = this.tasks.findIndex(task => task.id == taskId);
            if (taskIndex !== -1) {
                this.tasks[taskIndex].status = currentStatus;
                this.log('Status atualizado localmente');
            }
            
            // Atualizar visual da tarefa
            const taskCard = checkbox.closest('.task-card');
            if (taskCard) {
                taskCard.dataset.status = currentStatus;
                const statusSpan = taskCard.querySelector('.task-status');
                if (statusSpan) {
                    statusSpan.textContent = `📊 ${this.translateStatus(currentStatus)}`;
                    statusSpan.className = `task-status status-${currentStatus}`;
                }
            }
            
            this.updateStats();
            
        } catch (error) {
            this.log('Erro ao atualizar status:', error);
            checkbox.checked = !checkbox.checked; // Reverter checkbox
            if (typeof window.api !== 'undefined') {
                window.api.showError('Erro ao atualizar status da tarefa');
            }
        }
    }

    /**
     * Traduzir status
     */
    translateStatus(status) {
        const translations = {
            'pending': 'Pendente',
            'in_progress': 'Em Andamento',
            'completed': 'Concluída',
            'cancelled': 'Cancelada'
        };
        return translations[status] || status;
    }

    /**
     * Atualizar estatísticas
     */
    async updateStats() {
        try {
            let stats;
            
            if (typeof window.api !== 'undefined') {
                const response = await window.api.getStats();
                stats = response.data || response;
            } else {
                // Calcular estatísticas localmente
                stats = this.calculateLocalStats();
            }
            
            this.renderStats(stats);
            
        } catch (error) {
            this.log('Erro ao atualizar estatísticas:', error);
            // Fallback para estatísticas locais
            const stats = this.calculateLocalStats();
            this.renderStats(stats);
        }
    }

    /**
     * Calcular estatísticas localmente
     */
    calculateLocalStats() {
        const stats = {
            total: this.tasks.length,
            pending: this.tasks.filter(t => t.status === 'pending').length,
            in_progress: this.tasks.filter(t => t.status === 'in_progress').length,
            completed: this.tasks.filter(t => t.status === 'completed').length,
            urgent: this.tasks.filter(t => t.priority === 'urgent').length,
            overdue: 0
        };

        // Calcular atrasadas
        const today = new Date();
        stats.overdue = this.tasks.filter(task => {
            if (!task.due_date || task.status === 'completed') return false;
            return new Date(task.due_date) < today;
        }).length;

        // Taxa de conclusão
        stats.completion_rate = stats.total > 0 ? Math.round((stats.completed / stats.total) * 100) : 0;

        return stats;
    }

    /**
     * Renderizar estatísticas
     */
    renderStats(stats) {
        // Atualizar contadores
        document.querySelector('.total-tasks')?.textContent = stats.total || 0;
        document.querySelector('.pending-tasks')?.textContent = stats.pending || 0;
        document.querySelector('.in-progress-tasks')?.textContent = stats.in_progress || 0;
        document.querySelector('.completed-tasks')?.textContent = stats.completed || 0;
        document.querySelector('.urgent-priority')?.textContent = stats.urgent || 0;
        document.querySelector('.overdue-tasks')?.textContent = stats.overdue || 0;

        // Atualizar taxa de conclusão
        const completionRate = stats.completion_rate || 0;
        document.querySelector('.completion-rate')?.textContent = `${completionRate}%`;
        document.querySelector('.completion-progress')?.style?.setProperty('width', `${completionRate}%`);
    }

    /**
     * Limpar todos os filtros
     */
    clearFilters() {
        this.log('Limpando todos os filtros...');
        
        // Resetar filtros
        this.filters = {
            status: '',
            category: '',
            priority: '',
            sort: 'created_desc'
        };

        // Resetar campos do formulário
        const statusFilter = document.getElementById('status-filter');
        const categoryFilter = document.getElementById('category-filter'); 
        const priorityFilter = document.getElementById('priority-filter');
        const sortFilter = document.getElementById('sort-filter');
        
        if (statusFilter) statusFilter.value = '';
        if (categoryFilter) categoryFilter.value = '';
        if (priorityFilter) priorityFilter.value = '';
        if (sortFilter) sortFilter.value = 'created_desc';

        // Aplicar filtros (vazio = mostrar todos)
        this.applyFilters();
    }

    /**
     * Excluir tarefa
     */
    async deleteTask(taskId) {
        if (!confirm('Tem certeza que deseja excluir esta tarefa?')) {
            return false;
        }

        try {
            if (typeof window.api !== 'undefined') {
                await window.api.deleteTask(taskId);
                window.api.showSuccess('Tarefa excluída com sucesso!');
            }

            // Remover do array local
            this.tasks = this.tasks.filter(task => task.id != taskId);
            
            // Remover do DOM
            const taskCard = document.querySelector(`[data-task-id="${taskId}"]`)?.closest('.task-card');
            if (taskCard) {
                taskCard.remove();
            }

            this.applyFilters();
            this.updateStats();
            
            return true;
        } catch (error) {
            this.log('Erro ao excluir tarefa:', error);
            if (typeof window.api !== 'undefined') {
                window.api.showError('Erro ao excluir tarefa');
            }
            return false;
        }
    }
}

/**
 * Função global para limpar filtros (chamada pelo HTML)
 */
function clearAllFilters() {
    if (window.taskManager) {
        window.taskManager.clearFilters();
    }
}

/**
 * Inicializar quando o DOM estiver pronto
 */
document.addEventListener('DOMContentLoaded', function() {
    if (document.querySelector('.tasks-grid')) {
        window.taskManager = new TaskManager();
    }
});

/**
 * Exportar para uso global
 */
window.TaskManager = TaskManager; 