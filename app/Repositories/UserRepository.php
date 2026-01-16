<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class UserRepository
{
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

    /**
     * Get all users with optional filters and pagination
     */
    public function getAllWithFilters(
        ?string $search = null,
        ?string $role = null,
        ?int $storeId = null,
        string $sortBy = 'name',
        string $sortDirection = 'asc',
        int $perPage = 10
    ): LengthAwarePaginator {
        $query = User::query()->with(['roles', 'stores', 'currentStore']);

        // Filter by current organization - show only users that belong to the same organization
        $organizationId = $this->getCurrentOrganizationId();
        if ($organizationId) {
            $query->where(function ($q) use ($organizationId) {
                // Users who belong to this organization via organization_user pivot table
                $q->whereHas('organizations', function ($subQ) use ($organizationId) {
                    $subQ->where('organizations.id', $organizationId);
                })
                // Or users whose default organization is this one
                ->orWhere('default_organization_id', $organizationId);
            });
        }

        // Search by name or email
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by role
        if ($role) {
            $query->whereHas('roles', function ($q) use ($role) {
                $q->where('slug', $role);
            });
        }

        // Filter by store
        if ($storeId) {
            $query->whereHas('stores', function ($q) use ($storeId) {
                $q->where('stores.id', $storeId);
            });
        }

        return $query->orderBy($sortBy, $sortDirection)->paginate($perPage);
    }

    /**
     * Find user by ID.
     */
    public function find(int $id): ?User
    {
        return User::with(['roles', 'stores', 'currentStore', 'managedStores'])->find($id);
    }

    /**
     * Find user by email.
     */
    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)
            ->with(['roles', 'stores', 'currentStore'])
            ->first();
    }

    /**
     * Get all users.
     */
    public function all(): Collection
    {
        return User::with(['roles', 'stores'])->orderBy('name')->get();
    }

    /**
     * Create a new user.
     */
    public function create(array $data): User
    {
        return User::create($data);
    }

    /**
     * Update user.
     */
    public function update(User $user, array $data): bool
    {
        return $user->update($data);
    }

    /**
     * Delete user.
     */
    public function delete(User $user): bool
    {
        return $user->delete();
    }

    /**
     * Check if email exists.
     */
    public function emailExists(string $email): bool
    {
        return User::where('email', $email)->exists();
    }

    /**
     * Update user's last login.
     */
    public function updateLastLogin(User $user): bool
    {
        return $user->update([
            'last_login_at' => now(),
        ]);
    }

    /**
     * Get active users.
     */
    public function getActive(): Collection
    {
        return User::whereNull('deleted_at')
            ->with(['roles', 'stores'])
            ->orderBy('name')
            ->get();
    }

    /**
     * Get users by role
     */
    public function getUsersByRole(string $roleSlug): Collection
    {
        return User::whereHas('roles', function ($q) use ($roleSlug) {
            $q->where('slug', $roleSlug);
        })->with(['roles', 'stores'])->get();
    }

    /**
     * Get users by store
     */
    public function getUsersByStore(int $storeId): Collection
    {
        return User::whereHas('stores', function ($q) use ($storeId) {
            $q->where('stores.id', $storeId);
        })->with(['roles', 'stores'])->get();
    }

    /**
     * Search users
     */
    public function search(string $query, int $limit = 10): Collection
    {
        return User::where('name', 'like', "%{$query}%")
            ->orWhere('email', 'like', "%{$query}%")
            ->with(['roles', 'stores'])
            ->limit($limit)
            ->get();
    }
}
