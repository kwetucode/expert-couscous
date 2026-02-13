<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\StoreApiController;
use App\Http\Controllers\Api\TransferApiController;
use App\Http\Controllers\Api\Mobile\MobileDashboardController;
use App\Http\Controllers\Api\Mobile\MobileSalesReportController;
use App\Http\Controllers\Api\Mobile\MobileStockReportController;
use App\Http\Controllers\Api\Mobile\MobileStockMovementController;
use App\Http\Controllers\Api\Mobile\MobileProductController;
use App\Http\Controllers\Api\Mobile\MobileTaxController;
use App\Http\Controllers\Api\Mobile\MobileProformaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// ===== Webhooks (publics - aucune authentification requise) =====
Route::prefix('webhooks')->name('webhooks.')->group(function () {
    Route::post('/shwary', [\App\Http\Controllers\Webhooks\ShwaryWebhookController::class, 'handleCallback'])->name('shwary');
    Route::get('/shwary/status/{transactionId}', [\App\Http\Controllers\Webhooks\ShwaryWebhookController::class, 'checkStatus'])->name('shwary.status');
});

// Alias pour Shwary (compatibilité)
Route::post('/shwary/callback', [\App\Http\Controllers\Webhooks\ShwaryWebhookController::class, 'handleCallback'])->name('shwary.callback');

// ===== Routes d'authentification (publiques) =====
Route::prefix('auth')->name('api.auth.')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->name('login');
});

