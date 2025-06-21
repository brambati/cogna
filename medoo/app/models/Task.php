<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../security.php';

use Medoo\Medoo;

/**
 * Model Task - Medoo
 */
class Task {
    private $database;
    
    public function __construct() {
        $config = require __DIR__ . '/../config/database.php';
        
        try {
            $this->database = new Medoo($config);
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
            
            $result = $this->database->insert('tasks', $taskData);
            
            if ($result->rowCount() > 0) {
                $taskId = $this->database->id();
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
            $where = ['tasks.user_id' => $userId];
            
            // Aplicar filtros
            if (!empty($filters['status'])) {
                $where['tasks.status'] = $filters['status'];
            }
            
            if (!empty($filters['priority'])) {
                $where['tasks.priority'] = $filters['priority'];
            }
            
            if (!empty($filters['search'])) {
                $search = '%' . $filters['search'] . '%';
                $where['OR'] = [
                    'tasks.title[~]' => $search,
                    'tasks.description[~]' => $search
                ];
            }
            
            if (!empty($filters['due_date_from'])) {
                $where['tasks.due_date[>=]'] = $filters['due_date_from'];
            }
            
            if (!empty($filters['due_date_to'])) {
                $where['tasks.due_date[<=]'] = $filters['due_date_to'];
            }
            
            $tasks = $this->database->select('tasks', [
                '[>]task_categories' => ['category_id' => 'id']
            ], [
                'tasks.id',
                'tasks.title',
                'tasks.description',
                'tasks.status',
                'tasks.priority',
                'tasks.due_date',
                'tasks.created_at',
                'tasks.updated_at',
                'tasks.category_id',
                'task_categories.name(category_name)',
                'task_categories.color(category_color)'
            ], [
                'AND' => $where,
                'ORDER' => ['tasks.created_at' => 'DESC']
            ]);
            
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
            $task = $this->database->get('tasks', '*', [
                'id' => $taskId,
                'user_id' => $userId
            ]);
                
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
            $result = $this->database->update('tasks', $updateData, [
                'id' => $taskId,
                'user_id' => $userId
            ]);
            
            if ($result->rowCount() > 0) {
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
            
            $result = $this->database->delete('tasks', [
                'id' => $taskId,
                'user_id' => $userId
            ]);
            
            if ($result->rowCount() > 0) {
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
            $total = $this->database->count('tasks', ['user_id' => $userId]);
            $stats['total'] = $total;
            
            // Por status
            $statusCounts = $this->database->select('tasks', [
                'status',
                'count' => Medoo::raw('COUNT(*)')
            ], [
                'user_id' => $userId,
                'GROUP' => 'status'
            ]);
            
            foreach ($statusCounts as $row) {
                $stats[$row['status']] = $row['count'];
            }
            
            // Tarefas em atraso
            $overdue = $this->database->count('tasks', [
                'user_id' => $userId,
                'due_date[<]' => date('Y-m-d H:i:s'),
                'status[!]' => ['completed', 'cancelled']
            ]);
            $stats['overdue'] = $overdue;
            
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