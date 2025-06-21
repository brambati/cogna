#!/bin/bash

# Script de configuração do Task Manager
# Teste de Desenvolvedor FullStack PHP

echo "🚀 Configurando Task Manager - Teste FullStack PHP"
echo "================================================="

# Verificar se Docker está instalado
if ! command -v docker &> /dev/null; then
    echo "❌ Docker não está instalado. Por favor instale o Docker Desktop."
    exit 1
fi

# Verificar se Docker Compose está disponível
if ! command -v docker-compose &> /dev/null; then
    echo "❌ Docker Compose não está disponível."
    exit 1
fi

echo "✅ Docker encontrado"

# Parar containers existentes se estiverem rodando
echo "🛑 Parando containers existentes..."
docker-compose down 2>/dev/null

# Construir e iniciar containers
echo "🏗️ Construindo e iniciando containers..."
docker-compose up -d --build

# Aguardar containers iniciarem
echo "⏳ Aguardando containers iniciarem..."
sleep 30

# Verificar se containers estão rodando
echo "📋 Verificando status dos containers..."
docker-compose ps

# Instalar dependências PHP
echo "📦 Instalando dependências PHP..."

echo "   - FluentPDO..."
docker exec taskmanager_php_fluentpdo composer install --no-dev --optimize-autoloader

echo "   - Medoo..."
docker exec taskmanager_php_medoo composer install --no-dev --optimize-autoloader

# Verificar se certificados SSL foram gerados
echo "🔐 Verificando certificados SSL..."
if docker exec taskmanager_nginx test -f /etc/nginx/ssl/projetofluentpdo.test.crt; then
    echo "✅ Certificados SSL gerados com sucesso"
else
    echo "⚠️ Gerando certificados SSL..."
    docker exec taskmanager_nginx /generate-ssl.sh
fi

# Testar conexões
echo "🌐 Testando conexões..."

if curl -k -s https://projetofluentpdo.test > /dev/null; then
    echo "✅ FluentPDO: https://projetofluentpdo.test"
else
    echo "❌ Erro ao acessar FluentPDO"
fi

if curl -k -s https://projetomedoo.test > /dev/null; then
    echo "✅ Medoo: https://projetomedoo.test"
else
    echo "❌ Erro ao acessar Medoo"
fi

if curl -s http://localhost:8080 > /dev/null; then
    echo "✅ phpMyAdmin: http://localhost:8080"
else
    echo "❌ Erro ao acessar phpMyAdmin"
fi

echo ""
echo "🎉 Configuração concluída!"
echo "================================================="
echo ""
echo "📋 Informações de Acesso:"
echo ""
echo "🌐 Aplicações Web:"
echo "   • FluentPDO: https://projetofluentpdo.test"
echo "   • Medoo:     https://projetomedoo.test"
echo "   • phpMyAdmin: http://localhost:8080"
echo ""
echo "👤 Credenciais de Demo:"
echo "   • Email: admin@taskmanager.test"
echo "   • Senha: admin123"
echo ""
echo "🗄️ Banco de Dados:"
echo "   • Host: localhost:3306"
echo "   • Usuário: taskuser"
echo "   • Senha: taskpass"
echo "   • Banco: taskmanager"
echo ""
echo "⚠️ Importante:"
echo "   • Adicione os domínios ao arquivo hosts:"
echo "     127.0.0.1 projetofluentpdo.test projetomedoo.test"
echo "   • Aceite os certificados SSL autoassinados no navegador"
echo ""
echo "📖 Para mais informações, consulte o README.md"
echo ""

# Verificar se arquivo hosts precisa ser configurado
if ! grep -q "projetofluentpdo.test" /etc/hosts 2>/dev/null; then
    echo "⚠️ ATENÇÃO: Configure o arquivo hosts manualmente:"
    echo ""
    if [[ "$OSTYPE" == "msys" || "$OSTYPE" == "win32" ]]; then
        echo "Windows: C:\\Windows\\System32\\drivers\\etc\\hosts"
    else
        echo "Linux/Mac: /etc/hosts"
    fi
    echo ""
    echo "Adicione estas linhas:"
    echo "127.0.0.1 projetofluentpdo.test"
    echo "127.0.0.1 projetomedoo.test"
    echo ""
fi