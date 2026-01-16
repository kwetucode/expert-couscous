<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Store extends Model
{
    protected $fillable = [
        'name',
        'code',
        'slug',
        'address',
        'city',
        'country',
        'phone',
        'email',
        'manager_id',
        'organization_id',
        'store_number',
        'is_active',
        'is_main',
        'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_main' => 'boolean',
        'settings' => 'array',
    ];

    /**
     * Get the manager of the store
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * Get the organization this store belongs to
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get all users assigned to this store
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'store_user')
            ->withPivot('role', 'is_default')
            ->withTimestamps();
    }

    /**
     * Get the stock for this store
     */
    public function stock(): HasMany
    {
        return $this->hasMany(StoreStock::class);
    }

    /**
     * Get outgoing transfers from this store
     */
    public function outgoingTransfers(): HasMany
    {
        return $this->hasMany(StoreTransfer::class, 'from_store_id');
    }

    /**
     * Get incoming transfers to this store
     */
    public function incomingTransfers(): HasMany
    {
        return $this->hasMany(StoreTransfer::class, 'to_store_id');
    }

    /**
     * Get all transfers (incoming and outgoing)
     * Returns a query builder, not a HasMany relation
     */
    public function transfers()
    {
        return StoreTransfer::where('from_store_id', $this->id)
            ->orWhere('to_store_id', $this->id);
    }

    /**
     * Get all transfers as a collection
     */
    public function getAllTransfers()
    {
        return $this->transfers()->get();
    }

    /**
     * Get products in this store
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Get sales from this store
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * Get purchases for this store
     */
    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeMain($query)
    {
        return $query->where('is_main', true);
    }

    /**
     * Scope for filtering by organization
     */
    public function scopeForOrganization($query, ?int $organizationId = null)
    {
        if ($organizationId) {
            return $query->where('organization_id', $organizationId);
        }

        // Si pas d'ID fourni, utiliser l'organisation courante du contexte
        $organization = app()->bound('current_organization') ? app('current_organization') : null;
        if ($organization) {
            return $query->where('organization_id', $organization->id);
        }

        return $query;
    }

    /**
     * Get the main store
     */
    public static function mainStore(): ?Store
    {
        return static::where('is_main', true)->first();
    }

    /**
     * Check if this is the main store
     */
    public function isMain(): bool
    {
        return $this->is_main;
    }

    /**
     * Check if store is active
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Get total stock value for this store
     */
    public function getTotalStockValue(): float
    {
        return $this->stock()
            ->join('product_variants', 'store_stock.product_variant_id', '=', 'product_variants.id')
            ->join('products', 'product_variants.product_id', '=', 'products.id')
            ->selectRaw('SUM(store_stock.quantity * products.cost_price) as total')
            ->value('total') ?? 0;
    }

    /**
     * Get low stock count for this store
     */
    public function getLowStockCount(): int
    {
        return $this->stock()
            ->whereColumn('quantity', '<=', 'low_stock_threshold')
            ->where('quantity', '>', 0)
            ->count();
    }

    /**
     * Get out of stock count for this store
     */
    public function getOutOfStockCount(): int
    {
        return $this->stock()
            ->whereColumn('quantity', '<=', 'min_stock_threshold')
            ->count();
    }
}
