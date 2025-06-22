/**
 * API Helper Functions
 * Funções utilitárias para comunicação com a API
 */

class API {
  constructor() {
    // Garantir que sempre use apenas a origem, sem path adicional
    const currentUrl = window.location;
    this.baseUrl = `${currentUrl.protocol}//${currentUrl.host}`;
  }

    /**
     * Fazer requisição HTTP genérica
     */
    async request(url, options = {}) {
        const defaultOptions = {
            headers: {
                "Content-Type": "application/json",
                "X-Requested-With": "XMLHttpRequest",
            },
        };

        const config = {
            ...defaultOptions,
            ...options,
            headers: {
                ...defaultOptions.headers,
                ...options.headers,
            },
        };

        try {
            const response = await fetch(url, config);
            const data = await response.json();
            
            if (!response.ok) {
                throw new Error(data.error || "Erro na requisição");
            }
            
            return data;
        } catch (error) {
            console.error("API Error:", error);
            throw error;
        }
    }

    /**
     * Requisição GET
     */
    async get(endpoint, params = {}) {
        const url = new URL(`${this.baseUrl}${endpoint}`);
        Object.keys(params).forEach((key) => {
            if (params[key] !== null && params[key] !== undefined) {
                url.searchParams.append(key, params[key]);
            }
        });
        
        return this.request(url.toString(), {
            method: "GET",
        });
    }

    /**
     * Requisição POST
     */
    async post(endpoint, data = {}) {
        return this.request(`${this.baseUrl}${endpoint}`, {
            method: "POST",
            body: JSON.stringify(data),
        });
    }

    /**
     * Requisição PUT
     */
    async put(endpoint, data = {}) {
        return this.request(`${this.baseUrl}${endpoint}`, {
            method: "PUT",
            body: JSON.stringify(data),
        });
    }

    /**
     * Requisição DELETE
     */
    async delete(endpoint) {
        return this.request(`${this.baseUrl}${endpoint}`, {
            method: "DELETE",
        });
    }

    /**
     * Requisição PATCH
     */
    async patch(endpoint, data = {}) {
        return this.request(`${this.baseUrl}${endpoint}`, {
            method: "PATCH",
            body: JSON.stringify(data),
        });
    }

    // ============ TAREFAS ============

    /**
     * Listar todas as tarefas
     */
    async getTasks(filters = {}) {
        return this.get("/api/tasks.php", filters);
    }

    /**
     * Obter tarefa específica
     */
    async getTask(id) {
        return this.get(`/api/tasks.php?id=${id}`);
    }

    /**
     * Criar nova tarefa
     */
    async createTask(taskData) {
        return this.post("/api/tasks.php", taskData);
    }

    /**
     * Atualizar tarefa
     */
    async updateTask(id, taskData) {
        return this.put(`/api/tasks.php?id=${id}`, taskData);
    }

    /**
     * Excluir tarefa
     */
    async deleteTask(id) {
        return this.delete(`/api/tasks.php?id=${id}`);
    }

    /**
     * Atualizar status da tarefa
     */
    async updateTaskStatus(id, status) {
        return this.patch(`/api/tasks.php?id=${id}`, { status });
    }

    // ============ CATEGORIAS ============

    /**
     * Listar categorias
     */
    async getCategories() {
        return this.get("/api/categories.php");
    }

    /**
     * Obter categoria específica
     */
    async getCategory(id) {
        return this.get(`/api/categories.php?id=${id}`);
    }

    /**
     * Criar categoria
     */
    async createCategory(categoryData) {
        return this.post("/api/categories.php", categoryData);
    }

    /**
     * Atualizar categoria
     */
    async updateCategory(id, categoryData) {
        return this.put(`/api/categories.php?id=${id}`, categoryData);
    }

    /**
     * Excluir categoria
     */
    async deleteCategory(id) {
        return this.delete(`/api/categories.php?id=${id}`);
    }

    // ============ USUÁRIO ============

    /**
     * Obter perfil do usuário
     */
    async getUserProfile() {
        return this.get("/app/api/user/profile.php");
    }

    /**
     * Atualizar perfil do usuário
     */
    async updateUserProfile(profileData) {
        return this.put("/app/api/user/profile.php", profileData);
    }

    /**
     * Alterar senha do usuário
     */
    async changePassword(passwordData) {
        return this.post("/app/api/user/change-password.php", passwordData);
    }

    // ============ ESTATÍSTICAS ============

    /**
     * Obter estatísticas
     */
    async getStats() {
        return this.get("/api/stats.php");
    }

    // ============ UTILITÁRIOS ============

    /**
     * Mostrar notificação de sucesso
     */
    showSuccess(message) {
        this.showNotification(message, "success");
    }

    /**
     * Mostrar notificação de erro
     */
    showError(message) {
        this.showNotification(message, "error");
    }

    /**
     * Mostrar notificação genérica
     */
    showNotification(message, type = "info") {
        // Criar elemento de notificação
        const notification = document.createElement("div");
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <span>${message}</span>
            <button class="notification-close">&times;</button>
        `;

        // Adicionar estilos se não existirem
        if (!document.getElementById("notification-styles")) {
            const styles = document.createElement("style");
            styles.id = "notification-styles";
            styles.innerHTML = `
                .notification {
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    padding: 15px 20px;
                    border-radius: 8px;
                    color: white;
                    font-weight: 500;
                    z-index: 9999;
                    min-width: 300px;
                    animation: slideIn 0.3s ease;
                }
                .notification-success { background: #10b981; }
                .notification-error { background: #ef4444; }
                .notification-info { background: #3b82f6; }
                .notification-close {
                    background: none;
                    border: none;
                    color: white;
                    font-size: 18px;
                    float: right;
                    cursor: pointer;
                    margin-left: 10px;
                }
                @keyframes slideIn {
                    from { transform: translateX(100%); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
            `;
            document.head.appendChild(styles);
        }

        // Adicionar ao DOM
        document.body.appendChild(notification);

        // Auto-remover após 5 segundos
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 5000);

        // Botão de fechar
        notification
            .querySelector(".notification-close")
            .addEventListener("click", () => {
                notification.remove();
            });
    }
}

// Instância global da API
window.api = new API(); 