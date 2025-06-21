<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/security.php';

use Lichtner\FluentPDO\FluentPDO;

/**
 * Model User - FluentPDO
 */
class User {
    private $fpdo;
    
    public function __construct() {
        $config = require __DIR__ . '/../config/database.php';
        
        try {
            $pdo = new PDO($config['dsn'], $config['username'], $config['password'], $config['options']);
            $this->fpdo = new FluentPDO($pdo);
        } catch (PDOException $e) {
            throw new Exception("Erro de conexão: " . $e->getMessage());
        }
    }
    
    /**
     * Criar novo usuário
     */
    public function create(array $data): array {
        try {
            // Validações
            $errors = $this->validateUserData($data);
            if (!empty($errors)) {
                return ['success' => false, 'errors' => $errors];
            }
            
            // Verificar se email já existe
            $existingUser = $this->fpdo->from('users')
                ->where('email = ? OR username = ?', $data['email'], $data['username'])
                ->fetch();
                
            if ($existingUser) {
                return ['success' => false, 'errors' => ['Email ou username já está em uso']];
            }
            
            // Hash da senha
            $passwordHash = hashPassword($data['password']);
            
            // Inserir usuário
            $userId = $this->fpdo->insertInto('users', [
                'username' => sanitizeInput($data['username']),
                'email' => sanitizeInput($data['email']),
                'password_hash' => $passwordHash,
                'first_name' => sanitizeInput($data['first_name']),
                'last_name' => sanitizeInput($data['last_name']),
                'created_at' => date('Y-m-d H:i:s')
            ])->execute();
            
            if ($userId) {
                return ['success' => true, 'user_id' => $userId];
            }
            
            return ['success' => false, 'errors' => ['Erro ao criar usuário']];
            
        } catch (Exception $e) {
            error_log("Erro ao criar usuário: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Erro interno do servidor']];
        }
    }
    
    /**
     * Autenticar usuário
     */
    public function authenticate(string $email, string $password): array {
        try {
            // Rate limiting
            $rateKey = 'login_' . getClientIP();
            if (!checkRateLimit($rateKey, 5, 300)) {
                return ['success' => false, 'errors' => ['Muitas tentativas de login. Tente novamente em 5 minutos.']];
            }
            
            // Buscar usuário
            $user = $this->fpdo->from('users')
                ->where('email = ? AND is_active = 1', $email)
                ->fetch();
                
            if (!$user || !verifyPassword($password, $user['password_hash'])) {
                // Log da tentativa falha
                $this->logLoginAttempt($email, false);
                return ['success' => false, 'errors' => ['Email ou senha inválidos']];
            }
            
            // Log da tentativa bem-sucedida
            $this->logLoginAttempt($email, true);
            
            // Criar sessão
            $sessionToken = generateToken();
            $apiToken = generateToken();
            
            $sessionId = $this->fpdo->insertInto('user_sessions', [
                'user_id' => $user['id'],
                'session_token' => $sessionToken,
                'api_token' => $apiToken,
                'ip_address' => getClientIP(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'expires_at' => date('Y-m-d H:i:s', strtotime('+24 hours'))
            ])->execute();
            
            if ($sessionId) {
                // Iniciar sessão PHP
                session_start();
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['session_token'] = $sessionToken;
                $_SESSION['api_token'] = $apiToken;
                
                return [
                    'success' => true,
                    'user' => [
                        'id' => $user['id'],
                        'username' => $user['username'],
                        'email' => $user['email'],
                        'first_name' => $user['first_name'],
                        'last_name' => $user['last_name']
                    ],
                    'tokens' => [
                        'session_token' => $sessionToken,
                        'api_token' => $apiToken
                    ]
                ];
            }
            
            return ['success' => false, 'errors' => ['Erro ao criar sessão']];
            
        } catch (Exception $e) {
            error_log("Erro na autenticação: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Erro interno do servidor']];
        }
    }
    
    /**
     * Buscar usuário por ID
     */
    public function findById(int $userId): ?array {
        try {
            $user = $this->fpdo->from('users')
                ->select('id, username, email, first_name, last_name, is_active, email_verified, created_at')
                ->where('id = ? AND is_active = 1', $userId)
                ->fetch();
                
            return $user ?: null;
            
        } catch (Exception $e) {
            error_log("Erro ao buscar usuário: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Verificar sessão
     */
    public function verifySession(string $sessionToken): ?array {
        try {
            $session = $this->fpdo->from('user_sessions us')
                ->leftJoin('users u ON us.user_id = u.id')
                ->select('us.*, u.username, u.email, u.first_name, u.last_name')
                ->where('us.session_token = ? AND us.is_active = 1 AND us.expires_at > NOW() AND u.is_active = 1', $sessionToken)
                ->fetch();
                
            if ($session) {
                // Atualizar última atividade
                $this->fpdo->update('user_sessions')
                    ->set(['last_activity' => date('Y-m-d H:i:s')])
                    ->where('id = ?', $session['id'])
                    ->execute();
                    
                return $session;
            }
            
            return null;
            
        } catch (Exception $e) {
            error_log("Erro ao verificar sessão: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Logout
     */
    public function logout(string $sessionToken): bool {
        try {
            $result = $this->fpdo->update('user_sessions')
                ->set(['is_active' => 0])
                ->where('session_token = ?', $sessionToken)
                ->execute();
                
            // Destruir sessão PHP
            session_start();
            session_destroy();
            
            return $result !== false;
            
        } catch (Exception $e) {
            error_log("Erro no logout: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Validar dados do usuário
     */
    private function validateUserData(array $data): array {
        $errors = [];
        
        if (empty($data['username']) || strlen($data['username']) < 3) {
            $errors[] = 'Username deve ter pelo menos 3 caracteres';
        }
        
        if (!validateEmail($data['email'])) {
            $errors[] = 'Email inválido';
        }
        
        if (empty($data['first_name'])) {
            $errors[] = 'Nome é obrigatório';
        }
        
        if (empty($data['last_name'])) {
            $errors[] = 'Sobrenome é obrigatório';
        }
        
        if (!empty($data['password'])) {
            $passwordErrors = validatePasswordStrength($data['password']);
            $errors = array_merge($errors, $passwordErrors);
        }
        
        return $errors;
    }
    
    /**
     * Log de tentativas de login
     */
    private function logLoginAttempt(string $email, bool $success): void {
        try {
            $this->fpdo->insertInto('login_attempts', [
                'email' => $email,
                'ip_address' => getClientIP(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'success' => $success ? 1 : 0,
                'attempted_at' => date('Y-m-d H:i:s')
            ])->execute();
        } catch (Exception $e) {
            error_log("Erro ao registrar tentativa de login: " . $e->getMessage());
        }
    }
}