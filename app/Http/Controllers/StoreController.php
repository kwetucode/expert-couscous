<?php

namespace App\Http\Controllers;

use App\Actions\Store\CreateStoreAction;
use App\Actions\Store\UpdateStoreAction;
use App\Actions\Store\DeleteStoreAction;
use App\Actions\Store\SwitchUserStoreAction;
use App\Repositories\StoreRepository;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    public function index()
    {
        return view('livewire.store.store-index');
    }

    public function create()
    {
        return view('livewire.store.store-create');
    }

    public function show($id)
    {
        return view('livewire.store.store-show', ['storeId' => $id]);
    }

    public function edit($id)
    {
        return view('livewire.store.store-edit', ['storeId' => $id]);
    }

    /**
     * Switch current store for authenticated user
     * Pass null to view all stores (admin only)
     */
    public function switch(Request $request, ?string $storeId = null)
    {
        try {
            $user = auth()->user();

            // Si storeId est "null" ou vide, c'est pour voir tous les stores
            if ($storeId === 'null' || $storeId === '' || $storeId === null) {
                // Vérifier que l'utilisateur est admin
                if (!$user->isAdmin()) {
                    return redirect()->back()->with('error', 'Vous n\'avez pas les droits pour voir tous les magasins');
                }

                // Mettre à null pour voir tous les stores
                $user->update(['current_store_id' => null]);

                // Forcer la re-authentification
                auth()->setUser($user->fresh());

                // Mettre à jour la session
                session()->put('current_store_id', null);
                session()->save();

                return redirect()->back()->with('success', 'Affichage de tous les magasins');
            }

            // Convertir en int pour un store spécifique
            $storeIdInt = (int) $storeId;
            $action = app(SwitchUserStoreAction::class);
            $action->execute(auth()->id(), $storeIdInt);

            // Forcer la re-authentification
            auth()->setUser(auth()->user()->fresh());

            // Mettre à jour la session
            session()->put('current_store_id', $storeIdInt);
            session()->save();

            return redirect()->back()->with('success', 'Magasin changé avec succès');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Get stores for authenticated user (API)
     */
    public function userStores(StoreRepository $repository)
    {
        $stores = $repository->getStoresForUser(auth()->id());

        return response()->json([
            'stores' => $stores,
            'current_store_id' => auth()->user()->current_store_id,
        ]);
    }
}
