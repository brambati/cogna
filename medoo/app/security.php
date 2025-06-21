<?php

/**
 * Funções de Segurança
 */

// Iniciar sessão se ainda não foi iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
 * Gera token aleatório
 */
function generateToken(): string {
    return bin2hex(random_bytes(32));
}

/**
 * Obtém IP do cliente
 */
function getClientIP(): string {
    $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
    
    foreach ($ipKeys as $key) {
        if (!empty($_SERVER[$key])) {
            $ips = explode(',', $_SERVER[$key]);
            return trim($ips[0]);
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

/**
 * Rate limiting simples
 */
function checkRateLimit(string $key, int $limit, int $window): bool {
    // Implementação simples usando arquivos
    $dir = sys_get_temp_dir() . '/rate_limit';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    
    $file = $dir . '/' . md5($key);
    $now = time();
    
    $attempts = [];
    if (file_exists($file)) {
        $attempts = json_decode(file_get_contents($file), true) ?: [];
    }
    
    // Remover tentativas antigas
    $attempts = array_filter($attempts, function($time) use ($now, $window) {
        return ($now - $time) < $window;
    });
    
    // Verificar limite
    if (count($attempts) >= $limit) {
        return false;
    }
    
    // Adicionar tentativa atual
    $attempts[] = $now;
    file_put_contents($file, json_encode($attempts));
    
    return true;
}

/**
 * Chave secreta para JWT (mude em produção)
 */
define('JWT_SECRET', 'sua_chave_secreta_aqui_mude_em_producao_123456789');

/**
 * Codificar Base64 URL-safe
 */
function base64UrlEncode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

/**
 * Decodificar Base64 URL-safe
 */
function base64UrlDecode($data) {
    return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
}

/**
 * Gerar JWT Token
 */
function generateJWT($payload) {
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $payload = json_encode($payload);
    
    $base64Header = base64UrlEncode($header);
    $base64Payload = base64UrlEncode($payload);
    
    $signature = hash_hmac('sha256', $base64Header . '.' . $base64Payload, JWT_SECRET, true);
    $base64Signature = base64UrlEncode($signature);
    
    return $base64Header . '.' . $base64Payload . '.' . $base64Signature;
}

/**
 * Verificar JWT Token
 */
function verifyJWT($token) {
    $parts = explode('.', $token);
    
    if (count($parts) !== 3) {
        return false;
    }
    
    [$base64Header, $base64Payload, $base64Signature] = $parts;
    
    // Verificar assinatura
    $signature = base64UrlDecode($base64Signature);
    $expectedSignature = hash_hmac('sha256', $base64Header . '.' . $base64Payload, JWT_SECRET, true);
    
    if (!hash_equals($signature, $expectedSignature)) {
        return false;
    }
    
    // Decodificar payload
    $payload = json_decode(base64UrlDecode($base64Payload), true);
    
    if (!$payload) {
        return false;
    }
    
    // Verificar expiração
    if (isset($payload['exp']) && $payload['exp'] < time()) {
        return false;
    }
    
    return $payload;
}

/**
 * Obter usuário do token JWT
 */
function getUserFromToken() {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    
    if (empty($authHeader)) {
        return null;
    }
    
    $token = str_replace('Bearer ', '', $authHeader);
    $payload = verifyJWT($token);
    
    return $payload ? $payload : null;
}

/**
 * Middleware de autenticação
 */
function requireAuth() {
    $user = getUserFromToken();
    
    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'Token não fornecido ou inválido']);
        exit;
    }
    
    return $user;
}