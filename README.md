# Task Manager - Teste de Desenvolvedor FullStack PHP

Este projeto implementa um sistema completo de gerenciamento de tarefas usando duas bibliotecas diferentes de acesso ao banco de dados: **FluentPDO** e **Medoo**. Ambos os sistemas oferecem funcionalidades idênticas, permitindo comparar as diferentes abordagens de implementação.

## 🚀 Características

- **Dois sistemas idênticos** com bibliotecas diferentes:
  - **Sistema 1**: FluentPDO - `https://projetofluentpdo.test`
  - **Sistema 2**: Medoo - `https://projetomedoo.test`
- **Docker completo** com nginx, PHP 8.2, MySQL 8.0
- **HTTPS obrigatório** com certificados SSL
- **API REST** completa
- **Interface responsiva** com jQuery
- **Autenticação segura** com proteções avançadas
- **Medidas de segurança** contra XSS, CSRF, SQL Injection

## 📋 Funcionalidades

### Sistema de Autenticação
- ✅ Login e registro de usuários
- ✅ Recuperação de senha
- ✅ Sessões seguras com tokens
- ✅ Rate limiting para tentativas de login
- ✅ Logs de segurança

### Gerenciamento de Tarefas
- ✅ CRUD completo de tarefas
- ✅ Categorização de tarefas
- ✅ Níveis de prioridade (baixa, média, alta, urgente)
- ✅ Status de tarefas (pendente, em progresso, concluída, cancelada)
- ✅ Filtros e busca avançada
- ✅ Datas de vencimento

### API REST
- ✅ Endpoints para todas as operações
- ✅ Autenticação via tokens
- ✅ Respostas JSON padronizadas
- ✅ Status HTTP apropriados

## 🛠️ Tecnologias Utilizadas

- **Backend**: PHP 8.2 (procedural)
- **Banco de Dados**: MySQL 8.0
- **ORM/Query Builders**: FluentPDO e Medoo
- **Frontend**: HTML5, CSS3, JavaScript, jQuery
- **Servidor Web**: Nginx
- **Containerização**: Docker + Docker Compose
- **SSL**: Certificados auto-assinados
- **Gerenciamento de Dependências**: Composer

## 📁 Estrutura do Projeto

```
/project
├── docker/                 # Configurações Docker
│   ├── nginx/              # Configuração Nginx + SSL
│   ├── php/                # Configuração PHP-FPM
│   └── mysql/              # Scripts de inicialização do banco
├── fluentpdo/              # Sistema usando FluentPDO
│   ├── app/
│   │   ├── api/            # Endpoints da API
│   │   ├── auth/           # Sistema de autenticação
│   │   ├── config/         # Configurações
│   │   ├── helpers/        # Funções auxiliares
│   │   └── models/         # Models de dados
│   └── public/             # Arquivos públicos
├── medoo/                  # Sistema usando Medoo
│   ├── app/                # (mesma estrutura do FluentPDO)
│   └── public/
├── docker-compose.yml      # Orquestração dos containers
└── README.md              # Esta documentação
```

## 🚀 Instalação e Configuração

### Pré-requisitos

- Docker Desktop instalado
- Git instalado
- Acesso de administrador no sistema

### Passo 1: Clonar o Repositório

```bash
git clone [URL_DO_REPOSITORIO]
cd taskmanager
```

### Passo 2: Configurar Domínios Locais

Edite o arquivo hosts do seu sistema operacional:

**Windows**: `C:\\Windows\\System32\\drivers\\etc\\hosts`
**Linux/Mac**: `/etc/hosts`

Adicione as seguintes linhas:

```
127.0.0.1 projetofluentpdo.test
127.0.0.1 projetomedoo.test
```

### Passo 3: Iniciar o Projeto

```bash
# Construir e iniciar todos os containers
docker-compose up -d

# Verificar se todos os containers estão rodando
docker-compose ps
```

### Passo 4: Instalar Dependências PHP

