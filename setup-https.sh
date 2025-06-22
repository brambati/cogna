#!/bin/bash

echo "🔐 Configuração Automática de HTTPS - Task Manager"
echo "=================================================="

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
SSL_DIR="$SCRIPT_DIR/docker/nginx/ssl"

echo "📂 Diretório do projeto: $SCRIPT_DIR"

echo ""
echo "🔍 Verificando pré-requisitos..."

if ! command -v docker &> /dev/null; then
    echo "❌ Docker não encontrado. Instale o Docker primeiro."
    exit 1
fi

if ! command -v docker-compose &> /dev/null; then
    echo "❌ Docker Compose não encontrado. Instale o Docker Compose primeiro."
    exit 1
fi

echo "✅ Docker e Docker Compose encontrados!"

echo ""
echo "🔧 Gerando certificados SSL..."

if ! bash "$SCRIPT_DIR/docker/nginx/generate-ssl.sh"; then
    echo "❌ Erro ao gerar certificados SSL"
    exit 1
fi

echo ""
echo "📝 Configurando arquivo hosts..."

HOSTS_FILE=""
if [[ "$OSTYPE" == "msys" ]] || [[ "$OSTYPE" == "win32" ]]; then
    HOSTS_FILE="/c/Windows/System32/drivers/etc/hosts"
elif [[ "$OSTYPE" == "linux-gnu"* ]] || [[ "$OSTYPE" == "darwin"* ]]; then
    HOSTS_FILE="/etc/hosts"
else
    echo "⚠️  Sistema operacional não detectado automaticamente."
    echo "📋 Adicione manualmente no arquivo hosts:"
    echo "127.0.0.1 projetofluentpdo.test"
    echo "127.0.0.1 projetomedoo.test"
fi

if [ -n "$HOSTS_FILE" ] && [ -f "$HOSTS_FILE" ]; then
    if ! grep -q "projetofluentpdo.test" "$HOSTS_FILE"; then
        echo "📝 Tentando adicionar entradas no arquivo hosts..."
        echo "⚠️  Pode ser necessário executar como administrador/sudo"
        
        {
            echo "127.0.0.1 projetofluentpdo.test" 
            echo "127.0.0.1 projetomedoo.test"
        } | tee -a "$HOSTS_FILE" 2>/dev/null || {
            echo "❌ Não foi possível modificar o arquivo hosts automaticamente."
            echo "📋 Adicione manualmente as seguintes linhas no arquivo hosts ($HOSTS_FILE):"
            echo "127.0.0.1 projetofluentpdo.test"
            echo "127.0.0.1 projetomedoo.test"
            echo ""
            echo "💡 No Windows: Execute o Notepad como Administrador"
            echo "💡 No Linux/Mac: Execute 'sudo nano /etc/hosts'"
            read -p "Pressione Enter após adicionar as entradas no arquivo hosts..."
        }
    else
        echo "✅ Entradas já existem no arquivo hosts!"
    fi
fi

echo ""
echo "🐳 Parando containers existentes..."
docker-compose down 2>/dev/null || true

echo ""
echo "🚀 Iniciando containers com HTTPS..."
if ! docker-compose up -d; then
    echo "❌ Erro ao iniciar containers"
    exit 1
fi

echo ""
echo "⏳ Aguardando containers iniciarem..."
sleep 10

echo ""
echo "🧪 Testando configuração HTTPS..."

TEST_FAILED=false

echo "📡 Testando projetomedoo.test..."
if curl -k -s -o /dev/null -w "%{http_code}" https://projetomedoo.test | grep -q "200\|30[0-9]"; then
    echo "✅ projetomedoo.test - OK"
else
    echo "❌ projetomedoo.test - FALHOU"
    TEST_FAILED=true
fi

echo "📡 Testando projetofluentpdo.test..."
if curl -k -s -o /dev/null -w "%{http_code}" https://projetofluentpdo.test | grep -q "200\|30[0-9]"; then
    echo "✅ projetofluentpdo.test - OK"
else
    echo "❌ projetofluentpdo.test - FALHOU"
    TEST_FAILED=true
fi

echo ""
echo "=================================================="

if [ "$TEST_FAILED" = false ]; then
    echo "🎉 HTTPS configurado com sucesso!"
    echo ""
    echo "🔗 Links dos projetos:"
    echo "   • Projeto Medoo: https://projetomedoo.test"
    echo "   • Projeto FluentPDO: https://projetofluentpdo.test"
    echo "   • phpMyAdmin: http://localhost:8080"
    echo ""
    echo "⚠️  Aviso: Seu navegador mostrará aviso de certificado auto-assinado"
    echo "   Clique em 'Avançado' → 'Prosseguir para o site'"
    echo ""
    echo "📖 Para mais detalhes, consulte: HTTPS-SETUP.md"
else
    echo "⚠️  Alguns testes falharam. Verifique:"
    echo "   1. Se os containers estão rodando: docker-compose ps"
    echo "   2. Se o arquivo hosts foi configurado corretamente"
    echo "   3. Os logs dos containers: docker-compose logs"
    echo ""
    echo "📖 Consulte HTTPS-SETUP.md para solução de problemas"
fi

echo "==================================================" 