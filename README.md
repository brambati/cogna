# 🎯 Sistema de Tarefas - PHP

Sistema completo de gerenciamento de tarefas desenvolvido em PHP com Docker, implementado em **duas versões técnicas 100% idênticas**:

- **🔵 Medoo**: Usando Medoo ORM
- **🟡 FluentPDO**: Usando FluentPDO Query Builder

## 📋 Índice

- [Funcionalidades](#-funcionalidades)
- [Tecnologias](#-tecnologias)
- [Pré-requisitos](#-pré-requisitos)
- [Instalação](#-instalação)
- [Uso](#-uso)
- [Estrutura do Projeto](#-estrutura-do-projeto)
- [API](#-api)
- [JavaScript & AJAX](#-javascript--ajax)
- [Segurança](#-segurança)
- [Troubleshooting](#-troubleshooting)

## 🚀 Funcionalidades

### ✅ **Gestão Completa de Tarefas**

- ➕ Criar, editar e excluir tarefas
- ☑️ Marcar como concluída via checkbox (tempo real)
- 🎯 Prioridades: baixa, média, alta, urgente
- 📊 Status: pendente, em andamento, concluída, cancelada
- 📅 Data de vencimento
- 🔍 **Filtros inteligentes em tempo real**
- 🔍 **Busca com debounce** (300ms)
- 📄 **Visualização detalhada** de tarefas
- 🔄 **Ordenação dinâmica** (data, prioridade, título)

### 📁 **Sistema de Categorias**

- 🎨 Criar categorias com cores personalizadas
- 🏷️ Organizar tarefas por categoria
- 📊 Estatísticas por categoria
- 🗑️ **Soft delete** (categorias com tarefas são arquivadas)
- 🔧 Gerenciamento completo via API

### 🔐 **Autenticação & Segurança**

- 👤 Sistema de login/logout
- ✍️ Registro de novos usuários
- 🔑 Recuperação de senha via token
- 👤 **Perfil editável** com validações
- 🔒 **Alteração de senha** com critérios de segurança
- 🛡️ **Rate limiting** por IP
- 📝 **Logs de segurança** detalhados
- 🔍 **Validações robustas** de entrada

### 📊 **Dashboard Interativo**

- 📈 **Estatísticas em tempo real**
- 📊 **Taxa de conclusão visual** com progress bar
- ⚠️ **Tarefas atrasadas** destacadas
- 🔄 **Contadores dinâmicos** auto-atualizáveis
- 🎨 **Interface responsiva** (mobile/tablet/desktop)
- 🔔 **Sistema de notificações** visuais

### 🌐 **API REST Completa**

- 🎯 **Endpoints estruturados** (/app/api/)
- 📡 **Router centralizado** de APIs
- 🔒 **Autenticação obrigatória** em endpoints protegidos
- 📝 **Documentação completa** de endpoints
- 🛡️ **Rate limiting** global
- 📊 **Respostas padronizadas** JSON

### ⚡ **JavaScript Avançado**

- 📚 **Biblioteca API completa** (api.js)
- 🎯 **Gerenciador de tarefas** inteligente (tasks.js)
- 🔔 **Sistema de notificações** em tempo real
- 🔍 **Filtros combinados** dinâmicos
- ⚡ **AJAX otimizado** com fallbacks
- 🎨 **Animações suaves** e feedback visual

## 🛠️ Tecnologias

### **Backend**

- **PHP**: 8.2+ com FPM
- **MySQL**: 8.0
- **ORMs**: Medoo e FluentPDO
- **Composer**: Gerenciamento de dependências

### **Frontend**

- **HTML5/CSS3**: Layout responsivo
- **JavaScript ES6+**: Classes e async/await
- **AJAX**: Comunicação assíncrona
- **Progressive Enhancement**: Funciona sem JavaScript

### **Infrastructure**

- **Docker**: Containerização completa
- **Nginx**: Servidor web com SSL
- **PHP-FPM**: Processamento PHP otimizado
- **MySQL**: Banco de dados

### **Segurança**

- **Rate Limiting**: Por IP e ação
- **CSRF Protection**: Tokens validados
- **Input Validation**: Sanitização robusta
- **Password Security**: Critérios rigorosos
- **Security Headers**: Proteção XSS/CSRF

## 📋 Pré-requisitos

- **Docker**: 20.10+
- **Docker Compose**: 2.0+
- **Portas livres**: 80, 443, 3306, 8080

## 🚀 Instalação

### 1. **Clone o Repositório**

```bash
git clone <repo-url>
cd cogna
```

### 2. **Configure Hosts (Opcional)**

Adicione ao arquivo `/etc/hosts` (Linux/Mac) ou `C:\Windows\System32\drivers\etc\hosts` (Windows):

```
127.0.0.1 projetomedoo.test
127.0.0.1 projetofluentpdo.test
```

### 3. **Inicie os Containers**

```bash
# Iniciar todos os serviços
docker-compose up -d

# Verificar status
docker-compose ps
```

### 4. **Aguarde a Inicialização**

```bash
# Aguardar MySQL inicializar (primeira vez pode demorar)
docker-compose logs -f mysql

# Quando aparecer "ready for connections", pressione Ctrl+C
```

### 5. **Verificar Funcionamento**

- **Medoo**: http://projetomedoo.test ou http://localhost
- **FluentPDO**: http://projetofluentpdo.test ou http://localhost
- **phpMyAdmin**: http://localhost:8080

## 🎯 Uso

### **Credenciais Padrão**

- **Usuário**: `admin`
- **Senha**: `admin123`

### **Acessos Rápidos**

#### **Sistema Medoo**

- 🏠 **Dashboard**: http://projetomedoo.test/dashboard.php
- 🔐 **Login**: http://projetomedoo.test/login.php
- ➕ **Nova Tarefa**: http://projetomedoo.test/add-task.php
- 📁 **Categorias**: http://projetomedoo.test/categories.php
- 👤 **Perfil**: http://projetomedoo.test/profile.php
- 📄 **Detalhes**: http://projetomedoo.test/task-details.php?id=X

#### **Sistema FluentPDO**

- 🏠 **Dashboard**: http://projetofluentpdo.test/dashboard.php
- 🔐 **Login**: http://projetofluentpdo.test/login.php
- ➕ **Nova Tarefa**: http://projetofluentpdo.test/add-task.php
- 📁 **Categorias**: http://projetofluentpdo.test/categories.php
- 👤 **Perfil**: http://projetofluentpdo.test/profile.php
- 📄 **Detalhes**: http://projetofluentpdo.test/task-details.php?id=X

### **Funcionalidades Avançadas**

1. **Login Inteligente** - Redirecionamento automático
2. **Dashboard Dinâmico** - Estatísticas em tempo real
3. **Filtros Inteligentes** - Combinação de status, categoria, prioridade
4. **Busca em Tempo Real** - Com debounce de 300ms
5. **Notificações Visuais** - Feedback de ações
6. **Toggle de Status** - Checkbox inteligente com AJAX
7. **Gerenciamento de Perfil** - Edição com validações
8. **Soft Delete** - Categorias são arquivadas, não excluídas

## 📁 Estrutura do Projeto

```
cogna/
├── 📁 medoo/                    # Sistema usando Medoo ORM
│   ├── 📁 app/
│   │   ├── 📁 api/             # APIs RESTful estruturadas
│   │   │   ├── 📁 auth/        # Autenticação
│   │   │   ├── 📁 tasks/       # CRUD de tarefas
│   │   │   ├── 📁 categories/  # CRUD de categorias
│   │   │   ├── 📁 user/        # Perfil e senha
│   │   │   └── router.php      # Router centralizado
│   │   ├── 📁 config/          # Configurações
│   │   ├── 📁 models/          # Modelos de dados
│   │   └── 📁 helpers/         # SecurityHelper avançado
│   ├── 📁 public/              # Arquivos públicos
│   │   ├── 📁 css/            # Estilos responsivos
│   │   ├── 📁 js/             # JavaScript avançado
│   │   │   ├── api.js         # Biblioteca de APIs (7KB)
│   │   │   ├── tasks.js       # Gerenciador de tarefas (14KB)
│   │   │   └── *.js           # Outros módulos
│   │   ├── 📁 api/            # Endpoints legados
│   │   ├── index.php          # Ponto de entrada
│   │   ├── task-details.php   # Visualização detalhada
│   │   └── *.php              # Páginas principais
│   └── 📁 vendor/              # Dependências Composer
│
├── 📁 fluentpdo/               # Sistema usando FluentPDO
│   └── [estrutura idêntica ao medoo]
│
├── 📁 docker/                  # Configurações Docker
│   ├── 📁 nginx/              # Nginx + SSL
│   ├── 📁 php/                # PHP-FPM 8.2
│   └── 📁 mysql/              # MySQL + init scripts
│
├── 📄 docker-compose.yml       # Orquestração dos containers
└── 📄 README.md               # Esta documentação
```

## 🔌 API

### **API REST Estruturada**

#### **🎯 Tarefas (/app/api/tasks/)**

```http
GET    /app/api/tasks/          # Listar tarefas (com filtros)
GET    /app/api/tasks/?id=1     # Obter tarefa específica
POST   /app/api/tasks/          # Criar nova tarefa
PUT    /app/api/tasks/?id=1     # Atualizar tarefa completa
PATCH  /app/api/tasks/?id=1     # Atualizar apenas status
DELETE /app/api/tasks/?id=1     # Excluir tarefa
```

**Filtros Disponíveis:**

- `?status=pending` - Filtrar por status
- `?priority=high` - Filtrar por prioridade
- `?category_id=1` - Filtrar por categoria
- `?search=texto` - Busca textual
- `?sort=created_desc` - Ordenação
- `?limit=50` - Limitar resultados

#### **📁 Categorias (/app/api/categories/)**

```http
GET    /app/api/categories/     # Listar categorias
GET    /app/api/categories/?id=1 # Obter categoria específica
POST   /app/api/categories/     # Criar categoria
PUT    /app/api/categories/?id=1 # Atualizar categoria
DELETE /app/api/categories/?id=1 # Excluir/arquivar categoria
```

#### **👤 Usuário (/app/api/user/)**

```http
GET    /app/api/user/profile.php    # Obter perfil
PUT    /app/api/user/profile.php    # Atualizar perfil
POST   /app/api/user/change-password.php # Alterar senha
```

#### **🔐 Autenticação (/app/api/auth/)**

```http
POST   /app/api/auth/login.php      # Login
POST   /app/api/auth/register.php   # Registro
POST   /app/api/auth/logout.php     # Logout
POST   /app/api/auth/forgot-password.php  # Esqueci senha
POST   /app/api/auth/reset-password.php   # Reset senha
GET    /app/api/auth/verify-token.php     # Verificar token
```

#### **📊 Estatísticas (Legacy)**

```http
GET    /api/stats.php           # Estatísticas do dashboard
POST   /api/simple-update.php   # Atualizar status (legacy)
```

### **🎯 Router Centralizado**

```http
GET    /app/api/router.php/tasks          # Via router
GET    /app/api/router.php/categories     # Via router
GET    /app/api/router.php/user/profile   # Via router
```

### **Exemplo de Uso da API**

```javascript
// Usando a biblioteca API incorporada
window.api
  .updateTaskStatus(1, "completed")
  .then((response) => {
    window.api.showSuccess("Tarefa concluída!");
  })
  .catch((error) => {
    window.api.showError("Erro ao atualizar tarefa");
  });

// Filtrar tarefas
window.api
  .getTasks({
    status: "pending",
    priority: "high",
    search: "reunião",
  })
  .then((response) => {
    console.log(response.data);
  });
```

## ⚡ JavaScript & AJAX

### **📚 Biblioteca API (api.js)**

- ✅ **Métodos HTTP**: GET, POST, PUT, DELETE, PATCH
- ✅ **Tratamento de Erros**: Try/catch robusto
- ✅ **Notificações**: Sistema visual integrado
- ✅ **Rate Limiting**: Respeita limites da API
- ✅ **Fallbacks**: Funciona mesmo com problemas de rede

### **🎯 Gerenciador de Tarefas (tasks.js)**

- ✅ **Filtros Dinâmicos**: Status, categoria, prioridade, busca
- ✅ **Busca Inteligente**: Debounce de 300ms
- ✅ **Toggle Status**: Checkbox com AJAX
- ✅ **Ordenação**: Múltiplos critérios
- ✅ **Estatísticas**: Atualização automática
- ✅ **Estado Vazio**: Mensagens contextuais

### **🔔 Sistema de Notificações**

```javascript
// Tipos de notificação
window.api.showSuccess("Operação realizada com sucesso!");
window.api.showError("Erro ao processar solicitação");
window.api.showNotification("Informação geral", "info");
```

### **🎨 Funcionalidades JavaScript**

- ✅ **Progressive Enhancement**: Funciona sem JS
- ✅ **Responsividade**: Adapta-se a qualquer tela
- ✅ **Acessibilidade**: Suporte a leitores de tela
- ✅ **Performance**: Lazy loading e otimizações

## 🛡️ Segurança

### **🔒 SecurityHelper Avançado**

```php
// Rate limiting por IP
SecurityHelper::checkRateLimit('login', 5, 15); // 5 tentativas em 15min

// Validação robusta
SecurityHelper::validateUserInput($data, [
    'email' => 'required|email',
    'password' => 'required|min:6'
]);

// Força da senha
SecurityHelper::validatePasswordStrength($password);

// Logs de segurança
SecurityHelper::logSecurityEvent('login_attempt', ['ip' => $ip]);
```

### **🛡️ Medidas de Proteção**

- ✅ **Rate Limiting**: 60 req/5min global, específico por ação
- ✅ **Validação de Entrada**: Sanitização robusta
- ✅ **Headers de Segurança**: XSS, CSRF, Clickjacking
- ✅ **Senhas Seguras**: Critérios obrigatórios
- ✅ **Logs Detalhados**: Monitoramento de atividades
- ✅ **Tokens Seguros**: Expiração automática
- ✅ **IP Whitelisting**: Suporte futuro

### **📝 Logs de Segurança**

```bash
# Ver logs de segurança
tail -f /tmp/security_log.txt

# Exemplo de entrada
{
  "timestamp": "2024-06-22 14:30:15",
  "ip": "192.168.1.100",
  "event": "login_success",
  "details": {"user_id": 1},
  "user_agent": "Mozilla/5.0..."
}
```

## 🐳 Comandos Docker Úteis

### **Gerenciamento dos Containers**

```bash
# Iniciar serviços
docker-compose up -d

# Parar serviços
docker-compose down

# Reiniciar um serviço específico
docker-compose restart nginx

# Ver logs
docker-compose logs -f [serviço]

# Acessar container
docker-compose exec php-medoo bash
docker-compose exec mysql mysql -u root -p
```

### **Desenvolvimento**

```bash
# Rebuild containers
docker-compose build --no-cache

# Reset completo (CUIDADO: apaga dados)
docker-compose down -v
docker-compose up -d

# Verificar recursos
docker stats
docker system df
```

## 🔧 Configurações

### **Banco de Dados**

- **Host**: mysql (interno) / localhost:3306 (externo)
- **Database**: taskmanager
- **Username**: taskuser
- **Password**: taskpass
- **Root Password**: rootpass

### **URLs de Acesso**

- **Medoo**: http://projetomedoo.test
- **FluentPDO**: http://projetofluentpdo.test
- **phpMyAdmin**: http://localhost:8080

### **Rate Limiting Padrão**

- **API Global**: 60 requests / 5 minutos
- **Login**: 5 tentativas / 15 minutos
- **Perfil**: 10 requests / 10 minutos
- **Mudança de Senha**: 3 tentativas / 30 minutos

## 🛠️ Troubleshooting

### **Problemas Comuns**

#### **Portas Ocupadas**

```bash
# Verificar portas em uso
netstat -tulpn | grep -E ':(80|443|3306|8080)'

# Parar outros serviços ou alterar portas no docker-compose.yml
```

#### **JavaScript Não Carrega**

```bash
# Verificar arquivos JS
curl -I http://projetomedoo.test/js/api.js
curl -I http://projetofluentpdo.test/js/tasks.js

# Verificar console do navegador (F12)
```

#### **API Retorna 401**

```bash
# Verificar autenticação
curl -H "Content-Type: application/json" http://projetomedoo.test/app/api/tasks/

# Deve retornar: {"error":"Não autorizado"}
```

#### **Rate Limiting Ativo**

```bash
# Limpar arquivos de rate limiting
docker-compose exec php-medoo rm -f /tmp/rate_limit_*
docker-compose exec php-fluentpdo rm -f /tmp/rate_limit_*
```

#### **Containers não Iniciam**

```bash
# Ver logs detalhados
docker-compose logs

# Verificar espaço em disco
df -h

# Limpar recursos Docker não utilizados
docker system prune -a
```

#### **Erro de Permissão**

```bash
# Ajustar permissões (Linux/Mac)
sudo chown -R $USER:$USER ./medoo ./fluentpdo
chmod -R 755 ./medoo ./fluentpdo
```

#### **Banco não Conecta**

```bash
# Aguardar MySQL inicializar completamente
docker-compose logs -f mysql

# Testar conexão
docker-compose exec mysql mysql -u taskuser -p taskmanager
```

### **Reset Completo**

```bash
# ATENÇÃO: Apaga todos os dados
docker-compose down -v
docker system prune -a
docker-compose up -d
```

## 📊 Monitoramento

### **Verificar Status dos Serviços**

```bash
# Status dos containers
docker-compose ps

# Uso de recursos
docker stats

# Logs em tempo real
docker-compose logs -f
```

### **Testes de Funcionalidade**

```bash
# Testar websites
curl -I http://projetomedoo.test/
curl -I http://projetofluentpdo.test/

# Testar APIs (deve retornar 401 - correto)
curl -H "Content-Type: application/json" http://projetomedoo.test/app/api/tasks/

# Testar JavaScript
curl -I http://projetomedoo.test/js/api.js
curl -I http://projetofluentpdo.test/js/tasks.js
```

### **Acesso aos Logs**

- **Nginx**: `./docker/nginx/logs/`
- **MySQL**: `docker-compose logs mysql`
- **PHP**: `docker-compose logs php-medoo`
- **Segurança**: `/tmp/security_log.txt` (dentro dos containers)

---

## 🎯 **Sistemas 100% Idênticos e Funcionais!**

### ✅ **Status de Implementação**

| **Funcionalidade** | **Medoo**      | **FluentPDO**  | **Status**      |
| ------------------ | -------------- | -------------- | --------------- |
| **Frontend**       | ✅ 10 páginas  | ✅ 10 páginas  | 🟢 **Idêntico** |
| **JavaScript**     | ✅ 7KB + 14KB  | ✅ 7KB + 14KB  | 🟢 **Idêntico** |
| **API REST**       | ✅ 8 endpoints | ✅ 8 endpoints | 🟢 **Idêntico** |
| **Segurança**      | ✅ Avançada    | ✅ Avançada    | 🟢 **Idêntico** |
| **Features**       | ✅ 100%        | ✅ 100%        | 🟢 **Idêntico** |

### 🚀 **Funcionalidades Testadas**

- ✅ **Containers Docker**: 5/5 funcionando
- ✅ **Websites**: Redirecionamento HTTP 302
- ✅ **Login Pages**: HTTP 200
- ✅ **JavaScript**: api.js e tasks.js carregando
- ✅ **APIs**: Estrutura implementada e protegida
- ✅ **Segurança**: Rate limiting e validações ativas
- ✅ **Responsividade**: Mobile/tablet/desktop

### 🎨 **Arquivos Implementados**

- **📜 JavaScript**: `api.js` (7KB), `tasks.js` (14KB)
- **🔒 Segurança**: `SecurityHelper` (9KB) com rate limiting
- **🌐 API**: Endpoints estruturados em `/app/api/`
- **🎯 Router**: Centralizador de rotas (6KB)
- **👤 User**: Perfil e alteração de senha
- **📄 Frontend**: `task-details.php` e melhorias

