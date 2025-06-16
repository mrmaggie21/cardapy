# Cardapy - Sistema de Cardápio Digital Multi-Tenant

[![Laravel](https://img.shields.io/badge/Laravel-10+-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2+-blue.svg)](https://php.net)
[![Livewire](https://img.shields.io/badge/Livewire-3.0-green.svg)](https://laravel-livewire.com)
[![TailwindCSS](https://img.shields.io/badge/TailwindCSS-3.0-teal.svg)](https://tailwindcss.com)

Sistema completo de cardápio digital com arquitetura multi-tenant, permitindo que múltiplos restaurantes tenham seus próprios subdomínios personalizados e experiência totalmente isolada.

## 🚀 **Características Principais**

### **Multi-Tenancy Completo**
- ✅ **Subdomínios personalizados** (`restaurante.cardapy.com`)
- ✅ **Isolamento total** de dados entre restaurantes
- ✅ **Sharding de banco** para escalabilidade
- ✅ **Cache isolado** por tenant
- ✅ **Storage isolado** por tenant

### **Funcionalidades do Cliente**
- 📱 **Cardápio responsivo** com design moderno
- 🛒 **Carrinho de compras** com sessão persistente
- 💳 **Pagamentos integrados** (Mercado Pago)
- 🔔 **Acompanhamento** de pedidos em tempo real
- ⭐ **Sistema de avaliações** e feedback
- 🔍 **Busca avançada** com Elasticsearch

### **Painel do Restaurante**
- 🧾 **Gestão completa** de cardápios
- 📊 **Dashboard analítico** com métricas
- 🔔 **Gerenciamento** de pedidos
- ⚙️ **Configurações** personalizadas
- 👥 **Multi-usuários** com permissões
- 💰 **Relatórios financeiros**

### **Tecnologias**
- **Backend**: Laravel 10+, PHP 8.2+, DDD
- **Frontend**: Livewire 3, Alpine.js, TailwindCSS
- **Banco**: MySQL (sharding), Redis, Elasticsearch
- **Pagamentos**: Mercado Pago (PIX, cartão, boleto)
- **Infraestrutura**: Docker, Kubernetes

## 🏗️ **Arquitetura**

```
cardapy.com                 → Landing page principal
admin.cardapy.com          → Painel administrativo
api.cardapy.com            → API pública e webhooks
{restaurante}.cardapy.com  → Frontend do restaurante
```

### **Fluxo Multi-Tenant**
1. **DNS Wildcard** resolve `*.cardapy.com`
2. **Middleware** identifica restaurante pelo subdomínio
3. **Conexão dinâmica** ao shard correto do banco
4. **Cache isolado** por tenant
5. **Experiência personalizada** por restaurante

## 🚀 **Instalação Rápida**

### **Pré-requisitos**
- Docker & Docker Compose
- Git

### **1. Clone e Configure**
```bash
git clone https://github.com/seu-usuario/cardapy.git
cd cardapy

# Copie o arquivo de ambiente
cp env.example .env

# Configure as variáveis necessárias
nano .env
```

### **2. Inicie com Docker**
```bash
# Construa e inicie os containers
docker-compose up -d --build

# Instale dependências
docker-compose exec app composer install

# Execute as migrations
docker-compose exec app php artisan migrate

# Gere chave da aplicação
docker-compose exec app php artisan key:generate

# Compile assets
docker-compose exec app npm run build
```

### **3. Configure DNS Local (Desenvolvimento)**
Adicione ao seu `/etc/hosts` (Linux/Mac) ou `C:\Windows\System32\drivers\etc\hosts` (Windows):
```
127.0.0.1 cardapy.test
127.0.0.1 admin.cardapy.test
127.0.0.1 api.cardapy.test
127.0.0.1 demo.cardapy.test
```

### **4. Acesse a Aplicação**
- **Landing**: http://cardapy.test
- **Admin**: http://admin.cardapy.test
- **Demo**: http://demo.cardapy.test

## ⚙️ **Configuração**

### **Variáveis de Ambiente Principais**
```env
# Aplicação
APP_NAME="Cardapy"
APP_URL=http://cardapy.test
CARDAPY_DOMAIN=cardapy.test

# Banco de Dados
DB_CONNECTION=mysql
DB_HOST=db
DB_DATABASE=cardapy

# Multi-Tenancy
DB_TENANT_PREFIX=tenant_
DB_SHARD_COUNT=4

# Mercado Pago
MERCADOPAGO_PUBLIC_KEY=your_public_key
MERCADOPAGO_ACCESS_TOKEN=your_access_token

# Redis
REDIS_HOST=redis

# Elasticsearch
ELASTICSEARCH_HOST=elasticsearch:9200
```

### **Configuração de Sharding**
O sistema distribui automaticamente os restaurantes entre shards:
- **Shard 0**: Restaurantes com ID % 4 = 0
- **Shard 1**: Restaurantes com ID % 4 = 1
- **Shard 2**: Restaurantes com ID % 4 = 2
- **Shard 3**: Restaurantes com ID % 4 = 3

## 📊 **Recursos Inclusos**

### **Interfaces de Desenvolvimento**
- **Adminer**: http://localhost:8080 (Banco de dados)
- **Redis Commander**: http://localhost:8081 (Cache)
- **Kibana**: http://localhost:5601 (Elasticsearch)
- **MailHog**: http://localhost:8025 (Emails)

### **Monitoramento**
- **Logs centralizados** com contexto de tenant
- **Métricas de performance** por restaurante
- **Alertas automáticos** para falhas

## 🛠️ **Comandos Úteis**

### **Desenvolvimento**
```bash
# Recriar containers
docker-compose down && docker-compose up -d --build

# Logs em tempo real
docker-compose logs -f

# Executar migrations em todos os shards
docker-compose exec app php artisan tenant:migrate

# Reindexar Elasticsearch
docker-compose exec app php artisan scout:import "App\Models\MenuItem"

# Limpar caches
docker-compose exec app php artisan optimize:clear
```

### **Produção**
```bash
# Deploy com zero downtime
./scripts/deploy.sh

# Backup dos bancos
./scripts/backup.sh

# Monitoramento de saúde
./scripts/health-check.sh
```

## 🔧 **Personalização**

### **Criando um Novo Restaurante**
```php
$restaurant = Restaurant::create([
    'name' => 'Restaurante Demo',
    'subdomain' => 'demo',
    'email' => 'contato@demo.com',
    'phone' => '(11) 99999-9999',
    'shard_id' => Restaurant::generateShardId(),
    // ... outros dados
]);

// Cria estrutura do banco automaticamente
$restaurant->createTenantDatabase();
```

### **Adicionando Novos Métodos de Pagamento**
```php
// Em config/payment-methods.php
return [
    'mercadopago' => MercadoPagoService::class,
    'stripe' => StripeService::class, // Novo método
    'paypal' => PayPalService::class, // Novo método
];
```

## 🚀 **Deploy em Produção**

### **Kubernetes (Recomendado)**
```bash
# Configure o cluster
kubectl apply -f k8s/

# Configure o DNS wildcard
# *.cardapy.com → Load Balancer IP

# Configure SSL automático
kubectl apply -f k8s/cert-manager/
```

### **Docker Swarm**
```bash
# Inicie o cluster
docker swarm init

# Deploy da stack
docker stack deploy -c docker-stack.yml cardapy
```

## 🔒 **Segurança**

- ✅ **Isolamento completo** entre tenants
- ✅ **Autenticação** multi-fator
- ✅ **Rate limiting** por subdomínio
- ✅ **SSL** obrigatório em produção
- ✅ **Backup automático** de dados
- ✅ **Logs de auditoria**

## 📈 **Escalabilidade**

### **Suporte Atual**
- **1 → 10.000+** restaurantes
- **Sharding automático** de banco
- **Cache distribuído**
- **Auto-scaling** no Kubernetes

### **Métricas de Performance**
- **< 200ms** tempo de resposta médio
- **99.9%** disponibilidade
- **Elastic scaling** baseado em demanda

## 🤝 **Contribuição**

1. Fork o projeto
2. Crie uma branch: `git checkout -b feature/nova-funcionalidade`
3. Commit: `git commit -m 'Adiciona nova funcionalidade'`
4. Push: `git push origin feature/nova-funcionalidade`
5. Abra um Pull Request

## 📄 **Licença**

Este projeto está sob a licença MIT. Veja o arquivo [LICENSE](LICENSE) para detalhes.

## 🆘 **Suporte**

- **Documentação**: [docs.cardapy.com](https://docs.cardapy.com)
- **Issues**: [GitHub Issues](https://github.com/seu-usuario/cardapy/issues)
- **Discord**: [Comunidade Cardapy](https://discord.gg/cardapy)
- **Email**: suporte@cardapy.com

---

**Desenvolvido com ❤️ para revolucionar o delivery de comida no Brasil** 