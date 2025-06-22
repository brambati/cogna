# ✅ Checklist HTTPS - Configuração Rápida

## 🚀 Configuração Automática (Recomendada)

```bash
# 1. Executar script automático
bash setup-https.sh
```

**✅ O script faz tudo automaticamente!**

---

## 🛠️ Configuração Manual

### ☑️ **1. Gerar Certificados SSL**
```bash
bash docker/nginx/generate-ssl.sh
```

### ☑️ **2. Configurar Arquivo Hosts**

**Windows:**
- Abrir Notepad como Administrador
- Editar: `C:\Windows\System32\drivers\etc\hosts`
- Adicionar:
```
127.0.0.1 projetofluentpdo.test
127.0.0.1 projetomedoo.test
```

**Linux/Mac:**
```bash
sudo nano /etc/hosts
# Adicionar as mesmas linhas
```

### ☑️ **3. Reiniciar Containers**
```bash
docker-compose down
docker-compose up -d
```

### ☑️ **4. Testar Funcionamento**

Aguardar ~30 segundos e testar:
- https://projetomedoo.test
- https://projetofluentpdo.test

---

## 🔍 Verificação Final

### ✅ **Testes Obrigatórios**

- [ ] HTTPS funciona: https://projetomedoo.test
- [ ] HTTPS funciona: https://projetofluentpdo.test  
- [ ] HTTP redireciona para HTTPS automaticamente
- [ ] Cadeado de segurança aparece no navegador
- [ ] Login funciona normalmente via HTTPS

### ✅ **Comandos de Teste**

```bash
# Testar HTTPS
curl -k -I https://projetomedoo.test

# Testar redirecionamento
curl -I http://projetomedoo.test

# Verificar containers
docker-compose ps
```

---

## ⚠️ Problemas Comuns

### **Certificado não confiável**
**✅ Solução:** Clicar em "Avançado" → "Prosseguir para o site"

### **Site não carrega**
```bash
# Verificar containers
docker-compose ps

# Ver logs
docker-compose logs nginx

# Reiniciar
docker-compose restart
```

### **Hosts não funcionam**
```bash
# Windows
ipconfig /flushdns

# Linux/Mac  
sudo dscacheutil -flushcache
```

---

## 📋 Status Final

Quando tudo estiver funcionando:

- ✅ **HTTPS Ativo**: Sites carregam com cadeado verde
- ✅ **Redirecionamento**: HTTP → HTTPS automático  
- ✅ **Funcionalidade**: Login e dashboard funcionando
- ✅ **Containers**: Todos rodando normalmente

**🎉 HTTPS configurado com sucesso!**

---

**📖 Documentação completa:** [HTTPS-SETUP.md](HTTPS-SETUP.md) 