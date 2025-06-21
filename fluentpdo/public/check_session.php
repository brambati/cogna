<?php
/**
 * Verificador simples de sessão
 */

session_start();

echo "<h2>Informações da Sessão</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

if (isset($_SESSION['user_id'])) {
    echo "<p style='color: green;'>✓ Usuário logado - ID: " . $_SESSION['user_id'] . "</p>";
} else {
    echo "<p style='color: red;'>✗ Usuário não está logado</p>";
}

echo "<p><a href='login.php'>Login</a> | <a href='dashboard.php'>Dashboard</a> | <a href='logout.php'>Logout</a></p>";
?> 