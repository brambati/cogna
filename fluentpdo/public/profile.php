<?php
session_start();
require_once '../vendor/autoload.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Inicializar conexão com o banco usando FluentPDO
try {
    $config = require '../app/config/database.php';
    $pdo = new PDO(
        $config['dsn'],
        $config['username'],
        $config['password'],
        $config['options']
    );
    $fluent = new Envms\FluentPDO\Query($pdo);
} catch (Exception $e) {
    $error_message = 'Erro de conexão com o banco de dados.';
    $fluent = null;
}

// Buscar dados do usuário
$user = null;
if ($fluent) {
    try {
        $user = $fluent->from('users')
            ->select('id, username, email, first_name, last_name, created_at')
            ->where('id', $user_id)
            ->fetch();
    } catch (Exception $e) {
        $error_message = 'Erro ao carregar dados do usuário.';
    }
}

// Buscar estatísticas do usuário
$stats = [
    'total_tasks' => 0,
    'completed_tasks' => 0,
    'in_progress_tasks' => 0,
    'pending_tasks' => 0,
    'overdue_tasks' => 0,
    'total_categories' => 0
];

if ($fluent) {
    try {
        $stats['total_tasks'] = $fluent->from('tasks')->where('user_id', $user_id)->count();
        $stats['completed_tasks'] = $fluent->from('tasks')->where('user_id', $user_id)->where('status', 'completed')->count();
        $stats['in_progress_tasks'] = $fluent->from('tasks')->where('user_id', $user_id)->where('status', 'in_progress')->count();
        $stats['pending_tasks'] = $fluent->from('tasks')->where('user_id', $user_id)->where('status', 'pending')->count();
        
        // Tarefas atrasadas - usando PDO direto para a query complexa
        $overdue_count = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE user_id = ? AND due_date < NOW() AND status NOT IN ('completed', 'cancelled')");
        $overdue_count->execute([$user_id]);
        $stats['overdue_tasks'] = $overdue_count->fetchColumn();
        
        $stats['total_categories'] = $fluent->from('task_categories')
            ->where('user_id', $user_id)
            ->where('is_active', 1)
            ->count();
    } catch (Exception $e) {
        // Manter valores padrão em caso de erro
    }
}

