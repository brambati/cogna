<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../security.php';

use Medoo\Medoo;

/**
 * Model User - Medoo
 */
class User {
    private $database;
    
    public function __construct() {
        $config = require __DIR__ . '/../config/database.php';
        
        try {
            $this->database = new Medoo($config);
        } catch (Exception $e) {
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
            $existingUser = $this->database->get('users', '*', [
                'OR' => [
                    'email' => $data['email'],
                    'username' => $data['username']
                ]
            ]);
                
            if ($existingUser) {
                return ['success' => false, 'errors' => ['Email ou username já está em uso']];
            }
            
            // Hash da senha
            $passwordHash = hashPassword($data['password']);
            
            // Inserir usuário
            $this->database->insert('users', [
                'username' => sanitizeInput($data['username']),
                'email' => sanitizeInput($data['email']),
                'password_hash' => $passwordHash,
                'first_name' => sanitizeInput($data['first_name']),
                'last_name' => sanitizeInput($data['last_name']),
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            $userId = $this->database->id();
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
            $user = $this->database->get('users', '*', [
                'email' => $email,
                'is_active' => 1
            ]);
                
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
            
            $this->database->insert('user_sessions', [
                'user_id' => $user['id'],
                'session_token' => $sessionToken,
                'api_token' => $apiToken,
                'ip_address' => getClientIP(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'expires_at' => date('Y-m-d H:i:s', strtotime('+24 hours'))
            ]);
            
            $sessionId = $this->database->id();
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
     * Buscar usuário por email ou username
     */
    public function findByEmailOrUsername($identifier) {
        return $this->database->get('users', '*', [
            'OR' => [
                'email' => $identifier,
                'username' => $identifier
            ]
        ]);
    }

    /**
     * Buscar usuário por email
     */
    public function findByEmail($email) {
        return $this->database->get('users', '*', ['email' => $email]);
    }

    /**
     * Buscar usuário por ID
     */
    public function findById($id) {
        return $this->database->get('users', '*', ['id' => $id]);
    }

    /**
     * Verificar se email já existe
     */
    public function emailExists($email) {
        $count = $this->database->count('users', ['email' => $email]);
        return $count > 0;
    }    /**
     * Criar usuário (versão simplificada para API)
     */
    public function createUser($data) {
        $result = $this->database->insert('users', $data);
        
        if ($result->rowCount() > 0) {
            return $this->database->id();
        }
        
        return false;
    }

    /**
     * Atualizar último login
     */
    public function updateLastLogin($userId) {
        return $this->database->update('users', [
            'last_login' => date('Y-m-d H:i:s')
        ], ['id' => $userId]);
    }
    
    /**
     * Verificar sessão
     */
    public function verifySession(string $sessionToken): ?array {
        try {
            $session = $this->database->get('user_sessions', [
                '[>]users' => ['user_id' => 'id']
            ], [
                'user_sessions.id',
                'user_sessions.user_id',
                'user_sessions.session_token',
                'user_sessions.api_token',
                'user_sessions.ip_address',
                'user_sessions.expires_at',
                'users.username',
                'users.email',
                'users.first_name',
                'users.last_name'
            ], [
                'user_sessions.session_token' => $sessionToken,
                'user_sessions.is_active' => 1,
                'user_sessions.expires_at[>]' => date('Y-m-d H:i:s'),
                'users.is_active' => 1
            ]);
                
            if ($session) {
                // Atualizar última atividade
                $this->database->update('user_sessions', [
                    'last_activity' => date('Y-m-d H:i:s')
                ], [
                    'id' => $session['id']
                ]);
                    
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
            $this->database->update('user_sessions', [
                'is_active' => 0
            ], [
                'session_token' => $sessionToken
            ]);
                
            // Destruir sessão PHP
            session_start();
            session_destroy();
            
            return true;
            
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
            $this->database->insert('login_attempts', [
                'email' => $email,
                'ip_address' => getClientIP(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'success' => $success ? 1 : 0,
                'attempted_at' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            error_log("Erro ao registrar tentativa de login: " . $e->getMessage());
        }
    }
}