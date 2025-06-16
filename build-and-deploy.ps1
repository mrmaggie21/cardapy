# Script de Build e Deploy - Cardapy
# Sistema de Cardápio Digital Multi-Tenant

param(
    [Parameter(Mandatory=$false)]
    [ValidateSet("development", "production")]
    [string]$Environment = "development",
    
    [Parameter(Mandatory=$false)]
    [switch]$SkipBuild,
    
    [Parameter(Mandatory=$false)]
    [switch]$SkipTests,
    
    [Parameter(Mandatory=$false)]
    [switch]$CleanBuild
)

# Configurações
$PROJECT_NAME = "cardapy"
$DOCKER_IMAGE = "$PROJECT_NAME:latest"
$DOCKER_REGISTRY = "registry.lemontech.cloud"

Write-Host "🚀 Iniciando Build e Deploy do Cardapy" -ForegroundColor Green
Write-Host "📋 Ambiente: $Environment" -ForegroundColor Cyan
Write-Host "🏗️  Imagem: $DOCKER_IMAGE" -ForegroundColor Cyan

# Função para verificar se comando existe
function Test-Command($cmdname) {
    return [bool](Get-Command -Name $cmdname -ErrorAction SilentlyContinue)
}

# Verificar dependências
Write-Host "🔍 Verificando dependências..." -ForegroundColor Yellow

$dependencies = @("docker", "docker-compose")
foreach ($dep in $dependencies) {
    if (-not (Test-Command $dep)) {
        Write-Host "❌ $dep não encontrado!" -ForegroundColor Red
        exit 1
    }
    Write-Host "✅ $dep encontrado" -ForegroundColor Green
}

# Limpar build anterior se solicitado
if ($CleanBuild) {
    Write-Host "🧹 Limpando build anterior..." -ForegroundColor Yellow
    docker system prune -f
    docker-compose down -v --remove-orphans
}

# Verificar se .env existe
if (-not (Test-Path ".env")) {
    Write-Host "⚠️  Arquivo .env não encontrado, copiando do exemplo..." -ForegroundColor Yellow
    if (Test-Path ".env.example") {
        Copy-Item ".env.example" ".env"
        Write-Host "✅ Arquivo .env criado" -ForegroundColor Green
    } else {
        Write-Host "❌ Arquivo .env.example não encontrado!" -ForegroundColor Red
        exit 1
    }
}

# Build da aplicação
if (-not $SkipBuild) {
    Write-Host "🏗️  Construindo imagem Docker..." -ForegroundColor Yellow
    
    $buildArgs = @(
        "--build-arg", "user=$PROJECT_NAME",
        "--build-arg", "uid=1000",
        "--build-arg", "gid=1000"
    )
    
    if ($Environment -eq "production") {
        $buildArgs += @("--target", "production")
    }
    
    $buildCommand = @("docker", "build") + $buildArgs + @("-t", $DOCKER_IMAGE, ".")
    
    Write-Host "Executando: $($buildCommand -join ' ')" -ForegroundColor Cyan
    & $buildCommand[0] $buildCommand[1..($buildCommand.Length-1)]
    
    if ($LASTEXITCODE -ne 0) {
        Write-Host "❌ Erro no build da imagem!" -ForegroundColor Red
        exit 1
    }
    
    Write-Host "✅ Imagem construída com sucesso!" -ForegroundColor Green
}

# Executar testes se não for pulado
if (-not $SkipTests -and $Environment -eq "development") {
    Write-Host "🧪 Executando testes..." -ForegroundColor Yellow
    
    # Subir serviços de teste
    docker-compose -f docker-compose.yml -f docker-compose.test.yml up -d mysql redis
    
    # Aguardar serviços
    Start-Sleep -Seconds 30
    
    # Executar testes
    docker-compose -f docker-compose.yml -f docker-compose.test.yml run --rm app php artisan test
    
    if ($LASTEXITCODE -ne 0) {
        Write-Host "❌ Testes falharam!" -ForegroundColor Red
        docker-compose -f docker-compose.yml -f docker-compose.test.yml down
        exit 1
    }
    
    Write-Host "✅ Testes executados com sucesso!" -ForegroundColor Green
    docker-compose -f docker-compose.yml -f docker-compose.test.yml down
}

# Deploy
Write-Host "🚀 Iniciando deploy..." -ForegroundColor Yellow

if ($Environment -eq "development") {
    # Deploy local
    Write-Host "📦 Deploy local (desenvolvimento)..." -ForegroundColor Cyan
    
    docker-compose up -d
    
    if ($LASTEXITCODE -ne 0) {
        Write-Host "❌ Erro no deploy local!" -ForegroundColor Red
        exit 1
    }
    
    # Aguardar aplicação ficar disponível
    Write-Host "⏳ Aguardando aplicação ficar disponível..." -ForegroundColor Yellow
    $timeout = 120
    $elapsed = 0
    
    do {
        try {
            $response = Invoke-WebRequest -Uri "http://localhost/health" -TimeoutSec 5 -ErrorAction Stop
            if ($response.StatusCode -eq 200) {
                break
            }
        } catch {
            # Continuar tentando
        }
        
        Start-Sleep -Seconds 5
        $elapsed += 5
        
        if ($elapsed -ge $timeout) {
            Write-Host "❌ Timeout aguardando aplicação!" -ForegroundColor Red
            docker-compose logs app
            exit 1
        }
    } while ($true)
    
    Write-Host "✅ Deploy local concluído!" -ForegroundColor Green
    Write-Host "🌐 Aplicação: http://localhost" -ForegroundColor Cyan
    Write-Host "🗄️  PHPMyAdmin: http://localhost:8080" -ForegroundColor Cyan
    Write-Host "📧 MailHog: http://localhost:8025" -ForegroundColor Cyan
    Write-Host "🔴 Redis Commander: http://localhost:8081" -ForegroundColor Cyan
    
} else {
    # Deploy produção
    Write-Host "🏭 Deploy produção..." -ForegroundColor Cyan
    
    # Tag para registry
    $prodImage = "$DOCKER_REGISTRY/$DOCKER_IMAGE"
    docker tag $DOCKER_IMAGE $prodImage
    
    # Push para registry
    Write-Host "📤 Enviando imagem para registry..." -ForegroundColor Yellow
    docker push $prodImage
    
    if ($LASTEXITCODE -ne 0) {
        Write-Host "❌ Erro ao enviar imagem!" -ForegroundColor Red
        exit 1
    }
    
    Write-Host "✅ Deploy produção concluído!" -ForegroundColor Green
    Write-Host "🏭 Imagem: $prodImage" -ForegroundColor Cyan
}

# Mostrar status dos containers
Write-Host "📊 Status dos containers:" -ForegroundColor Yellow
docker-compose ps

Write-Host "🎉 Build e Deploy concluídos com sucesso!" -ForegroundColor Green
Write-Host "📋 Resumo:" -ForegroundColor Cyan
Write-Host "   - Ambiente: $Environment" -ForegroundColor White
Write-Host "   - Imagem: $DOCKER_IMAGE" -ForegroundColor White
Write-Host "   - Build: $(if ($SkipBuild) { 'Pulado' } else { 'Executado' })" -ForegroundColor White
Write-Host "   - Testes: $(if ($SkipTests) { 'Pulados' } else { 'Executados' })" -ForegroundColor White 