<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\User;
use App\Notifications\OrganizationInvitationNotification;
use App\Repositories\OrganizationRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Exception;

class OrganizationService
{
    public function __construct(
        private OrganizationRepository $repository
    ) {}

    /**
     * Créer une nouvelle organisation
     */
    public function create(array $data, User $owner): Organization
    {
        return DB::transaction(function () use ($data, $owner) {
            // Générer le slug si non fourni
            $data['slug'] = $data['slug'] ?? Str::slug($data['name']);
            $data['slug'] = $this->ensureUniqueSlug($data['slug']);
            $data['owner_id'] = $owner->id;

            // Appliquer les limites selon le plan
            $data = $this->applyPlanLimits($data);

            // Créer l'organisation
            $organization = $this->repository->create($data);

            // Ajouter le propriétaire comme membre avec le rôle 'owner'
            $organization->members()->attach($owner->id, [
                'role' => 'owner',
                'accepted_at' => now(),
                'is_active' => true,
            ]);

            // Définir comme organisation par défaut si l'utilisateur n'en a pas
            if (!$owner->default_organization_id) {
                $owner->update(['default_organization_id' => $organization->id]);
            }

            return $organization;
        });
    }

    /**
     * Mettre à jour une organisation
     */
    public function update(Organization $organization, array $data): Organization
    {
        // Régénérer le slug si le nom change
        if (isset($data['name']) && !isset($data['slug'])) {
            $newSlug = Str::slug($data['name']);
            if ($newSlug !== $organization->slug) {
                $data['slug'] = $this->ensureUniqueSlug($newSlug, $organization->id);
            }
        }

        // Appliquer les limites UNIQUEMENT si le plan change réellement
        if (isset($data['subscription_plan'])) {
            $currentPlan = $organization->subscription_plan instanceof \App\Enums\SubscriptionPlan
                ? $organization->subscription_plan->value
                : $organization->subscription_plan;

            if ($data['subscription_plan'] !== $currentPlan) {
                $data = $this->applyPlanLimits($data);
            }
        }

        return $this->repository->update($organization, $data);
    }

    /**
     * Supprimer une organisation
     */
    public function delete(Organization $organization): bool
    {
        return DB::transaction(function () use ($organization) {
            // Vérifier qu'il n'y a plus de magasins actifs
            if ($organization->stores()->count() > 0) {
                throw new Exception("Impossible de supprimer une organisation avec des magasins. Supprimez d'abord tous les magasins.");
            }

            // Détacher tous les membres
            $organization->members()->detach();

            // Supprimer les invitations en attente
            $organization->invitations()->delete();

            // Supprimer l'organisation (soft delete)
            return $this->repository->delete($organization);
        });
    }

    /**
     * Inviter un membre dans l'organisation
     */
    public function inviteMember(Organization $organization, string $email, string $role, User $invitedBy): OrganizationInvitation
    {
        // Vérifier les limites
        if (!$organization->canAddUser()) {
            throw new Exception("Limite d'utilisateurs atteinte ({$organization->max_users} maximum) pour cette organisation. Passez à un plan supérieur pour ajouter plus d'utilisateurs.");
        }

        // Vérifier si l'utilisateur existe déjà et est membre
        $existingUser = User::where('email', $email)->first();
        if ($existingUser && $organization->hasMember($existingUser)) {
            throw new Exception("Cet utilisateur ({$email}) est déjà membre de l'organisation.");
        }

        // Vérifier s'il y a déjà une invitation en attente pour cet email
        $existingInvitation = $organization->pendingInvitations()
            ->where('email', $email)
            ->first();

        if ($existingInvitation) {
            throw new Exception("Une invitation est déjà en attente pour cet email. Elle expire dans {$existingInvitation->days_until_expiration} jour(s).");
        }

        // Créer l'invitation
        $invitation = $organization->invitations()->create([
            'email' => $email,
            'role' => $role,
            'token' => Str::random(64),
            'invited_by' => $invitedBy->id,
            'expires_at' => now()->addDays(7),
        ]);

        // Envoyer la notification par email
        Notification::route('mail', $email)->notify(
            new OrganizationInvitationNotification($invitation, $organization)
        );

        return $invitation;
    }

