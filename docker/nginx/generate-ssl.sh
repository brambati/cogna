#!/bin/sh

# Gerar certificados SSL auto-assinados
echo "🔐 Gerando certificados SSL..."

# Criar diretório SSL se não existir
mkdir -p /etc/nginx/ssl

# Gerar certificado para projetofluentpdo.test
openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
    -keyout /etc/nginx/ssl/projetofluentpdo.test.key \
    -out /etc/nginx/ssl/projetofluentpdo.test.crt \
    -subj "/C=BR/ST=SP/L=São Paulo/O=TaskManager/OU=Development/CN=projetofluentpdo.test"

# Gerar certificado para projetomedoo.test
openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
    -keyout /etc/nginx/ssl/projetomedoo.test.key \
    -out /etc/nginx/ssl/projetomedoo.test.crt \
    -subj "/C=BR/ST=SP/L=São Paulo/O=TaskManager/OU=Development/CN=projetomedoo.test"

# Definir permissões
chmod 600 /etc/nginx/ssl/*.key
chmod 644 /etc/nginx/ssl/*.crt

echo "✅ Certificados SSL gerados com sucesso!"