<?php

use App\Http\Controllers\StoreController;
use App\Http\Controllers\TransferController;
use App\Livewire\Store\StoreIndex;
use App\Livewire\Store\StoreCreate;
use App\Livewire\Store\StoreEdit;
use App\Livewire\Store\StoreShow;
use App\Livewire\Transfer\TransferIndex;
use App\Livewire\Transfer\TransferShow;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Store & Transfer Routes
|--------------------------------------------------------------------------
|
| Routes pour la gestion des magasins et des transferts inter-magasins
|
*/

Route::middleware(['auth'])->group(function () {

    // ===== Gestion des Magasins =====
    Route::prefix('stores')->name('stores.')->middleware('permission:stores.view')->group(function () {
        Route::get('/', StoreIndex::class)->name('index');
        Route::get('/create', StoreCreate::class)->name('create')->middleware('permission:stores.create');
        Route::get('/{storeId}/edit', StoreEdit::class)->name('edit')->middleware('permission:stores.edit');
        Route::get('/{storeId}', StoreShow::class)->name('show');

        // Changer de magasin (store peut être null pour "Tous les magasins")
        Route::post('/switch/{store?}', [StoreController::class, 'switch'])->name('switch');
    });

    // ===== Gestion des Transferts =====
    Route::prefix('transfers')->name('transfers.')->middleware('permission:transfers.view')->group(function () {
        Route::get('/', TransferIndex::class)->name('index');
        Route::get('/{transferId}', TransferShow::class)->name('show');

        // Actions sur transferts (handled by Livewire components)
    });

    // ===== API Routes =====
    Route::prefix('api')->name('api.')->group(function () {
        Route::get('/stores/user', [StoreController::class, 'userStores'])->name('stores.user');
    });
});
