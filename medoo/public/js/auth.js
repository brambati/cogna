// Validação de formulários de autenticação
document.addEventListener('DOMContentLoaded', function() {
    console.log('Auth.js carregado');
    
    // Formulário de Login
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        console.log('Formulário de login encontrado');
        loginForm.addEventListener('submit', function(e) {
            console.log('Submit do login interceptado');
            // Só previne o envio se houver erros de validação
            if (!validateLoginFormSync()) {
                console.log('Validação falhou, prevenindo submit');
                e.preventDefault();
            } else {
                console.log('Validação passou, permitindo submit');
            }
        });
    } else {
        console.log('Formulário de login NÃO encontrado');
    }

    // Formulário de Registro
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            e.preventDefault();
            validateRegisterForm();
        });
    }

    // Formulário de Esqueci a Senha
    const forgotForm = document.getElementById('forgotForm');
    if (forgotForm) {
        forgotForm.addEventListener('submit', function(e) {
            e.preventDefault();
            validateForgotForm();
        });
    }

    // Formulário de Reset de Senha
    const resetForm = document.getElementById('resetForm');
    if (resetForm) {
        resetForm.addEventListener('submit', function(e) {
            e.preventDefault();
            validateResetForm();
        });
    }
});

// Validação síncrona do formulário de login (retorna true/false)
function validateLoginFormSync() {
    console.log('Executando validação do login');
    clearErrors();
    
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;
    
    console.log('Email:', email);
    console.log('Password length:', password.length);
    
    let isValid = true;
    
    // Validar email
    if (!email) {
        console.log('Email vazio');
        showError('emailError', 'Email ou usuário é obrigatório');
        isValid = false;
    }
    
    // Validar senha
    if (!password) {
        console.log('Senha vazia');
        showError('passwordError', 'Senha é obrigatória');
        isValid = false;
    }
    
    console.log('Validação resultado:', isValid);
    return isValid;
}

// Validação do formulário de login (versão legada)
function validateLoginForm() {
    clearErrors();
    
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;
    
    let isValid = true;
    
    // Validar email
    if (!email) {
        showError('emailError', 'Email ou usuário é obrigatório');
        isValid = false;
    }
    
    // Validar senha
    if (!password) {
        showError('passwordError', 'Senha é obrigatória');
        isValid = false;
    }
    
    if (isValid) {
        // Enviar o formulário realmente
        document.getElementById('loginForm').submit();
    }
}

// Validação do formulário de registro
function validateRegisterForm() {
    clearErrors();
    
    const name = document.getElementById('name').value.trim();
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    const terms = document.getElementById('terms').checked;
    
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
    
    // Validar senha
    if (!password) {
        showError('passwordError', 'Senha é obrigatória');
        isValid = false;
    } else if (!isValidPassword(password)) {
        showError('passwordError', 'Senha deve ter pelo menos 6 caracteres, incluindo letras e números');
        isValid = false;
    }
    
    // Validar confirmação de senha
    if (!confirmPassword) {
        showError('confirmPasswordError', 'Confirmação de senha é obrigatória');
        isValid = false;
    } else if (password !== confirmPassword) {
        showError('confirmPasswordError', 'Senhas não conferem');
        isValid = false;
    }
      // Validar termos
    if (!terms) {
        alert('Você deve aceitar os termos de uso');
        isValid = false;
    }
    
    if (isValid) {
        // Enviar o formulário de registro
        document.getElementById('registerForm').submit();
    }
}

// Validação do formulário de esqueci a senha
function validateForgotForm() {
    clearErrors();
    
    const email = document.getElementById('email').value.trim();
    
    let isValid = true;
    
    // Validar email
    if (!email) {
        showError('emailError', 'Email é obrigatório');
        isValid = false;    } else if (!isValidEmail(email)) {
        showError('emailError', 'Email inválido');
        isValid = false;
    }
    
    if (isValid) {
        // Enviar o formulário de recuperação
        document.getElementById('forgotForm').submit();
    }
}

// Validação do formulário de reset de senha
function validateResetForm() {
    clearErrors();
    
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    let isValid = true;
    
    // Validar senha
    if (!password) {
        showError('passwordError', 'Nova senha é obrigatória');
        isValid = false;
    } else if (!isValidPassword(password)) {
        showError('passwordError', 'Senha deve ter pelo menos 6 caracteres, incluindo letras e números');
        isValid = false;
    }
    
    // Validar confirmação de senha
    if (!confirmPassword) {
        showError('confirmPasswordError', 'Confirmação de senha é obrigatória');
        isValid = false;    } else if (password !== confirmPassword) {
        showError('confirmPasswordError', 'Senhas não conferem');
        isValid = false;
    }
    
    if (isValid) {
        // Enviar o formulário de reset
        document.getElementById('resetForm').submit();
    }
}

// Função para validar email
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Função para validar senha
function isValidPassword(password) {
    // Pelo menos 6 caracteres, contendo letras e números
    const passwordRegex = /^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d@$!%*#?&]{6,}$/;
    return passwordRegex.test(password);
}

// Função para mostrar erro
function showError(elementId, message) {
    const errorElement = document.getElementById(elementId);
    if (errorElement) {
        errorElement.textContent = message;
        errorElement.style.display = 'block';
    }
}

// Função para limpar todos os erros
function clearErrors() {
    const errorElements = document.querySelectorAll('.error-message');
    errorElements.forEach(element => {
        element.textContent = '';
        element.style.display = 'none';
    });
}

// Função para mostrar sucesso
function showSuccess(message) {
    // Criar elemento de sucesso temporário
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
    
    // Remover após 3 segundos
    setTimeout(() => {
        successDiv.remove();
    }, 3000);
}

// Adicionar CSS para animação
const style = document.createElement('style');
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
`;
document.head.appendChild(style);
