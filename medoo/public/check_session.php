<?php
session_start();

echo "<h2>Verificação de Sessão</h2>";

if (isset($_SESSION['user_id'])) {
    echo "<p style='color: green;'>✓ Usuário logado!</p>";
    echo "<p>ID: " . $_SESSION['user_id'] . "</p>";
    echo "<p>Nome: " . $_SESSION['name'] . "</p>";
    echo "<p>Email: " . $_SESSION['email'] . "</p>";
    echo "<p><a href='dashboard.php'>Ir para Dashboard</a></p>";
} else {
    echo "<p style='color: red;'>✗ Usuário não está logado</p>";
    echo "<p><a href='login.php'>Fazer Login</a></p>";
}

echo "<p><a href='logout.php'>Fazer Logout</a></p>";
?>