    /**
     * Accepter une invitation
     */
    public function acceptInvitation(OrganizationInvitation $invitation, User $user): void
    {
        if ($invitation->isExpired()) {
            throw new Exception("Cette invitation a expiré.");
        }

        if ($invitation->isAccepted()) {
            throw new Exception("Cette invitation a déjà été acceptée.");
        }

        if ($invitation->email !== $user->email) {
            throw new Exception("Cette invitation n'est pas destinée à cet utilisateur.");
        }

        $organization = $invitation->organization;

        if (!$organization->canAddUser()) {
            throw new Exception("L'organisation a atteint sa limite d'utilisateurs.");
        }

        DB::transaction(function () use ($invitation, $organization, $user) {
            // Ajouter l'utilisateur comme membre
            $organization->members()->attach($user->id, [
                'role' => $invitation->role,
                'invited_at' => $invitation->created_at,
                'accepted_at' => now(),
                'invited_by' => $invitation->invited_by,
                'is_active' => true,
            ]);

            // Marquer l'invitation comme acceptée
            $invitation->update(['accepted_at' => now()]);

            // Définir comme organisation par défaut si l'utilisateur n'en a pas
            if (!$user->default_organization_id) {
                $user->update(['default_organization_id' => $organization->id]);
            }
        });
    }

    /**
     * Annuler une invitation
     */
    public function cancelInvitation(OrganizationInvitation $invitation): bool
    {
        return $invitation->delete();
    }

    /**
     * Renvoyer une invitation
     */
    public function resendInvitation(OrganizationInvitation $invitation): OrganizationInvitation
    {
        // Mettre à jour le token et la date d'expiration
        $invitation->update([
            'token' => Str::random(64),
            'expires_at' => now()->addDays(7),
        ]);

        // Renvoyer la notification par email
        Notification::route('mail', $invitation->email)->notify(
            new OrganizationInvitationNotification($invitation, $invitation->organization)
        );

        return $invitation->fresh();
    }

    /**
     * Ajouter un utilisateur existant directement (sans invitation)
     */
    public function addMember(Organization $organization, User $user, string $role = 'member', ?User $addedBy = null): void
    {
        if (!$organization->canAddUser()) {
            throw new Exception("Limite d'utilisateurs atteinte pour cette organisation.");
        }

        if ($organization->hasMember($user)) {
            throw new Exception("Cet utilisateur est déjà membre de l'organisation.");
        }

        $organization->members()->attach($user->id, [
            'role' => $role,
            'accepted_at' => now(),
            'invited_by' => $addedBy?->id,
            'is_active' => true,
        ]);
    }

    /**
     * Retirer un membre de l'organisation
     */
    public function removeMember(Organization $organization, User $user): void
    {
        if ($organization->isOwner($user)) {
            throw new Exception("Impossible de retirer le propriétaire de l'organisation. Transférez d'abord la propriété à un autre membre.");
        }

        if (!$organization->hasMember($user)) {
            throw new Exception("Cet utilisateur n'est pas membre de l'organisation.");
        }

        // Détacher le membre
        $organization->members()->detach($user->id);

        // Si c'était l'organisation par défaut de l'utilisateur, la changer
        if ($user->default_organization_id === $organization->id) {
            $newDefault = $user->organizations()->first();
            $user->update(['default_organization_id' => $newDefault?->id]);
        }
    }

    /**
     * Mettre à jour le rôle d'un membre
     */
    public function updateMemberRole(Organization $organization, User $user, string $newRole): void
    {
        if ($organization->isOwner($user) && $newRole !== 'owner') {
            throw new Exception("Impossible de modifier le rôle du propriétaire. Transférez d'abord la propriété.");
        }

        if (!$organization->hasMember($user)) {
            throw new Exception("Cet utilisateur n'est pas membre de l'organisation.");
        }

        $organization->members()->updateExistingPivot($user->id, ['role' => $newRole]);
    }

    /**
     * Activer/Désactiver un membre
     */
    public function toggleMemberStatus(Organization $organization, User $user): bool
    {
        if ($organization->isOwner($user)) {
            throw new Exception("Impossible de désactiver le propriétaire de l'organisation.");
        }

        $member = $organization->members()->where('user_id', $user->id)->first();

        if (!$member) {
            throw new Exception("Cet utilisateur n'est pas membre de l'organisation.");
        }

        $newStatus = !$member->pivot->is_active;
        $organization->members()->updateExistingPivot($user->id, ['is_active' => $newStatus]);

        return $newStatus;
    }

    /**
     * Transférer la propriété de l'organisation
     */
    public function transferOwnership(Organization $organization, User $newOwner): void
    {
        if (!$organization->hasMember($newOwner)) {
            throw new Exception("Le nouveau propriétaire doit être membre de l'organisation.");
        }

        if ($organization->isOwner($newOwner)) {
            throw new Exception("Cet utilisateur est déjà le propriétaire.");
        }

        DB::transaction(function () use ($organization, $newOwner) {
            $currentOwner = $organization->owner;

            // Mettre à jour le propriétaire
            $organization->update(['owner_id' => $newOwner->id]);

            // Mettre à jour les rôles
            $organization->members()->updateExistingPivot($currentOwner->id, ['role' => 'admin']);
            $organization->members()->updateExistingPivot($newOwner->id, ['role' => 'owner']);
        });
    }

