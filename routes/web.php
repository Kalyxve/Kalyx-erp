<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\OutController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\FacturaController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\CompraController;
use App\Http\Controllers\VendedorController;   // <- NUEVO
// use App\Http\Controllers\RateController;    // <- opcional si cambias el endpoint de BCV

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// === BCV (mantengo tu proxy con cache de 1h) ===
Route::get('/api/bcv-rate', function () {
    return Cache::remember('bcv-rate-v1', 3600, function () {
        try {
            $raw = Http::get('https://bcv-api.rafnixg.dev/rates/')->json();

            $candidates = [
                data_get($raw, 'dollar'),
                data_get($raw, 'usd'),
                data_get($raw, 'USD'),
                data_get($raw, 'USD.rate'),
                data_get($raw, 'usd.rate'),
                data_get($raw, 'usd.value'),
                data_get($raw, 'rates.USD'),
                data_get($raw, 'data.USD.value'),
            ];

            $rate = null;
            foreach ($candidates as $c) {
                $n = is_numeric($c) ? (float) $c : null;
                if ($n && $n > 0) {
                    $rate = $n;
                    break;
                }
            }

            $date = data_get($raw, 'date') ?: now()->toDateString();

            return response()->json([
                'usd'  => $rate,
                'date' => $date,
                'src'  => 'bcv-proxy',
            ]);
        } catch (\Throwable $e) {
            return response()->json(['usd' => null, 'date' => now()->toDateString()], 502);
        }
    });
});

// (Opcional) Si prefieres usar el RateController y tu tabla 'tasas':
// Route::get('/api/bcv-rate', [RateController::class, 'bcv'])->name('api.bcv');

// Raíz
Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
})->name('home');

/* ---------------- Invitados ---------------- */
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/auth/login', [AuthController::class, 'login'])->name('auth.login');
});

/* ---------------- Autenticados ---------------- */
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ===== Clientes =====
    Route::get('clientes/list', [ClienteController::class, 'list'])->name('clientes.list');
    Route::resource('clientes', ClienteController::class)->names('clientes');

    // ===== Productos =====
    Route::get('productos/list',  [ProductoController::class, 'list'])->name('productos.list');
    Route::get('productos/combo', [ProductoController::class, 'combo'])->name('productos.combo');
    Route::resource('productos', ProductoController::class)->names('productos');

    // Categorías dentro de Productos
    Route::prefix('productos')->name('productos.')->group(function () {
        Route::resource('categorias', CategoriaController::class)->names('categorias');
    });

    // ===== Proveedores =====
    Route::get('proveedores/list', [ProveedorController::class, 'list'])->name('proveedores.list');
    Route::resource('proveedores', ProveedorController::class)->names('proveedores');

    // ===== Vendedores (NUEVO) =====
    Route::get('vendedores/list', [VendedorController::class, 'list'])->name('vendedores.list');
    Route::post('vendedores',     [VendedorController::class, 'store'])->name('vendedores.store');

    // ===== Facturas (ventas / pagos directos) =====
    Route::get('facturas/list', [FacturaController::class, 'list'])->name('facturas.list');

    // CAMBIO: antes tenías POST /facturas/{factura}/pagos -> storePago
    // Ahora usamos un único endpoint de abono:
    Route::post('facturas/{factura}/pago',   [FacturaController::class, 'abonar'])->name('facturas.abonar');  // NUEVO
    Route::post('facturas/{factura}/anular', [FacturaController::class, 'anular'])->name('facturas.anular');  // NUEVO

    // Resource mantiene index/show/store/update/destroy si los usas
    Route::resource('facturas', FacturaController::class)->names('facturas');

    // ===== Compras (entradas) =====
    Route::get('compras/list', [CompraController::class, 'list'])->name('compras.list');
    Route::resource('compras', CompraController::class)->names('compras');

    // Cerrar sesión
    Route::post('/logout', OutController::class)->name('logout');
});

/* ---------------- Fallback ---------------- */
Route::fallback(fn() => abort(404));
