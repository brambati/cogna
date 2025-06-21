FROM php:8.2-fpm

# Instalar dependências do sistema
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Instalar extensões PHP
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configurar usuário
RUN groupadd -g 1000 www
RUN useradd -u 1000 -ms /bin/bash -g www www

# Copiar configuração PHP personalizada
COPY conf.d/custom.ini /usr/local/etc/php/conf.d/custom.ini

# Definir diretório de trabalho
WORKDIR /var/www/html

# Alterar propriedade dos arquivos
RUN chown -R www:www /var/www/html

# Mudar para usuário www
USER www

# Expor porta 9000
EXPOSE 9000

CMD ["php-fpm"]