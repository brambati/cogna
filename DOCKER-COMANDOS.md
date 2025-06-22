# 🐳 Comandos Docker - Sistema de Tarefas

## 📋 Comandos Essenciais

### **🚀 Inicialização**
```bash
# Iniciar todos os serviços
docker-compose up -d

# Iniciar com rebuild
docker-compose up -d --build

# Iniciar mostrando logs
docker-compose up
```

### **⏹️ Parada**
```bash
# Parar todos os serviços
docker-compose down

# Parar e remover volumes (APAGA DADOS!)
docker-compose down -v

# Parar serviço específico
docker-compose stop nginx
docker-compose stop mysql
```

### **🔄 Restart**
```bash
# Reiniciar todos os serviços
docker-compose restart

# Reiniciar serviço específico
docker-compose restart nginx
docker-compose restart php-medoo
docker-compose restart mysql
```

## 📊 Monitoramento

### **Status dos Containers**
```bash
# Ver status
docker-compose ps

# Ver uso de recursos em tempo real
docker stats

# Ver espaço usado pelo Docker
docker system df
```

### **📋 Logs**
```bash
# Ver logs de todos os serviços
docker-compose logs

# Logs em tempo real
docker-compose logs -f

# Logs de serviço específico
docker-compose logs nginx
docker-compose logs mysql
docker-compose logs php-medoo

# Últimas 50 linhas
docker-compose logs --tail=50

# Logs com timestamp
docker-compose logs -t
```

## 🔧 Acesso aos Containers

### **Shell nos Containers**
```bash
# Acessar container PHP Medoo
docker-compose exec php-medoo bash

# Acessar container PHP FluentPDO
docker-compose exec php-fluentpdo bash

# Acessar container MySQL
docker-compose exec mysql bash

# Acessar container Nginx
docker-compose exec nginx sh
```

### **MySQL**
```bash
# Conectar no MySQL como root
docker-compose exec mysql mysql -u root -p
# Senha: rootpass

# Conectar como usuário da aplicação
docker-compose exec mysql mysql -u taskuser -p
# Senha: taskpass

# Backup do banco
docker-compose exec mysql mysqldump -u root -p taskmanager > backup.sql

# Restaurar backup
docker-compose exec -T mysql mysql -u root -p taskmanager < backup.sql
```

## 🛠️ Desenvolvimento

### **Rebuild Containers**
```bash
# Rebuild todos os containers
docker-compose build

# Rebuild sem cache
docker-compose build --no-cache

# Rebuild serviço específico
docker-compose build php-medoo
docker-compose build nginx
```

### **Atualizar Código**
```bash
# Se mudou código PHP (não precisa rebuild)
docker-compose restart php-medoo
docker-compose restart php-fluentpdo

# Se mudou configuração Nginx
docker-compose restart nginx

# Se mudou docker-compose.yml
docker-compose down
docker-compose up -d
```

### **Instalar Dependências PHP**
```bash
# Instalar dependências Composer (Medoo)
docker-compose exec php-medoo composer install

# Instalar dependências Composer (FluentPDO)
docker-compose exec php-fluentpdo composer install

# Atualizar dependências
docker-compose exec php-medoo composer update
```

## 🧹 Limpeza

### **Limpeza Básica**
```bash
# Remover containers parados
docker container prune

# Remover imagens não utilizadas
docker image prune

# Remover volumes não utilizados
docker volume prune

# Remover redes não utilizadas
docker network prune
```

### **Limpeza Completa**
```bash
# Limpar tudo (CUIDADO!)
docker system prune -a

# Ver espaço liberado
docker system df
```

### **Reset Total do Projeto**
```bash
# ATENÇÃO: Remove TODOS os dados do projeto
docker-compose down -v
docker system prune -a
docker-compose up -d
```

## 🔍 Debug e Diagnóstico

