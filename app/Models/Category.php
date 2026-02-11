<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory, BelongsToOrganization;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'organization_id',
        'product_type_id',
        'parent_id',
        'name',
        'description',
        'slug',
        'level',
        'path',
        'icon',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'level' => 'integer',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Bootstrap the model and its traits.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate slug if not provided
        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get all products in this category.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Get the product type that owns the category.
     */
    public function productType(): BelongsTo
    {
        return $this->belongsTo(ProductType::class);
    }

    /**
     * Get the parent category.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Get all child categories.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * Get all descendants recursively.
     */
    public function descendants(): HasMany
    {
        return $this->children()->with('descendants');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Scope to filter categories with products.
     */
    public function scopeWithProducts(Builder $query): Builder
    {
        return $query->has('products');
    }

    /**
     * Scope to filter categories without products.
     */
    public function scopeWithoutProducts(Builder $query): Builder
    {
        return $query->doesntHave('products');
    }

    /**
     * Scope to filter categories by search term.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (empty($term)) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', '%' . $term . '%')
              ->orWhere('description', 'like', '%' . $term . '%')
              ->orWhere('slug', 'like', '%' . $term . '%');
        });
    }

    /**
     * Scope to order categories by product count.
     */
    public function scopeOrderByProductCount(Builder $query, string $direction = 'desc'): Builder
    {
        return $query->withCount('products')->orderBy('products_count', $direction);
    }

    /**
     * Scope to get popular categories (with most products).
     */
    public function scopePopular(Builder $query, int $limit = 10): Builder
    {
        return $query->withCount('products')
            ->having('products_count', '>', 0)
            ->orderBy('products_count', 'desc')
            ->limit($limit);
    }

    /**
     * Scope to filter categories for the current organization.
     * Prioritizes organization-specific categories over global categories to avoid duplicates.
     */
    public function scopeForCurrentOrganization(Builder $query): Builder
    {
        $organization = current_organization();
        $isServiceOrg = is_service_organization();

        if (!$organization) {
            // No organization context, filter by service type only
            if ($isServiceOrg) {
                return $query->whereHas('productType', function ($subQ) {
                    $subQ->where('is_service', true);
                });
            }
            return $query->where(function ($q) {
                $q->whereNull('product_type_id')
                  ->orWhereHas('productType', function ($subQ) {
                      $subQ->where('is_service', false);
                  });
            });
        }

        // Check if organization has its own categories
        $hasOwnCategories = static::where('organization_id', $organization->id)
            ->where('is_active', true)
            ->exists();

        if ($hasOwnCategories) {
            // Show only organization-specific categories (avoid duplicates with global)
            $query->where('organization_id', $organization->id)
                  ->where('is_active', true);
        } else {
            // Fallback to global categories if no org-specific categories exist yet
            $query->whereNull('organization_id')
                  ->where('is_active', true);
        }

        // Also filter by service type
        if ($isServiceOrg) {
            return $query->whereHas('productType', function ($subQ) {
                $subQ->where('is_service', true);
            });
        }

        return $query->where(function ($q) {
            $q->whereNull('product_type_id')
              ->orWhereHas('productType', function ($subQ) {
                  $subQ->where('is_service', false);
              });
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors & Mutators
    |--------------------------------------------------------------------------
    */

    /**
     * Get the formatted name attribute.
     */
    public function getFormattedNameAttribute(): string
    {
        return ucfirst($this->name);
    }

    /**
     * Get the short description.
     */
    public function getShortDescriptionAttribute(): string
    {
        if (empty($this->description)) {
            return '';
        }

        return Str::limit($this->description, 100);
    }

    /**
     * Set the name attribute.
     */
    public function setNameAttribute(string $value): void
    {
        $this->attributes['name'] = trim($value);
    }

    /**
     * Set the description attribute.
     */
    public function setDescriptionAttribute(?string $value): void
    {
        $this->attributes['description'] = $value ? trim($value) : null;
    }

    /*
    |--------------------------------------------------------------------------
    | Business Logic Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Check if the category has products.
     */
    public function hasProducts(): bool
    {
        return $this->products()->exists();
    }

    /**
     * Get the number of products in this category.
     */
    public function getProductsCount(): int
    {
        return $this->products()->count();
    }

    /**
     * Check if the category can be deleted.
     */
    public function canBeDeleted(): bool
    {
        return !$this->hasProducts();
    }

    /**
     * Check if the category can be modified by the current user/organization.
     * Une catégorie peut être modifiée si :
     * - L'utilisateur est super-admin
     * - L'organisation de l'utilisateur est celle qui a créé la catégorie
     * - La catégorie n'a pas d'organization_id (catégorie globale) et l'utilisateur est super-admin
     */
    public function canBeModifiedBy($user = null): bool
    {
        $user = $user ?? auth()->user();

        if (!$user) {
            return false;
        }

        // Super-admin peut tout modifier
        if ($user->hasRole('super-admin')) {
            return true;
        }

        // Si la catégorie n'a pas d'organization_id, seul le super-admin peut la modifier
        if (!$this->organization_id) {
            return false;
        }

        // Récupérer l'organisation courante de l'utilisateur
        $currentOrgId = null;

        if ($user->current_store_id && $user->currentStore) {
            $currentOrgId = $user->currentStore->organization_id;
        } elseif ($user->default_organization_id) {
            $currentOrgId = $user->default_organization_id;
        } else {
            $userOrg = $user->organizations()->first();
            $currentOrgId = $userOrg?->id;
        }

        // L'utilisateur peut modifier si c'est son organisation qui a créé la catégorie
        return $currentOrgId && $this->organization_id === $currentOrgId;
    }

    /**
     * Get active products only.
     */
    public function getActiveProducts()
    {
        return $this->products()->where('status', 'active')->get();
    }

    /**
     * Get the URL for this category.
     */
    public function getUrl(): string
    {
        return route('categories.show', $this->slug);
    }
}
