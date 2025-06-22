# 🚀 START - Início Rápido

## ⚡ TL;DR - Execute Agora

```bash
# 1. Clone/baixe o projeto
cd cogna

# 2. Inicie os containers
docker-compose up -d

# 3. Aguarde MySQL (primeira vez demora)
docker-compose logs -f mysql
# Ctrl+C quando aparecer "ready for connections"

# 4. Acesse o sistema
# http://localhost (ou http://projetomedoo.test)
# Usuário: admin | Senha: admin123
```

## 🎯 Links Rápidos

### **🌐 Acessos**
- **Medoo**: http://localhost ou http://projetomedoo.test
- **FluentPDO**: http://localhost ou http://projetofluentpdo.test  
- **phpMyAdmin**: http://localhost:8080

### **🔑 Credenciais**
```
Login: admin
Senha: admin123

MySQL:
- Usuário: taskuser
- Senha: taskpass
- Database: taskmanager
```

### **📚 Documentação**
- **📖 README.md** - Documentação completa
- **🚀 INSTALACAO.md** - Guia de instalação detalhado
- **🐳 DOCKER-COMANDOS.md** - Comandos Docker úteis

## 🔧 Comandos Essenciais

```bash
# Iniciar sistema
docker-compose up -d

# Ver status
docker-compose ps

# Ver logs
docker-compose logs -f

# Parar sistema
docker-compose down

# Reset completo (apaga dados)
docker-compose down -v && docker-compose up -d
```

## ✅ Verificação Rápida

1. **Containers rodando**: `docker-compose ps`
2. **Acesso web**: Abrir http://localhost
3. **Login funciona**: admin/admin123
4. **Criar tarefa**: Testar funcionalidade
5. **Checkbox funciona**: Marcar tarefa como concluída

## 🆘 Problemas Comuns

### **Porta 80 ocupada**
```bash
# Ver quem usa a porta
sudo netstat -tulpn | grep :80
# Parar Apache/Nginx local ou alterar porta no docker-compose.yml
```

### **MySQL demora para iniciar**
```bash
# Aguardar logs
docker-compose logs -f mysql
# Esperar: "ready for connections"
```

### **Containers não sobem**
```bash
# Ver logs de erro
docker-compose logs

# Limpar Docker
docker system prune -a
```

### **Páginas não carregam**
```bash
# Reiniciar containers
docker-compose restart

# Verificar logs
docker-compose logs nginx php-medoo
```

---

## 🎯 Pronto para Usar!

Se os comandos acima funcionaram, você tem:

✅ **Sistema rodando**  
✅ **Banco configurado**  
✅ **Interface acessível**  
✅ **Funcionalidades operacionais**  

**Próximo passo**: Explore o sistema criando tarefas e categorias! 🎉 