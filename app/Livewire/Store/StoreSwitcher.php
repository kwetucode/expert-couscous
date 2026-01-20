<?php

namespace App\Livewire\Store;

use App\Services\StoreService;
use Livewire\Component;

class StoreSwitcher extends Component
{
    public $currentStoreId;
    public $availableStores = [];
    public $showDropdown = false;

    protected $listeners = [
        'storeCreated' => 'refreshStores',
        'storeUpdated' => 'refreshStores',
        'organizationSwitched' => 'refreshStores',
    ];

    public function mount(StoreService $service)
    {
        $this->refreshStores($service);
    }

    public function refreshStores(StoreService $service = null)
    {
        $service = $service ?? app(StoreService::class);

        // Get current user and force reload of relationships
        $user = auth()->user();

        if (!$user) {
            $this->availableStores = [];
            return;
        }

        $user->load('roles', 'stores');

        // Super-admin n'a pas de stores ni d'organisation
        if ($user->hasRole('super-admin')) {
            $this->availableStores = [];
            $this->currentStoreId = null;
            return;
        }

        // Get current store
        $this->currentStoreId = $user->current_store_id;

        // Get current organization (avec gestion d'erreur)
        try {
            $currentOrganization = app()->bound('current_organization') ? app('current_organization') : null;
        } catch (\Exception $e) {
            $currentOrganization = $user->defaultOrganization;
        }

        // Get available stores for this user filtered by current organization
        if ($user->isAdmin()) {
            // Admins can access all stores but filtered by current organization
            $query = \App\Models\Store::query()
                ->where('is_active', true)
                ->orderBy('name');

            // Filter by current organization if one is selected
            if ($currentOrganization) {
                $query->where('organization_id', $currentOrganization->id);
            }

            $this->availableStores = $query->get()->toArray();
        } else {
            // Regular users can only access their assigned stores
            $query = $user->stores()
                ->where('is_active', true)
                ->orderBy('name');

            // Filter by current organization if one is selected
            if ($currentOrganization) {
                $query->where('organization_id', $currentOrganization->id);
            }

            $this->availableStores = $query->get()->toArray();
        }
    }

    public function switchStore($storeId, StoreService $service)
    {
        try {
            $user = auth()->user();

            // Si storeId est null, c'est pour voir tous les stores (admins uniquement)
            if ($storeId === null) {
                if (!$user->isAdmin()) {
                    throw new \Exception('Accès non autorisé');
                }

                // Mettre current_store_id à null pour voir tous les stores
                $user->update(['current_store_id' => null]);

                // Forcer le rafraîchissement de l'utilisateur pour éviter le cache
                $user->refresh();
            } else {
                // Changer vers un store spécifique
                $service->switchUserStore(auth()->id(), $storeId);
            }

            $this->currentStoreId = $storeId;
            $this->showDropdown = false;

            $message = $storeId === null ? 'Affichage de tous les magasins' : 'Magasin changé avec succès !';

            // Dispatch event before reload
            $this->dispatch('storeChanged', storeId: $storeId);

            // Force page reload to refresh all data
            return redirect(request()->header('Referer') ?? route('dashboard'));

        } catch (\Exception $e) {
            $this->dispatch('show-toast', message: 'Erreur : ' . $e->getMessage(), type: 'error');
        }
    }

    public function toggleDropdown()
    {
        $this->showDropdown = !$this->showDropdown;
    }

    public function closeDropdown()
    {
        $this->showDropdown = false;
    }

    public function render()
    {
        return view('livewire.store.store-switcher');
    }
}
