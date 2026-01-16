<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'last_login_at',
        'current_store_id',
        'default_organization_id',
        'is_active',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Check if user has two-factor authentication enabled.
     */
    public function hasTwoFactorEnabled(): bool
    {
        return !is_null($this->two_factor_secret);
    }

    /**
     * Cached admin status for performance
     */
    private ?bool $cachedIsAdmin = null;

    /**
     * Check if user is admin.
     * Checks for 'admin', 'super-admin' or 'manager' role in role_user table
     */
    public function isAdmin(): bool
    {
        // Charger les rôles si pas déjà chargés pour éviter N+1
        if (!$this->relationLoaded('roles')) {
            $this->load('roles');
        }

        // Vérifier dans la collection chargée plutôt que faire une requête
        return $this->roles->whereIn('slug', ['admin', 'super-admin', 'manager'])->isNotEmpty();
    }

    /**
     * Clear cached admin status
     */
    public function clearAdminCache(): void
    {
        $this->cachedIsAdmin = null;
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    /**
     * Get the roles assigned to this user.
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_user')
            ->withTimestamps();
    }

    /**
     * Get the stores this user has access to
     */
    public function stores()
    {
        return $this->belongsToMany(Store::class, 'store_user')
            ->withPivot('role', 'is_default')
            ->withTimestamps();
    }

    /**
     * Get the user's current store
     */
    public function currentStore()
    {
        return $this->belongsTo(Store::class, 'current_store_id');
    }

    /**
     * Get stores managed by this user
     */
    public function managedStores()
    {
        return $this->hasMany(Store::class, 'manager_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Organization Relations
    |--------------------------------------------------------------------------
    */

    /**
     * Organizations where user is a member
     */
    public function organizations()
    {
        return $this->belongsToMany(Organization::class, 'organization_user')
            ->withPivot('role', 'invited_at', 'accepted_at', 'invited_by', 'is_active')
            ->withTimestamps();
    }

    /**
     * Organizations owned by user
     */
    public function ownedOrganizations()
    {
        return $this->hasMany(Organization::class, 'owner_id');
    }

    /**
     * Default organization
     */
    public function defaultOrganization()
    {
        return $this->belongsTo(Organization::class, 'default_organization_id');
    }

    /**
     * Check if user belongs to an organization
     */
    public function belongsToOrganization(int $organizationId): bool
    {
        return $this->organizations()->where('organizations.id', $organizationId)->exists();
    }

    /**
     * Get user's role in an organization
     */
    public function getRoleInOrganization(Organization $organization): ?string
    {
        $membership = $this->organizations()
            ->where('organizations.id', $organization->id)
            ->first();

        return $membership?->pivot->role;
    }

    /**
     * Check if user is admin in an organization
     */
    public function isOrganizationAdmin(Organization $organization): bool
    {
        $role = $this->getRoleInOrganization($organization);
        return in_array($role, ['owner', 'admin']);
    }

    /**
     * Check if user is owner of an organization
     */
    public function isOrganizationOwner(Organization $organization): bool
    {
        return $organization->owner_id === $this->id;
    }

    /**
     * Get user's active organizations
     */
    public function activeOrganizations()
    {
        return $this->organizations()->wherePivot('is_active', true);
    }

    /**
     * Check if user has access to a specific store
     */
    public function hasAccessToStore(int $storeId): bool
    {
        // Les admins, super-admins et managers ont accès à tous les stores
        if ($this->hasAnyRole(['admin', 'super-admin', 'manager'])) {
            return true;
        }

        return $this->stores()->where('stores.id', $storeId)->exists();
    }

    /**
     * Get user's role in a specific store
     */
    public function getRoleInStore(int $storeId): ?string
    {
        $store = $this->stores()->where('stores.id', $storeId)->first();
        return $store?->pivot->role;
    }

    /**
     * Check if user has a specific role.
     */
    public function hasRole(string|array $roles): bool
    {
        if (is_array($roles)) {
            return $this->roles()->whereIn('slug', $roles)->exists();
        }

        return $this->roles()->where('slug', $roles)->exists();
    }

    /**
     * Check if user has any of the given roles.
     */
    public function hasAnyRole(array $roles): bool
    {
        return $this->roles()->whereIn('slug', $roles)->exists();
    }

    /**
     * Check if user has all of the given roles.
     */
    public function hasAllRoles(array $roles): bool
    {
        foreach ($roles as $role) {
            if (!$this->hasRole($role)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Assign a role to the user.
     */
    public function assignRole(string|int|Role $role): void
    {
        if (is_string($role)) {
            // Check if string is numeric (ID passed as string from form)
            if (is_numeric($role)) {
                $role = Role::findOrFail((int) $role);
            } else {
                $role = Role::where('slug', $role)->firstOrFail();
            }
        } elseif (is_int($role)) {
            $role = Role::findOrFail($role);
        }

        if (!$this->roles()->where('roles.id', $role->id)->exists()) {
            $this->roles()->attach($role->id);
        }
    }

    /**
     * Assign multiple roles to the user.
     */
    public function assignRoles(array $roles): void
    {
        foreach ($roles as $role) {
            $this->assignRole($role);
        }
    }

    /**
     * Remove a role from the user.
     */
    public function removeRole(string|int|Role $role): void
    {
        if (is_string($role)) {
            // Check if string is numeric (ID passed as string from form)
            if (is_numeric($role)) {
                $role = Role::find((int) $role);
            } else {
                $role = Role::where('slug', $role)->first();
            }
        } elseif (is_int($role)) {
            $role = Role::find($role);
        }

        if ($role) {
            $this->roles()->detach($role->id);
        }
    }

    /**
     * Sync user roles.
     */
    public function syncRoles(array $roles): void
    {
        $roleIds = [];

        foreach ($roles as $role) {
            if (is_string($role)) {
                // Check if string is numeric (ID passed as string from form)
                if (is_numeric($role)) {
                    $roleIds[] = (int) $role;
                } else {
                    $roleModel = Role::where('slug', $role)->first();
                    if ($roleModel) {
                        $roleIds[] = $roleModel->id;
                    }
                }
            } elseif (is_int($role)) {
                $roleIds[] = $role;
            } elseif ($role instanceof Role) {
                $roleIds[] = $role->id;
            }
        }

        $this->roles()->sync($roleIds);
    }

    /**
     * Check if user has a specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        foreach ($this->roles as $role) {
            if ($role->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user has any of the given permissions.
     */
    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user has all of the given permissions.
     */
    public function hasAllPermissions(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($permission)) {
                return false;
            }
        }

        return true;
    }
}

