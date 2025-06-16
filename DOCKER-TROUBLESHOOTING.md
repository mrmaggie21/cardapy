# 🔧 Docker Build Troubleshooting - Cardapy

Este guia ajuda a resolver problemas comuns durante o build do Docker.

## 🚨 Erro: Composer Install Failed

### Problema
```
=> ERROR [stage-0 15/23] RUN composer install --no-dev --no-scripts --no-autoloader --optimize-autoloader --prefer-dist
```

### Possíveis Causas
1. **Arquivo composer.lock ausente**
2. **Dependências incompatíveis**
3. **Problemas de rede**
4. **Memória insuficiente**

### Soluções

#### 1. Usar Dockerfile Simplificado
```bash
# Use o Dockerfile simplificado que tem fallbacks
docker build -f Dockerfile.simple -t cardapy:latest .
```

#### 2. Gerar composer.lock
```bash
# Execute localmente para gerar o composer.lock
composer install
git add composer.lock
git commit -m "Add composer.lock"
```

#### 3. Build com Mais Memória
```bash
# Aumentar memória disponível para Docker
docker build --memory=2g -t cardapy:latest .
```

#### 4. Build sem Cache
```bash
# Build limpo sem cache
docker build --no-cache -t cardapy:latest .
```

## 🚨 Erro: NPM Install Failed

### Problema
```
=> ERROR [stage-0 16/23] RUN npm install --only=production
```

### Soluções

#### 1. Verificar package.json
```bash
# Verificar se package.json está válido
npm install --dry-run
```

#### 2. Limpar Cache NPM
```bash
# No Dockerfile, adicionar limpeza de cache
RUN npm cache clean --force
```

#### 3. Usar Yarn (alternativa)
```dockerfile
# Substituir npm por yarn
RUN apk add --no-cache yarn
RUN yarn install --production
```

## 🚨 Erro: Asset Compilation Failed

### Problema
```
=> ERROR [stage-0 17/23] RUN npm run build
```

### Soluções

#### 1. Verificar Scripts
```json
// Verificar se existe em package.json
{
  "scripts": {
    "build": "vite build",
    "production": "vite build"
  }
}
```

#### 2. Pular Compilação de Assets
```dockerfile
# Usar fallback que não falha
RUN npm run build || echo "Asset compilation skipped"
```

## 🚨 Erro: Permission Denied

### Problema
```
=> ERROR [stage-0 20/23] RUN chown -R cardapy:www-data /var/www/html
```

### Soluções

#### 1. Ajustar UIDs
```bash
# Build com UID do usuário atual
docker build --build-arg uid=$(id -u) --build-arg gid=$(id -g) -t cardapy:latest .
```

#### 2. Usar Root Temporariamente
```dockerfile
# Executar como root e depois mudar
USER root
RUN chown -R cardapy:www-data /var/www/html
USER cardapy
```

## 🚨 Erro: Network/DNS Issues

### Problema
```
=> ERROR [stage-0 5/23] RUN apk add --no-cache bash curl git
```

### Soluções

#### 1. Configurar DNS
```bash
# Build com DNS personalizado
docker build --dns=8.8.8.8 -t cardapy:latest .
```

#### 2. Usar Proxy
```bash
# Se estiver atrás de proxy corporativo
docker build --build-arg HTTP_PROXY=http://proxy:8080 -t cardapy:latest .
```

## 🛠️ Scripts de Diagnóstico

### 1. Teste de Build
```bash
# Testar build principal
.\test-build.ps1 -DockerfileType main

# Testar build simplificado
.\test-build.ps1 -DockerfileType simple
```

### 2. Verificar Dependências
```bash
# Verificar composer.json
composer validate

# Verificar package.json
npm audit
```

### 3. Limpar Ambiente
```bash
# Limpar tudo do Docker
docker system prune -a -f
docker builder prune -a -f
```

## 🔍 Debug Avançado

### 1. Build Interativo
```bash
# Parar no ponto de erro
docker build --target=debug -t cardapy:debug .
docker run -it cardapy:debug /bin/bash
```

### 2. Logs Detalhados
```bash
# Build com logs completos
docker build --progress=plain -t cardapy:latest . 2>&1 | tee build.log
```

### 3. Verificar Camadas
```bash
# Analisar camadas da imagem
docker history cardapy:latest
```

## 📋 Checklist de Pré-Build

- [ ] Docker Desktop está rodando
- [ ] Memória suficiente (>2GB)
- [ ] Espaço em disco (>5GB)
- [ ] Conexão com internet estável
- [ ] Arquivos necessários existem:
  - [ ] composer.json
  - [ ] package.json
  - [ ] env.example
  - [ ] Arquivos docker/

## 🚀 Builds Alternativos

### 1. Build Mínimo (Apenas PHP)
```dockerfile
FROM php:8.2-fpm-alpine
RUN docker-php-ext-install pdo_mysql
COPY . /var/www/html
WORKDIR /var/www/html
EXPOSE 9000
CMD ["php-fpm"]
```

### 2. Build com Multi-Stage
```dockerfile
# Stage 1: Dependencies
FROM composer:2.6 as composer
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader

# Stage 2: Assets
FROM node:18-alpine as assets
COPY package.json ./
RUN npm install
COPY . .
RUN npm run build

# Stage 3: Final
FROM php:8.2-fpm-alpine
COPY --from=composer /app/vendor ./vendor
COPY --from=assets /app/public ./public
```

## 📞 Suporte

Se os problemas persistirem:

1. **Verifique os logs**: `docker build --progress=plain`
2. **Use o Dockerfile.simple**: Versão com fallbacks
3. **Teste localmente**: Instale dependências manualmente
4. **Reporte o bug**: Com logs completos

---

**Dica**: Sempre use o `Dockerfile.simple` para desenvolvimento rápido e o `Dockerfile` principal para produção. 