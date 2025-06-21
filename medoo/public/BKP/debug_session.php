<?php

/**
 * Debug de sessão
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h3>Debug de Sessão</h3>";

echo "Status da sessão: " . session_status() . "<br>";
echo "Session_status() === PHP_SESSION_NONE: " . (session_status() === PHP_SESSION_NONE ? 'true' : 'false') . "<br>";

if (session_status() === PHP_SESSION_NONE) {
    echo "Iniciando sessão...<br>";
    session_start();
} else {
    echo "Sessão já iniciada<br>";
}

echo "Session ID: " . session_id() . "<br>";
echo "Session name: " . session_name() . "<br>";

echo "<h4>Dados da sessão:</h4>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

require_once __DIR__ . '/../app/security.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h4>POST recebido:</h4>";
    echo "Token enviado: " . ($_POST['csrf_token'] ?? 'nenhum') . "<br>";
    echo "Token na sessão: " . ($_SESSION['csrf_token'] ?? 'nenhum') . "<br>";
    echo "Tokens iguais: " . (($_POST['csrf_token'] ?? '') === ($_SESSION['csrf_token'] ?? '') ? 'SIM' : 'NÃO') . "<br>";
    echo "validateCSRFToken(): " . (validateCSRFToken($_POST['csrf_token'] ?? '') ? 'VÁLIDO' : 'INVÁLIDO') . "<br>";
}

$token = generateCSRFToken();
echo "<h4>Token gerado:</h4>";
echo "Token: $token<br>";

?>

<form method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo $token; ?>">
    <button type="submit">Testar CSRF</button>
</form>

<a href="/login">Voltar ao Login</a>