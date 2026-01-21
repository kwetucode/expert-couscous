<?php

use App\Livewire\Dashboard;
use App\Livewire\Category\CategoryIndex;
use App\Livewire\Product\ProductIndex;
use App\Livewire\Sale\SaleIndex;
use App\Livewire\Sale\SaleCreate;
use App\Livewire\Sale\SaleEdit;
use App\Livewire\Purchase\PurchaseIndex;
use App\Livewire\Purchase\PurchaseCreate;
use App\Livewire\Purchase\PurchaseEdit;
use App\Livewire\Client\ClientIndex;
use App\Livewire\Supplier\SupplierIndex;
use App\Livewire\Invoice\InvoiceIndex;
use App\Livewire\Invoice\InvoiceCreate;
use App\Livewire\Invoice\InvoiceEdit;
use App\Livewire\Invoice\InvoiceShow;
use App\Livewire\Proforma\ProformaIndex;
use App\Livewire\Proforma\ProformaCreate;
use App\Livewire\Proforma\ProformaEdit;
use App\Livewire\Proforma\ProformaShow;
use App\Livewire\Pos\CashRegisterModular;
use App\Livewire\PrinterConfiguration;
use App\Livewire\Stock\StockIndex;
use App\Livewire\Stock\StockOverview;
use App\Livewire\Stock\StockDashboard;
use App\Livewire\Stock\StockAlerts;
use App\Livewire\Stock\StockHistory;
use App\Livewire\User\Index as UserIndex;
use App\Livewire\Role\Index as RoleIndex;
use App\Livewire\Role\Create as RoleCreate;
use App\Livewire\Role\Edit as RoleEdit;
use App\Livewire\Admin\MenuPermissionManager;
use App\Livewire\Admin\SubscriptionSettings;
use App\Livewire\Admin\SuperAdminDashboard;
use App\Livewire\Organization\OrganizationIndex;
use App\Livewire\Organization\OrganizationCreate;
use App\Livewire\Organization\OrganizationEdit;
use App\Livewire\Organization\OrganizationShow;
use App\Livewire\Organization\OrganizationMembers;
use App\Livewire\Organization\OrganizationPayment;
use App\Livewire\Organization\OrganizationTaxes;
use App\Livewire\Organization\SubscriptionManager;
use App\Livewire\ProductType\ProductTypeIndex;
use App\Livewire\ProductAttribute\AttributeManager;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\OrganizationInvitationController;
use App\Services\SubscriptionService;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $plans = SubscriptionService::getPlansFromDatabase();
    $currency = SubscriptionService::getCurrencyFromCache();

    return view('welcome', compact('plans', 'currency'));
})->name('home');

