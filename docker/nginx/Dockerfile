FROM nginx:alpine

# Instalar OpenSSL para geração de certificados
RUN apk add --no-cache openssl

# Copiar configuração personalizada do nginx
COPY nginx.conf /etc/nginx/nginx.conf

# Criar diretório para SSL
RUN mkdir -p /etc/nginx/ssl

# Copiar script de geração de SSL
COPY generate-ssl.sh /generate-ssl.sh
RUN chmod +x /generate-ssl.sh

# Expor portas
EXPOSE 80 443

# Script de inicialização
COPY docker-entrypoint.sh /docker-entrypoint.sh
RUN chmod +x /docker-entrypoint.sh

ENTRYPOINT ["/docker-entrypoint.sh"]
CMD ["nginx", "-g", "daemon off;"]