### **Verificar Configurações**
```bash
# Ver configuração do docker-compose
docker-compose config

# Ver variáveis de ambiente
docker-compose exec php-medoo env

# Ver configuração PHP
docker-compose exec php-medoo php -i
```

### **Testar Conectividade**
```bash
# Testar conexão entre containers
docker-compose exec php-medoo ping mysql
docker-compose exec php-medoo ping nginx

# Testar portas
docker-compose exec php-medoo nc -zv mysql 3306
```

### **Arquivos de Log**
```bash
# Logs do Nginx (dentro do container)
docker-compose exec nginx cat /var/log/nginx/access.log
docker-compose exec nginx cat /var/log/nginx/error.log

# Logs do PHP (dentro do container)
docker-compose exec php-medoo cat /var/log/php-fpm.log
```

## 📦 Volumes e Dados

### **Listar Volumes**
```bash
# Ver todos os volumes
docker volume ls

# Ver volumes do projeto
docker volume ls | grep cogna
```

### **Backup de Volumes**
```bash
# Backup do volume MySQL
docker run --rm -v cogna_mysql-data:/data -v $(pwd):/backup ubuntu tar czf /backup/mysql-backup.tar.gz -C /data .

# Restaurar backup
docker run --rm -v cogna_mysql-data:/data -v $(pwd):/backup ubuntu tar xzf /backup/mysql-backup.tar.gz -C /data
```

## 🌐 Rede

### **Listar Redes**
```bash
# Ver redes do Docker
docker network ls

# Ver detalhes da rede do projeto
docker network inspect cogna_task-network
```

### **Teste de Conectividade**
```bash
# Ping entre containers
docker-compose exec php-medoo ping mysql
docker-compose exec php-medoo ping nginx

# Ver IPs dos containers
docker-compose exec php-medoo hostname -i
docker-compose exec mysql hostname -i
```

## 🚨 Troubleshooting

### **Container não Inicia**
```bash
# Ver logs detalhados
docker-compose logs [serviço]

# Verificar configuração
docker-compose config

# Testar manualmente
docker-compose run --rm php-medoo bash
```

### **Porta Ocupada**
```bash
# Ver processos usando porta 80
sudo netstat -tulpn | grep :80

# Matar processo
sudo kill -9 [PID]

# Ou alterar porta no docker-compose.yml
```

### **Problemas de Permissão**
```bash
# Ajustar permissões (Linux/Mac)
sudo chown -R $USER:$USER ./medoo ./fluentpdo
chmod -R 755 ./medoo ./fluentpdo

# Ver permissões dentro do container
docker-compose exec php-medoo ls -la /var/www/html/
```

### **MySQL não Conecta**
```bash
# Ver logs do MySQL
docker-compose logs mysql

# Testar conexão
docker-compose exec mysql mysqladmin ping

# Reset do MySQL
docker-compose down
docker volume rm cogna_mysql-data
docker-compose up -d
```

---

## 📚 Comandos por Cenário

### **🆕 Primeira Instalação**
```bash
docker-compose up -d
docker-compose logs -f mysql  # Aguardar "ready for connections"
```

### **💻 Desenvolvimento Diário**
```bash
# Iniciar trabalho
docker-compose up -d

# Ver logs se algo não funcionar
docker-compose logs -f

# Parar ao final do dia
docker-compose down
```

### **🔧 Mudanças no Código**
```bash
# Apenas PHP alterado
docker-compose restart php-medoo php-fluentpdo

# Configuração alterada
docker-compose down
docker-compose up -d
```

### **🚨 Problemas Gerais**
```bash
# Reset suave
docker-compose restart

# Reset médio
docker-compose down
docker-compose up -d

# Reset total (perde dados)
docker-compose down -v
docker-compose up -d
```

### **📊 Monitoramento**
```bash
# Status geral
docker-compose ps && docker stats --no-stream

# Logs importantes
docker-compose logs --tail=20 nginx mysql
```

---

**💡 Dica**: Salve este arquivo como referência rápida para comandos Docker do projeto! 