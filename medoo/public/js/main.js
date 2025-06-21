// JavaScript para funcionalidades do dashboard e gerenciamento de tarefas
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar filtros
    initializeFilters();
    
    // Inicializar formulários
    initializeForms();
    
    // Inicializar funcionalidades do perfil
    initializeProfile();
});

// Inicializar filtros do dashboard
function initializeFilters() {
    const statusFilter = document.getElementById('status-filter');
    const categoryFilter = document.getElementById('category-filter');
    const priorityFilter = document.getElementById('priority-filter');
    const sortFilter = document.getElementById('sort-filter');
    
    if (statusFilter) statusFilter.addEventListener('change', filterTasks);
    if (categoryFilter) categoryFilter.addEventListener('change', filterTasks);
    if (priorityFilter) priorityFilter.addEventListener('change', filterTasks);
    if (sortFilter) sortFilter.addEventListener('change', sortTasks);
    
    // Executar filtros na inicialização
    filterTasks();
}

// Limpar todos os filtros
function clearAllFilters() {
    document.getElementById('status-filter').value = '';
    document.getElementById('category-filter').value = '';
    document.getElementById('priority-filter').value = '';
    document.getElementById('sort-filter').value = 'created_desc';
    
    filterTasks();
    sortTasks();
}

// Filtrar tarefas
function filterTasks() {
    const statusFilter = document.getElementById('status-filter')?.value || '';
    const categoryFilter = document.getElementById('category-filter')?.value || '';
    const priorityFilter = document.getElementById('priority-filter')?.value || '';
    
    const taskCards = document.querySelectorAll('.task-card');
    let visibleTasks = 0;
    
    taskCards.forEach(card => {
        const taskStatus = card.dataset.status || '';
        const taskCategory = card.dataset.category || '';
        const taskPriority = card.dataset.priority || '';
        
        let shouldShow = true;
        
        if (statusFilter && taskStatus !== statusFilter) shouldShow = false;
        if (categoryFilter && taskCategory !== categoryFilter) shouldShow = false;
        if (priorityFilter && taskPriority !== priorityFilter) shouldShow = false;
        
        if (shouldShow) {
            card.style.display = 'block';
            visibleTasks++;
        } else {
            card.style.display = 'none';
        }
    });
    
    // Mostrar/ocultar estado vazio
    const emptyStates = document.querySelectorAll('.empty-state');
    emptyStates.forEach(emptyState => {
        if (visibleTasks === 0) {
            emptyState.style.display = 'block';
        } else {
            emptyState.style.display = 'none';
        }
    });
}

// Ordenar tarefas
function sortTasks() {
    const sortValue = document.getElementById('sort-filter')?.value || 'created_desc';
    const tasksGrid = document.querySelector('.tasks-grid');
    const taskCards = Array.from(document.querySelectorAll('.task-card'));
    
    if (!tasksGrid || taskCards.length === 0) return;
    
    taskCards.sort((a, b) => {
        switch (sortValue) {
            case 'created_asc':
                return new Date(a.dataset.created) - new Date(b.dataset.created);
            case 'created_desc':
                return new Date(b.dataset.created) - new Date(a.dataset.created);
            case 'due_date_asc':
                const dueDateA = a.querySelector('.task-due-date')?.textContent || '';
                const dueDateB = b.querySelector('.task-due-date')?.textContent || '';
                if (!dueDateA && !dueDateB) return 0;
                if (!dueDateA) return 1;
                if (!dueDateB) return -1;
                return dueDateA.localeCompare(dueDateB);
            case 'priority_desc':
                const priorityOrder = { 
                    'urgent': 4, 
                    'high': 3, 
                    'medium': 2, 
                    'low': 1 
                };
                const priorityA = priorityOrder[a.dataset.priority] || 0;
                const priorityB = priorityOrder[b.dataset.priority] || 0;
                return priorityB - priorityA;
            default:
                return 0;
        }
    });
    
    // Reordenar no DOM
    taskCards.forEach(card => tasksGrid.appendChild(card));
}

// Inicializar formulários
function initializeForms() {
    // Formulário de adicionar tarefa - apenas validações visuais, sem interceptar submit
    const addTaskForm = document.querySelector('form.task-form');
    if (addTaskForm) {
        // Validações em tempo real nos campos
        const titleInput = addTaskForm.querySelector('input[name="title"]');
        const categorySelect = addTaskForm.querySelector('select[name="category"]');
        const prioritySelect = addTaskForm.querySelector('select[name="priority"]');
        
        if (titleInput) {
            titleInput.addEventListener('blur', function() {
                validateField(this, 'Título é obrigatório', this.value.trim().length >= 3);
            });
        }
        
        if (categorySelect) {
            categorySelect.addEventListener('change', function() {
                validateField(this, 'Categoria é obrigatória', this.value !== '');
            });
        }
        
        if (prioritySelect) {
            prioritySelect.addEventListener('change', function() {
                validateField(this, 'Prioridade é obrigatória', this.value !== '');
            });
        }
    }
    
    // Formulário de editar tarefa - DESABILITADO para permitir envio direto
    /*
    const editTaskForm = document.getElementById('editTaskForm');
    if (editTaskForm) {
        editTaskForm.addEventListener('submit', function(e) {
            e.preventDefault();
            validateTaskForm(this);
        });
    }
    */
    
    // Formulário de perfil
    const profileForm = document.getElementById('profileForm');
    if (profileForm) {
        profileForm.addEventListener('submit', function(e) {
            e.preventDefault();
            validateProfileForm();
        });
    }
    
    // Formulário de senha
    const passwordForm = document.getElementById('passwordForm');
    if (passwordForm) {
        passwordForm.addEventListener('submit', function(e) {
            e.preventDefault();
            validatePasswordForm();
        });
    }
}

