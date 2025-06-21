<?php
session_start();

// Se já está logado, redireciona para dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';
$token_valid = false;
$user_data = null;

// Verificar se token foi fornecido
$token = $_GET['token'] ?? '';

if (empty($token)) {
    $error = 'Token de recuperação não fornecido.';
} else {
    try {
        // Carregar autoload do Composer
        require_once '../vendor/autoload.php';
        
        // Carregar configuração do banco
        $config = require '../app/config/database.php';
        $pdo = new PDO($config['dsn'], $config['username'], $config['password'], $config['options']);
        $fluent = new Envms\FluentPDO\Query($pdo);
        
        // Verificar se token é válido e não expirou
        $user_data = $fluent->from('users')
            ->select('id, email, first_name, last_name, reset_token_expires')
            ->where('reset_token', $token)
            ->where('reset_token_expires > NOW()')
            ->fetch();
        
        if ($user_data) {
            $token_valid = true;
        } else {
            $error = 'Token inválido ou expirado. Solicite um novo link de recuperação.';
        }
        
    } catch (Exception $e) {
        $error = 'Erro ao verificar token. Tente novamente.';
    }
}

// Processar formulário de nova senha
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $token_valid) {
    try {
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Validações
        if (empty($new_password)) {
            throw new Exception('Nova senha é obrigatória.');
        }
        
        if (strlen($new_password) < 6) {
            throw new Exception('Nova senha deve ter pelo menos 6 caracteres.');
        }
        
        if ($new_password !== $confirm_password) {
            throw new Exception('Nova senha e confirmação não conferem.');
        }
        
        // Hash da nova senha
        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Atualizar senha e remover token
        $result = $fluent->update('users')
            ->set([
                'password_hash' => $password_hash,
                'reset_token' => null,
                'reset_token_expires' => null,
                'updated_at' => date('Y-m-d H:i:s')
            ])
            ->where('id', $user_data['id'])
            ->execute();
        
        if ($result) {
            $success = 'Senha alterada com sucesso! Você pode fazer login com sua nova senha.';
            $token_valid = false; // Não mostrar mais o formulário
        } else {
            throw new Exception('Erro ao alterar senha. Tente novamente.');
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir Senha - Sistema de Tarefas</title>
    <link rel="stylesheet" href="css/auth.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1>Redefinir Senha</h1>
                <?php if ($token_valid): ?>
                    <p>Digite sua nova senha abaixo</p>
                <?php elseif ($success): ?>
                    <p>Senha alterada com sucesso!</p> 
                <?php else: ?>
                    <p>Link inválido ou expirado</p>
                <?php endif; ?>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <strong>Erro:</strong> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <strong>Sucesso:</strong> <?php echo htmlspecialchars($success); ?>
                </div>
                
                <div class="success-actions">
                    <a href="login.php" class="btn-primary">Fazer Login</a>
                </div>
            <?php endif; ?>

            <?php if ($token_valid && !$success): ?>
                <?php if ($user_data): ?>
                    <div class="user-info">
                        <p><strong>Redefinindo senha para:</strong></p>
                        <p><?php echo htmlspecialchars($user_data['first_name'] . ' ' . $user_data['last_name']); ?></p>
                        <p><small><?php echo htmlspecialchars($user_data['email']); ?></small></p>
                    </div>
                <?php endif; ?>

                <form class="auth-form" method="POST">
                    <div class="form-group">
                        <label for="new_password">Nova Senha</label>
                        <input type="password" id="new_password" name="new_password" required minlength="6"
                               placeholder="Digite sua nova senha">
                        <small>Mínimo 6 caracteres</small>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirmar Nova Senha</label>
                        <input type="password" id="confirm_password" name="confirm_password" required minlength="6"
                               placeholder="Digite novamente sua nova senha">
                    </div>

                    <div class="password-strength" id="passwordStrength" style="display: none;">
                        <div class="strength-bar">
                            <div class="strength-fill" id="strengthFill"></div>
                        </div>
                        <span class="strength-text" id="strengthText"></span>
                    </div>

                    <button type="submit" class="btn-primary" id="submitBtn">Alterar Senha</button>
                </form>
            <?php elseif (!$success): ?>
                <div class="error-actions">
                    <a href="forgot-password.php" class="btn-secondary">Solicitar Novo Link</a>
                    <a href="login.php" class="btn-primary">Voltar ao Login</a>
                </div>
            <?php endif; ?>

            <div class="auth-footer">
                <?php if (!$success): ?>
                    <p><a href="login.php">← Voltar para o login</a></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Validação em tempo real da confirmação de senha
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            const submitBtn = document.getElementById('submitBtn');
            
            if (password && confirmPassword) {
                if (password === confirmPassword) {
                    this.style.borderColor = '#28a745';
                    this.setCustomValidity('');
                    submitBtn.disabled = false;
                } else {
                    this.style.borderColor = '#dc3545';
                    this.setCustomValidity('As senhas não conferem');
                    submitBtn.disabled = true;
                }
            }
        });

        // Indicador de força da senha
        document.getElementById('new_password').addEventListener('input', function() {
            const password = this.value;
            const strengthDiv = document.getElementById('passwordStrength');
            const strengthFill = document.getElementById('strengthFill');
            const strengthText = document.getElementById('strengthText');
            
            if (password.length === 0) {
                strengthDiv.style.display = 'none';
                return;
            }
            
            strengthDiv.style.display = 'block';
            
            let strength = 0;
            let text = '';
            let color = '';
            
            // Critérios de força
            if (password.length >= 6) strength += 1;
            if (password.length >= 8) strength += 1;
            if (/[a-z]/.test(password)) strength += 1;
            if (/[A-Z]/.test(password)) strength += 1;
            if (/[0-9]/.test(password)) strength += 1;
            if (/[^A-Za-z0-9]/.test(password)) strength += 1;
            
            // Determinar nível e cor
            if (strength <= 2) {
                text = 'Fraca';
                color = '#dc3545';
            } else if (strength <= 4) {
                text = 'Média';
                color = '#ffc107';
            } else {
                text = 'Forte';
                color = '#28a745';
            }
            
            // Atualizar visual
            const percentage = (strength / 6) * 100;
            strengthFill.style.width = percentage + '%';
            strengthFill.style.backgroundColor = color;
            strengthText.textContent = text;
            strengthText.style.color = color;
        });

        // Auto-hide alerts
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                alert.style.opacity = '0';
                setTimeout(function() {
                    if (alert.parentNode) {
                        alert.remove();
                    }
                }, 300);
            });
        }, 8000);
    </script>

    <style>
        .auth-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
        }

        .auth-card {
            background: white;
            border-radius: 12px;
            padding: 40px;
            width: 100%;
            max-width: 450px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .auth-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .auth-header h1 {
            margin: 0 0 10px 0;
            color: #333;
            font-size: 28px;
        }

        .auth-header p {
            margin: 0;
            color: #666;
            font-size: 16px;
        }

        .user-info {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 25px;
            text-align: center;
        }

        .user-info p {
            margin: 5px 0;
        }

        .user-info small {
            color: #666;
        }

        .alert {
            padding: 15px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
            transition: opacity 0.3s ease;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: #333;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
        }

        .form-group small {
            display: block;
            margin-top: 4px;
            color: #666;
            font-size: 12px;
        }

        .password-strength {
            margin: 15px 0;
        }

        .strength-bar {
            width: 100%;
            height: 6px;
            background: #e9ecef;
            border-radius: 3px;
            overflow: hidden;
            margin-bottom: 5px;
        }

        .strength-fill {
            height: 100%;
            width: 0%;
            transition: width 0.3s, background-color 0.3s;
        }

        .strength-text {
            font-size: 12px;
            font-weight: 600;
        }

        .btn-primary {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .btn-primary:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .btn-secondary {
            display: inline-block;
            padding: 10px 20px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin-right: 10px;
            transition: transform 0.2s;
        }

        .btn-secondary:hover {
            transform: translateY(-1px);
            background: #5a6268;
        }

        .success-actions, .error-actions {
            text-align: center;
            margin: 20px 0;
        }

        .error-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        .auth-footer {
            text-align: center;
            margin-top: 20px;
        }

        .auth-footer a {
            color: #007bff;
            text-decoration: none;
            font-weight: 500;
        }

        .auth-footer a:hover {
            text-decoration: underline;
        }
    </style>
</body>
</html>
