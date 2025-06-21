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
$user = null;

// Verificar token na URL
$token = $_GET['token'] ?? '';

if (empty($token)) {
    $error = 'Token de recuperação não fornecido.';
} else {
    try {
        // Carregar autoload e configuração
        require_once '../vendor/autoload.php';
        $config = require '../app/config/database.php';
        $database = new Medoo\Medoo($config);
        
        // Verificar se token é válido e não expirou
        $user = $database->get('users', [
            'id',
            'username',
            'first_name', 
            'last_name',
            'email',
            'reset_token_expires'
        ], [
            'reset_token' => $token,
            'is_active' => 1,
            'reset_token_expires[>=]' => date('Y-m-d H:i:s')
        ]);
        
        if ($user) {
            $token_valid = true;
        } else {
            $error = 'Token inválido ou expirado. Solicite um novo link de recuperação.';
        }
        
    } catch (Exception $e) {
        $error = 'Erro ao verificar token. Tente novamente.';
    }
}

// Processar formulário de nova senha
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $token_valid && $user) {
    try {
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Validações
        if (empty($new_password)) {
            throw new Exception('Nova senha é obrigatória');
        }
        
        if (strlen($new_password) < 6) {
            throw new Exception('Nova senha deve ter pelo menos 6 caracteres');
        }
        
        if ($new_password !== $confirm_password) {
            throw new Exception('As senhas não conferem');
        }
        
        // Atualizar senha e limpar token
        $result = $database->update('users', [
            'password_hash' => password_hash($new_password, PASSWORD_DEFAULT),
            'reset_token' => null,
            'reset_token_expires' => null,
            'updated_at' => date('Y-m-d H:i:s')
        ], [
            'id' => $user['id']
        ]);
        
        if ($result->rowCount() > 0) {
            $success = 'Senha alterada com sucesso! Você pode fazer login agora.';
            $token_valid = false; // Esconder formulário
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
                <p>
                    <?php if ($token_valid): ?>
                        Digite sua nova senha
                    <?php elseif ($success): ?>
                        Senha alterada com sucesso!
                    <?php else: ?>
                        Token inválido
                    <?php endif; ?>
                </p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <?php if ($token_valid && $user): ?>
                <div class="user-info">
                    <p><strong>Redefinindo senha para:</strong></p>
                    <p><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></p>
                    <p><small><?php echo htmlspecialchars($user['email']); ?></small></p>
                </div>

                <form class="auth-form" method="POST" action="reset-password.php?token=<?php echo htmlspecialchars($token); ?>">
                    <div class="form-group">
                        <label for="new_password">Nova Senha</label>
                        <input type="password" id="new_password" name="new_password" required minlength="6"
                               placeholder="Digite sua nova senha">
                        <div class="password-hint">
                            Mínimo 6 caracteres
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirmar Nova Senha</label>
                        <input type="password" id="confirm_password" name="confirm_password" required minlength="6"
                               placeholder="Confirme sua nova senha">
                    </div>

                    <button type="submit" class="btn-primary">Alterar Senha</button>
                </form>
            <?php endif; ?>

            <div class="auth-footer">
                <?php if ($success): ?>
                    <p><a href="login.php" class="btn-link">Fazer Login</a></p>
                <?php else: ?>
                    <p><a href="login.php">← Voltar para o login</a></p>
                    <?php if (!$token_valid): ?>
                        <p><a href="forgot-password.php">Solicitar novo link</a></p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Validação de confirmação de senha em tempo real
        document.getElementById('confirm_password')?.addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (newPassword !== confirmPassword) {
                this.setCustomValidity('As senhas não conferem');
            } else {
                this.setCustomValidity('');
            }
        });

        // Auto-hide alerts após 8 segundos
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

        .user-info {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 20px;
            text-align: center;
        }

        .user-info p {
            margin: 5px 0;
        }

        .user-info small {
            color: #666;
        }

        .password-hint {
            font-size: 12px;
            color: #666;
            margin-top: 4px;
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

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .btn-link {
            display: inline-block;
            padding: 10px 20px;
            background: #007bff;
            color: white !important;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            transition: background 0.3s;
        }

        .btn-link:hover {
            background: #0056b3;
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
