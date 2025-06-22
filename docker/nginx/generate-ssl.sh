#!/bin/sh

# Gerar certificados SSL auto-assinados
echo "🔐 Gerando certificados SSL..."

# Obter o diretório do script
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
SSL_DIR="$SCRIPT_DIR/ssl"

# Criar diretório SSL se não existir
mkdir -p "$SSL_DIR"

# Gerar certificado para projetofluentpdo.test
openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
    -keyout "$SSL_DIR/projetofluentpdo.test.key" \
    -out "$SSL_DIR/projetofluentpdo.test.crt" \
    -subj "/C=BR/ST=SP/L=São Paulo/O=TaskManager/OU=Development/CN=projetofluentpdo.test"

# Gerar certificado para projetomedoo.test
openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
    -keyout "$SSL_DIR/projetomedoo.test.key" \
    -out "$SSL_DIR/projetomedoo.test.crt" \
    -subj "/C=BR/ST=SP/L=São Paulo/O=TaskManager/OU=Development/CN=projetomedoo.test"

# Definir permissões
chmod 600 "$SSL_DIR"/*.key
chmod 644 "$SSL_DIR"/*.crt

echo "✅ Certificados SSL gerados com sucesso em: $SSL_DIR"
echo "📋 Certificados criados:"
ls -la "$SSL_DIR"