# Script de Configuracao do Ambiente de Desenvolvimento
# Instala e configura ferramentas essenciais para desenvolvimento

Write-Host "=== CARDAPY - CONFIGURACAO DO AMBIENTE DE DESENVOLVIMENTO ===" -ForegroundColor Green
Write-Host ""

# Funcoes auxiliares
function Write-Success($message) {
    Write-Host "✓ $message" -ForegroundColor Green
}

function Write-Info($message) {
    Write-Host "[INFO] $message" -ForegroundColor Blue
}

function Write-Warning($message) {
    Write-Host "[WARN] $message" -ForegroundColor Yellow
}

function Write-Error($message) {
    Write-Host "[ERROR] $message" -ForegroundColor Red
}

# Verifica versoes das ferramentas instaladas
Write-Info "Verificando ferramentas instaladas..."
Write-Host ""

# Git
try {
    $gitVersion = & git --version 2>$null
    Write-Success "Git: $gitVersion"
} catch {
    Write-Warning "Git nao encontrado - Execute: winget install Git.Git"
}

# Go
try {
    $goVersion = & go version 2>$null
    Write-Success "Go: $goVersion"
} catch {
    Write-Warning "Go nao encontrado - Execute: winget install GoLang.Go"
}

# Docker
try {
    $dockerVersion = & docker --version 2>$null
    Write-Success "Docker: $dockerVersion"
} catch {
    Write-Warning "Docker nao encontrado - Instale Docker Desktop"
}

# Node.js
try {
    $nodeVersion = & node --version 2>$null
    Write-Success "Node.js: $nodeVersion"
} catch {
    Write-Warning "Node.js nao encontrado - Execute: winget install OpenJS.NodeJS"
}

# PHP
try {
    $phpVersion = & php --version 2>$null | Select-Object -First 1
    Write-Success "PHP: $phpVersion"
} catch {
    Write-Warning "PHP nao encontrado - Execute: winget install PHP.PHP"
}

Write-Host ""
Write-Info "=== CONFIGURACAO DO PROJETO CARDAPY ==="
Write-Host ""

# Configura Git se necessario
$gitUser = & git config --global user.name 2>$null
$gitEmail = & git config --global user.email 2>$null

if (-not $gitUser -or -not $gitEmail) {
    Write-Info "Configurando Git..."
    
    if (-not $gitUser) {
        $userName = Read-Host "Digite seu nome para o Git"
        & git config --global user.name "$userName"
        Write-Success "Nome configurado: $userName"
    }
    
    if (-not $gitEmail) {
        $userEmail = Read-Host "Digite seu email para o Git"
        & git config --global user.email "$userEmail"
        Write-Success "Email configurado: $userEmail"
    }
} else {
    Write-Success "Git ja configurado - Usuario: $gitUser, Email: $gitEmail"
}

# Configura Go workspace
Write-Info "Configurando Go workspace..."
$goPath = $env:GOPATH
if (-not $goPath) {
    $goPath = "$env:USERPROFILE\go"
    [Environment]::SetEnvironmentVariable("GOPATH", $goPath, "User")
    Write-Success "GOPATH configurado: $goPath"
} else {
    Write-Success "GOPATH ja configurado: $goPath"
}

# Cria diretorios necessarios
$dirs = @(
    "storage/app/public",
    "storage/framework/cache",
    "storage/framework/sessions", 
    "storage/framework/views",
    "storage/logs",
    "bootstrap/cache"
)

Write-Info "Criando diretorios do Laravel..."
foreach ($dir in $dirs) {
    if (-not (Test-Path $dir)) {
        New-Item -ItemType Directory -Path $dir -Force | Out-Null
        Write-Success "Criado: $dir"
    }
}

# Cria arquivo .env se nao existir
if (-not (Test-Path ".env")) {
    if (Test-Path "env.example") {
        Copy-Item "env.example" ".env"
        Write-Success "Arquivo .env criado baseado em env.example"
    } else {
        Write-Warning "Arquivo env.example nao encontrado"
    }
} else {
    Write-Success "Arquivo .env ja existe"
}

Write-Host ""
Write-Info "=== INSTALACAO DE FERRAMENTAS ADICIONAIS ==="
Write-Host ""

# Lista de ferramentas opcionais
$tools = @(
    @{Name="Visual Studio Code"; Package="Microsoft.VisualStudioCode"; Description="Editor de codigo"},
    @{Name="Windows Terminal"; Package="Microsoft.WindowsTerminal"; Description="Terminal moderno"},
    @{Name="Docker Desktop"; Package="Docker.DockerDesktop"; Description="Containerizacao"},
    @{Name="Node.js"; Package="OpenJS.NodeJS"; Description="Runtime JavaScript"},
    @{Name="PHP"; Package="PHP.PHP"; Description="Linguagem de programacao"},
    @{Name="Composer"; Package="Composer.Composer"; Description="Gerenciador de dependencias PHP"},
    @{Name="Postman"; Package="Postman.Postman"; Description="Teste de APIs"}
)

Write-Host "Ferramentas disponiveis para instalacao:" -ForegroundColor Cyan
for ($i = 0; $i -lt $tools.Length; $i++) {
    Write-Host "$($i + 1). $($tools[$i].Name) - $($tools[$i].Description)" -ForegroundColor White
}

Write-Host ""
$choice = Read-Host "Digite os numeros das ferramentas que deseja instalar (separados por virgula) ou 'all' para todas"

if ($choice -eq "all") {
    $selectedTools = $tools
} elseif ($choice -ne "") {
    $indices = $choice -split "," | ForEach-Object { [int]$_.Trim() - 1 }
    $selectedTools = $indices | ForEach-Object { $tools[$_] }
} else {
    $selectedTools = @()
}

foreach ($tool in $selectedTools) {
    Write-Info "Instalando $($tool.Name)..."
    try {
        & winget install $tool.Package --silent
        Write-Success "$($tool.Name) instalado com sucesso"
    } catch {
        Write-Error "Falha ao instalar $($tool.Name)"
    }
}

Write-Host ""
Write-Info "=== COMANDOS UTEIS ==="
Write-Host ""

Write-Host "Proximos passos para o projeto Cardapy:" -ForegroundColor Cyan
Write-Host "1. docker-compose up -d              # Iniciar containers" -ForegroundColor White
Write-Host "2. composer install                  # Instalar dependencias PHP" -ForegroundColor White
Write-Host "3. npm install                       # Instalar dependencias Node.js" -ForegroundColor White
Write-Host "4. php artisan key:generate          # Gerar chave da aplicacao" -ForegroundColor White
Write-Host "5. php artisan migrate               # Executar migrations" -ForegroundColor White
Write-Host "6. npm run dev                       # Compilar assets" -ForegroundColor White
Write-Host ""

Write-Host "Scripts disponiveis:" -ForegroundColor Cyan
Write-Host "• .\scripts\network-setup.ps1        # Configurar LemonNetwork" -ForegroundColor White
Write-Host "• .\scripts\network-monitor.ps1      # Monitorar rede" -ForegroundColor White
Write-Host "• docker-compose logs -f             # Ver logs em tempo real" -ForegroundColor White
Write-Host ""

Write-Success "=== AMBIENTE DE DESENVOLVIMENTO CONFIGURADO COM SUCESSO! ==="
Write-Host ""
Write-Host "Reinicie o terminal para aplicar todas as configuracoes." -ForegroundColor Yellow 