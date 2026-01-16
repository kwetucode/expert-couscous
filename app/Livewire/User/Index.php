<?php

namespace App\Livewire\User;

use App\Models\User;
use App\Models\Role;
use App\Models\Store;
use App\Models\Organization;
use App\Services\UserService;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public $search = '';
    public $roleFilter = '';
    public $storeFilter = '';
    public $sortBy = 'name';
    public $sortDirection = 'asc';
    public $perPage = 10;

    public $showDeleteModal = false;
    public $showModal = false;
    public $showAssignModal = false;
    public $editMode = false;

    public $selectedUserId;
    public $selectedUser;
    public $assignUserId;
    public $assignUser;

    // Form fields
    public $name = '';
    public $email = '';
    public $isActive = true;
    public $selectedRoles = [];
    public $selectedStores = [];
    public $storeRoles = []; // Store roles [store_id => role]
    public $defaultStore = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'roleFilter' => ['except' => ''],
        'storeFilter' => ['except' => ''],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingRoleFilter()
    {
        $this->resetPage();
    }

    public function updatingStoreFilter()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function openDeleteModal($userId)
    {
        $this->selectedUserId = $userId;
        $this->selectedUser = User::find($userId);
        $this->showDeleteModal = true;
    }

    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
        $this->selectedUserId = null;
        $this->selectedUser = null;
    }

    public function openAssignModal($userId)
    {
        $this->assignUserId = $userId;
        $this->assignUser = User::with(['roles', 'stores'])->find($userId);

        if ($this->assignUser) {
            $this->selectedRoles = $this->assignUser->roles->pluck('id')->toArray();
            $this->selectedStores = $this->assignUser->stores->pluck('id')->toArray();

            // Set store roles
            foreach ($this->assignUser->stores as $store) {
                $this->storeRoles[$store->id] = $store->pivot->role ?? 'staff';
                if ($store->pivot->is_default) {
                    $this->defaultStore = $store->id;
                }
            }

            // Dispatch Alpine.js event to open modal
            $this->dispatch('open-assign-modal');
        }
    }

    public function closeAssignModal()
    {
        $this->assignUserId = null;
        $this->assignUser = null;
        $this->selectedRoles = [];
        $this->selectedStores = [];
        $this->storeRoles = [];
        $this->defaultStore = null;
        $this->resetValidation();
        // Dispatch Alpine.js event to close modal
        $this->dispatch('close-assign-modal');
    }

    public function updateAssignments()
    {
        // Filter out empty values
        $this->selectedRoles = array_filter($this->selectedRoles, fn($value) => !empty($value));

        $this->validate([
            'selectedRoles' => 'required|array|min:1',
            'selectedRoles.*' => 'exists:roles,id',
            'selectedStores' => 'nullable|array',
            'selectedStores.*' => 'exists:stores,id',
        ], [
            'selectedRoles.required' => 'Au moins un rôle doit être sélectionné.',
            'selectedRoles.min' => 'Au moins un rôle doit être sélectionné.',
        ]);

        try {
            $userService = app(UserService::class);

            $storesData = [];
            foreach ($this->selectedStores as $storeId) {
                $storesData[$storeId] = [
                    'role' => $this->storeRoles[$storeId] ?? 'staff',
                    'is_default' => $this->defaultStore == $storeId,
                ];
            }

            $data = [
                'roles' => $this->selectedRoles,
                'stores' => $storesData,
            ];

            $userService->updateUser($this->assignUserId, $data);

            $this->closeAssignModal();
            $this->dispatch('show-toast', message: 'Assignations mises à jour avec succès.', type: 'success');
        } catch (\Exception $e) {
            \Log::error('Error updating assignments: ' . $e->getMessage());
            $this->dispatch('show-toast', message: 'Erreur: ' . $e->getMessage(), type: 'error');
        }
    }

    public function deleteUser($userId = null)
    {
        try {
            $userService = app(UserService::class);
            $userIdToDelete = $userId ?? $this->selectedUserId;
            $userService->deleteUser($userIdToDelete);

            $this->dispatch('show-toast', message: 'Utilisateur supprimé avec succès.', type: 'success');
            $this->closeDeleteModal();
        } catch (\Exception $e) {
            $this->dispatch('show-toast', message: 'Erreur: ' . $e->getMessage(), type: 'error');
        }
    }

    public function toggleUserStatus($userId)
    {
        try {
            $user = User::find($userId);

            if ($user) {
                $user->is_active = !$user->is_active;
                $user->save();

                $status = $user->is_active ? 'activé' : 'désactivé';
                $this->dispatch('show-toast', message: "Utilisateur {$status} avec succès.", type: 'success');
            }
        } catch (\Exception $e) {
            \Log::error('Error toggling user status: ' . $e->getMessage());
            $this->dispatch('show-toast', message: 'Erreur: ' . $e->getMessage(), type: 'error');
        }
    }

    public function openCreateModal()
    {
        $this->resetForm();
        $this->editMode = false;
        // Modal is opened via Alpine.js @click in the view
    }

    public function closeModal()
    {
        $this->editMode = false;
        $this->selectedUserId = null;
        $this->selectedUser = null;
        $this->resetForm();
        // Dispatch Alpine.js event to close modal
        $this->dispatch('close-user-modal');
    }

    public function openEditModal($userId)
    {
        $this->selectedUserId = $userId;
        $this->selectedUser = User::with(['roles', 'stores'])->find($userId);

        if ($this->selectedUser) {
            $this->name = $this->selectedUser->name;
            $this->email = $this->selectedUser->email;
            $this->isActive = $this->selectedUser->is_active ?? true;
            $this->selectedRoles = $this->selectedUser->roles->pluck('id')->toArray();
            $this->selectedStores = $this->selectedUser->stores->pluck('id')->toArray();

            // Set store roles
            foreach ($this->selectedUser->stores as $store) {
                $this->storeRoles[$store->id] = $store->pivot->role ?? 'staff';
                if ($store->pivot->is_default) {
                    $this->defaultStore = $store->id;
                }
            }

            $this->editMode = true;
            // Dispatch Alpine.js event to open edit modal
            $this->dispatch('open-edit-modal');
        }
    }

    public function save()
    {
        if ($this->editMode) {
            $this->updateUser();
        } else {
            $this->createUser();
        }
    }

    public function createUser()
    {
        \Log::info('CreateUser method called', [
            'name' => $this->name,
            'email' => $this->email,
            'selectedRoles' => $this->selectedRoles,
            'selectedStores' => $this->selectedStores,
        ]);

        // Filter out empty or null values from selectedRoles
        $this->selectedRoles = array_filter($this->selectedRoles, fn($value) => !empty($value));

        \Log::info('After filtering selectedRoles', ['selectedRoles' => $this->selectedRoles]);

        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'selectedRoles' => 'required|array|min:1',
            'selectedRoles.*' => 'exists:roles,id',
            'selectedStores' => 'nullable|array',
            'selectedStores.*' => 'exists:stores,id',
        ], [
            'name.required' => 'Le nom est requis.',
            'email.required' => 'L\'email est requis.',
            'email.email' => 'L\'email doit être valide.',
            'email.unique' => 'Cet email est déjà utilisé.',
            'selectedRoles.required' => 'Au moins un rôle doit être sélectionné.',
            'selectedRoles.min' => 'Au moins un rôle doit être sélectionné.',
        ]);

        \Log::info('Validation passed');

        try {
            $userService = app(UserService::class);

            $storesData = [];
            foreach ($this->selectedStores as $storeId) {
                $storesData[$storeId] = [
                    'role' => $this->storeRoles[$storeId] ?? 'staff',
                    'is_default' => $this->defaultStore == $storeId,
                ];
            }

            $userService->createUser([
                'name' => $this->name,
                'email' => $this->email,
                'password' => 'Password123!',
                'is_active' => $this->isActive,
                'roles' => $this->selectedRoles,
                'stores' => $storesData,
            ]);

            $this->closeModal();
            $this->dispatch('show-toast', message: 'Utilisateur créé avec succès.', type: 'success');
        } catch (\Exception $e) {
            \Log::error('Error creating user: ' . $e->getMessage());
            $this->dispatch('show-toast', message: 'Erreur: ' . $e->getMessage(), type: 'error');
        }
    }

    public function updateUser()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $this->selectedUserId,
            'selectedRoles' => 'required|array|min:1',
            'selectedRoles.*' => 'exists:roles,id',
            'selectedStores' => 'nullable|array',
            'selectedStores.*' => 'exists:stores,id',
        ], [
            'name.required' => 'Le nom est requis.',
            'email.required' => 'L\'email est requis.',
            'email.email' => 'L\'email doit être valide.',
            'email.unique' => 'Cet email est déjà utilisé.',
            'selectedRoles.required' => 'Au moins un rôle doit être sélectionné.',
            'selectedRoles.min' => 'Au moins un rôle doit être sélectionné.',
        ]);

        try {
            $userService = app(UserService::class);

            $storesData = [];
            foreach ($this->selectedStores as $storeId) {
                $storesData[$storeId] = [
                    'role' => $this->storeRoles[$storeId] ?? 'staff',
                    'is_default' => $this->defaultStore == $storeId,
                ];
            }

            $data = [
                'name' => $this->name,
                'email' => $this->email,
                'is_active' => $this->isActive,
                'roles' => $this->selectedRoles,
                'stores' => $storesData,
            ];

            $userService->updateUser($this->selectedUserId, $data);

            session()->flash('success', 'Utilisateur modifié avec succès.');
            $this->closeModal();
            $this->dispatch('show-toast', message: 'Utilisateur modifié avec succès.', type: 'success');
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    private function resetForm()
    {
        $this->name = '';
        $this->email = '';
        $this->isActive = true;
        $this->selectedRoles = [];
        $this->selectedStores = [];
        $this->storeRoles = [];
        $this->defaultStore = null;
        $this->resetValidation();
    }

    public function render()
    {
        $userService = app(UserService::class);

        $users = $userService->getAllUsers(
            $this->search,
            $this->roleFilter,
            $this->storeFilter ? (int)$this->storeFilter : null,
            $this->sortBy,
            $this->sortDirection,
            $this->perPage
        );

        $roles = Role::active()->get();

        // Get current organization ID
        $organizationId = $this->getCurrentOrganizationId();

        // Pour la gestion des accès, afficher les stores de l'organisation courante
        $stores = Store::when($organizationId, function ($query) use ($organizationId) {
            return $query->where('organization_id', $organizationId);
        })->orderBy('name')->get();

        return view('livewire.user.index', [
            'users' => $users,
            'roles' => $roles,
            'stores' => $stores,
        ]);
    }

    /**
     * Get the current organization ID from various sources
     */
    protected function getCurrentOrganizationId(): ?int
    {
        // 1. Try from app container
        try {
            $organization = app()->bound('current_organization') ? app('current_organization') : null;
            if ($organization) {
                return $organization->id;
            }
        } catch (\Exception $e) {
            // Continue to fallbacks
        }

        // 2. Try from session
        $orgId = session('current_organization_id');
        if ($orgId) {
            return (int) $orgId;
        }

        // 3. Try from authenticated user's current store
        $user = auth()->user();
        if ($user) {
            if ($user->current_store_id && $user->currentStore) {
                return $user->currentStore->organization_id;
            }

            // 4. Try user's default organization
            if ($user->default_organization_id) {
                return $user->default_organization_id;
            }

            // 5. Try user's first organization
            $userOrg = $user->organizations()->first();
            if ($userOrg) {
                return $userOrg->id;
            }
        }

        return null;
    }
}
