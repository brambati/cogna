<?php
session_start();
header('Content-Type: application/json');

// Check login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

// Check method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Only POST allowed']);
    exit;
}

// Get task ID and status
$task_id = $_GET['id'] ?? null;
$input = json_decode(file_get_contents('php://input'), true);
$status = $input['status'] ?? null;

if (!$task_id || !$status) {
    echo json_encode(['error' => 'Missing task ID or status']);
    exit;
}

try {
    // Usar as mesmas credenciais que funcionaram no Medoo
    $host = 'mysql';  // Docker service name
    $dbname = 'taskmanager';
    $username = 'taskuser';
    $password = 'taskpass';
    $port = 3306;
    
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Simple update query
    $stmt = $pdo->prepare("UPDATE tasks SET status = ?, updated_at = NOW() WHERE id = ? AND user_id = ?");
    $result = $stmt->execute([$status, $task_id, $_SESSION['user_id']]);
    
    if ($result && $stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'FluentPDO Task updated successfully']);
    } else {
        echo json_encode(['error' => 'No task updated - check task ID and ownership']);
    }
    
} catch (Exception $e) {
    echo json_encode(['error' => 'FluentPDO Database error: ' . $e->getMessage()]);
}
?>
