# Ì∫Ä Como Usar o Sistema de Tarefas

## ‚ö° In√≠cio R√°pido (5 minutos)

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

## Ì≥ã Funcionalidades

### ‚úÖ **Tarefas**
1. **Criar**: Dashboard ‚Üí "Nova Tarefa"
2. **Editar**: Clicar em "Editar" na tarefa
3. **Concluir**: Clicar no checkbox ‚òëÔ∏è
4. **Excluir**: Clicar em "Excluir"

### Ì≥Å **Categorias** 
1. **Criar**: Menu ‚Üí "Categorias" ‚Üí "Nova Categoria"
2. **Cores**: Escolher cor personalizada
3. **Filtrar**: Usar filtro de categoria no dashboard

### Ì≥ä **Dashboard**
- **Estat√≠sticas**: Atualizadas automaticamente
- **Filtros**: Status, categoria, prioridade
- **Pesquisa**: Campo de busca no topo

---

## Ì∞≥ Comandos Docker

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

## Ì¥ß URLs e Credenciais

### **Acessos Web**
- Medoo: http://localhost ou http://projetomedoo.test
- FluentPDO: http://localhost ou http://projetofluentpdo.test
- phpMyAdmin: http://localhost:8080

### **Login Sistema**
- Usu√°rio: `admin`
- Senha: `admin123`

### **Banco de Dados**
- Host: mysql (interno) / localhost:3306 (externo)
- Database: taskmanager
- User: taskuser
- Password: taskpass

---

## Ì≥ö Documenta√ß√£o Completa

- **README.md** - Vis√£o geral e funcionalidades
- **INSTALACAO.md** - Guia de instala√ß√£o detalhado  
- **DOCKER-COMANDOS.md** - Comandos Docker √∫teis

---

**Sistema pronto para uso! Ìæâ**
