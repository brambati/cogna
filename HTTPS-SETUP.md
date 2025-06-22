# 🔐 Configuração HTTPS - Guia Completo

## 📋 Pré-requisitos

Antes de começar, certifique-se de ter:
- Docker e Docker Compose instalados
- Git instalado
- Projeto clonado e funcionando

## 🚀 Instalação Rápida

### 1. **Gerar Certificados SSL**

Execute o script para gerar os certificados SSL auto-assinados:

```bash
# Navegar até o diretório do projeto
cd cogna

# Executar o script de geração de certificados
bash docker/nginx/generate-ssl.sh
```

### 2. **Configurar Hosts Locais**

Adicione os domínios no arquivo hosts do seu sistema:

**Windows:**
- Abra o Notepad como Administrador
- Abra o arquivo: `C:\Windows\System32\drivers\etc\hosts`
- Adicione as linhas:
```
127.0.0.1 projetofluentpdo.test
127.0.0.1 projetomedoo.test
```

**Linux/Mac:**
```bash
sudo nano /etc/hosts
```
Adicione as linhas:
```
127.0.0.1 projetofluentpdo.test
127.0.0.1 projetomedoo.test
```

### 3. **Reiniciar os Containers**

```bash
# Parar os containers
docker-compose down

# Subir novamente com as configurações SSL
docker-compose up -d
```

### 4. **Testar a Configuração**

Aguarde alguns segundos e teste os links:

**URLs HTTPS (Seguras):**
- https://projetofluentpdo.test
- https://projetomedoo.test

**URLs HTTP (Redirecionam automaticamente):**
- http://projetofluentpdo.test → https://projetofluentpdo.test
- http://projetomedoo.test → https://projetomedoo.test

## ⚠️ Aviso de Certificado

Como utilizamos certificados auto-assinados para desenvolvimento local, seu navegador mostrará um aviso de segurança. Para prosseguir:

1. Clique em **"Avançado"** ou **"Advanced"**
2. Clique em **"Prosseguir para o site"** ou **"Proceed to site"**
3. O site carregará normalmente com HTTPS

## 🔧 Testes de Verificação

### Teste via Terminal

```bash
# Testar HTTPS
curl -k -I https://projetomedoo.test
curl -k -I https://projetofluentpdo.test

# Testar redirecionamento HTTP → HTTPS
curl -I http://projetomedoo.test
curl -I http://projetofluentpdo.test
```

### Teste no Navegador

1. Acesse qualquer URL HTTP dos projetos
2. Verifique se redireciona automaticamente para HTTPS
3. Confirme que o cadeado de segurança aparece na barra de endereços

## 🔄 Regenerar Certificados

Se os certificados expirarem (válidos por 365 dias) ou houver problemas:

```bash
# Regenerar certificados
bash docker/nginx/generate-ssl.sh

# Reiniciar nginx
docker-compose restart nginx
```

## 📂 Estrutura dos Arquivos SSL

```
docker/nginx/ssl/
├── projetofluentpdo.test.crt
├── projetofluentpdo.test.key
├── projetomedoo.test.crt
├── projetomedoo.test.key
└── ssl.conf
```

## 🛠️ Solução de Problemas

### Problema: "Certificados não encontrados"

**Solução:**
```bash
# Verificar se os certificados existem
ls -la docker/nginx/ssl/

# Se não existirem, gerar novamente
bash docker/nginx/generate-ssl.sh
```

### Problema: "Site não carrega"

**Solução:**
```bash
# Verificar se os containers estão rodando
docker-compose ps

# Verificar logs do nginx
docker-compose logs nginx

# Reiniciar containers
docker-compose restart
```

### Problema: "Hosts não funcionam"

**Solução:**
1. Verificar se os domínios foram adicionados corretamente no arquivo hosts
2. Reiniciar o navegador
3. Limpar cache DNS:
   - **Windows:** `ipconfig /flushdns`
   - **Linux/Mac:** `sudo dscacheutil -flushcache`

## 🔒 Recursos de Segurança Implementados

- **SSL/TLS**: Criptografia de dados em trânsito
- **HSTS**: Força conexões HTTPS por 1 ano
- **Headers de Segurança**: Proteção contra XSS, clickjacking
- **Redirecionamento Automático**: HTTP → HTTPS (301)
- **HTTP/2**: Protocolo otimizado para HTTPS

## 📝 Comandos Úteis

```bash
# Verificar status dos containers
docker-compose ps

# Ver logs em tempo real
docker-compose logs -f nginx

# Reiniciar apenas o nginx
docker-compose restart nginx

# Parar todos os containers
docker-compose down

# Subir em modo detached
docker-compose up -d
```

## 🎯 Próximos Passos

Para **produção**, considere:
1. Substituir certificados auto-assinados por certificados válidos (Let's Encrypt)
2. Configurar domínios reais
3. Implementar renovação automática de certificados
4. Configurar logs de segurança específicos

---

**✅ Status:** Configuração HTTPS completamente funcional para desenvolvimento local

**🔗 Links Rápidos:**
- [Projeto Medoo](https://projetomedoo.test)
- [Projeto FluentPDO](https://projetofluentpdo.test)
- [phpMyAdmin](http://localhost:8080) 