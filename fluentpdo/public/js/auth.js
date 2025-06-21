// JavaScript para funcionalidades de autenticação
document.addEventListener('DOMContentLoaded', function() {
    initializeAuthForms();
    initializePasswordStrength();
    initializeAutoHideAlerts();
});

// Inicializer formulários de autenticação
function initializeAuthForms() {
    // Formulário de login
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            validateLoginForm(e);
        });
    }
    
    // Formulário de registro
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            validateRegisterForm(e);
        });
    }
    
    // Formulário de esqueci senha
    const forgotPasswordForm = document.getElementById('forgotPasswordForm');
    if (forgotPasswordForm) {
        forgotPasswordForm.addEventListener('submit', function(e) {
            validateForgotPasswordForm(e);
        });
    }
    
    // Formulário de reset de senha
    const resetPasswordForm = document.getElementById('resetPasswordForm');
    if (resetPasswordForm) {
        resetPasswordForm.addEventListener('submit', function(e) {
            validateResetPasswordForm(e);
        });
    }
}

// Validar formulário de login
function validateLoginForm(e) {
    clearAuthErrors();
    
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;
    
    let isValid = true;
    
    // Validar email
    if (!email) {
        showAuthError('emailError', 'Email é obrigatório');
        isValid = false;
    } else if (!isValidEmail(email)) {
        showAuthError('emailError', 'Email inválido');
        isValid = false;
    }
    
    // Validar senha
    if (!password) {
        showAuthError('passwordError', 'Senha é obrigatória');
        isValid = false;
    }
    
    if (!isValid) {
        e.preventDefault();
    }
}

// Validar formulário de registro
function validateRegisterForm(e) {
    clearAuthErrors();
    
    const username = document.getElementById('username').value.trim();
    const email = document.getElementById('email').value.trim();
    const firstName = document.getElementById('first_name').value.trim();
    const lastName = document.getElementById('last_name').value.trim();
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    let isValid = true;
    
    // Validar username
    if (!username) {
        showAuthError('usernameError', 'Nome de usuário é obrigatório');
        isValid = false;
    } else if (username.length < 3) {
        showAuthError('usernameError', 'Nome de usuário deve ter pelo menos 3 caracteres');
        isValid = false;
    }
    
    // Validar email
    if (!email) {
        showAuthError('emailError', 'Email é obrigatório');
        isValid = false;
    } else if (!isValidEmail(email)) {
        showAuthError('emailError', 'Email inválido');
        isValid = false;
    }
    
    // Validar nome
    if (!firstName) {
        showAuthError('firstNameError', 'Primeiro nome é obrigatório');
        isValid = false;
    }
    
    if (!lastName) {
        showAuthError('lastNameError', 'Último nome é obrigatório');
        isValid = false;
    }
    
    // Validar senha
    if (!password) {
        showAuthError('passwordError', 'Senha é obrigatória');
        isValid = false;
    } else {
        const passwordErrors = validatePasswordStrength(password);
        if (passwordErrors.length > 0) {
            showAuthError('passwordError', passwordErrors[0]);
            isValid = false;
        }
    }
    
    // Validar confirmação de senha
    if (!confirmPassword) {
        showAuthError('confirmPasswordError', 'Confirmação de senha é obrigatória');
        isValid = false;
    } else if (password !== confirmPassword) {
        showAuthError('confirmPasswordError', 'Senhas não conferem');
        isValid = false;
    }
    
    if (!isValid) {
        e.preventDefault();
    }
}

// Validar formulário de esqueci senha
function validateForgotPasswordForm(e) {
    clearAuthErrors();
    
    const email = document.getElementById('email').value.trim();
    
    let isValid = true;
    
    // Validar email
    if (!email) {
        showAuthError('emailError', 'Email é obrigatório');
        isValid = false;
    } else if (!isValidEmail(email)) {
        showAuthError('emailError', 'Email inválido');
        isValid = false;
    }
    
    if (!isValid) {
        e.preventDefault();
    }
}

// Validar formulário de reset de senha
function validateResetPasswordForm(e) {
    clearAuthErrors();
    
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    let isValid = true;
    
    // Validar senha
    if (!password) {
        showAuthError('passwordError', 'Nova senha é obrigatória');
        isValid = false;
    } else {
        const passwordErrors = validatePasswordStrength(password);
        if (passwordErrors.length > 0) {
            showAuthError('passwordError', passwordErrors[0]);
            isValid = false;
        }
    }
    
    // Validar confirmação de senha
    if (!confirmPassword) {
        showAuthError('confirmPasswordError', 'Confirmação de senha é obrigatória');
        isValid = false;
    } else if (password !== confirmPassword) {
        showAuthError('confirmPasswordError', 'Senhas não conferem');
        isValid = false;
    }
    
    if (!isValid) {
        e.preventDefault();
    }
}

