# � Como Usar o Sistema de Tarefas

## ⚡ Início Rápido (5 minutos)

### 1. **Iniciar Sistema**
```bash
docker-compose up -d
```

### 2. **Aguardar MySQL**
```bash
docker-compose logs -f mysql
# Aguardar: "ready for connections"
# Pressionar Ctrl+C
```

### 3. **Acessar**
- **Medoo**: http://localhost
- **FluentPDO**: http://localhost
- **Login**: admin / admin123

---

## � Funcionalidades

### ✅ **Tarefas**
1. **Criar**: Dashboard → "Nova Tarefa"
2. **Editar**: Clicar em "Editar" na tarefa
3. **Concluir**: Clicar no checkbox ☑️
4. **Excluir**: Clicar em "Excluir"

### � **Categorias** 
1. **Criar**: Menu → "Categorias" → "Nova Categoria"
2. **Cores**: Escolher cor personalizada
3. **Filtrar**: Usar filtro de categoria no dashboard

### � **Dashboard**
- **Estatísticas**: Atualizadas automaticamente
- **Filtros**: Status, categoria, prioridade
- **Pesquisa**: Campo de busca no topo

---

## � Comandos Docker

```bash
# Iniciar
docker-compose up -d

# Parar  
docker-compose down

# Ver status
docker-compose ps

# Ver logs
docker-compose logs -f

# Reset (apaga dados)
docker-compose down -v
docker-compose up -d
```

---

## � URLs e Credenciais

### **Acessos Web**
- Medoo: http://localhost ou http://projetomedoo.test
- FluentPDO: http://localhost ou http://projetofluentpdo.test
- phpMyAdmin: http://localhost:8080

### **Login Sistema**
- Usuário: `admin`
- Senha: `admin123`

### **Banco de Dados**
- Host: mysql (interno) / localhost:3306 (externo)
- Database: taskmanager
- User: taskuser
- Password: taskpass

---

## � Documentação Completa

- **README.md** - Visão geral e funcionalidades
- **INSTALACAO.md** - Guia de instalação detalhado  
- **DOCKER-COMANDOS.md** - Comandos Docker úteis

---

**Sistema pronto para uso! �**
