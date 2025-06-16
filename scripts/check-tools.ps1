# Verifica ferramentas de desenvolvimento instaladas

Write-Host "=== VERIFICACAO DE FERRAMENTAS ===" -ForegroundColor Green
Write-Host ""

# Git
try {
    $gitVersion = git --version
    Write-Host "✓ Git: $gitVersion" -ForegroundColor Green
} catch {
    Write-Host "✗ Git: NAO ENCONTRADO" -ForegroundColor Red
}

# Go
try {
    $goVersion = go version
    Write-Host "✓ Go: $goVersion" -ForegroundColor Green
} catch {
    Write-Host "✗ Go: NAO ENCONTRADO" -ForegroundColor Red
}

# Docker
try {
    $dockerVersion = docker --version
    Write-Host "✓ Docker: $dockerVersion" -ForegroundColor Green
} catch {
    Write-Host "✗ Docker: NAO ENCONTRADO" -ForegroundColor Red
}

# Node.js
try {
    $nodeVersion = node --version
    Write-Host "✓ Node.js: $nodeVersion" -ForegroundColor Green
} catch {
    Write-Host "✗ Node.js: NAO ENCONTRADO" -ForegroundColor Red
}

# PHP
try {
    $phpVersion = php --version | Select-Object -First 1
    Write-Host "✓ PHP: $phpVersion" -ForegroundColor Green
} catch {
    Write-Host "✗ PHP: NAO ENCONTRADO" -ForegroundColor Red
}

# Composer
try {
    $composerVersion = composer --version
    Write-Host "✓ Composer: $composerVersion" -ForegroundColor Green
} catch {
    Write-Host "✗ Composer: NAO ENCONTRADO" -ForegroundColor Red
}

Write-Host ""
Write-Host "=== STATUS DO PROJETO CARDAPY ===" -ForegroundColor Blue
Write-Host ""

# Verifica estrutura do projeto
if (Test-Path "composer.json") {
    Write-Host "✓ composer.json encontrado" -ForegroundColor Green
} else {
    Write-Host "✗ composer.json NAO encontrado" -ForegroundColor Red
}

if (Test-Path "package.json") {
    Write-Host "✓ package.json encontrado" -ForegroundColor Green
} else {
    Write-Host "✗ package.json NAO encontrado" -ForegroundColor Red
}

if (Test-Path ".env") {
    Write-Host "✓ .env encontrado" -ForegroundColor Green
} else {
    Write-Host "✗ .env NAO encontrado" -ForegroundColor Red
}

if (Test-Path "docker-compose.yml") {
    Write-Host "✓ docker-compose.yml encontrado" -ForegroundColor Green
} else {
    Write-Host "✗ docker-compose.yml NAO encontrado" -ForegroundColor Red
}

Write-Host ""
Write-Host "=== COMANDOS RAPIDOS ===" -ForegroundColor Cyan
Write-Host ""
Write-Host "Instalar ferramentas faltantes:" -ForegroundColor Yellow
Write-Host "• winget install Git.Git" -ForegroundColor White
Write-Host "• winget install GoLang.Go" -ForegroundColor White
Write-Host "• winget install Docker.DockerDesktop" -ForegroundColor White
Write-Host "• winget install OpenJS.NodeJS" -ForegroundColor White
Write-Host "• winget install PHP.PHP" -ForegroundColor White
Write-Host "• winget install Composer.Composer" -ForegroundColor White
Write-Host "" 