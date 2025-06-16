<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\RestaurantController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\OrderController;
use App\Http\Middleware\IdentifyTenant;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Rotas do Admin (admin.cardapy.com)
Route::domain(config('app.cardapy_domain'))->group(function () {
    Route::get('/', function () {
        return view('welcome');
    })->name('home');
});

// Rotas do Painel Admin (admin.cardapy.com)
Route::domain('admin.' . config('app.cardapy_domain'))->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/login', [DashboardController::class, 'login'])->name('admin.login');
    Route::post('/login', [DashboardController::class, 'authenticate'])->name('admin.authenticate');
    Route::post('/logout', [DashboardController::class, 'logout'])->name('admin.logout');
    
    Route::middleware('auth:admin')->group(function () {
        // Gestão de Restaurantes
        Route::resource('restaurants', RestaurantController::class);
        
        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'dashboard'])->name('admin.dashboard.main');
        
        // Relatórios
        Route::get('/reports', [DashboardController::class, 'reports'])->name('admin.reports');
        Route::get('/analytics', [DashboardController::class, 'analytics'])->name('admin.analytics');
    });
});

// Rotas dos Restaurantes (subdomínio.cardapy.com)
Route::domain('{subdomain}.' . config('app.cardapy_domain'))
    ->middleware([IdentifyTenant::class])
    ->group(function () {
        
        // Página inicial do restaurante (cardápio)
        Route::get('/', [MenuController::class, 'index'])->name('menu.index');
        
        // Categoria específica
        Route::get('/categoria/{category}', [MenuController::class, 'category'])->name('menu.category');
        
        // Item específico
        Route::get('/item/{item}', [MenuController::class, 'item'])->name('menu.item');
        
        // Busca
        Route::get('/buscar', [MenuController::class, 'search'])->name('menu.search');
        
        // Checkout
        Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout');
        Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
        
        // Pedidos
        Route::get('/pedido/{order}', [OrderController::class, 'show'])->name('order.show');
        Route::get('/pedido/{order}/acompanhar', [OrderController::class, 'track'])->name('order.track');
        
        // Callbacks do Mercado Pago
        Route::get('/pagamento/sucesso/{order}', [OrderController::class, 'success'])->name('order.success');
        Route::get('/pagamento/pendente/{order}', [OrderController::class, 'pending'])->name('order.pending');
        Route::get('/pagamento/falha/{order}', [OrderController::class, 'failure'])->name('order.failure');
        
        // Avaliações
        Route::post('/avaliar/{order}', [OrderController::class, 'review'])->name('order.review');
        
        // Sobre o restaurante
        Route::get('/sobre', [MenuController::class, 'about'])->name('restaurant.about');
        
        // Contato
        Route::get('/contato', [MenuController::class, 'contact'])->name('restaurant.contact');
        Route::post('/contato', [MenuController::class, 'sendContact'])->name('restaurant.contact.send');
    });

// Rotas de API (api.cardapy.com)
Route::domain('api.' . config('app.cardapy_domain'))->group(function () {
    // Webhook do Mercado Pago
    Route::post('/webhook/mercadopago', [OrderController::class, 'mercadoPagoWebhook'])
        ->name('api.mercadopago.webhook');
    
    // API pública dos restaurantes
    Route::get('/restaurant/{subdomain}', [RestaurantController::class, 'api'])->name('api.restaurant');
    Route::get('/restaurant/{subdomain}/menu', [MenuController::class, 'api'])->name('api.menu');
    Route::get('/restaurant/{subdomain}/status', [RestaurantController::class, 'status'])->name('api.restaurant.status');
});

// Fallback para desenvolvimento local
if (app()->environment('local')) {
    Route::get('/test-restaurant/{subdomain}', function ($subdomain) {
        return redirect()->to("http://{$subdomain}.cardapy.test");
    });
} 