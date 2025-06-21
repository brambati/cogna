<?php
session_start();

// Se já está logado, redireciona para dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';
$email_sent = false;
$reset_link = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Carregar autoload e configuração
        require_once '../vendor/autoload.php';
        $config = require '../app/config/database.php';
        $database = new Medoo\Medoo($config);
        
        $email = trim($_POST['email'] ?? '');
        
        // Validações
        if (empty($email)) {
            throw new Exception('Email é obrigatório');
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Email inválido');
        }
        
        // Verificar se email existe no sistema
        $user = $database->get('users', [
            'id',
            'username', 
            'first_name',
            'last_name',
            'email'
        ], [
            'email' => $email,
            'is_active' => 1
        ]);
        
        if (!$user) {
            // Por segurança, não informamos se o email existe ou não
            throw new Exception('Se este email estiver cadastrado, você receberá as instruções de recuperação.');
        }
        
        // Gerar token único
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour')); // Token válido por 1 hora
        
        // Salvar token no banco
        $result = $database->update('users', [
            'reset_token' => $token,
            'reset_token_expires' => $expires,
            'updated_at' => date('Y-m-d H:i:s')
        ], [
            'id' => $user['id']
        ]);
        
        if ($result->rowCount() > 0) {
            $email_sent = true;
            $success = 'Instruções de recuperação foram enviadas para seu email.';
            
            // Gerar link de recuperação
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $reset_link = "$protocol://$host/reset-password.php?token=" . $token;
            
            // Log do link para desenvolvimento (remover em produção)
            error_log("Reset link for {$email}: {$reset_link}");
            
        } else {
            throw new Exception('Erro ao processar solicitação. Tente novamente.');
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
    <title>Esqueci minha senha - Sistema de Tarefas</title>
    <link rel="stylesheet" href="css/auth.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1>Recuperar Senha</h1>
                <p><?php echo $email_sent ? 'Link de recuperação gerado!' : 'Digite seu email para receber o link de recuperação'; ?></p>
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
                
                <?php if ($reset_link): ?>
                    <div class="dev-link-container">
                        <h3>🛠️ Modo Desenvolvimento</h3>
                        <p><strong>Use este link para redefinir sua senha:</strong></p>
                        <div class="reset-link-box">
                            <input type="text" id="resetLink" value="<?php echo htmlspecialchars($reset_link); ?>" readonly>
                            <button type="button" onclick="copyLink()" class="btn-copy">📋 Copiar</button>
                        </div>
                        <div class="link-actions">
                            <a href="<?php echo htmlspecialchars($reset_link); ?>" class="btn-primary" target="_blank">
                                🔗 Abrir Link de Recuperação
                            </a>
                        </div>
                        <small>⏰ Este link é válido por 1 hora</small>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <?php if (!$email_sent): ?>
                <form class="auth-form" method="POST" action="forgot-password.php">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                               placeholder="Digite seu email cadastrado">
                    </div>

                    <button type="submit" class="btn-primary">Enviar Link de Recuperação</button>
                </form>
            <?php endif; ?>

            <div class="auth-footer">
                <p><a href="login.php">← Voltar para o login</a></p>
                <?php if ($email_sent): ?>
                    <p><a href="forgot-password.php">Solicitar novo link</a></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function copyLink() {
            const linkInput = document.getElementById('resetLink');
            linkInput.select();
            linkInput.setSelectionRange(0, 99999); // Para mobile
            
            try {
                document.execCommand('copy');
                const button = document.querySelector('.btn-copy');
                const originalText = button.textContent;
                button.textContent = '✅ Copiado!';
                button.style.background = '#28a745';
                
                setTimeout(() => {
                    button.textContent = originalText;
                    button.style.background = '';
                }, 2000);
            } catch (err) {
                alert('Link copiado para a área de transferência!');
            }
        }

        // Auto-hide alerts após 10 segundos (mais tempo para ler)
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
        }, 10000);
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

        .dev-link-container {
            background: #f8f9fa;
            border: 2px solid #007bff;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
        }

        .dev-link-container h3 {
            margin: 0 0 15px 0;
            color: #007bff;
            font-size: 16px;
        }

        .reset-link-box {
            display: flex;
            gap: 10px;
            margin: 15px 0;
            align-items: center;
        }

        .reset-link-box input {
            flex: 1;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 12px;
            background: white;
        }

        .btn-copy {
            padding: 8px 12px;
            background: #6c757d;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            transition: background 0.3s;
        }

        .btn-copy:hover {
            background: #5a6268;
        }

        .link-actions {
            margin: 15px 0;
        }

        .link-actions .btn-primary {
            display: inline-block;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
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

        small {
            color: #666;
            font-size: 12px;
        }
    </style>
</body>
</html>
