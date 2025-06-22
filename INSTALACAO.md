# 🚀 Guia de Instalação - Sistema de Tarefas

## 📋 Índice Rápido
1. [Pré-requisitos](#1-pré-requisitos)
2. [Download e Setup](#2-download-e-setup)
3. [Configuração Docker](#3-configuração-docker)
4. [Primeiro Acesso](#4-primeiro-acesso)
5. [Verificação](#5-verificação)
6. [Troubleshooting](#6-troubleshooting)

---

## 1. 📋 Pré-requisitos

### **Sistema Operacional**
- ✅ Windows 10/11 com WSL2
- ✅ macOS 10.14+
- ✅ Linux (Ubuntu 18.04+, CentOS 7+, etc.)

### **Software Necessário**
```bash
# Verificar se Docker está instalado
docker --version
# Deve retornar: Docker version 20.10+

# Verificar Docker Compose
docker-compose --version
# Deve retornar: Docker Compose version 2.0+
```

### **Instalar Docker (se necessário)**

#### **Windows**
1. Baixar Docker Desktop: https://www.docker.com/products/docker-desktop
2. Instalar e reiniciar
3. Habilitar WSL2 se solicitado

#### **macOS**
1. Baixar Docker Desktop: https://www.docker.com/products/docker-desktop
2. Instalar e reiniciar

#### **Linux (Ubuntu/Debian)**
```bash
# Atualizar sistema
sudo apt update

# Instalar Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh

# Adicionar usuário ao grupo docker
sudo usermod -aG docker $USER

# Instalar Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

# Reiniciar sessão
newgrp docker
```

### **Portas Necessárias**
Verificar se estas portas estão livres:
```bash
# Verificar portas em uso (Linux/Mac)
netstat -tulpn | grep -E ':(80|443|3306|8080)'

# Windows
netstat -an | findstr -E ":(80|443|3306|8080)"
```

- **80**: Nginx HTTP
- **443**: Nginx HTTPS  
- **3306**: MySQL
- **8080**: phpMyAdmin

---

## 2. 📁 Download e Setup

### **Opção A: Git Clone**
```bash
# Clone do repositório
git clone <URL_DO_REPOSITORIO>
cd cogna
```

### **Opção B: Download ZIP**
1. Baixar ZIP do projeto
2. Extrair para pasta `cogna`
3. Abrir terminal na pasta

### **Verificar Estrutura**
```bash
# Deve mostrar esta estrutura:
ls -la
# medoo/
# fluentpdo/
# docker/
# docker-compose.yml
# README.md
```

---

## 3. 🐳 Configuração Docker

### **Configurar Hosts (Recomendado)**

#### **Windows**
1. Abrir Bloco de Notas como Administrador
2. Abrir: `C:\Windows\System32\drivers\etc\hosts`
3. Adicionar no final:
```
127.0.0.1 projetomedoo.test
127.0.0.1 projetofluentpdo.test
```

#### **macOS/Linux**
```bash
# Editar arquivo hosts
sudo nano /etc/hosts

# Adicionar estas linhas:
127.0.0.1 projetomedoo.test
127.0.0.1 projetofluentpdo.test
```

### **Iniciar Sistema**
```bash
# Navegar para pasta do projeto
cd cogna

# Iniciar todos os containers
docker-compose up -d

# Aguardar inicialização (primeira vez demora mais)
echo "Aguardando MySQL inicializar..."
docker-compose logs -f mysql
```

**Aguardar até ver**: `[Server] /usr/sbin/mysqld: ready for connections`

### **Verificar Containers**
```bash
# Ver status dos containers
docker-compose ps

# Deve mostrar todos como "Up":
# taskmanager_nginx        Up    0.0.0.0:80->80/tcp, 0.0.0.0:443->443/tcp
# taskmanager_php_medoo    Up    9000/tcp
# taskmanager_php_fluentpdo Up   9000/tcp  
# taskmanager_mysql        Up    0.0.0.0:3306->3306/tcp
# taskmanager_phpmyadmin   Up    0.0.0.0:8080->80/tcp
```

---

## 4. 🎯 Primeiro Acesso

### **URLs de Acesso**

#### **Se configurou hosts:**
- 🔵 **Medoo**: http://projetomedoo.test
- 🟡 **FluentPDO**: http://projetofluentpdo.test

#### **Se NÃO configurou hosts:**
- 🔵 **Medoo**: http://localhost
- 🟡 **FluentPDO**: http://localhost (alterar URL manualmente)

#### **Outros serviços:**
- 🗄️ **phpMyAdmin**: http://localhost:8080

### **Credenciais Padrão**
```
Usuário: admin
Senha: admin123
```

### **Primeiros Passos**
1. **Acesse** um dos sistemas (Medoo ou FluentPDO)
2. **Faça login** com as credenciais padrão
3. **Crie uma categoria** (ex: "Trabalho" com cor azul)
4. **Adicione uma tarefa** na categoria criada
5. **Teste o checkbox** para marcar como concluída
6. **Veja as estatísticas** sendo atualizadas

---

## 5. ✅ Verificação

### **Checklist de Funcionamento**

#### **🐳 Docker**
```bash
# Todos containers rodando
docker-compose ps | grep Up

# Sem erros nos logs
docker-compose logs --tail=50
```

#### **🌐 Web**
- [ ] Medoo carrega sem erro
- [ ] FluentPDO carrega sem erro
- [ ] Login funciona
- [ ] Dashboard exibe estatísticas
- [ ] phpMyAdmin conecta

#### **📋 Funcionalidades**
- [ ] Criar tarefa funciona
- [ ] Checkbox marca/desmarca tarefa
- [ ] Filtros funcionam
- [ ] Categorias podem ser criadas
- [ ] Estatísticas se atualizam

#### **🔧 Banco de Dados**
```bash
# Conectar no MySQL
docker-compose exec mysql mysql -u taskuser -p
# Senha: taskpass

# Verificar tabelas
USE taskmanager;
SHOW TABLES;
# Deve mostrar: tasks, task_categories, users

# Sair
EXIT;
```

---

## 6. 🛠️ Troubleshooting

### **❌ Container não inicia**

#### **Erro de porta ocupada**
```bash
# Identificar processo usando a porta
sudo lsof -i :80
# ou
sudo netstat -tulpn | grep :80

# Parar processo ou alterar porta no docker-compose.yml
```

#### **Erro de memória**
```bash
# Verificar uso de memória
docker stats

# Limpar containers não utilizados
docker system prune -a
```

### **❌ MySQL não conecta**

#### **Aguardar inicialização**
```bash
# Ver logs do MySQL
docker-compose logs -f mysql

# Aguardar mensagem: "ready for connections"
```

#### **Reset do banco**
```bash
# ATENÇÃO: Apaga todos os dados
docker-compose down -v
docker-compose up -d
```

### **❌ Páginas não carregam**

#### **Verificar Nginx**
```bash
# Ver logs do Nginx
docker-compose logs nginx

# Reiniciar Nginx
docker-compose restart nginx
```

#### **Verificar PHP**
```bash
# Ver logs do PHP
docker-compose logs php-medoo

# Acessar container PHP
docker-compose exec php-medoo bash
```

### **❌ Permissões (Linux/Mac)**
```bash
# Ajustar permissões
sudo chown -R $USER:$USER ./medoo ./fluentpdo
chmod -R 755 ./medoo ./fluentpdo
```

### **🔄 Reset Completo**
```bash
# ATENÇÃO: Remove TODOS os dados
docker-compose down -v
docker system prune -a
docker-compose up -d
```

---

## 📞 Suporte

### **Logs Importantes**
```bash
# Ver todos os logs
docker-compose logs

# Logs específicos
docker-compose logs nginx
docker-compose logs mysql
docker-compose logs php-medoo
docker-compose logs php-fluentpdo
```

### **Informações do Sistema**
```bash
# Versão do Docker
docker --version
docker-compose --version

# Status dos containers
docker-compose ps

# Uso de recursos
docker stats

# Espaço em disco
docker system df
```

### **Arquivos de Configuração**
- **Docker**: `docker-compose.yml`
- **Nginx**: `docker/nginx/nginx.conf`
- **PHP**: `docker/php/conf.d/custom.ini`
- **MySQL**: `docker/mysql/init/01-create-tables.sql`

---

## 🎉 Instalação Concluída!

Se todos os passos foram seguidos corretamente, você deve ter:

✅ **Sistema rodando** em containers Docker  
✅ **Medoo e FluentPDO** funcionando  
✅ **Banco de dados** configurado  
✅ **Interface web** acessível  
✅ **Funcionalidades** operacionais  

**Próximos passos**: Explore o sistema, crie tarefas e aproveite! 🚀 