// Validar campo individual
function validateField(field, message, isValid) {
    // Encontrar ou criar span de erro
    let errorSpan = field.parentNode.querySelector('.error-message');
    if (!errorSpan) {
        errorSpan = document.createElement('span');
        errorSpan.className = 'error-message';
        field.parentNode.appendChild(errorSpan);
    }
    
    if (!isValid) {
        errorSpan.textContent = message;
        errorSpan.style.display = 'block';
        field.style.borderColor = '#dc3545';
    } else {
        errorSpan.textContent = '';
        errorSpan.style.display = 'none';
        field.style.borderColor = '#e1e5e9';
    }
}

// Validar formulário de tarefa (mantido para compatibilidade)
function validateTaskForm(form) {
    clearErrors();
    
    const title = form.querySelector('input[name="title"]').value.trim();
    const category = form.querySelector('select[name="category"]').value;
    const priority = form.querySelector('select[name="priority"]').value;
    
    let isValid = true;
    
    // Validar título
    if (!title) {
        isValid = false;
    } else if (title.length < 3) {
        isValid = false;
    }
    
    // Validar categoria
    if (!category) {
        isValid = false;
    }
    
    // Validar prioridade
    if (!priority) {
        isValid = false;
    }
    
    return isValid;
}

// Validar formulário de perfil
function validateProfileForm() {
    clearErrors();
    
    const name = document.getElementById('name').value.trim();
    const email = document.getElementById('email').value.trim();
    
    let isValid = true;
    
    // Validar nome
    if (!name) {
        showError('nameError', 'Nome é obrigatório');
        isValid = false;
    } else if (name.length < 2) {
        showError('nameError', 'Nome deve ter pelo menos 2 caracteres');
        isValid = false;
    }
    
    // Validar email
    if (!email) {
        showError('emailError', 'Email é obrigatório');
        isValid = false;
    } else if (!isValidEmail(email)) {
        showError('emailError', 'Email inválido');
        isValid = false;
    }
    
    if (isValid) {
        showSuccess('Perfil atualizado com sucesso!');
    }
}

// Validar formulário de senha
function validatePasswordForm() {
    clearErrors();
    
    const currentPassword = document.getElementById('current_password').value;
    const newPassword = document.getElementById('new_password').value;
    const confirmNewPassword = document.getElementById('confirm_new_password').value;
    
    let isValid = true;
    
    // Validar senha atual
    if (!currentPassword) {
        showError('currentPasswordError', 'Senha atual é obrigatória');
        isValid = false;
    }
    
    // Validar nova senha
    if (!newPassword) {
        showError('newPasswordError', 'Nova senha é obrigatória');
        isValid = false;
    } else if (!isValidPassword(newPassword)) {
        showError('newPasswordError', 'Senha deve ter pelo menos 6 caracteres, incluindo letras e números');
        isValid = false;
    }
    
    // Validar confirmação
    if (!confirmNewPassword) {
        showError('confirmNewPasswordError', 'Confirmação de senha é obrigatória');
        isValid = false;
    } else if (newPassword !== confirmNewPassword) {
        showError('confirmNewPasswordError', 'Senhas não conferem');
        isValid = false;
    }
    
    if (isValid) {
        showSuccess('Senha alterada com sucesso!');
    }
}

// Inicializar funcionalidades do perfil
function initializeProfile() {
    // Configurações com checkboxes
    const settingCheckboxes = document.querySelectorAll('.setting-item input[type="checkbox"]');
    settingCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            // Aqui você salvaria a configuração
            showSuccess('Configuração salva!');
        });
    });
}



// Função para confirmar exclusão de conta
function confirmDeleteAccount() {
    const confirmation = prompt('Para confirmar a exclusão da sua conta, digite "EXCLUIR":');
    if (confirmation === 'EXCLUIR') {
        if (confirm('Esta ação é irreversível. Tem certeza absoluta?')) {
            alert('Conta excluída com sucesso. Você será redirecionado.');
            // window.location.href = 'login.php';
        }
    } else if (confirmation !== null) {
        alert('Confirmação incorreta. Conta não foi excluída.');
    }
}

// Funções utilitárias
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function isValidPassword(password) {
    const passwordRegex = /^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d@$!%*#?&]{6,}$/;
    return passwordRegex.test(password);
}

function showError(elementId, message) {
    const errorElement = document.getElementById(elementId);
    if (errorElement) {
        errorElement.textContent = message;
        errorElement.style.display = 'block';
    }
}

function clearErrors() {
    const errorElements = document.querySelectorAll('.error-message');
    errorElements.forEach(element => {
        element.textContent = '';
        element.style.display = 'none';
    });
}

function showSuccess(message) {
    const successDiv = document.createElement('div');
    successDiv.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: #28a745;
        color: white;
        padding: 15px 25px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 1000;
        animation: slideInRight 0.3s ease-out;
    `;
    successDiv.textContent = message;
    
    document.body.appendChild(successDiv);
    
    setTimeout(() => {
        successDiv.remove();
    }, 3000);
}



// Adicionar animação CSS se não existir
if (!document.querySelector('#main-animations')) {
    const style = document.createElement('style');
    style.id = 'main-animations';
    style.textContent = `
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        .task-card {
            animation: fadeIn 0.5s ease-out;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    `;
    document.head.appendChild(style);
}