// Inicializar indicador de força da senha
function initializePasswordStrength() {
    const passwordInputs = document.querySelectorAll('input[type="password"]');
    
    passwordInputs.forEach(input => {
        if (input.id === 'password' || input.id === 'new_password') {
            input.addEventListener('input', function() {
                updatePasswordStrength(this);
            });
        }
    });
}

// Atualizar indicador de força da senha
function updatePasswordStrength(input) {
    const password = input.value;
    let strengthIndicator = input.parentNode.querySelector('.password-strength');
    
    if (!strengthIndicator) {
        strengthIndicator = document.createElement('div');
        strengthIndicator.className = 'password-strength';
        input.parentNode.appendChild(strengthIndicator);
    }
    
    if (password.length === 0) {
        strengthIndicator.innerHTML = '';
        return;
    }
    
    const strength = calculatePasswordStrength(password);
    const strengthText = ['Muito Fraca', 'Fraca', 'Média', 'Forte', 'Muito Forte'];
    const strengthColors = ['#dc3545', '#fd7e14', '#ffc107', '#28a745', '#20c997'];
    
    strengthIndicator.innerHTML = `
        <div class="strength-bar">
            <div class="strength-fill" style="width: ${(strength + 1) * 20}%; background-color: ${strengthColors[strength]}"></div>
        </div>
        <span class="strength-text" style="color: ${strengthColors[strength]}">${strengthText[strength]}</span>
    `;
    
    // Adicionar CSS se não existir
    if (!document.querySelector('#password-strength-styles')) {
        const style = document.createElement('style');
        style.id = 'password-strength-styles';
        style.textContent = `
            .password-strength {
                margin-top: 8px;
            }
            .strength-bar {
                height: 4px;
                background: #e1e5e9;
                border-radius: 2px;
                overflow: hidden;
                margin-bottom: 5px;
            }
            .strength-fill {
                height: 100%;
                transition: all 0.3s ease;
            }
            .strength-text {
                font-size: 12px;
                font-weight: 500;
            }
        `;
        document.head.appendChild(style);
    }
}

// Calcular força da senha
function calculatePasswordStrength(password) {
    let strength = 0;
    
    // Comprimento
    if (password.length >= 8) strength++;
    if (password.length >= 12) strength++;
    
    // Caracteres diversos
    if (/[a-z]/.test(password)) strength++;
    if (/[A-Z]/.test(password)) strength++;
    if (/[0-9]/.test(password)) strength++;
    if (/[^A-Za-z0-9]/.test(password)) strength++;
    
    // Ajustar para escala 0-4
    return Math.min(4, Math.max(0, strength - 1));
}

// Validar força da senha (retorna array de erros)
function validatePasswordStrength(password) {
    const errors = [];
    
    if (password.length < 8) {
        errors.push('Senha deve ter pelo menos 8 caracteres');
    }
    
    if (!/[A-Z]/.test(password)) {
        errors.push('Senha deve conter pelo menos uma letra maiúscula');
    }
    
    if (!/[a-z]/.test(password)) {
        errors.push('Senha deve conter pelo menos uma letra minúscula');
    }
    
    if (!/[0-9]/.test(password)) {
        errors.push('Senha deve conter pelo menos um número');
    }
    
    return errors;
}

// Inicializar auto-hide de alertas
function initializeAutoHideAlerts() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.classList.add('auto-hide');
            setTimeout(() => {
                alert.remove();
            }, 500);
        }, 5000);
    });
}

// Funções utilitárias de autenticação
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function showAuthError(elementId, message) {
    const errorElement = document.getElementById(elementId);
    if (errorElement) {
        errorElement.textContent = message;
        errorElement.style.display = 'block';
        errorElement.style.color = '#dc3545';
        errorElement.style.fontSize = '12px';
        errorElement.style.marginTop = '5px';
    }
}

function clearAuthErrors() {
    const errorElements = document.querySelectorAll('[id$="Error"]');
    errorElements.forEach(element => {
        element.textContent = '';
        element.style.display = 'none';
    });
    
    // Limpar também spans de erro genéricos
    const errorSpans = document.querySelectorAll('.error-message');
    errorSpans.forEach(span => {
        span.textContent = '';
        span.style.display = 'none';
    });
}

// Função para copiar texto (usado no forgot-password)
function copyToClipboard(text) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(() => {
            showAuthSuccess('Link copiado para a área de transferência!');
        });
    } else {
        // Fallback para navegadores mais antigos
        const textArea = document.createElement('textarea');
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        try {
            document.execCommand('copy');
            showAuthSuccess('Link copiado para a área de transferência!');
        } catch (err) {
            console.error('Erro ao copiar: ', err);
        }
        document.body.removeChild(textArea);
    }
}

function showAuthSuccess(message) {
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
    
    // Adicionar animação se não existir
    if (!document.querySelector('#auth-animations')) {
        const style = document.createElement('style');
        style.id = 'auth-animations';
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
    }
} 