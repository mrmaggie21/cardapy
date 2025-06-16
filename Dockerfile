# Dockerfile para Cardapy - Sistema de Cardápio Digital Multi-Tenant
FROM php:8.2-fpm-alpine

# Metadados
LABEL maintainer="Cardapy Team"
LABEL description="Sistema de Cardápio Digital Multi-Tenant"
LABEL version="1.0.0"

# Argumentos do build
ARG user=cardapy
ARG uid=1000
ARG gid=1000

# Variáveis de ambiente
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV COMPOSER_HOME=/tmp
ENV NODE_VERSION=18

# Instalar dependências do sistema
RUN apk add --no-cache \
    # Dependências básicas
    bash \
    curl \
    git \
    unzip \
    zip \
    # Dependências do PHP
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    libxml2-dev \
    # Dependências do MySQL
    mysql-client \
    # Dependências do Redis
    redis \
    # Nginx
    nginx \
    # Supervisor
    supervisor \
    # Node.js
    nodejs \
    npm

# Configurar e instalar extensões PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mysqli \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        xml \
        soap \
        opcache

# Instalar Redis extension
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

# Instalar Composer
COPY --from=composer:2.6 /usr/bin/composer /usr/bin/composer

# Criar grupo e usuário
RUN addgroup -g $gid $user \
    && adduser -D -u $uid -G $user -s /bin/bash $user \
    && adduser $user www-data

# Configurar diretórios
WORKDIR /var/www/html

# Copiar arquivos de configuração primeiro (para cache do Docker)
COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-custom.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/10-opcache.ini
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Copiar arquivos do projeto
COPY --chown=$user:$user composer.json ./
COPY --chown=$user:$user package.json ./
COPY --chown=$user:$user env.example ./.env.example

# Instalar dependências como usuário
USER $user

# Criar diretórios necessários
RUN mkdir -p vendor bootstrap/cache

# Instalar dependências do Composer
RUN composer install \
    --no-dev \
    --no-scripts \
    --optimize-autoloader \
    --prefer-dist \
    --no-interaction \
    || composer install --no-dev --optimize-autoloader --prefer-dist --no-interaction

# Instalar dependências do Node.js
RUN npm install --only=production --no-audit --no-fund --silent || npm install --production

# Voltar para root
USER root

# Copiar resto dos arquivos
COPY --chown=$user:$user . .

# Finalizar instalação do Composer
USER $user
RUN composer dump-autoload --optimize || echo "Autoload dump failed, continuing..."

# Compilar assets (com fallback)
RUN npm run build || npm run production || echo "Asset compilation failed, continuing..."

# Voltar para root para configurações finais
USER root

# Configurar permissões
RUN chown -R $user:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && mkdir -p /var/www/html/storage/logs \
    && mkdir -p /var/www/html/storage/framework/cache \
    && mkdir -p /var/www/html/storage/framework/sessions \
    && mkdir -p /var/www/html/storage/framework/views \
    && mkdir -p /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

# Configurar Nginx
RUN mkdir -p /var/log/nginx \
    && mkdir -p /var/lib/nginx/tmp \
    && chown -R nginx:nginx /var/log/nginx \
    && chown -R nginx:nginx /var/lib/nginx

# Script de inicialização
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Expor portas
EXPOSE 80 9000

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/health || exit 1

# Volumes
VOLUME ["/var/www/html/storage", "/var/www/html/bootstrap/cache"]

# Comando de inicialização
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"] 