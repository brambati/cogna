// Dashboard Counters - Sistema de contadores em tempo real

class DashboardCounters {
    constructor() {
        this.updateInterval = 30000; // 30 segundos
        this.init();
    }

    init() {
        this.updateCounters();
        this.startAutoUpdate();
        this.bindEvents();
    }

    // Atualizar contadores via API
    async updateCounters() {
        try {
            const response = await fetch('api/stats.php');
            const data = await response.json();
            
            if (data.success) {
                this.renderCounters(data.data);
            }
        } catch (error) {
            console.error('Erro ao carregar estatísticas:', error);
        }
    }

    // Renderizar contadores na interface
    renderCounters(stats) {
        // Atualizar contadores principais
        this.updateElement('.total-tasks', stats.total_tasks);
        this.updateElement('.pending-tasks', stats.pending_tasks);
        this.updateElement('.in-progress-tasks', stats.in_progress_tasks);
        this.updateElement('.completed-tasks', stats.completed_tasks);
        
        // Atualizar contadores de prioridade
        this.updateElement('.high-priority', stats.high_priority);
        this.updateElement('.urgent-priority', stats.urgent_priority);
        
        // Contadores especiais
        this.updateElement('.overdue-tasks', stats.overdue_tasks);
        this.updateElement('.today-tasks', stats.today_tasks);
        this.updateElement('.active-categories', stats.active_categories);
        
        // Taxa de conclusão
        this.updateElement('.completion-rate', stats.completion_rate + '%');
        
        // Progresso visual
        this.updateProgressBar('.completion-progress', stats.completion_rate);
    }

    // Atualizar elemento do DOM
    updateElement(selector, value) {
        const element = document.querySelector(selector);
        if (element) {
            element.textContent = value;
            this.animateCounter(element);
        }
    }

    // Animar contador
    animateCounter(element) {
        element.style.transform = 'scale(1.1)';
        element.style.color = '#007bff';
        
        setTimeout(() => {
            element.style.transform = 'scale(1)';
            element.style.color = '';
        }, 200);
    }

    // Atualizar barra de progresso
    updateProgressBar(selector, percentage) {
        const progressBar = document.querySelector(selector);
        if (progressBar) {
            progressBar.style.width = percentage + '%';
            progressBar.setAttribute('aria-valuenow', percentage);
            
            // Cores baseadas no progresso
            progressBar.className = 'progress-bar';
            if (percentage >= 80) {
                progressBar.classList.add('bg-success');
            } else if (percentage >= 50) {
                progressBar.classList.add('bg-warning');
            } else {
                progressBar.classList.add('bg-danger');
            }
        }
    }

    // Iniciar atualização automática
    startAutoUpdate() {
        setInterval(() => {
            this.updateCounters();
        }, this.updateInterval);
    }

    // Vincular eventos
    bindEvents() {
        // Botão de atualização manual
        const refreshBtn = document.querySelector('.refresh-stats');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => {
                this.updateCounters();
                this.showRefreshFeedback();
            });
        }

        // Atualizar após operações de tarefa
        document.addEventListener('taskCreated', () => this.updateCounters());
        document.addEventListener('taskUpdated', () => this.updateCounters());
        document.addEventListener('taskDeleted', () => this.updateCounters());
        document.addEventListener('taskStatusChanged', () => this.updateCounters());
    }

    // Mostrar feedback de atualização
    showRefreshFeedback() {
        const refreshBtn = document.querySelector('.refresh-stats');
        if (refreshBtn) {
            const originalText = refreshBtn.innerHTML;
            refreshBtn.innerHTML = '<i class="fas fa-check"></i> Atualizado';
            refreshBtn.disabled = true;
            
            setTimeout(() => {
                refreshBtn.innerHTML = originalText;
                refreshBtn.disabled = false;
            }, 2000);
        }
    }
}

// Gerenciador de status de tarefas
class TaskStatusManager {
    constructor() {
        this.bindEvents();
        this.debugCheckboxes();
    }

    bindEvents() {
        // Checkboxes de status
        document.addEventListener('change', (e) => {
            if (e.target.classList.contains('task-status-checkbox')) {
                this.toggleTaskStatus(e.target);
            }
        });

        // Botões de ação rápida
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('quick-complete-btn')) {
                this.quickComplete(e.target);
            }
        });
    }

    // Debug dos checkboxes
    debugCheckboxes() {
        setTimeout(() => {
            const checkboxes = document.querySelectorAll('.task-status-checkbox');
            if (checkboxes.length === 0) {
                console.warn('Nenhum checkbox encontrado');
            }
        }, 1000);
    }

    // Alternar status da tarefa
    async toggleTaskStatus(checkbox) {
        const taskId = checkbox.dataset.taskId;
        const newStatus = checkbox.checked ? 'completed' : 'pending';
        
        try {
            const response = await fetch(`api/simple-update.php?id=${taskId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ 
                    status: newStatus 
                })
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();
            
            if (data.success) {
                this.updateTaskVisual(checkbox, newStatus);
                this.showStatusFeedback(checkbox, newStatus);
                
                // Disparar evento para atualizar contadores
                document.dispatchEvent(new CustomEvent('taskStatusChanged', {
                    detail: { taskId, newStatus }
                }));
            } else {
                // Reverter checkbox em caso de erro
                checkbox.checked = !checkbox.checked;
                this.showError(`Erro: ${data.error || 'Erro desconhecido'}`);
            }
        } catch (error) {
            console.error('Erro ao alterar status da tarefa:', error);
            checkbox.checked = !checkbox.checked;
            this.showError(`Erro de conexão: ${error.message}`);
        }
    }

    // Atualizar visual da tarefa
    updateTaskVisual(checkbox, status) {
        const taskCard = checkbox.closest('.task-card');
        if (taskCard) {
            if (status === 'completed') {
                taskCard.classList.add('task-completed');
            } else {
                taskCard.classList.remove('task-completed');
            }
        }
    }

    // Mostrar feedback de status
    showStatusFeedback(checkbox, status) {
        const feedback = document.createElement('div');
        feedback.className = 'status-feedback';
        feedback.innerHTML = status === 'completed' ? 
            '<i class="fas fa-check-circle text-success"></i> Concluída' : 
            '<i class="fas fa-clock text-warning"></i> Pendente';
        
        document.body.appendChild(feedback);
        
        setTimeout(() => {
            feedback.remove();
        }, 2000);
    }

    // Mostrar erro
    showError(message) {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'alert alert-danger position-fixed';
        errorDiv.style.cssText = `
            top: 20px;
            right: 20px;
            z-index: 9999;
            max-width: 400px;
            animation: slideIn 0.3s ease;
        `;
        errorDiv.innerHTML = `
            <i class="fas fa-exclamation-triangle"></i>
            <strong>Erro:</strong> ${message}
            <button type="button" class="close ml-2" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        `;
        
        // Adicionar botão de fechar
        const closeBtn = errorDiv.querySelector('.close');
        closeBtn.addEventListener('click', () => {
            errorDiv.remove();
        });
        
        document.body.appendChild(errorDiv);
        
        // Remover automaticamente após 5 segundos
        setTimeout(() => {
            if (errorDiv.parentNode) {
                errorDiv.remove();
            }
        }, 5000);
    }
}

// Inicializar quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    new DashboardCounters();
    new TaskStatusManager();
});

// Exportar para uso global
window.DashboardCounters = DashboardCounters;
window.TaskStatusManager = TaskStatusManager; 