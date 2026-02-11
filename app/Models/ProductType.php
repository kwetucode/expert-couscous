<?php

namespace App\Models;

use App\Enums\BusinessActivityType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductType extends Model
{
    protected $fillable = [
        'organization_id',
        'name',
        'slug',
        'icon',
        'description',
        'has_variants',
        'has_expiry_date',
        'has_weight',
        'has_dimensions',
        'has_serial_number',
        'is_active',
        'display_order',
        // Service-specific fields
        'is_service',
        'default_duration_minutes',
        'requires_booking',
        // Business activity compatibility
        'compatible_activities',
    ];

    protected $casts = [
        'has_variants' => 'boolean',
        'has_expiry_date' => 'boolean',
        'has_weight' => 'boolean',
        'has_dimensions' => 'boolean',
        'has_serial_number' => 'boolean',
        'is_active' => 'boolean',
        'display_order' => 'integer',
        // Service-specific casts
        'is_service' => 'boolean',
        'requires_booking' => 'boolean',
        'default_duration_minutes' => 'integer',
        // Business activity compatibility
        'compatible_activities' => 'array',
    ];

    /**
     * Boot method to handle service type logic
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-disable physical product features when is_service is true
        static::saving(function ($productType) {
            if ($productType->is_service) {
                $productType->has_variants = false;
                $productType->has_weight = false;
                $productType->has_dimensions = false;
                $productType->has_expiry_date = false;
                $productType->has_serial_number = false;
            }
        });
    }

    /**
     * Get the organization that owns this product type
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Check if the product type can be modified by the current user/organization.
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

        // Si le type n'a pas d'organization_id, seul le super-admin peut le modifier
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

        // L'utilisateur peut modifier si c'est son organisation qui a créé le type
        return $currentOrgId && $this->organization_id === $currentOrgId;
    }

    /**
     * Get all attributes for this product type
     */
    public function attributes(): HasMany
    {
        return $this->hasMany(ProductAttribute::class)->orderBy('display_order');
    }

    /**
     * Get only variant attributes (used to generate product variants)
     */
    public function variantAttributes(): HasMany
    {
        return $this->attributes()->where('is_variant_attribute', true);
    }

    /**
     * Get only filterable attributes
     */
    public function filterableAttributes(): HasMany
    {
        return $this->attributes()->where('is_filterable', true);
    }

    /**
     * Get categories of this product type
     */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    /**
     * Get products of this product type
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Scope to get only active product types
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by display order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order');
    }

    /**
     * Scope to get only service types
     */
    public function scopeServices($query)
    {
        return $query->where('is_service', true);
    }

    /**
     * Scope to get only physical product types (non-services)
     */
    public function scopePhysicalProducts($query)
    {
        return $query->where('is_service', false);
    }

    /**
     * Scope to filter product types for the current organization.
     * Prioritizes organization-specific types over global types to avoid duplicates.
     */
    public function scopeForCurrentOrganization($query)
    {
        $organization = current_organization();
        
        if (!$organization) {
            // No organization context, show all active types
            return $query->where('is_active', true);
        }

        $isServiceOrg = is_service_organization($organization);

        // Check if organization has its own types
        $hasOwnTypes = static::where('organization_id', $organization->id)
            ->where('is_active', true)
            ->where('is_service', $isServiceOrg)
            ->exists();

        if ($hasOwnTypes) {
            // Show only organization-specific types (avoid duplicates with global)
            return $query
                ->where('organization_id', $organization->id)
                ->where('is_active', true)
                ->where('is_service', $isServiceOrg);
        }

        // Fallback to global types if no org-specific types exist yet
        return $query
            ->whereNull('organization_id')
            ->where('is_active', true)
            ->where('is_service', $isServiceOrg);
    }

    /**
     * Check if this product type is a service
     */
    public function isService(): bool
    {
        return (bool) $this->is_service;
    }

    /**
     * Check if this product type requires stock tracking
     */
    public function requiresStockTracking(): bool
    {
        return !$this->is_service;
    }

    /**
     * Check if this product type is compatible with a given business activity
     */
    public function isCompatibleWith(BusinessActivityType|string $activity): bool
    {
        // If no restrictions, compatible with all
        if (empty($this->compatible_activities)) {
            return true;
        }

        $activityValue = $activity instanceof BusinessActivityType
            ? $activity->value
            : $activity;

        // Mixed activities can use any type
        if ($activityValue === 'mixed') {
            return true;
        }

        return in_array($activityValue, $this->compatible_activities);
    }

    /**
     * Scope to filter by business activity compatibility
     */
    public function scopeCompatibleWithActivity($query, BusinessActivityType|string $activity)
    {
        $activityValue = $activity instanceof BusinessActivityType
            ? $activity->value
            : $activity;

        // Mixed can access everything
        if ($activityValue === 'mixed') {
            return $query;
        }

        return $query->where(function ($q) use ($activityValue) {
            // Types with null compatible_activities are available to all
            $q->whereNull('compatible_activities')
              // Or types that have this activity in their compatible list
              ->orWhereJsonContains('compatible_activities', $activityValue);
        });
    }

    /**
     * Scope to get product types available for a specific organization
     */
    public function scopeForOrganization($query, Organization $organization)
    {
        return $query
            ->where(function ($q) use ($organization) {
                // Global types (no organization_id) OR types created by this organization
                $q->whereNull('organization_id')
                  ->orWhere('organization_id', $organization->id);
            })
            ->where('is_active', true)
            ->compatibleWithActivity($organization->business_activity);
    }

    /**
     * Get the compatible activities as an array of labels
     */
    public function getCompatibleActivitiesLabelsAttribute(): array
    {
        if (empty($this->compatible_activities)) {
            return ['Tous les types d\'activité'];
        }

        return collect($this->compatible_activities)
            ->map(fn($activity) => BusinessActivityType::tryFrom($activity)?->label() ?? $activity)
            ->toArray();
    }
}