Route::middleware(['auth'])->group(function () {
    // Dashboard - accessible to all authenticated users
    Route::get('dashboard', Dashboard::class)->name('dashboard');

    // Super Admin Dashboard - accessible uniquement au super-admin
    Route::get('/admin/dashboard', SuperAdminDashboard::class)->name('admin.dashboard')->middleware('role:super-admin');

    // Categories Management
    Route::prefix('categories')->name('categories.')->middleware('permission:categories.view')->group(function () {
        Route::get('/', CategoryIndex::class)->name('index');
    });

    // Products Management
    Route::prefix('products')->name('products.')->middleware('permission:products.view')->group(function () {
        Route::get('/', ProductIndex::class)->name('index');
    });

    // Product Types Management
    Route::prefix('product-types')->name('product-types.')->middleware('permission:products.view')->group(function () {
        Route::get('/', ProductTypeIndex::class)->name('index');
    });

    // Product Attributes Management
    Route::prefix('product-attributes')->name('product-attributes.')->middleware('permission:products.view')->group(function () {
        Route::get('/', AttributeManager::class)->name('index');
    });

    // Sales Management
    Route::prefix('sales')->name('sales.')->middleware('permission:sales.view')->group(function () {
        Route::get('/', SaleIndex::class)->name('index');
        Route::get('/create', SaleCreate::class)->name('create')->middleware('permission:sales.create');
        Route::get('/{id}/edit', SaleEdit::class)->name('edit')->middleware('permission:sales.edit');
    });

    // Purchases Management
    Route::prefix('purchases')->name('purchases.')->middleware('permission:purchases.view')->group(function () {
        Route::get('/', PurchaseIndex::class)->name('index');
        Route::get('/create', PurchaseCreate::class)->name('create')->middleware('permission:purchases.create');
        Route::get('/{id}/edit', PurchaseEdit::class)->name('edit')->middleware('permission:purchases.edit');
    });

    // Clients Management
    Route::get('/clients', ClientIndex::class)->name('clients.index')->middleware('permission:clients.view');

    // Suppliers Management
    Route::get('/suppliers', SupplierIndex::class)->name('suppliers.index')->middleware('permission:suppliers.view');

    // Invoices Management
    Route::prefix('invoices')->name('invoices.')->middleware('permission:sales.view')->group(function () {
        Route::get('/', InvoiceIndex::class)->name('index');
        Route::get('/create', InvoiceCreate::class)->name('create')->middleware('permission:sales.create');
        Route::get('/{id}', InvoiceShow::class)->name('show');
        Route::get('/{id}/edit', InvoiceEdit::class)->name('edit')->middleware('permission:sales.edit');
    });

    // Proforma Invoices Management
    Route::prefix('proformas')->name('proformas.')->middleware('permission:sales.view')->group(function () {
        Route::get('/', ProformaIndex::class)->name('index');
        Route::get('/create', ProformaCreate::class)->name('create')->middleware('permission:sales.create');
        Route::get('/{proforma}', ProformaShow::class)->name('show');
        Route::get('/{proforma}/edit', ProformaEdit::class)->name('edit')->middleware('permission:sales.edit');
        Route::get('/{proforma}/pdf', [\App\Http\Controllers\ProformaPdfController::class, 'export'])->name('pdf');
        Route::get('/{proforma}/pdf/view', [\App\Http\Controllers\ProformaPdfController::class, 'stream'])->name('pdf.view');
    });

    // POS - Point of Sale
    Route::get('/pos', CashRegisterModular::class)->name('pos.cash-register')->middleware('permission:sales.create');

    // Printer Configuration

    Route::get('/printer-config', PrinterConfiguration::class)->name('printer.config');
    // Stock Management
    Route::prefix('stock')->name('stock.')->middleware('permission:products.view')->group(function () {
        Route::get('/', StockIndex::class)->name('index');
        Route::get('/overview', action: StockOverview::class)->name('overview');
        Route::get('/dashboard', StockDashboard::class)->name('dashboard');
        Route::get('/alerts', StockAlerts::class)->name('alerts');
        Route::get('/history/{variantId}', StockHistory::class)->name('history');

        // Exports
        Route::get('/export/excel', [\App\Http\Controllers\StockExportController::class, 'exportExcel'])->name('export.excel');
        Route::get('/export/pdf', [\App\Http\Controllers\StockExportController::class, 'exportPdf'])->name('export.pdf');
    });

    // Reports PDF
    Route::prefix('reports')->name('reports.')->middleware('permission:reports.sales,reports.stock')->group(function () {
        Route::get('/products', [ReportController::class, 'products'])->name('products');
        Route::get('/stock', [ReportController::class, 'stock'])->name('stock');
        Route::get('/stock-movements', [ReportController::class, 'stockMovements'])->name('stock-movements');
        Route::get('/inventory', [ReportController::class, 'inventory'])->name('inventory');
        Route::get('/stock-alerts', [ReportController::class, 'stockAlerts'])->name('stock-alerts');
    });

    // User Management - Admin only
    Route::prefix('users')->name('users.')->middleware('permission:users.view')->group(function () {
        Route::get('/', UserIndex::class)->name('index');
    });

    // Role Management - Admin only
    Route::prefix('roles')->name('roles.')->middleware('permission:roles.view')->group(function () {
        Route::get('/', RoleIndex::class)->name('index');
        Route::get('/create', RoleCreate::class)->name('create')->middleware('permission:roles.create');
        Route::get('/{id}/edit', RoleEdit::class)->name('edit')->middleware('permission:roles.edit');
    });

    // Menu Permissions Management - Super Admin only
    Route::get('/menu-permissions', MenuPermissionManager::class)->name('menu-permissions.index')->middleware('role:super-admin');

    // Subscription Admin Settings - Super Admin only
    Route::get('/admin/subscription-settings', SubscriptionSettings::class)->name('admin.subscription-settings')->middleware('role:super-admin');

    // Organization Management
    Route::prefix('organizations')->name('organizations.')->group(function () {
        Route::get('/', OrganizationIndex::class)->name('index');
        Route::get('/create', OrganizationCreate::class)->name('create')->middleware('role:super-admin');
        Route::get('/{organization}', OrganizationShow::class)->name('show');
        Route::get('/{organization}/edit', OrganizationEdit::class)->name('edit');
        Route::get('/{organization}/members', OrganizationMembers::class)->name('members');
        Route::get('/{organization}/taxes', OrganizationTaxes::class)->name('taxes');
        Route::get('/{organization}/subscription', SubscriptionManager::class)->name('subscription');
    });

    // Organization Payment Route
    Route::get('/organization/{organization}/payment', OrganizationPayment::class)
        ->name('organization.payment')
        ->where('organization', '[0-9]+');
});



// Organization Invitation Routes (public - no auth required)
Route::prefix('organization/invitation')->name('organization.invitation.')->group(function () {
    Route::get('/{token}', [OrganizationInvitationController::class, 'show'])->name('show');
    Route::post('/{token}/accept', [OrganizationInvitationController::class, 'accept'])->name('accept');
    Route::delete('/{token}/decline', [OrganizationInvitationController::class, 'decline'])->name('decline');
});

// Include store and transfer routes
require __DIR__ . '/stores.php';
