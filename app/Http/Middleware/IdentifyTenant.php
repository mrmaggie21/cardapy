<?php

namespace App\Http\Middleware;

use App\Models\Restaurant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Spatie\Multitenancy\Models\Tenant;

class IdentifyTenant
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $host = $request->getHost();
        $subdomain = $this->extractSubdomain($host);

        // Se não há subdomínio ou é admin/api, não aplica tenant
        if (!$subdomain || in_array($subdomain, ['admin', 'api', 'www'])) {
            return $next($request);
        }

        // Busca o restaurante pelo subdomínio
        $restaurant = Restaurant::where('subdomain', $subdomain)
            ->where('is_active', true)
            ->first();

        if (!$restaurant) {
            abort(404, 'Restaurante não encontrado');
        }

        // Configura o tenant atual
        $this->configureTenant($restaurant);

        // Disponibiliza o restaurante globalmente
        app()->instance('currentRestaurant', $restaurant);
        
        // Adiciona ao request para fácil acesso
        $request->merge(['restaurant' => $restaurant]);

        return $next($request);
    }

    /**
     * Extrai o subdomínio do host
     */
    private function extractSubdomain(string $host): ?string
    {
        $parts = explode('.', $host);
        
        // Se tem pelo menos 3 partes (sub.domain.com), pega o primeiro
        if (count($parts) >= 3) {
            return $parts[0];
        }

        // Para desenvolvimento local com porta
        if (count($parts) === 2 && str_contains($host, 'localhost')) {
            return null;
        }

        return null;
    }

    /**
     * Configura o tenant (multi-tenancy)
     */
    private function configureTenant(Restaurant $restaurant): void
    {
        // Configura conexão de banco de dados dinâmica
        $this->configureDatabaseConnection($restaurant);

        // Configura cache único por tenant
        $this->configureCachePrefix($restaurant);

        // Configura storage único por tenant
        $this->configureStorage($restaurant);

        // Registra tenant no container
        Tenant::current($restaurant);
    }

    /**
     * Configura conexão dinâmica do banco
     */
    private function configureDatabaseConnection(Restaurant $restaurant): void
    {
        $shardId = $restaurant->shard_id ?? 0;
        $databaseName = config('database.connections.mysql.database') . '_tenant_' . $shardId;

        // Clona a configuração padrão
        $tenantConfig = config('database.connections.mysql');
        $tenantConfig['database'] = $databaseName;

        // Define nova conexão
        Config::set('database.connections.tenant', $tenantConfig);
        
        // Define como conexão padrão para este request
        Config::set('database.default', 'tenant');

        // Testa conexão
        try {
            DB::connection('tenant')->getPdo();
        } catch (\Exception $e) {
            // Se falhar, cria o banco automaticamente (ambiente de desenvolvimento)
            if (app()->environment('local')) {
                $this->createTenantDatabase($databaseName);
            } else {
                abort(503, 'Banco de dados do restaurante indisponível');
            }
        }
    }

    /**
     * Cria banco de tenant automaticamente (desenvolvimento)
     */
    private function createTenantDatabase(string $databaseName): void
    {
        try {
            DB::statement("CREATE DATABASE IF NOT EXISTS `{$databaseName}`");
            
            // Reconecta com o novo banco
            DB::purge('tenant');
            DB::reconnect('tenant');
            
        } catch (\Exception $e) {
            abort(503, 'Erro ao criar banco de dados do restaurante');
        }
    }

    /**
     * Configura prefixo único do cache por tenant
     */
    private function configureCachePrefix(Restaurant $restaurant): void
    {
        $cachePrefix = 'tenant_' . $restaurant->id . '_';
        Config::set('cache.prefix', $cachePrefix);
    }

    /**
     * Configura storage único por tenant
     */
    private function configureStorage(Restaurant $restaurant): void
    {
        $storagePath = 'tenants/' . $restaurant->id;
        
        Config::set('filesystems.disks.tenant', [
            'driver' => 'local',
            'root' => storage_path('app/' . $storagePath),
            'url' => env('APP_URL') . '/storage/' . $storagePath,
            'visibility' => 'public',
        ]);
    }
} 