    /**
     * Changer d'organisation active (contexte utilisateur)
     */
    public function switchOrganization(User $user, Organization $organization): void
    {
        if (!$user->belongsToOrganization($organization->id)) {
            throw new Exception("Vous n'avez pas accès à cette organisation.");
        }

        // Check if current store belongs to the new organization
        $currentStoreId = $user->current_store_id;
        $newStoreId = null;

        if ($currentStoreId) {
            $currentStore = \App\Models\Store::find($currentStoreId);
            if ($currentStore && $currentStore->organization_id === $organization->id) {
                // Current store is valid for the new organization
                $newStoreId = $currentStoreId;
            }
        }

        // If no valid store, find the first store of the new organization
        if (!$newStoreId) {
            $firstStore = \App\Models\Store::where('organization_id', $organization->id)
                ->where('is_active', true)
                ->orderByDesc('is_main')
                ->first();

            $newStoreId = $firstStore?->id;
        }

        $user->update([
            'default_organization_id' => $organization->id,
            'current_store_id' => $newStoreId,
        ]);

        session(['current_organization_id' => $organization->id]);
    }

    /**
     * Mettre à jour l'abonnement
     */
    public function updateSubscription(Organization $organization, string $plan, ?int $durationMonths = 1): Organization
    {
        $data = [
            'subscription_plan' => $plan,
            'is_trial' => false,
        ];

        // Définir les dates d'abonnement
        if ($plan !== 'free') {
            $data['subscription_starts_at'] = now();
            $data['subscription_ends_at'] = now()->addMonths($durationMonths);
        } else {
            $data['subscription_starts_at'] = null;
            $data['subscription_ends_at'] = null;
        }

        // Appliquer les nouvelles limites
        $data = $this->applyPlanLimits($data);

        return $this->repository->update($organization, $data);
    }

    /**
     * Vérifier une organisation (admin)
     */
    public function verify(Organization $organization): Organization
    {
        return $this->repository->update($organization, [
            'is_verified' => true,
            'verified_at' => now(),
        ]);
    }

    /**
     * Obtenir les statistiques d'une organisation
     */
    public function getStatistics(Organization $organization): array
    {
        return $this->repository->getStatistics($organization);
    }

    /**
     * Initialiser les types de produits et catégories pour une nouvelle organisation.
     * Copie les types de produits globaux (sans organization_id) compatibles
     * avec le type d'activité de l'organisation.
     */
    public function initializeProductTypesAndCategories(Organization $organization): void
    {
        $businessActivity = $organization->business_activity?->value ?? 'mixed';
        
        // Déterminer si c'est une organisation de services
        $isServiceOrg = ($businessActivity === 'services');
        $isMixed = ($businessActivity === 'mixed');

        // Récupérer tous les types de produits globaux (sans organization_id)
        // Filtrer par is_service selon le type d'organisation
        // Eager load attributes and categories to avoid lazy loading violation
        $query = \App\Models\ProductType::whereNull('organization_id')
            ->where('is_active', true)
            ->with(['attributes', 'categories' => function($q) {
                $q->whereNull('organization_id')->where('is_active', true);
            }]);
        
        // Pour les organisations mixtes, on prend tous les types
        // Pour les autres, on filtre par is_service
        if (!$isMixed) {
            $query->where('is_service', $isServiceOrg);
        }
        
        $globalProductTypes = $query->get();

        foreach ($globalProductTypes as $globalType) {
            // Vérifier si ce type de produit existe déjà pour cette organisation (par slug)
            $existingType = \App\Models\ProductType::where('organization_id', $organization->id)
                ->where('slug', $globalType->slug)
                ->first();

            if ($existingType) {
                // Le type existe déjà, on passe au suivant
                continue;
            }

            // Créer une copie du type de produit pour cette organisation
            $newType = $globalType->replicate();
            $newType->organization_id = $organization->id;
            $newType->save();

            // Copier les attributs du type de produit
            foreach ($globalType->attributes as $attribute) {
                // Vérifier si l'attribut existe déjà
                $existingAttribute = \App\Models\ProductAttribute::where('product_type_id', $newType->id)
                    ->where('name', $attribute->name)
                    ->first();

                if (!$existingAttribute) {
                    $newAttribute = $attribute->replicate();
                    $newAttribute->product_type_id = $newType->id;
                    $newAttribute->save();
                }
            }

            // Copier les catégories globales associées à ce type de produit
            foreach ($globalType->categories as $globalCategory) {
                // Vérifier si la catégorie existe déjà (par slug)
                $existingCategory = \App\Models\Category::where('organization_id', $organization->id)
                    ->where('slug', $globalCategory->slug)
                    ->first();

                if (!$existingCategory) {
                    // Créer une copie de la catégorie pour cette organisation
                    $newCategory = $globalCategory->replicate();
                    $newCategory->organization_id = $organization->id;
                    $newCategory->product_type_id = $newType->id; // Lier au nouveau type de produit
                    $newCategory->save();
                }
            }
        }

        // Si aucun type n'a été copié, créer des catégories par défaut
        $orgTypesCount = \App\Models\ProductType::where('organization_id', $organization->id)->count();
        if ($orgTypesCount === 0) {
            $this->createDefaultCategoriesIfNeeded($organization, $businessActivity);
        }
    }

