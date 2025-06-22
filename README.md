# 🎯 Sistema de Tarefas - PHP

Sistema completo de gerenciamento de tarefas desenvolvido em PHP com Docker, implementado em **duas versões técnicas idênticas**:

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
- [Troubleshooting](#-troubleshooting)

## 🚀 Funcionalidades

### ✅ **Gestão Completa de Tarefas**
- ➕ Criar, editar e excluir tarefas
- ☑️ Marcar como concluída via checkbox (tempo real)
- 🎯 Prioridades: baixa, média, alta, urgente
- 📊 Status: pendente, em andamento, concluída, cancelada
- 📅 Data de vencimento
- 🔍 Filtros avançados e pesquisa

### 📁 **Sistema de Categorias**
- 🎨 Criar categorias com cores personalizadas
- 🏷️ Organizar tarefas por categoria
- 📊 Estatísticas por categoria
- 🔧 Gerenciamento completo

### 🔐 **Autenticação Segura**
- 👤 Sistema de login/logout
- ✍️ Registro de novos usuários
- 🔑 Recuperação de senha via token
- 👤 Perfil do usuário editável

### 📊 **Dashboard Interativo**
- 📈 Estatísticas em tempo real
- 📊 Taxa de conclusão visual
- ⚠️ Tarefas atrasadas
- 🔄 Contadores dinâmicos auto-atualizáveis

## 🛠️ Tecnologias

### **Backend**
- **PHP**: 8.2+ com FPM
- **MySQL**: 8.0
- **ORMs**: Medoo e FluentPDO
- **Composer**: Gerenciamento de dependências

### **Frontend**
- **HTML5/CSS3**: Layout responsivo
- **JavaScript**: Interações dinâmicas
- **AJAX**: Comunicação com APIs

### **Infrastructure**
- **Docker**: Containerização completa
- **Nginx**: Servidor web com SSL
- **PHP-FPM**: Processamento PHP otimizado
- **MySQL**: Banco de dados

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

#### **Sistema FluentPDO**
- 🏠 **Dashboard**: http://projetofluentpdo.test/dashboard.php
- 🔐 **Login**: http://projetofluentpdo.test/login.php
- ➕ **Nova Tarefa**: http://projetofluentpdo.test/add-task.php
- 📁 **Categorias**: http://projetofluentpdo.test/categories.php

### **Funcionalidades Principais**

1. **Fazer Login** com as credenciais padrão
2. **Criar Categorias** (opcional) com cores personalizadas
3. **Adicionar Tarefas** com prioridade e categoria
4. **Marcar como Concluída** clicando no checkbox ☑️
5. **Filtrar e Pesquisar** tarefas no dashboard
6. **Ver Estatísticas** em tempo real

## 📁 Estrutura do Projeto

```
cogna/
├── 📁 medoo/                    # Sistema usando Medoo ORM
│   ├── 📁 app/
│   │   ├── 📁 api/             # APIs RESTful
│   │   ├── 📁 config/          # Configurações
│   │   ├── 📁 models/          # Modelos de dados
│   │   └── 📁 helpers/         # Funções auxiliares
│   ├── 📁 public/              # Arquivos públicos
│   │   ├── 📁 css/            # Estilos
│   │   ├── 📁 js/             # JavaScript
│   │   ├── 📁 api/            # Endpoints da API
│   │   └── *.php              # Páginas principais
│   └── 📁 vendor/              # Dependências Composer
│
├── 📁 fluentpdo/               # Sistema usando FluentPDO
│   └── [mesma estrutura do medoo]
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

### **Endpoints Principais**

#### **Tarefas**
```http
GET    /api/tasks.php           # Listar tarefas
POST   /api/simple-update.php   # Atualizar status da tarefa
GET    /api/stats.php           # Estatísticas do dashboard
```

#### **Categorias**
```http
GET    /api/categories.php      # Listar categorias
POST   /api/categories.php      # Criar categoria
PUT    /api/categories.php      # Atualizar categoria
DELETE /api/categories.php      # Excluir categoria
```

#### **Autenticação**
```http
POST   /api/auth/login.php      # Login
POST   /api/auth/register.php   # Registro
POST   /api/auth/logout.php     # Logout
POST   /api/auth/forgot-password.php  # Esqueci senha
POST   /api/auth/reset-password.php   # Reset senha
```

### **Exemplo de Uso da API**
```javascript
// Marcar tarefa como concluída
fetch('api/simple-update.php?id=1', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ status: 'completed' })
})
.then(response => response.json())
.then(data => console.log(data));
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

## 🛠️ Troubleshooting

### **Problemas Comuns**

#### **Portas Ocupadas**
```bash
# Verificar portas em uso
netstat -tulpn | grep -E ':(80|443|3306|8080)'

# Parar outros serviços ou alterar portas no docker-compose.yml
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

### **Acesso aos Logs**
- **Nginx**: `./docker/nginx/logs/`
- **MySQL**: `docker-compose logs mysql`
- **PHP**: `docker-compose logs php-medoo`

---

## 🎯 **Sistema Pronto para Produção!**

✅ **Medoo**: 100% funcional  
✅ **FluentPDO**: 100% funcional  
✅ **Docker**: Configurado e otimizado  
✅ **SSL**: Certificados auto-gerados  
✅ **Backup**: Dados persistentes em volumes  

**Desenvolvido com ❤️ em PHP + Docker**