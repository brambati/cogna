#!/bin/sh

echo "🚀 Iniciando Nginx com SSL..."

# Gerar certificados SSL se não existirem
if [ ! -f /etc/nginx/ssl/projetofluentpdo.test.crt ] || [ ! -f /etc/nginx/ssl/projetomedoo.test.crt ]; then
    echo "📋 Certificados não encontrados, gerando..."
    /generate-ssl.sh
fi

# Verificar se os certificados foram criados
if [ -f /etc/nginx/ssl/projetofluentpdo.test.crt ] && [ -f /etc/nginx/ssl/projetomedoo.test.crt ]; then
    echo "✅ Certificados SSL prontos!"
else
    echo "❌ Erro ao gerar certificados SSL"
    exit 1
fi

# Testar configuração do nginx
nginx -t

if [ $? -eq 0 ]; then
    echo "✅ Configuração do Nginx válida!"
    # Iniciar nginx
    exec "$@"
else
    echo "❌ Erro na configuração do Nginx"
    exit 1
fi