    /**
     * Créer des catégories par défaut si l'organisation n'en a pas encore.
     */
    private function createDefaultCategoriesIfNeeded(Organization $organization, string $businessActivity): void
    {
        // Vérifier si l'organisation a déjà des catégories
        $existingCategories = \App\Models\Category::where('organization_id', $organization->id)->count();
        if ($existingCategories > 0) {
            return; // Des catégories existent déjà
        }

        // Catégories par défaut selon le type d'activité
        $defaultCategories = match($businessActivity) {
            'retail' => [
                ['name' => 'Vêtements', 'description' => 'Vêtements et accessoires de mode', 'icon' => '👕'],
                ['name' => 'Chaussures', 'description' => 'Chaussures pour tous', 'icon' => '👟'],
                ['name' => 'Accessoires', 'description' => 'Sacs, ceintures, bijoux', 'icon' => '👜'],
                ['name' => 'Électronique', 'description' => 'Appareils et accessoires électroniques', 'icon' => '📱'],
            ],
            'food' => [
                ['name' => 'Boissons', 'description' => 'Boissons fraîches et chaudes', 'icon' => '🥤'],
                ['name' => 'Produits frais', 'description' => 'Fruits, légumes et produits laitiers', 'icon' => '🥬'],
                ['name' => 'Épicerie', 'description' => 'Produits d\'épicerie générale', 'icon' => '🛒'],
                ['name' => 'Boulangerie', 'description' => 'Pains et pâtisseries', 'icon' => '🥖'],
            ],
            'services' => [
                ['name' => 'Coiffure', 'description' => 'Services de coiffure', 'icon' => '💇'],
                ['name' => 'Esthétique', 'description' => 'Soins esthétiques et beauté', 'icon' => '💅'],
                ['name' => 'Consultation', 'description' => 'Services de consultation', 'icon' => '🗣️'],
                ['name' => 'Réparation', 'description' => 'Services de réparation', 'icon' => '🔧'],
            ],
            'mixed' => [
                ['name' => 'Produits', 'description' => 'Produits physiques généraux', 'icon' => '📦'],
                ['name' => 'Services', 'description' => 'Services généraux', 'icon' => '🛠️'],
                ['name' => 'Accessoires', 'description' => 'Accessoires divers', 'icon' => '🎁'],
            ],
            default => [
                ['name' => 'Général', 'description' => 'Catégorie générale', 'icon' => '📦'],
            ],
        };

        foreach ($defaultCategories as $categoryData) {
            \App\Models\Category::create([
                'organization_id' => $organization->id,
                'name' => $categoryData['name'],
                'description' => $categoryData['description'],
                'slug' => \Illuminate\Support\Str::slug($categoryData['name'] . '-' . $organization->id),
                'icon' => $categoryData['icon'] ?? null,
                'is_active' => true,
            ]);
        }
    }

    /**
     * Appliquer les limites selon le plan d'abonnement
     */
    private function applyPlanLimits(array $data): array
    {
        $planLimits = [
            'free' => ['max_stores' => 1, 'max_users' => 3, 'max_products' => 100],
            'starter' => ['max_stores' => 3, 'max_users' => 10, 'max_products' => 1000],
            'professional' => ['max_stores' => 10, 'max_users' => 50, 'max_products' => 10000],
            'enterprise' => ['max_stores' => 100, 'max_users' => 500, 'max_products' => 100000],
        ];

        $plan = $data['subscription_plan'] ?? 'free';
        $limits = $planLimits[$plan] ?? $planLimits['free'];

        // Ne pas écraser les limites si elles sont déjà définies (personnalisation)
        return array_merge($limits, $data);
    }

    /**
     * Assurer l'unicité du slug
     */
    private function ensureUniqueSlug(string $slug, ?int $excludeId = null): string
    {
        $originalSlug = $slug;
        $counter = 1;

        while ($this->repository->slugExists($slug, $excludeId)) {
            $slug = "{$originalSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
