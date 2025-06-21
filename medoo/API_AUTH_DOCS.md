# 🔐 API de Autenticação - Documentação

## Visão Geral

Esta API fornece endpoints para autenticação de usuários, incluindo registro, login, logout, recuperação de senha e verificação de tokens.

**Base URL:** `/api/auth/`

**Formato de Resposta:** JSON

**Autenticação:** JWT (JSON Web Token)

---

## 📋 Endpoints Disponíveis

### 1. **POST** `/api/auth/register`
Registra um novo usuário no sistema.

#### Parâmetros (JSON)
```json
{
  "name": "string (obrigatório)",
  "email": "string (obrigatório)",
  "password": "string (obrigatório, min: 6 chars)",
  "confirm_password": "string (obrigatório)"
}
```

#### Resposta de Sucesso (201)
```json
{
  "success": true,
  "message": "Usuário criado com sucesso",
  "data": {
    "user": {
      "id": 1,
      "name": "João Silva",
      "email": "joao@email.com",
      "created_at": "2025-06-19 10:30:00"
    },
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "expires_in": 86400
  }
}
```

#### Resposta de Erro (422)
```json
{
  "errors": {
    "email": "Email já está em uso",
    "password": "Senha deve conter pelo menos uma letra e um número"
  }
}
```

---

### 2. **POST** `/api/auth/login`
Autentica um usuário existente.

#### Parâmetros (JSON)
```json
{
  "email": "string (obrigatório)",
  "password": "string (obrigatório)",
  "remember": "boolean (opcional)"
}
```

#### Resposta de Sucesso (200)
```json
{
  "success": true,
  "message": "Login realizado com sucesso",
  "data": {
    "user": {
      "id": 1,
      "name": "João Silva",
      "email": "joao@email.com",
      "created_at": "2025-06-19 10:30:00"
    },
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "expires_in": 86400
  }
}
```

#### Resposta de Erro (401)
```json
{
  "error": "Credenciais inválidas"
}
```

---

### 3. **POST** `/api/auth/logout`
Encerra a sessão do usuário.

#### Headers
```
Authorization: Bearer <token>
```

#### Resposta de Sucesso (200)
```json
{
  "success": true,
  "message": "Logout realizado com sucesso"
}
```

#### Resposta de Erro (401)
```json
{
  "error": "Token não fornecido ou inválido"
}
```

---

### 4. **POST** `/api/auth/forgot-password`
Solicita recuperação de senha.

#### Parâmetros (JSON)
```json
{
  "email": "string (obrigatório)"
}
```

#### Resposta de Sucesso (200)
```json
{
  "success": true,
  "message": "Se o email estiver cadastrado, você receberá instruções para redefinir sua senha"
}
```

---

### 5. **POST** `/api/auth/reset-password`
Redefine a senha usando token de recuperação.

#### Parâmetros (JSON)
```json
{
  "token": "string (obrigatório)",
  "password": "string (obrigatório, min: 6 chars)",
  "confirm_password": "string (obrigatório)"
}
```

#### Resposta de Sucesso (200)
```json
{
  "success": true,
  "message": "Senha redefinida com sucesso"
}
```

#### Resposta de Erro (400)
```json
{
  "error": "Token inválido ou expirado"
}
```

---

### 6. **GET** `/api/auth/verify-token`
Verifica se o token JWT é válido.

#### Headers
```
Authorization: Bearer <token>
```

#### Resposta de Sucesso (200)
```json
{
  "success": true,
  "message": "Token válido",
  "data": {
    "user": {
      "id": 1,
      "name": "João Silva",
      "email": "joao@email.com",
      "created_at": "2025-06-19 10:30:00"
    },
    "expires_in": 82800,
    "expires_at": "2025-06-20 10:30:00"
  }
}
```

#### Resposta de Erro (401)
```json
{
  "error": "Token inválido ou expirado"
}
```

---

## 🔒 Autenticação

### JWT Token
- Os tokens JWT são enviados no header `Authorization`
- Formato: `Authorization: Bearer <token>`
- Tokens têm expiração: 24h (padrão) ou 30 dias (com "remember me")

### Estrutura do Token
```json
{
  "user_id": 1,
  "email": "joao@email.com",
  "exp": 1719747000
}
```

---

## 📝 Códigos de Status HTTP

| Código | Descrição |
|--------|-----------|
| 200 | Sucesso |
| 201 | Criado com sucesso |
| 400 | Dados inválidos |
| 401 | Não autenticado |
| 403 | Acesso negado |
| 404 | Endpoint não encontrado |
| 405 | Método não permitido |
| 409 | Conflito (email já existe) |
| 422 | Dados inválidos (validação) |
| 500 | Erro interno do servidor |

---

## 🧪 Teste das APIs

Acesse `/test-api.html` para uma interface de teste completa das APIs.

### Exemplo com cURL

#### Registro
```bash
curl -X POST http://localhost/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "João Silva",
    "email": "joao@email.com",
    "password": "123456",
    "confirm_password": "123456"
  }'
```

#### Login
```bash
curl -X POST http://localhost/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "joao@email.com",
    "password": "123456",
    "remember": false
  }'
```

#### Verificar Token
```bash
curl -X GET http://localhost/api/auth/verify-token \
  -H "Authorization: Bearer <seu_token_aqui>"
```

---

## 🔧 Configuração

### Variáveis de Ambiente
- `JWT_SECRET`: Chave secreta para assinar tokens JWT
- `DB_HOST`: Host do banco de dados
- `DB_NAME`: Nome do banco de dados
- `DB_USER`: Usuário do banco de dados
- `DB_PASS`: Senha do banco de dados

### Segurança
- Tokens são assinados com HMAC SHA-256
- Senhas são hasheadas com PASSWORD_DEFAULT (Argon2ID)
- Rate limiting implementado para prevenir ataques
- Headers de segurança configurados

---

## 🐛 Tratamento de Erros

Todas as respostas de erro seguem o formato:

```json
{
  "error": "Mensagem de erro descritiva"
}
```

Para erros de validação:

```json
{
  "errors": {
    "campo": "Mensagem de erro específica do campo"
  }
}
```

---

## 📚 Exemplos de Uso com JavaScript

```javascript
// Função para fazer requisições autenticadas
async function makeAuthRequest(url, method, data = null) {
    const token = localStorage.getItem('authToken');
    const options = {
        method: method,
        headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${token}`
        }
    };
    
    if (data) {
        options.body = JSON.stringify(data);
    }
    
    const response = await fetch(url, options);
    return await response.json();
}

// Login
const loginResponse = await makeAuthRequest('/api/auth/login', 'POST', {
    email: 'joao@email.com',
    password: '123456'
});

if (loginResponse.success) {
    localStorage.setItem('authToken', loginResponse.data.token);
}
```
