<?php

/**
 * Funções de Segurança
 */

/**
 * Gera token CSRF
 */
function generateCSRFToken(): string {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Valida token CSRF
 */
function validateCSRFToken(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Sanitiza entrada de dados
 */
function sanitizeInput(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Valida email
 */
function validateEmail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Gera hash de senha seguro
 */
function hashPassword(string $password): string {
    return password_hash($password, PASSWORD_ARGON2ID);
}

/**
 * Verifica senha
 */
function verifyPassword(string $password, string $hash): bool {
    return password_verify($password, $hash);
}

/**
 * Gera token aleatório
 */
function generateToken(int $length = 32): string {
    return bin2hex(random_bytes($length));
}

/**
 * Valida força da senha
 */
function validatePasswordStrength(string $password): array {
    $errors = [];
    
    if (strlen($password) < 8) {
        $errors[] = 'Senha deve ter pelo menos 8 caracteres';
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Senha deve conter pelo menos uma letra maiúscula';
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Senha deve conter pelo menos uma letra minúscula';
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Senha deve conter pelo menos um número';
    }
    
    return $errors;
}

/**
 * Previne ataques de timing
 */
function safeCompare(string $a, string $b): bool {
    return hash_equals($a, $b);
}

/**
 * Rate limiting simples
 */
function checkRateLimit(string $key, int $maxAttempts = 5, int $timeWindow = 300): bool {
    $attempts = $_SESSION['rate_limit'][$key] ?? [];
    $now = time();
    
    // Remove tentativas antigas
    $attempts = array_filter($attempts, function($timestamp) use ($now, $timeWindow) {
        return ($now - $timestamp) < $timeWindow;
    });
    
    if (count($attempts) >= $maxAttempts) {
        return false;
    }
    
    $attempts[] = $now;
    $_SESSION['rate_limit'][$key] = $attempts;
    
    return true;
}

/**
 * Obtém IP do cliente
 */
function getClientIP(): string {
    $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
    
    foreach ($ipKeys as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = trim(explode(',', $_SERVER[$key])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * Headers de segurança
 */
function setSecurityHeaders(): void {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

/**
 * Força HTTPS (desabilitado para desenvolvimento)
 */
function forceHTTPS(): void {
    // Desabilitado para desenvolvimento HTTP
    // Será habilitado quando implementarmos SSL
    return;
}