// ===== Routes protégées par Sanctum =====
Route::middleware('auth:sanctum')->group(function () {

    // ===== Auth Routes (protégées) - Toujours accessibles =====
    Route::prefix('auth')->name('api.auth.')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::post('/logout-all', [AuthController::class, 'logoutAll'])->name('logout-all');
        Route::get('/me', [AuthController::class, 'me'])->name('me');
        Route::post('/refresh', [AuthController::class, 'refresh'])->name('refresh');
    });

    // User info - Toujours accessible
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // ===== Routes nécessitant l'accès API (plan Professional+) =====
    // Middlewares:
    // - api.organization : Résout et vérifie l'organisation courante
    // - feature:api_access : Vérifie que le plan a accès à l'API
    // - subscription.active : Vérifie que l'abonnement est actif
    // - api.rate.limit : Applique le rate limiting selon le plan
    Route::middleware(['api.organization', 'feature:api_access', 'subscription.active', 'api.rate.limit'])->group(function () {

    // ===== Store API Routes =====
    Route::prefix('stores')->name('api.stores.')->middleware('api.permission:stores.view')->group(function () {

        // List and filter stores
        Route::get('/', [StoreApiController::class, 'index'])->name('index');

        // Active stores only
        Route::get('/active', [StoreApiController::class, 'active'])->name('active');

        // User's stores
        Route::get('/user', [StoreApiController::class, 'userStores'])->name('user');

        // Show specific store
        Route::get('/{id}', [StoreApiController::class, 'show'])->name('show');

        // Create store (vérifie la limite de magasins + permission)
        Route::post('/', [StoreApiController::class, 'store'])->middleware(['resource.limit:stores', 'api.permission:stores.create'])->name('store');

        // Update store
        Route::put('/{id}', [StoreApiController::class, 'update'])->name('update');
        Route::patch('/{id}', [StoreApiController::class, 'update'])->name('patch');

        // Delete store
        Route::delete('/{id}', [StoreApiController::class, 'destroy'])->name('destroy');

        // Store actions (vérifie la limite d'utilisateurs)
        Route::post('/{id}/assign-user', [StoreApiController::class, 'assignUser'])->middleware('resource.limit:users')->name('assign-user');
        Route::delete('/{storeId}/remove-user/{userId}', [StoreApiController::class, 'removeUser'])->name('remove-user');
        Route::post('/{id?}/switch', [StoreApiController::class, 'switchStore'])->name('switch');

        // Store stock
        Route::get('/{id}/stock', [StoreApiController::class, 'stock'])->name('stock');
    });



    // ===== Mobile API Routes =====
    Route::prefix('mobile')->name('api.mobile.')->group(function () {

        // Dashboard principal
        Route::get('/dashboard', [MobileDashboardController::class, 'index'])->name('dashboard');

        // Contexte utilisateur (organisation, stores, rôle)
        Route::get('/context', [MobileDashboardController::class, 'userContext'])->name('context');

        // Stores accessibles
        Route::get('/stores', [MobileDashboardController::class, 'stores'])->name('stores');

        // Changer de store actif (storeId peut être null pour voir tous les magasins)
        Route::post('/switch-store/{storeId?}', [MobileDashboardController::class, 'switchStore'])->name('switch-store');

        // Performance des stores (admin/manager)
        Route::get('/stores-performance', [MobileDashboardController::class, 'storesPerformance'])->name('stores-performance');

        // Rafraîchir le cache
        Route::post('/refresh', [MobileDashboardController::class, 'refresh'])->name('refresh');

        // ===== Rapports de Ventes =====
        Route::prefix('sales')->name('sales.')->middleware('api.permission:sales.view')->group(function () {
            Route::get('/summary', [MobileSalesReportController::class, 'summary'])->name('summary');
            Route::get('/daily', [MobileSalesReportController::class, 'daily'])->name('daily');
            Route::get('/weekly', [MobileSalesReportController::class, 'weekly'])->name('weekly');
            Route::get('/monthly', [MobileSalesReportController::class, 'monthly'])->name('monthly');
            Route::get('/chart/{period?}', [MobileSalesReportController::class, 'chart'])->name('chart');
            Route::get('/top-products', [MobileSalesReportController::class, 'topProducts'])->name('top-products');
            Route::get('/by-store', [MobileSalesReportController::class, 'byStore'])->name('by-store');
        });

        // ===== Rapports de Stock (nécessite module_stock + permission + gestion de stock active) =====
        Route::prefix('stock')->name('stock.')->middleware(['feature:module_stock', 'api.permission:products.view', 'stock.required'])->group(function () {
            Route::get('/alerts', [MobileStockReportController::class, 'alerts'])->name('alerts');
            Route::get('/alerts/list', [MobileStockReportController::class, 'alertsList'])->name('alerts.list');
            Route::get('/summary', [MobileStockReportController::class, 'summary'])->name('summary');
            Route::get('/overview', [MobileStockReportController::class, 'overview'])->name('overview');
            Route::get('/dashboard', [MobileStockReportController::class, 'dashboard'])->name('dashboard');
            Route::get('/low-stock', [MobileStockReportController::class, 'lowStock'])->name('low-stock');
            Route::get('/out-of-stock', [MobileStockReportController::class, 'outOfStock'])->name('out-of-stock');
            Route::get('/value', [MobileStockReportController::class, 'stockValue'])->name('value');
            Route::get('/by-store', [MobileStockReportController::class, 'byStore'])->name('by-store');
            Route::get('/widget', [MobileStockReportController::class, 'widget'])->name('widget');

            // ===== Mouvements de Stock =====
            Route::get('/movements', [MobileStockMovementController::class, 'index'])->name('movements.index');
            Route::get('/movements/grouped', [MobileStockMovementController::class, 'groupedMovements'])->name('movements.grouped');
            Route::get('/movements/{id}', [MobileStockMovementController::class, 'show'])->name('movements.show');
            Route::post('/movements/add', [MobileStockMovementController::class, 'addStock'])->name('movements.add');
            Route::post('/movements/remove', [MobileStockMovementController::class, 'removeStock'])->name('movements.remove');
            Route::post('/movements/adjust', [MobileStockMovementController::class, 'adjustStock'])->name('movements.adjust');
            Route::get('/movement-types', [MobileStockMovementController::class, 'movementTypes'])->name('movement-types');

            // ===== Gestion des variantes =====
            Route::get('/search-variants', [MobileStockMovementController::class, 'searchVariants'])->name('search-variants');
            Route::get('/variant/{variantId}', [MobileStockMovementController::class, 'getVariantStock'])->name('variant');
            Route::get('/variant/{variantId}/history', [MobileStockMovementController::class, 'getVariantHistory'])->name('variant.history');
        });

        // ===== Gestion des Produits =====
        Route::prefix('products')->name('products.')->middleware('api.permission:products.view')->group(function () {
            // Recherche et utilitaires
            Route::get('/search', [MobileProductController::class, 'search'])->name('search');
            Route::get('/categories', [MobileProductController::class, 'categories'])->name('categories');
            Route::get('/product-types', [MobileProductController::class, 'productTypes'])->name('product-types');
            Route::get('/product-types/{id}', [MobileProductController::class, 'productTypeDetails'])->name('product-types.show');
            Route::get('/create-form-data', [MobileProductController::class, 'createFormData'])->name('create-form-data');
            Route::get('/generate-reference', [MobileProductController::class, 'generateReference'])->name('generate-reference');

            // Génération d'étiquettes PDF (nécessite feature export_pdf)
            Route::post('/labels/bulk', [MobileProductController::class, 'generateBulkLabels'])->middleware('feature:export_pdf')->name('labels.bulk');
            Route::get('/{id}/labels', [MobileProductController::class, 'generateLabels'])->middleware('feature:export_pdf')->name('labels');

            // CRUD
            Route::get('/', [MobileProductController::class, 'index'])->name('index');
            // Création de produit (vérifie la limite de produits + permission)
            Route::post('/', [MobileProductController::class, 'store'])->middleware(['resource.limit:products', 'api.permission:products.create'])->name('store');
            Route::get('/{id}', [MobileProductController::class, 'show'])->name('show');
            Route::put('/{id}', [MobileProductController::class, 'update'])->name('update');
            Route::delete('/{id}', [MobileProductController::class, 'destroy'])->name('destroy');

            // Actions
            Route::post('/{id}/archive', [MobileProductController::class, 'archive'])->name('archive');
            Route::post('/{id}/restore', [MobileProductController::class, 'restore'])->name('restore');
        });

        // ===== Gestion des Taxes =====
        Route::prefix('taxes')->name('taxes.')->middleware('api.permission:sales.view')->group(function () {
            // Utilitaires
            Route::get('/default', [MobileTaxController::class, 'default'])->name('default');
            Route::post('/calculate', [MobileTaxController::class, 'calculate'])->name('calculate');
            Route::post('/calculate-lines', [MobileTaxController::class, 'calculateLines'])->name('calculate-lines');

            // CRUD
            Route::get('/', [MobileTaxController::class, 'index'])->name('index');
            Route::post('/', [MobileTaxController::class, 'store'])->name('store');
            Route::get('/{id}', [MobileTaxController::class, 'show'])->name('show');
            Route::put('/{id}', [MobileTaxController::class, 'update'])->name('update');
            Route::delete('/{id}', [MobileTaxController::class, 'destroy'])->name('destroy');

            // Actions
            Route::post('/{id}/set-default', [MobileTaxController::class, 'setDefault'])->name('set-default');
        });

        // ===== Checkout / Facturation (nécessite permission sales.create) =====
        Route::prefix('checkout')->name('checkout.')->middleware('api.permission:sales.create')->group(function () {
            // Valider le panier avant checkout
            Route::post('/validate', [\App\Http\Controllers\Api\Mobile\MobileSalesController::class, 'validateCart'])->name('validate');

            // Créer une vente (facturation)
            Route::post('/', [\App\Http\Controllers\Api\Mobile\MobileSalesController::class, 'checkout'])->name('create');
        });

        // ===== Historique des Ventes =====
        Route::prefix('sales')->name('sales.')->middleware('api.permission:sales.view')->group(function () {
            // Statistiques des ventes (cohérent avec Livewire)
            Route::get('/statistics', [\App\Http\Controllers\Api\Mobile\MobileSalesController::class, 'statistics'])->name('statistics');

            // Liste des ventes
            Route::get('/', [\App\Http\Controllers\Api\Mobile\MobileSalesController::class, 'salesHistory'])->name('history');

            // Détail d'une vente
            Route::get('/{id}', [\App\Http\Controllers\Api\Mobile\MobileSalesController::class, 'saleDetail'])->name('detail');
        });

        // ===== Rapports et Statistiques (nécessite advanced_reports + permission) =====
        Route::get('/reports', [\App\Http\Controllers\Api\Mobile\ReportController::class, 'index'])
            ->middleware(['feature:advanced_reports', 'api.permission:reports.sales,reports.stock'])
            ->name('reports');

        // ===== Transfer API Routes (nécessite module_transfers + permission) =====
        Route::prefix('transfers')->name('api.transfers.')->middleware(['feature:module_transfers', 'api.permission:transfers.view', 'stock.required'])->group(function () {

            // List and filter transfers
            Route::get('/', [TransferApiController::class, 'index'])->name('index');

            // Show specific transfer
            Route::get('/{id}', [TransferApiController::class, 'show'])->name('show');

            // Create transfer
            Route::post('/', [TransferApiController::class, 'store'])->name('store');

            // Transfer actions
            Route::post('/{id}/approve', [TransferApiController::class, 'approve'])->name('approve');
            Route::post('/{id}/receive', [TransferApiController::class, 'receive'])->name('receive');
            Route::post('/{id}/cancel', [TransferApiController::class, 'cancel'])->name('cancel');
        });

        // ===== POS API Routes (nécessite permission sales.create) =====
        Route::prefix('pos')->name('pos.')->middleware('api.permission:sales.create')->group(function () {
            // Créer une vente depuis le POS
            Route::post('/sales', [\App\Http\Controllers\Api\Mobile\MobileSalesController::class, 'checkout'])->name('sales.create');
        });

        // ===== Gestion des Proformas (Devis) — nécessite module_proformas + permission =====
        Route::prefix('proformas')->name('proformas.')->middleware(['feature:module_proformas', 'api.permission:sales.view'])->group(function () {
            // Utilitaires
            Route::get('/search-products', [MobileProformaController::class, 'searchProducts'])->name('search-products');
            Route::get('/statistics', [MobileProformaController::class, 'statistics'])->name('statistics');

            // CRUD
            Route::get('/', [MobileProformaController::class, 'index'])->name('index');
            Route::post('/', [MobileProformaController::class, 'store'])->name('store');
            Route::get('/{id}', [MobileProformaController::class, 'show'])->name('show');
            Route::put('/{id}', [MobileProformaController::class, 'update'])->name('update');
            Route::delete('/{id}', [MobileProformaController::class, 'destroy'])->name('destroy');

            // Actions
            Route::post('/{id}/change-status', [MobileProformaController::class, 'changeStatus'])->name('change-status');
            Route::post('/{id}/convert-to-sale', [MobileProformaController::class, 'convertToSale'])->name('convert-to-sale');
            Route::post('/{id}/duplicate', [MobileProformaController::class, 'duplicate'])->name('duplicate');
            Route::post('/{id}/send-email', [MobileProformaController::class, 'sendEmail'])->name('send-email');
        });
        });

    }); // Fin du groupe feature:api_access
});