```bash
# Instalar dependências do projeto FluentPDO
docker exec taskmanager_php_fluentpdo composer install

# Instalar dependências do projeto Medoo
docker exec taskmanager_php_medoo composer install
```

### Passo 5: Configurar Certificados SSL

Os certificados SSL são gerados automaticamente quando os containers iniciam. Para confiar nos certificados:

1. Acesse: `https://projetofluentpdo.test`
2. Clique em **"Avançado"** (ou "Advanced")
3. Clique em **"Continuar para projetofluentpdo.test (não seguro)"**
4. Repita para `https://projetomedoo.test`

### Passo 6: Verificar Instalação

Acesse os seguintes URLs para verificar se tudo está funcionando:

- **FluentPDO**: https://projetofluentpdo.test
- **Medoo**: https://projetomedoo.test
- **phpMyAdmin**: http://localhost:8080

## 🔐 Configuração de Certificados SSL Confiáveis (Opcional)

Para evitar avisos de segurança nos navegadores, você pode instalar os certificados como confiáveis:

### Windows

1. Execute no PowerShell:
```bash
docker cp taskmanager_nginx:/etc/nginx/ssl/projetofluentpdo.test.crt .
```

2. Duplo clique no arquivo `.crt`
3. Clique em **"Instalar Certificado"**
4. Escolha **"Máquina Local"**
5. Selecione **"Colocar todos os certificados no repositório a seguir"**
6. Clique em **"Procurar"** e escolha **"Autoridades de Certificação Raiz Confiáveis"**
7. Clique **"OK"** e **"Concluir"**
8. Repita para o certificado do Medoo

### macOS

```bash
# Extrair certificado
docker cp taskmanager_nginx:/etc/nginx/ssl/projetofluentpdo.test.crt .

# Adicionar ao keychain
sudo security add-trusted-cert -d -r trustRoot -k /Library/Keychains/System.keychain projetofluentpdo.test.crt
```

## 👤 Credenciais de Acesso

### Usuário de Demonstração
- **Email**: admin@taskmanager.test
- **Senha**: admin123

### Banco de Dados
- **Host**: localhost:3306
- **Usuário**: taskuser
- **Senha**: taskpass
- **Banco**: taskmanager

### phpMyAdmin
- **URL**: http://localhost:8080
- **Usuário**: taskuser
- **Senha**: taskpass

## 📚 Documentação da API

### Autenticação

#### POST /api/auth/login
```json
{
  "email": "admin@taskmanager.test",
  "password": "admin123"
}
```

#### POST /api/auth/register
```json
{
  "username": "novousuario",
  "email": "usuario@email.com",
  "password": "senhasegura123",
  "first_name": "Nome",
  "last_name": "Sobrenome"
}
```

#### POST /api/auth/logout
```json
{
  "session_token": "token_da_sessao"
}
```

### Tarefas

#### GET /api/tasks
Listar tarefas do usuário autenticado

#### POST /api/tasks
```json
{
  "title": "Nova Tarefa",
  "description": "Descrição da tarefa",
  "category_id": 1,
  "priority": "high",
  "due_date": "2024-12-31 23:59:59"
}
```

#### PUT /api/tasks/{id}
Atualizar tarefa existente

#### DELETE /api/tasks/{id}
Excluir tarefa

### Categorias

#### GET /api/categories
Listar categorias do usuário

#### POST /api/categories
```json
{
  "name": "Nova Categoria",
  "description": "Descrição da categoria",
  "color": "#ff0000"
}
```

## 🔒 Medidas de Segurança Implementadas

### Autenticação e Autorização
- Hash seguro de senhas (Argon2ID)
- Tokens de sessão únicos
- Expiração automática de sessões
- Rate limiting para tentativas de login
- Logs de tentativas de acesso

### Proteção contra Ataques
- **SQL Injection**: Uso de prepared statements
- **XSS**: Sanitização de dados de entrada
- **CSRF**: Tokens de proteção em formulários
- **Session Hijacking**: Tokens de sessão seguros
- **Brute Force**: Rate limiting

