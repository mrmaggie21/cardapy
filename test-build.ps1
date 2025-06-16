# Script de Teste de Build - Cardapy
param(
    [Parameter(Mandatory=$false)]
    [ValidateSet("main", "simple")]
    [string]$DockerfileType = "main"
)

Write-Host "🧪 Testando build do Cardapy" -ForegroundColor Green
Write-Host "📋 Dockerfile: $DockerfileType" -ForegroundColor Cyan

# Verificar se Docker está disponível
if (-not (Get-Command docker -ErrorAction SilentlyContinue)) {
    Write-Host "❌ Docker não encontrado!" -ForegroundColor Red
    Write-Host "💡 Instale o Docker Desktop ou adicione ao PATH" -ForegroundColor Yellow
    exit 1
}

# Definir qual Dockerfile usar
$dockerfilePath = if ($DockerfileType -eq "simple") { "Dockerfile.simple" } else { "Dockerfile" }

Write-Host "🏗️  Iniciando build..." -ForegroundColor Yellow
Write-Host "📄 Usando: $dockerfilePath" -ForegroundColor Cyan

# Executar build
$buildCommand = @(
    "docker", "build",
    "-f", $dockerfilePath,
    "-t", "cardapy:test-$DockerfileType",
    "."
)

Write-Host "🔧 Comando: $($buildCommand -join ' ')" -ForegroundColor Cyan

try {
    & $buildCommand[0] $buildCommand[1..($buildCommand.Length-1)]
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✅ Build concluído com sucesso!" -ForegroundColor Green
        
        # Mostrar informações da imagem
        Write-Host "📊 Informações da imagem:" -ForegroundColor Yellow
        docker images cardapy:test-$DockerfileType
        
        # Testar se a imagem funciona
        Write-Host "🧪 Testando imagem..." -ForegroundColor Yellow
        docker run --rm cardapy:test-$DockerfileType php --version
        
    } else {
        Write-Host "❌ Build falhou!" -ForegroundColor Red
        exit 1
    }
    
} catch {
    Write-Host "❌ Erro durante o build: $_" -ForegroundColor Red
    exit 1
}

Write-Host "🎉 Teste concluído!" -ForegroundColor Green 