# 🐳 Docker Configuration - Cardapy

Este documento descreve a configuração Docker otimizada para o **Cardapy - Sistema de Cardápio Digital Multi-Tenant**.

## 📋 Visão Geral

O projeto utiliza uma arquitetura containerizada com:
- **PHP 8.2 FPM Alpine** - Base otimizada e leve
- **Nginx** - Servidor web de alta performance
- **MySQL 8.0** - Banco de dados principal
- **Redis 7** - Cache e sessões
- **Supervisor** - Gerenciamento de processos

## 🏗️ Estrutura do Dockerfile

### Características Principais

- ✅ **Multi-stage build** otimizado
- ✅ **Alpine Linux** para menor tamanho
- ✅ **OPcache + JIT** habilitado
- ✅ **Preload** de classes Laravel
- ✅ **Health checks** configurados
- ✅ **Supervisor** para gerenciar processos
- ✅ **Nginx** integrado
- ✅ **Permissões** de segurança

### Extensões PHP Instaladas

```
- pdo_mysql, mysqli (MySQL)
- redis (Redis)
- gd, exif (Imagens)
- zip, xml, soap (Utilitários)
- mbstring, intl (Internacionalização)
- opcache (Performance)
- bcmath, pcntl (Matemática/Processos)
```

## 🚀 Como Usar

### 1. Build da Imagem

```bash
# Build básico
docker build -t cardapy:latest .

# Build com argumentos personalizados
docker build \
  --build-arg user=cardapy \
  --build-arg uid=1000 \
  --build-arg gid=1000 \
  -t cardapy:latest .
```

### 2. Executar com Docker Compose

```bash
# Desenvolvimento
docker-compose up -d

# Produção
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d
```

### 3. Script Automatizado

```powershell
# Desenvolvimento
.\build-and-deploy.ps1 -Environment development

# Produção
.\build-and-deploy.ps1 -Environment production

# Com opções
.\build-and-deploy.ps1 -Environment development -CleanBuild -SkipTests
```

## 📁 Estrutura de Arquivos Docker

```
docker/
├── nginx/
│   ├── nginx.conf          # Configuração principal do Nginx
│   └── default.conf        # Virtual host da aplicação
├── php/
│   ├── php.ini            # Configurações PHP personalizadas
│   └── opcache.ini        # Configurações OPcache otimizadas
├── mysql/
│   └── my.cnf             # Configurações MySQL otimizadas
├── redis/
│   └── redis.conf         # Configurações Redis otimizadas
├── supervisor/
│   └── supervisord.conf   # Configuração do Supervisor
└── entrypoint.sh          # Script de inicialização
```

## ⚙️ Configurações Importantes

### PHP (php.ini)
- `memory_limit = 512M`
- `upload_max_filesize = 64M`
- `max_execution_time = 300`
- `timezone = America/Sao_Paulo`

### OPcache (opcache.ini)
- `opcache.memory_consumption = 256M`
- `opcache.jit_buffer_size = 256M`
- `opcache.preload = /var/www/html/preload.php`

### Nginx
- Compressão Gzip habilitada
- Cache de arquivos estáticos
- Headers de segurança
- Health check endpoint

### MySQL
- Character set UTF8MB4
- InnoDB otimizado
- Query cache habilitado
- Timezone configurado

### Redis
- Persistência AOF + RDB
- Política de memória LRU
- Configurações de performance

## 🔧 Variáveis de Ambiente

### Aplicação
```env
APP_NAME=Cardapy
APP_ENV=production
APP_DEBUG=false
APP_URL=https://seu-dominio.com
```

### Banco de Dados
```env
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=cardapy_db
DB_USERNAME=cardapy_user
DB_PASSWORD=sua_senha_segura
```

### Cache/Sessão
```env
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
REDIS_HOST=redis
REDIS_PORT=6379
```

### Mercado Pago
```env
MERCADOPAGO_ACCESS_TOKEN=seu_token
MERCADOPAGO_PUBLIC_KEY=sua_chave_publica
```

## 🏥 Health Checks

### Aplicação
```bash
curl -f http://localhost/health
```

### MySQL
```bash
mysqladmin ping -h mysql
```

### Redis
```bash
redis-cli -h redis ping
```

## 📊 Monitoramento

### Logs
```bash
# Logs da aplicação
docker-compose logs -f app

# Logs do Nginx
docker-compose exec app tail -f /var/log/nginx/access.log

# Logs do PHP-FPM
docker-compose exec app tail -f /var/log/php-fpm.log
```

### Métricas
- **PHPMyAdmin**: http://localhost:8080
- **Redis Commander**: http://localhost:8081
- **MailHog**: http://localhost:8025

## 🔒 Segurança

### Configurações Implementadas
- ✅ Usuário não-root para aplicação
- ✅ Headers de segurança no Nginx
- ✅ Arquivos sensíveis protegidos
- ✅ PHP expose_php desabilitado
- ✅ MySQL configurações seguras

### Recomendações Adicionais
- 🔐 Use senhas fortes
- 🔐 Configure SSL/TLS
- 🔐 Mantenha imagens atualizadas
- 🔐 Use secrets para produção

## 🚀 Performance

### Otimizações Implementadas
- ⚡ OPcache com JIT habilitado
- ⚡ Preload de classes Laravel
- ⚡ Nginx com compressão
- ⚡ Redis para cache/sessões
- ⚡ MySQL com InnoDB otimizado

### Benchmarks Esperados
- **Tempo de resposta**: < 100ms
- **Throughput**: > 1000 req/s
- **Uso de memória**: ~512MB
- **Tempo de build**: ~5-10min

## 🐛 Troubleshooting

### Problemas Comuns

#### 1. Erro de Permissão
```bash
# Corrigir permissões
docker-compose exec app chown -R cardapy:www-data /var/www/html/storage
```

#### 2. Erro de Conexão MySQL
```bash
# Verificar se MySQL está rodando
docker-compose exec mysql mysqladmin ping

# Verificar logs
docker-compose logs mysql
```

#### 3. Erro de Cache Redis
```bash
# Limpar cache Redis
docker-compose exec redis redis-cli FLUSHALL

# Verificar conexão
docker-compose exec redis redis-cli ping
```

#### 4. Erro de Build
```bash
# Build limpo
docker system prune -f
docker-compose build --no-cache
```

## 📚 Comandos Úteis

### Desenvolvimento
```bash
# Entrar no container
docker-compose exec app bash

# Executar Artisan
docker-compose exec app php artisan migrate

# Ver logs em tempo real
docker-compose logs -f

# Reiniciar serviços
docker-compose restart
```

### Produção
```bash
# Deploy com zero downtime
docker-compose up -d --no-deps app

# Backup do banco
docker-compose exec mysql mysqldump -u root -p cardapy_db > backup.sql

# Monitorar recursos
docker stats
```

## 📞 Suporte

Para dúvidas ou problemas:
1. Verifique os logs: `docker-compose logs`
2. Consulte este README
3. Verifique a documentação do Laravel
4. Entre em contato com a equipe de desenvolvimento

---

**Cardapy** - Sistema de Cardápio Digital Multi-Tenant  
Desenvolvido com ❤️ usando Laravel, Docker e as melhores práticas de DevOps. 