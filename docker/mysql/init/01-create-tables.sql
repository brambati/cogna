-- TaskManager Database Structure

USE taskmanager;

-- Tabela de usuários
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    email_verified BOOLEAN DEFAULT FALSE,
    reset_token VARCHAR(255) NULL,
    reset_token_expires DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_reset_token (reset_token),
    INDEX idx_created_at (created_at)
);

-- Tabela de categorias de tarefas
CREATE TABLE task_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    color VARCHAR(7) DEFAULT '#007bff',
    user_id INT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_name (name),
    UNIQUE KEY unique_user_category (user_id, name)
);

-- Tabela de tarefas
CREATE TABLE tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    user_id INT NOT NULL,
    category_id INT NULL,
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    due_date DATETIME NULL,
    completed_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES task_categories(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_category_id (category_id),
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_due_date (due_date),
    INDEX idx_created_at (created_at),
    INDEX idx_user_status (user_id, status),
    INDEX idx_user_priority (user_id, priority)
);

-- Tabela de sessões de usuários
CREATE TABLE user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_token VARCHAR(255) NOT NULL UNIQUE,
    api_token VARCHAR(255) NULL UNIQUE,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_session_token (session_token),
    INDEX idx_api_token (api_token),
    INDEX idx_expires_at (expires_at),
    INDEX idx_is_active (is_active)
);

-- Tabela de logs de tentativas de login
CREATE TABLE login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    success BOOLEAN DEFAULT FALSE,
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_email (email),
    INDEX idx_ip_address (ip_address),
    INDEX idx_attempted_at (attempted_at),
    INDEX idx_email_ip (email, ip_address)
);

-- Inserir categorias padrão para demonstração
INSERT INTO task_categories (name, description, color, user_id) VALUES
('Geral', 'Tarefas gerais', '#6c757d', 1),
('Trabalho', 'Tarefas relacionadas ao trabalho', '#007bff', 1),
('Pessoal', 'Tarefas pessoais', '#28a745', 1),
('Urgente', 'Tarefas urgentes', '#dc3545', 1);

-- Inserir usuário padrão para demonstração
-- Senha: admin123
INSERT INTO users (username, email, password_hash, first_name, last_name, email_verified) VALUES
('admin', 'admin@taskmanager.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador', 'Sistema', TRUE);

-- Inserir algumas tarefas de exemplo
INSERT INTO tasks (title, description, user_id, category_id, priority, status, due_date) VALUES
('Configurar ambiente de desenvolvimento', 'Configurar Docker, Nginx e certificados SSL', 1, 2, 'high', 'completed', DATE_ADD(NOW(), INTERVAL -1 DAY)),
('Implementar autenticação', 'Criar sistema de login e registro de usuários', 1, 2, 'high', 'completed', DATE_ADD(NOW(), INTERVAL 1 DAY)),
('Criar API REST', 'Implementar endpoints para CRUD de tarefas', 1, 2, 'high', 'in_progress', DATE_ADD(NOW(), INTERVAL 2 DAY)),
('Desenvolver frontend', 'Criar interface com jQuery e CSS responsivo', 1, 2, 'medium', 'pending', DATE_ADD(NOW(), INTERVAL 3 DAY)),
('Testes de segurança', 'Verificar proteções contra XSS, CSRF e SQL Injection', 1, 2, 'high', 'pending', DATE_ADD(NOW(), INTERVAL 4 DAY)),
('Documentação', 'Escrever README detalhado com instruções', 1, 2, 'medium', 'pending', DATE_ADD(NOW(), INTERVAL 5 DAY));

-- Trigger para atualizar completed_at quando status muda para completed
DELIMITER //
CREATE TRIGGER update_completed_at 
    BEFORE UPDATE ON tasks
    FOR EACH ROW
BEGIN
    IF NEW.status = 'completed' AND OLD.status != 'completed' THEN
        SET NEW.completed_at = NOW();
    ELSEIF NEW.status != 'completed' THEN
        SET NEW.completed_at = NULL;
    END IF;
END//
DELIMITER ;

-- View para relatórios de tarefas por usuário
CREATE VIEW user_task_summary AS
SELECT 
    u.id as user_id,
    u.username,
    u.first_name,
    u.last_name,
    COUNT(t.id) as total_tasks,
    SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
    SUM(CASE WHEN t.status = 'pending' THEN 1 ELSE 0 END) as pending_tasks,
    SUM(CASE WHEN t.status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_tasks,
    SUM(CASE WHEN t.due_date < NOW() AND t.status != 'completed' THEN 1 ELSE 0 END) as overdue_tasks
FROM users u
LEFT JOIN tasks t ON u.id = t.user_id
WHERE u.is_active = TRUE
GROUP BY u.id, u.username, u.first_name, u.last_name;