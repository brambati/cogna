<?php

/**
 * Logout - FluentPDO
 */

require_once __DIR__ . '/../app/security.php';

// Iniciar sessão se ainda não foi iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['session_token'])) {
    require_once __DIR__ . '/../app/models/User.php';
    $userModel = new User();
    $userModel->logout($_SESSION['session_token']);
}

// Destruir todas as variáveis de sessão
$_SESSION = array();

// Destruir o cookie de sessão
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destruir a sessão
session_destroy();

// Redirecionar para login
header('Location: /login');
exit;