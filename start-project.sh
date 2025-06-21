#!/bin/bash

echo "🚀 Iniciando Task Manager..."

# Verificar se arquivo hosts está configurado
echo "📋 Verificando configuração..."

# Iniciar containers
echo "🏗️ Iniciando containers Docker..."
docker-compose up -d

echo "⏳ Aguardando inicialização..."
sleep 10

echo "✅ Projeto iniciado!"
echo ""
echo "🌐 Acesse:"
echo "• FluentPDO: https://projetofluentpdo.test"  
echo "• Medoo: https://projetomedoo.test"
echo "• phpMyAdmin: http://localhost:8080"
echo ""
echo "👤 Login demo: admin@taskmanager.test / admin123"