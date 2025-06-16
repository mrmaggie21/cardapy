<?php
/**
 * Preload script para Cardapy
 * Otimiza o desempenho carregando classes frequentemente usadas no OPcache
 */

if (php_sapi_name() !== 'cli') {
    return;
}

$ignoreErrors = true;
set_error_handler(function ($severity, $message, $file, $line) use ($ignoreErrors) {
    if ($ignoreErrors) {
        return true;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

// Definir o diretório base
$baseDir = __DIR__;

// Carregar o autoloader do Composer
if (file_exists($baseDir . '/vendor/autoload.php')) {
    require_once $baseDir . '/vendor/autoload.php';
} else {
    echo "Autoloader não encontrado. Execute 'composer install' primeiro.\n";
    return;
}

// Carregar configuração do Laravel
if (file_exists($baseDir . '/bootstrap/app.php')) {
    $app = require_once $baseDir . '/bootstrap/app.php';
} else {
    echo "Bootstrap do Laravel não encontrado.\n";
    return;
}

// Classes do Laravel Framework para preload
$laravelClasses = [
    // Core
    'Illuminate\Foundation\Application',
    'Illuminate\Http\Request',
    'Illuminate\Http\Response',
    'Illuminate\Routing\Router',
    'Illuminate\Container\Container',
    
    // Database
    'Illuminate\Database\Eloquent\Model',
    'Illuminate\Database\Query\Builder',
    'Illuminate\Database\Eloquent\Builder',
    'Illuminate\Database\Connection',
    
    // Cache
    'Illuminate\Cache\CacheManager',
    'Illuminate\Cache\Repository',
    
    // Session
    'Illuminate\Session\SessionManager',
    'Illuminate\Session\Store',
    
    // Auth
    'Illuminate\Auth\AuthManager',
    'Illuminate\Auth\Guard',
    
    // Validation
    'Illuminate\Validation\Validator',
    'Illuminate\Validation\Factory',
    
    // View
    'Illuminate\View\Factory',
    'Illuminate\View\View',
    
    // Livewire
    'Livewire\Component',
    'Livewire\Livewire',
];

// Classes específicas do Cardapy
$cardapyClasses = [
    // Models
    'App\Models\User',
    'App\Models\Restaurant',
    'App\Models\Category',
    'App\Models\MenuItem',
    'App\Models\Order',
    'App\Models\OrderItem',
    'App\Models\Payment',
    'App\Models\Review',
    
    // Services
    'App\Services\MercadoPagoService',
    
    // Livewire Components
    'App\Livewire\MenuDisplay',
    
    // Middleware
    'App\Http\Middleware\IdentifyTenant',
];

// Função para carregar classes
function preloadClass($className) {
    try {
        if (class_exists($className, true)) {
            echo "✅ Carregado: $className\n";
            return true;
        }
    } catch (Throwable $e) {
        echo "❌ Erro ao carregar $className: " . $e->getMessage() . "\n";
    }
    return false;
}

echo "🚀 Iniciando preload do Cardapy...\n";

$loadedCount = 0;
$totalCount = 0;

// Carregar classes do Laravel
echo "\n📦 Carregando classes do Laravel Framework...\n";
foreach ($laravelClasses as $class) {
    $totalCount++;
    if (preloadClass($class)) {
        $loadedCount++;
    }
}

// Carregar classes do Cardapy
echo "\n🍽️  Carregando classes do Cardapy...\n";
foreach ($cardapyClasses as $class) {
    $totalCount++;
    if (preloadClass($class)) {
        $loadedCount++;
    }
}

// Carregar arquivos de configuração importantes
$configFiles = [
    'config/app.php',
    'config/database.php',
    'config/cache.php',
    'config/session.php',
    'config/auth.php',
];

echo "\n⚙️  Carregando arquivos de configuração...\n";
foreach ($configFiles as $configFile) {
    $fullPath = $baseDir . '/' . $configFile;
    if (file_exists($fullPath)) {
        try {
            opcache_compile_file($fullPath);
            echo "✅ Configuração carregada: $configFile\n";
        } catch (Throwable $e) {
            echo "❌ Erro ao carregar $configFile: " . $e->getMessage() . "\n";
        }
    }
}

echo "\n🎉 Preload concluído!\n";
echo "📊 Classes carregadas: $loadedCount/$totalCount\n";
echo "💾 Memória utilizada: " . round(memory_get_peak_usage() / 1024 / 1024, 2) . " MB\n";

restore_error_handler(); 