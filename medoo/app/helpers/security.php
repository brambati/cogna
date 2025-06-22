<?php
/**
 * Security Helper Functions
 * Funções auxiliares de segurança
 */

class SecurityHelper {
    
    /**
     * Sanitizar entrada de dados
     */
    public static function sanitizeInput($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitizeInput'], $data);
        }
        
        return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Validar email
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Validar força da senha
     */
    public static function validatePasswordStrength($password) {
        $errors = [];
        
        if (strlen($password) < 6) {
            $errors[] = 'Senha deve ter pelo menos 6 caracteres';
        }
        
        if (strlen($password) > 128) {
            $errors[] = 'Senha não pode ter mais de 128 caracteres';
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Senha deve conter pelo menos uma letra minúscula';
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Senha deve conter pelo menos uma letra maiúscula';
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Senha deve conter pelo menos um número';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'strength' => self::calculatePasswordStrength($password)
        ];
    }
    
    /**
     * Calcular força da senha (0-100)
     */
    private static function calculatePasswordStrength($password) {
        $strength = 0;
        $length = strlen($password);
        
        // Pontuação por comprimento
        if ($length >= 6) $strength += 20;
        if ($length >= 8) $strength += 10;
        if ($length >= 12) $strength += 10;
        
        // Pontuação por caracteres
        if (preg_match('/[a-z]/', $password)) $strength += 15;
        if (preg_match('/[A-Z]/', $password)) $strength += 15;
        if (preg_match('/[0-9]/', $password)) $strength += 15;
        if (preg_match('/[^a-zA-Z0-9]/', $password)) $strength += 15;
        
        return min(100, $strength);
    }
    
    /**
     * Gerar token seguro
     */
    public static function generateSecureToken($length = 32) {
        try {
            return bin2hex(random_bytes($length));
        } catch (Exception $e) {
            // Fallback para sistemas sem random_bytes
            return md5(uniqid(rand(), true));
        }
    }
    
    /**
     * Verificar se o token é válido e não expirou
     */
    public static function isValidToken($token, $created_at, $expiry_hours = 24) {
        if (empty($token) || empty($created_at)) {
            return false;
        }
        
        $created_time = strtotime($created_at);
        $expiry_time = $created_time + ($expiry_hours * 3600);
        
        return time() <= $expiry_time;
    }
    
    /**
     * Rate limiting simples baseado em IP
     */
    public static function checkRateLimit($action, $max_attempts = 5, $window_minutes = 15) {
        $ip = self::getClientIP();
        $key = $action . '_' . md5($ip);
        $file = sys_get_temp_dir() . '/rate_limit_' . $key;
        
        $attempts = [];
        if (file_exists($file)) {
            $attempts = json_decode(file_get_contents($file), true) ?: [];
        }
        
        // Limpar tentativas antigas
        $cutoff = time() - ($window_minutes * 60);
        $attempts = array_filter($attempts, function($timestamp) use ($cutoff) {
            return $timestamp > $cutoff;
        });
        
        // Verificar se excedeu o limite
        if (count($attempts) >= $max_attempts) {
            return [
                'allowed' => false,
                'remaining' => 0,
                'reset_time' => max($attempts) + ($window_minutes * 60)
            ];
        }
        
        // Registrar tentativa atual
        $attempts[] = time();
        file_put_contents($file, json_encode($attempts));
        
        return [
            'allowed' => true,
            'remaining' => $max_attempts - count($attempts),
            'reset_time' => null
        ];
    }
    
    /**
     * Obter IP real do cliente
     */
    public static function getClientIP() {
        $ip_keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                $ip = trim($ips[0]);
                
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Gerar hash de senha
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
    
    /**
     * Verificar senha
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Validar CSRF token (básico)
     */
    public static function generateCSRFToken() {
        if (!session_id()) {
            session_start();
        }
        
        $token = self::generateSecureToken(16);
        $_SESSION['csrf_token'] = $token;
        $_SESSION['csrf_time'] = time();
        
        return $token;
    }
    
    /**
     * Verificar CSRF token
     */
    public static function verifyCSRFToken($token, $max_age_minutes = 30) {
        if (!session_id()) {
            session_start();
        }
        
        if (empty($_SESSION['csrf_token']) || empty($_SESSION['csrf_time'])) {
            return false;
        }
        
        if (time() - $_SESSION['csrf_time'] > ($max_age_minutes * 60)) {
            unset($_SESSION['csrf_token'], $_SESSION['csrf_time']);
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Limpar dados antigos de rate limiting
     */
    public static function cleanupRateLimitFiles() {
        $temp_dir = sys_get_temp_dir();
        $files = glob($temp_dir . '/rate_limit_*');
        $cutoff = time() - (24 * 3600); // 24 horas
        
        foreach ($files as $file) {
            if (filemtime($file) < $cutoff) {
                unlink($file);
            }
        }
    }
    
    /**
     * Validar dados de entrada do usuário
     */
    public static function validateUserInput($data, $rules) {
        $errors = [];
        
        foreach ($rules as $field => $rule_set) {
            $value = $data[$field] ?? null;
            $rules_array = explode('|', $rule_set);
            
            foreach ($rules_array as $rule) {
                $rule_parts = explode(':', $rule);
                $rule_name = $rule_parts[0];
                $rule_param = $rule_parts[1] ?? null;
                
                switch ($rule_name) {
                    case 'required':
                        if (empty($value)) {
                            $errors[$field][] = ucfirst($field) . ' é obrigatório';
                        }
                        break;
                        
                    case 'email':
                        if (!empty($value) && !self::validateEmail($value)) {
                            $errors[$field][] = ucfirst($field) . ' deve ser um email válido';
                        }
                        break;
                        
                    case 'min':
                        if (!empty($value) && strlen($value) < (int)$rule_param) {
                            $errors[$field][] = ucfirst($field) . ' deve ter pelo menos ' . $rule_param . ' caracteres';
                        }
                        break;
                        
                    case 'max':
                        if (!empty($value) && strlen($value) > (int)$rule_param) {
                            $errors[$field][] = ucfirst($field) . ' não pode ter mais de ' . $rule_param . ' caracteres';
                        }
                        break;
                }
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Log de segurança
     */
    public static function logSecurityEvent($event, $details = []) {
        $log_entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'ip' => self::getClientIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'event' => $event,
            'details' => $details,
            'session_id' => session_id()
        ];
        
        $log_file = sys_get_temp_dir() . '/security_log.txt';
        file_put_contents($log_file, json_encode($log_entry) . "\n", FILE_APPEND | LOCK_EX);
    }
}

// Executar limpeza de arquivos antigos ocasionalmente
if (rand(1, 100) === 1) {
    SecurityHelper::cleanupRateLimitFiles();
}
?> 