FROM php:8.2-fpm

# Argumentos do build
ARG user=cardapy
ARG uid=1000

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
    nginx \
    supervisor \
    && docker-php-ext-configure zip \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Limpar cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Criar usuário do sistema para rodar Composer e Artisan
RUN useradd -G www-data,root -u $uid -d /home/$user $user
RUN mkdir -p /home/$user/.composer && \
    chown -R $user:$user /home/$user

# Instalar Node.js e npm
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs

# Configurar diretório de trabalho
WORKDIR /var/www

# Copiar arquivos da aplicação
COPY --chown=$user:$user . /var/www

# Instalar dependências do PHP
USER $user
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Instalar dependências do Node.js e compilar assets
RUN npm install && npm run build

# Voltar para root para configurações finais
USER root

# Configurar permissões
RUN chown -R $user:www-data /var/www \
    && chmod -R 755 /var/www \
    && chmod -R 775 /var/www/storage \
    && chmod -R 775 /var/www/bootstrap/cache

# Configurar Supervisor
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Configurar PHP
COPY docker/php/local.ini /usr/local/etc/php/conf.d/local.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Expor porta
EXPOSE 9000

# Script de inicialização
COPY docker/entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/entrypoint.sh

# Definir usuário padrão
USER $user

# Comando de inicialização
ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"] 