### Headers de Segurança
```
X-Content-Type-Options: nosniff
X-Frame-Options: SAMEORIGIN
X-XSS-Protection: 1; mode=block
Referrer-Policy: strict-origin-when-cross-origin
Strict-Transport-Security: max-age=31536000; includeSubDomains
```

### Configurações PHP Seguras
- `display_errors = Off`
- `expose_php = Off`
- Sessões com flags seguras
- Validação rigorosa de entrada

## 🏗️ Decisões de Arquitetura

### Separação de Projetos
- **FluentPDO** e **Medoo** em diretórios separados
- Configurações independentes
- Containers PHP separados para isolamento

### Padrão MVC Simplificado
- **Models**: Lógica de acesso aos dados
- **Views**: Templates HTML/PHP
- **Controllers**: Lógica de negócio (integrada às rotas)

### API REST
- Endpoints padronizados
- Respostas JSON consistentes
- Status HTTP apropriados
- Validação de dados

### Frontend
- **jQuery** para manipulação DOM
- **CSS Flexbox** para layout responsivo
- **AJAX** para comunicação com API
- **Validação** client-side e server-side

## 🎯 Diferenças entre FluentPDO e Medoo

### FluentPDO
```php
// Buscar usuário
$user = $this->fpdo->from('users')
    ->where('email = ? AND is_active = 1', $email)
    ->fetch();

// Inserir tarefa
$taskId = $this->fpdo->insertInto('tasks', $data)->execute();
```

### Medoo
```php
// Buscar usuário
$user = $this->database->get('users', '*', [
    'email' => $email,
    'is_active' => 1
]);

// Inserir tarefa
$this->database->insert('tasks', $data);
$taskId = $this->database->id();
```

## 🧪 Comandos de Desenvolvimento

```bash
# Ver logs dos containers
docker-compose logs -f

# Acessar container PHP
docker exec -it taskmanager_php_fluentpdo bash

# Backup do banco de dados
docker exec taskmanager_mysql mysqldump -u taskuser -ptaskpass taskmanager > backup.sql

# Restaurar banco de dados
docker exec -i taskmanager_mysql mysql -u taskuser -ptaskpass taskmanager < backup.sql

# Parar todos os containers
docker-compose down

# Reconstruir containers
docker-compose up -d --build
```

## 🚀 Produção

Para ambiente de produção, considere:

1. **Certificados SSL reais** (Let's Encrypt)
2. **Variáveis de ambiente** para configurações sensíveis
3. **Backup automático** do banco de dados
4. **Monitoramento** e logs centralizados
5. **Otimização** do nginx e PHP
6. **CDN** para arquivos estáticos

## 🐛 Solução de Problemas

### Containers não iniciam
```bash
# Verificar logs
docker-compose logs

# Reconstruir containers
docker-compose down
docker-compose up -d --build
```

### Erro de SSL
```bash
# Verificar se certificados foram gerados
docker exec taskmanager_nginx ls -la /etc/nginx/ssl/

# Regenerar certificados
docker exec taskmanager_nginx /generate-ssl.sh
docker-compose restart nginx
```

### Erro de conexão com banco
```bash
# Verificar se MySQL está rodando
docker-compose ps mysql

# Verificar logs do MySQL
docker-compose logs mysql
```

### Dependências PHP não instaladas
```bash
# Instalar dependências manualmente
docker exec taskmanager_php_fluentpdo composer install
docker exec taskmanager_php_medoo composer install
```

## 📞 Suporte

Para dúvidas ou problemas:

1. Verifique os logs: `docker-compose logs`
2. Consulte a documentação oficial do Docker
3. Verifique as configurações de rede
4. Confirme se os domínios estão configurados no arquivo hosts

## 📝 Licença

Este projeto foi desenvolvido como teste técnico e está disponível para fins educacionais.

---

**Desenvolvido com ❤️ para o teste de Desenvolvedor FullStack PHP**