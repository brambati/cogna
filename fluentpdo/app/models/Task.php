<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/security.php';

use Lichtner\FluentPDO\FluentPDO;

/**
 * Model Task - FluentPDO
 */
class Task {
    private $pdo;
    private $fluent;
    
    public function __construct() {
        $config = require __DIR__ . '/../config/database.php';
        
        try {
            $this->pdo = new PDO(
                $config['dsn'],
                $config['username'],
                $config['password'],
                $config['options']
            );
            $this->fluent = new Envms\FluentPDO\Query($this->pdo);
        } catch (Exception $e) {
            throw new Exception("Erro de conexão: " . $e->getMessage());
        }
    }
    
    /**
     * Criar nova tarefa
     */
    public function create(array $data, int $userId): array {
        try {
            // Validações
            $errors = $this->validateTaskData($data);
            if (!empty($errors)) {
                return ['success' => false, 'errors' => $errors];
            }
            
            // Inserir tarefa
            $taskData = [
                'user_id' => $userId,
                'title' => sanitizeInput($data['title']),
                'description' => sanitizeInput($data['description'] ?? ''),
                'category_id' => $data['category_id'] ?? null,
                'status' => $data['status'] ?? 'pending',
                'priority' => $data['priority'] ?? 'medium',
                'due_date' => !empty($data['due_date']) ? date('Y-m-d H:i:s', strtotime($data['due_date'])) : null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $result = $this->fluent->insertInto('tasks')->values($taskData)->execute();
            
            if ($result) {
                $taskId = $this->pdo->lastInsertId();
                $task = $this->findById($taskId, $userId);
                return ['success' => true, 'task' => $task];
            }
            
            return ['success' => false, 'errors' => ['Erro ao criar tarefa']];
            
        } catch (Exception $e) {
            error_log("Erro ao criar tarefa: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Erro interno do servidor']];
        }
    }
    
    /**
     * Listar tarefas do usuário
     */
    public function getByUser(int $userId, array $filters = []): array {
        try {
            $query = $this->fluent
                ->from('tasks')
                ->leftJoin('task_categories ON tasks.category_id = task_categories.id')
                ->select('
                    tasks.id,
                    tasks.title,
                    tasks.description,
                    tasks.status,
                    tasks.priority,
                    tasks.due_date,
                    tasks.created_at,
                    tasks.updated_at,
                    tasks.category_id,
                    task_categories.name AS category_name,
                    task_categories.color AS category_color
                ')
                ->where('tasks.user_id', $userId);
            
            // Aplicar filtros
            if (!empty($filters['status'])) {
                $query->where('tasks.status', $filters['status']);
            }
            
            if (!empty($filters['priority'])) {
                $query->where('tasks.priority', $filters['priority']);
            }
            
            if (!empty($filters['search'])) {
                $search = '%' . $filters['search'] . '%';
                $query->where('(tasks.title LIKE ? OR tasks.description LIKE ?)', $search, $search);
            }
            
            if (!empty($filters['due_date_from'])) {
                $query->where('tasks.due_date >= ?', $filters['due_date_from']);
            }
            
            if (!empty($filters['due_date_to'])) {
                $query->where('tasks.due_date <= ?', $filters['due_date_to']);
            }
            
            $tasks = $query->orderBy('tasks.created_at DESC')->fetchAll();
            
            return ['success' => true, 'tasks' => $tasks];
            
        } catch (Exception $e) {
            error_log("Erro ao listar tarefas: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Erro interno do servidor']];
        }
    }
    
    /**
     * Buscar tarefa por ID
     */
    public function findById(int $taskId, int $userId): ?array {
        try {
            $task = $this->fluent->from('tasks')
                ->where('id', $taskId)
                ->where('user_id', $userId)
                ->fetch();
                
            return $task ?: null;
            
        } catch (Exception $e) {
            error_log("Erro ao buscar tarefa: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Atualizar tarefa
     */
    public function update(int $taskId, array $data, int $userId): array {
        try {
            // Verificar se a tarefa existe e pertence ao usuário
            $existingTask = $this->findById($taskId, $userId);
            if (!$existingTask) {
                return ['success' => false, 'errors' => ['Tarefa não encontrada']];
            }
            
            // Validações
            $errors = $this->validateTaskData($data);
            if (!empty($errors)) {
                return ['success' => false, 'errors' => $errors];
            }
            
            // Preparar dados para atualização
            $updateData = [
                'title' => sanitizeInput($data['title']),
                'description' => sanitizeInput($data['description'] ?? ''),
                'status' => $data['status'] ?? $existingTask['status'],
                'priority' => $data['priority'] ?? $existingTask['priority'],
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            if (!empty($data['due_date'])) {
                $updateData['due_date'] = date('Y-m-d H:i:s', strtotime($data['due_date']));
            }
            
            // Atualizar tarefa
            $result = $this->fluent->update('tasks')
                ->set($updateData)
                ->where('id', $taskId)
                ->where('user_id', $userId)
                ->execute();
            
            if ($result) {
                $task = $this->findById($taskId, $userId);
                return ['success' => true, 'task' => $task];
            }
            
            return ['success' => false, 'errors' => ['Erro ao atualizar tarefa']];
            
        } catch (Exception $e) {
            error_log("Erro ao atualizar tarefa: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Erro interno do servidor']];
        }
    }
    
    /**
     * Deletar tarefa
     */
    public function delete(int $taskId, int $userId): array {
        try {
            // Verificar se a tarefa existe e pertence ao usuário
            $existingTask = $this->findById($taskId, $userId);
            if (!$existingTask) {
                return ['success' => false, 'errors' => ['Tarefa não encontrada']];
            }
            
            $result = $this->fluent->deleteFrom('tasks')
                ->where('id', $taskId)
                ->where('user_id', $userId)
                ->execute();
            
            if ($result) {
                return ['success' => true, 'message' => 'Tarefa deletada com sucesso'];
            }
            
            return ['success' => false, 'errors' => ['Erro ao deletar tarefa']];
            
        } catch (Exception $e) {
            error_log("Erro ao deletar tarefa: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Erro interno do servidor']];
        }
    }
    
    /**
     * Obter estatísticas das tarefas do usuário
     */
    public function getStats(int $userId): array {
        try {
            $stats = [
                'total' => 0,
                'pending' => 0,
                'in_progress' => 0,
                'completed' => 0,
                'cancelled' => 0,
                'overdue' => 0
            ];
            
            // Total de tarefas
            $total = $this->fluent->from('tasks')->where('user_id', $userId)->count();
            $stats['total'] = $total;
            
            // Por status
            $statusCounts = $this->pdo->prepare("
                SELECT status, COUNT(*) as count 
                FROM tasks 
                WHERE user_id = ? 
                GROUP BY status
            ");
            $statusCounts->execute([$userId]);
            
            while ($row = $statusCounts->fetch(PDO::FETCH_ASSOC)) {
                $stats[$row['status']] = $row['count'];
            }
            
            // Tarefas em atraso
            $overdueStmt = $this->pdo->prepare("
                SELECT COUNT(*) as count 
                FROM tasks 
                WHERE user_id = ? 
                AND due_date < NOW() 
                AND status NOT IN ('completed', 'cancelled')
            ");
            $overdueStmt->execute([$userId]);
            $overdue = $overdueStmt->fetch(PDO::FETCH_ASSOC);
            $stats['overdue'] = $overdue['count'];
            
            return ['success' => true, 'stats' => $stats];
            
        } catch (Exception $e) {
            error_log("Erro ao obter estatísticas: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Erro interno do servidor']];
        }
    }
    
    /**
     * Validar dados da tarefa
     */
    private function validateTaskData(array $data): array {
        $errors = [];
        
        if (empty($data['title'])) {
            $errors[] = 'Título é obrigatório';
        } elseif (strlen($data['title']) > 255) {
            $errors[] = 'Título não pode ter mais de 255 caracteres';
        }
        
        if (!empty($data['status']) && !in_array($data['status'], ['pending', 'in_progress', 'completed', 'cancelled'])) {
            $errors[] = 'Status inválido';
        }
        
        if (!empty($data['priority']) && !in_array($data['priority'], ['low', 'medium', 'high', 'urgent'])) {
            $errors[] = 'Prioridade inválida';
        }
        
        if (!empty($data['due_date']) && !strtotime($data['due_date'])) {
            $errors[] = 'Data de vencimento inválida';
        }
        
        return $errors;
    }
}