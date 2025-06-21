<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/security.php';

use Lichtner\FluentPDO\FluentPDO;

/**
 * Model Task - FluentPDO
 */
class Task {
    private $fpdo;
    
    public function __construct() {
        $config = require __DIR__ . '/../config/database.php';
        
        try {
            $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
            $pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
            $this->fpdo = new FluentPDO($pdo);
        } catch (PDOException $e) {
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
                'status' => $data['status'] ?? 'pending',
                'priority' => $data['priority'] ?? 'medium',
                'due_date' => !empty($data['due_date']) ? date('Y-m-d H:i:s', strtotime($data['due_date'])) : null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $taskId = $this->fpdo->insertInto('tasks', $taskData)->execute();
            
            if ($taskId) {
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
            $query = $this->fpdo->from('tasks')
                ->where('user_id = ?', $userId)
                ->orderBy('created_at DESC');
            
            // Aplicar filtros
            if (!empty($filters['status'])) {
                $query->where('status = ?', $filters['status']);
            }
            
            if (!empty($filters['priority'])) {
                $query->where('priority = ?', $filters['priority']);
            }
            
            if (!empty($filters['search'])) {
                $search = '%' . $filters['search'] . '%';
                $query->where('(title LIKE ? OR description LIKE ?)', $search, $search);
            }
            
            if (!empty($filters['due_date_from'])) {
                $query->where('due_date >= ?', $filters['due_date_from']);
            }
            
            if (!empty($filters['due_date_to'])) {
                $query->where('due_date <= ?', $filters['due_date_to']);
            }
            
            $tasks = $query->fetchAll();
            
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
            $task = $this->fpdo->from('tasks')
                ->where('id = ? AND user_id = ?', $taskId, $userId)
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
            $result = $this->fpdo->update('tasks')
                ->set($updateData)
                ->where('id = ? AND user_id = ?', $taskId, $userId)
                ->execute();
            
            if ($result !== false) {
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
            
            $result = $this->fpdo->deleteFrom('tasks')
                ->where('id = ? AND user_id = ?', $taskId, $userId)
                ->execute();
            
            if ($result !== false) {
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
            $total = $this->fpdo->from('tasks')
                ->where('user_id = ?', $userId)
                ->select('COUNT(*) as count')
                ->fetch();
            $stats['total'] = $total['count'];
            
            // Por status
            $statusCounts = $this->fpdo->from('tasks')
                ->where('user_id = ?', $userId)
                ->select('status, COUNT(*) as count')
                ->groupBy('status')
                ->fetchAll();
            
            foreach ($statusCounts as $row) {
                $stats[$row['status']] = $row['count'];
            }
            
            // Tarefas em atraso
            $overdue = $this->fpdo->from('tasks')
                ->where('user_id = ? AND due_date < NOW() AND status NOT IN (?, ?)', 
                        $userId, 'completed', 'cancelled')
                ->select('COUNT(*) as count')
                ->fetch();
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