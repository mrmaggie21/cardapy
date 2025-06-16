#!/bin/bash
set -e

echo "🚀 Iniciando Cardapy - Sistema de Cardápio Digital Multi-Tenant"

# Função para aguardar serviços
wait_for_service() {
    local host=$1
    local port=$2
    local service=$3
    
    echo "⏳ Aguardando $service ($host:$port)..."
    while ! nc -z $host $port; do
        sleep 1
    done
    echo "✅ $service está disponível!"
}

# Aguardar MySQL se configurado
if [ ! -z "$DB_HOST" ] && [ "$DB_HOST" != "localhost" ]; then
    wait_for_service $DB_HOST ${DB_PORT:-3306} "MySQL"
fi

# Aguardar Redis se configurado
if [ ! -z "$REDIS_HOST" ] && [ "$REDIS_HOST" != "localhost" ]; then
    wait_for_service $REDIS_HOST ${REDIS_PORT:-6379} "Redis"
fi

# Criar diretórios necessários
echo "📁 Criando diretórios necessários..."
mkdir -p /var/log/supervisor
mkdir -p /var/log/nginx
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/storage/framework/{cache,sessions,views}
mkdir -p /var/www/html/bootstrap/cache

# Configurar permissões
echo "🔐 Configurando permissões..."
chown -R cardapy:www-data /var/www/html
chmod -R 755 /var/www/html
chmod -R 775 /var/www/html/storage
chmod -R 775 /var/www/html/bootstrap/cache

# Verificar se o .env existe
if [ ! -f /var/www/html/.env ]; then
    echo "⚠️  Arquivo .env não encontrado, copiando do exemplo..."
    if [ -f /var/www/html/.env.example ]; then
        cp /var/www/html/.env.example /var/www/html/.env
        chown cardapy:www-data /var/www/html/.env
    else
        echo "❌ Arquivo .env.example não encontrado!"
    fi
fi

# Executar comandos Laravel como usuário cardapy
echo "🔧 Executando comandos Laravel..."
cd /var/www/html

# Gerar chave da aplicação se não existir
if ! grep -q "APP_KEY=" .env || grep -q "APP_KEY=$" .env; then
    echo "🔑 Gerando chave da aplicação..."
    sudo -u cardapy php artisan key:generate --force
fi

# Executar migrações se em ambiente de desenvolvimento
if [ "$APP_ENV" = "local" ] || [ "$RUN_MIGRATIONS" = "true" ]; then
    echo "🗄️  Executando migrações..."
    sudo -u cardapy php artisan migrate --force
fi

# Limpar e otimizar cache
echo "🧹 Limpando e otimizando cache..."
sudo -u cardapy php artisan config:clear
sudo -u cardapy php artisan route:clear
sudo -u cardapy php artisan view:clear
sudo -u cardapy php artisan cache:clear

# Otimizar para produção
if [ "$APP_ENV" = "production" ]; then
    echo "⚡ Otimizando para produção..."
    sudo -u cardapy php artisan config:cache
    sudo -u cardapy php artisan route:cache
    sudo -u cardapy php artisan view:cache
fi

# Criar link simbólico para storage
echo "🔗 Criando link simbólico para storage..."
sudo -u cardapy php artisan storage:link || true

# Verificar saúde da aplicação
echo "🏥 Verificando saúde da aplicação..."
if sudo -u cardapy php artisan --version > /dev/null 2>&1; then
    echo "✅ Laravel está funcionando corretamente!"
else
    echo "❌ Erro ao verificar Laravel!"
    exit 1
fi

echo "🎉 Cardapy inicializado com sucesso!"
echo "🌐 Aplicação disponível em: http://localhost"
echo "📊 Health check: http://localhost/health"

# Executar comando passado como argumento
exec "$@" 