// Processar formulários
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $fluent && $user) {
    $action = $_POST['action'] ?? 'update_profile';
    
    if ($action === 'update_profile') {
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        
        // Validações
        if (empty($first_name)) {
            $error_message = 'Nome é obrigatório.';
        } elseif (empty($last_name)) {
            $error_message = 'Sobrenome é obrigatório.';
        } elseif (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = 'Email válido é obrigatório.';
        } else {
            try {
                // Verificar se email já está em uso por outro usuário
                $existing_user = $fluent->from('users')
                    ->select('id')
                    ->where('email', $email)
                    ->where('id != ?', $user_id)
                    ->fetch();
                
                if ($existing_user) {
                    $error_message = 'Este email já está sendo usado por outro usuário.';
                } else {
                    $result = $fluent->update('users')
                        ->set([
                            'first_name' => $first_name,
                            'last_name' => $last_name,
                            'email' => $email,
                            'updated_at' => date('Y-m-d H:i:s')
                        ])
                        ->where('id', $user_id)
                        ->execute();
                    
                    if ($result) {
                        $success_message = 'Perfil atualizado com sucesso!';
                        // Atualizar dados do usuário na sessão
                        $_SESSION['name'] = $first_name . ' ' . $last_name;
                        // Recarregar dados do usuário
                        $user = $fluent->from('users')
                            ->select('id, username, email, first_name, last_name, created_at')
                            ->where('id', $user_id)
                            ->fetch();
                    } else {
                        $error_message = 'Nenhuma alteração foi realizada.';
                    }
                }
            } catch (Exception $e) {
                $error_message = 'Erro ao atualizar perfil. Tente novamente.';
            }
        }
    } elseif ($action === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_new_password'] ?? '';
        
        // Validações
        if (empty($current_password)) {
            $error_message = 'Senha atual é obrigatória.';
        } elseif (empty($new_password)) {
            $error_message = 'Nova senha é obrigatória.';
        } elseif (strlen($new_password) < 6) {
            $error_message = 'Nova senha deve ter pelo menos 6 caracteres.';
        } elseif ($new_password !== $confirm_password) {
            $error_message = 'Nova senha e confirmação não conferem.';
        } else {
            try {
                // Verificar senha atual
                $user_password = $fluent->from('users')
                    ->select('password_hash')
                    ->where('id', $user_id)
                    ->fetch();
                
                if (!password_verify($current_password, $user_password['password_hash'])) {
                    $error_message = 'Senha atual está incorreta.';
                } else {
                    // Atualizar senha
                    $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                    
                    $result = $fluent->update('users')
                        ->set([
                            'password_hash' => $new_password_hash,
                            'updated_at' => date('Y-m-d H:i:s')
                        ])
                        ->where('id', $user_id)
                        ->execute();
                    
                    if ($result) {
                        $success_message = 'Senha alterada com sucesso!';
                    } else {
                        $error_message = 'Erro ao alterar senha.';
                    }
                }
            } catch (Exception $e) {
                $error_message = 'Erro ao alterar senha. Tente novamente.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil - Sistema de Tarefas</title>
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>
    <header class="header">
        <div class="header-content">
            <h1>Sistema de Tarefas</h1>
            <nav class="nav">
                <a href="dashboard.php" class="nav-link">Dashboard</a>
                <a href="categories.php" class="nav-link">Categorias</a>
                <a href="profile.php" class="nav-link active">Perfil</a>
                <a href="logout.php" class="nav-link">Sair</a>
            </nav>
        </div>
    </header>

    <main class="main">
        <div class="container">
            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <div class="page-header">
                <h2>Meu Perfil</h2>
            </div>

            <?php if ($user): ?>
                <div class="profile-container">
                    <div class="profile-section">
                        <h3>Dados Pessoais</h3>
                        <form class="profile-form" method="POST" action="profile.php">
                            <input type="hidden" name="action" value="update_profile">
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="first_name">Nome</label>
                                    <input type="text" id="first_name" name="first_name" 
                                           value="<?php echo htmlspecialchars($_POST['first_name'] ?? $user['first_name']); ?>" 
                                           required maxlength="50">
                                </div>
                                <div class="form-group">
                                    <label for="last_name">Sobrenome</label>
                                    <input type="text" id="last_name" name="last_name" 
                                           value="<?php echo htmlspecialchars($_POST['last_name'] ?? $user['last_name']); ?>" 
                                           required maxlength="50">
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="username">Nome de usuário</label>
                                    <input type="text" id="username" name="username" 
                                           value="<?php echo htmlspecialchars($user['username']); ?>" 
                                           disabled>
                                    <small style="color: #666;">Nome de usuário não pode ser alterado</small>
                                </div>
                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input type="email" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($_POST['email'] ?? $user['email']); ?>" 
                                           required maxlength="100">
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>Membro desde</label>
                                    <input type="text" value="<?php echo date('d/m/Y', strtotime($user['created_at'])); ?>" disabled>
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn-primary">Salvar Alterações</button>
                            </div>
                        </form>
                    </div>

                    <div class="profile-section">
                        <h3>Alterar Senha</h3>
                        <form class="password-form" method="POST" action="profile.php">
                            <input type="hidden" name="action" value="change_password">
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="current_password">Senha Atual</label>
                                    <input type="password" id="current_password" name="current_password" required>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="new_password">Nova Senha</label>
                                    <input type="password" id="new_password" name="new_password" required minlength="6">
                                    <small style="color: #666;">Mínimo 6 caracteres</small>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="confirm_new_password">Confirmar Nova Senha</label>
                                    <input type="password" id="confirm_new_password" name="confirm_new_password" required minlength="6">
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn-primary">Alterar Senha</button>
                            </div>
                        </form>
                    </div>

                    <div class="profile-section">
                        <h3>Estatísticas</h3>
                        <div class="stats-grid">
                            <div class="stat-card">
                                <div class="stat-number"><?php echo $stats['total_tasks']; ?></div>
                                <div class="stat-label">Total de Tarefas</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-number"><?php echo $stats['completed_tasks']; ?></div>
                                <div class="stat-label">Concluídas</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-number"><?php echo $stats['in_progress_tasks']; ?></div>
                                <div class="stat-label">Em Andamento</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-number"><?php echo $stats['pending_tasks']; ?></div>
                                <div class="stat-label">Pendentes</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-number"><?php echo $stats['overdue_tasks']; ?></div>
                                <div class="stat-label">Atrasadas</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-number"><?php echo $stats['total_categories']; ?></div>
                                <div class="stat-label">Categorias</div>
                            </div>
                        </div>
                    </div>

                    <div class="profile-section">
                        <h3>Informações da Conta</h3>
                        <div class="account-info">
                            <div class="info-item">
                                <strong>ID do Usuário:</strong> <?php echo $user['id']; ?>
                            </div>
                            <div class="info-item">
                                <strong>Nome de usuário:</strong> <?php echo htmlspecialchars($user['username']); ?>
                            </div>
                            <div class="info-item">
                                <strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?>
                            </div>
                            <div class="info-item">
                                <strong>Data de cadastro:</strong> <?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-error">
                    <p>Erro ao carregar dados do usuário. Tente fazer login novamente.</p>
                    <a href="logout.php" class="btn-primary">Fazer Login</a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        // Auto-hide alerts
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.remove();
                }, 300);
            });
        }, 5000);

        // Validação de confirmação de senha
        document.getElementById('confirm_new_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (newPassword !== confirmPassword) {
                this.setCustomValidity('As senhas não conferem');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>

    <style>
        .profile-container {
            display: grid;
            grid-template-columns: 1fr;
            gap: 30px;
            margin-top: 20px;
        }

        .profile-section {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .profile-section h3 {
            margin: 0 0 20px 0;
            color: #333;
            font-size: 18px;
            font-weight: 600;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 10px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-row:last-child {
            margin-bottom: 0;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: 600;
            margin-bottom: 5px;
            color: #333;
        }

        .form-group input {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .form-group input:disabled {
            background-color: #f5f5f5;
            color: #666;
        }

        .form-group small {
            margin-top: 4px;
            font-size: 12px;
        }

        .form-actions {
            margin-top: 25px;
            text-align: right;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
        }

        .stat-card {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            padding: 20px;
            text-align: center;
            transition: transform 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .stat-number {
            font-size: 28px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 14px;
            color: #666;
            font-weight: 500;
        }

        .account-info {
            display: grid;
            gap: 15px;
        }

        .info-item {
            padding: 10px;
            background: #f8f9fa;
            border-radius: 4px;
            border-left: 4px solid #007bff;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 4px;
            margin-bottom: 20px;
            transition: opacity 0.3s;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</body>
</html> 