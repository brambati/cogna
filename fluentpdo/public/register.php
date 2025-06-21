<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

/**
 * Página de Registro - FluentPDO
 */

// Se já está logado, redireciona para dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Carregar autoload e configuração
        require_once '../vendor/autoload.php';
        $config = require '../app/config/database.php';
        $pdo = new PDO($config['dsn'], $config['username'], $config['password'], $config['options']);
        $fluent = new Envms\FluentPDO\Query($pdo);
        
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Validações
        if (empty($name) || empty($email) || empty($password)) {
            throw new Exception('Todos os campos são obrigatórios');
        }
        
        if (strlen($password) < 6) {
            throw new Exception('A senha deve ter pelo menos 6 caracteres');
        }
        
        if ($password !== $confirm_password) {
            throw new Exception('As senhas não conferem');
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Email inválido');
        }
        
        // Verificar se email já existe
        $existingEmail = $fluent->from('users')->select('id')->where('email = ?', $email)->fetch();
        if ($existingEmail) {
            throw new Exception('Email já está em uso');
        }
        
        // Separar nome em primeiro nome e sobrenome
        $nameParts = explode(' ', $name, 2);
        $firstName = $nameParts[0];
        $lastName = isset($nameParts[1]) ? $nameParts[1] : '';
        
        // Gerar username único baseado no nome
        $baseUsername = strtolower(str_replace(' ', '', $firstName));
        $username = $baseUsername;
        $counter = 1;
        
        // Verificar se username já existe e gerar um único
        while ($fluent->from('users')->select('id')->where('username = ?', $username)->fetch()) {
            $username = $baseUsername . $counter;
            $counter++;
        }
        
        // Criar usuário
        $result = $fluent->insertInto('users')->values([
            'username' => $username,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ])->execute();
        
        if ($result) {
            $success = 'Conta criada com sucesso! Você pode fazer login agora.';
        } else {
            throw new Exception('Erro ao criar conta. Tente novamente.');
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - Sistema de Tarefas</title>
    <link rel="stylesheet" href="css/auth.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1>Criar Conta</h1>
                <p>Cadastre-se para começar a gerenciar suas tarefas</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                    <br><a href="login.php" style="color: #155724; font-weight: bold;">Clique aqui para fazer login</a>
                </div>
            <?php endif; ?>

            <?php if (!$success): ?>
                <form class="auth-form" method="POST" action="register.php">
                    <div class="form-group">
                        <label for="name">Nome Completo</label>
                        <input type="text" id="name" name="name" required 
                               value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                               placeholder="Digite seu nome completo">
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required 
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                               placeholder="Digite seu email">
                    </div>

                    <div class="form-group">
                        <label for="password">Senha</label>
                        <input type="password" id="password" name="password" required minlength="6"
                               placeholder="Digite sua senha">
                        <div class="password-hint">
                            Mínimo 6 caracteres
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirmar Senha</label>
                        <input type="password" id="confirm_password" name="confirm_password" required minlength="6"
                               placeholder="Confirme sua senha">
                    </div>

                    <div class="form-options">
                        <label class="checkbox">
                            <input type="checkbox" name="terms" id="terms" required>
                            <span class="checkmark"></span>
                            Aceito os termos de uso e política de privacidade
                        </label>
                    </div>

                    <button type="submit" class="btn-primary">Criar Conta</button>
                </form>
            <?php endif; ?>

            <div class="auth-footer">
                <p>Já tem uma conta? <a href="login.php">Fazer login</a></p>
            </div>
        </div>
    </div>

    <script>
        // Validação de confirmação de senha em tempo real
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (password !== confirmPassword) {
                this.setCustomValidity('As senhas não conferem');
            } else {
                this.setCustomValidity('');
            }
        });

        // Auto-hide alerts após 5 segundos
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
        }, 5000);
    </script>

    <style>
        .alert {
            padding: 12px 16px;
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

        .checkbox {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: #555;
            cursor: pointer;
        }

        .checkbox input[type="checkbox"] {
            width: auto;
            margin: 0;
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
    </style>
</body>
</html>