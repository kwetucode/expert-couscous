<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\SubscriptionHistory;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Notifications\SubscriptionExpiringNotification;
use App\Notifications\SubscriptionExpiredNotification;
use App\Notifications\SubscriptionRenewedNotification;
use App\Notifications\SubscriptionUpgradedNotification;
use App\Repositories\OrganizationRepository;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubscriptionService
{
    /**
     * Limites par plan
     */
    public const PLAN_LIMITS = [
        'free' => ['max_stores' => 1, 'max_users' => 3, 'max_products' => 100],
        'starter' => ['max_stores' => 3, 'max_users' => 10, 'max_products' => 1000],
        'professional' => ['max_stores' => 10, 'max_users' => 50, 'max_products' => 10000],
        'enterprise' => ['max_stores' => 100, 'max_users' => 500, 'max_products' => 100000],
    ];

    /**
     * Labels des plans
     */
    public const PLAN_LABELS = [
        'free' => 'Gratuit',
        'starter' => 'Starter',
        'professional' => 'Professionnel',
        'enterprise' => 'Entreprise',
    ];

    /**
     * Ordre des plans (pour upgrade/downgrade)
     */
    public const PLAN_ORDER = [
        'free' => 0,
        'starter' => 1,
        'professional' => 2,
        'enterprise' => 3,
    ];

    public function __construct(
        private OrganizationRepository $organizationRepository
    ) {}

    /**
     * Récupérer tous les plans depuis la base de données
     */
    public static function getPlansFromDatabase(): array
    {
        $plansFromDb = SubscriptionPlan::active()->ordered()->get();

        $plans = [];
        foreach ($plansFromDb as $plan) {
            $plans[$plan->slug] = [
                'id' => $plan->id,
                'name' => $plan->name,
                'slug' => $plan->slug,
                'price' => $plan->price,
                'max_stores' => $plan->max_stores,
                'max_users' => $plan->max_users,
                'max_products' => $plan->max_products,
                'features' => $plan->features ?? [],
                'is_popular' => $plan->is_popular,
                'color' => $plan->color ?? 'gray',
            ];
        }

        return $plans ?: self::getDefaultPlans();
    }

    /**
     * Récupérer tous les plans depuis le cache ou les valeurs par défaut
     * @deprecated Utiliser getPlansFromDatabase() à la place
     */
    public static function getPlansFromCache(): array
    {
        return self::getPlansFromDatabase();
    }

    /**
     * Récupérer la devise depuis le cache
     */
    public static function getCurrencyFromCache(): string
    {
        return Cache::get('subscription_currency', 'CDF') ?: 'CDF';
    }

    /**
     * Convertit récursivement un objet/array en array PHP natif
     */
    protected static function ensureArrayRecursive(mixed $data): array
    {
        if (is_object($data)) {
            $data = (array) $data;
        }

        if (!is_array($data)) {
            return [];
        }

        foreach ($data as $key => $value) {
            if (is_object($value) || is_array($value)) {
                $data[$key] = self::ensureArrayRecursive($value);
            }
        }

        return $data;
    }

    /**
     * Plans par défaut
     */
    public static function getDefaultPlans(): array
    {
        return [
            'free' => [
                'id' => null, // ID null pour les plans par défaut
                'name' => 'Gratuit',
                'slug' => 'free',
                'price' => 0,
                'max_stores' => 1,
                'max_users' => 3,
                'max_products' => 100,
                'features' => [
                    'Jusqu\'à 1 magasin',
                    'Jusqu\'à 3 utilisateurs',
                    'Jusqu\'à 100 produits',
                    'Rapports de base',
                    'Support par email',
                ],
                'is_popular' => false,
                'color' => 'gray',
            ],
            'starter' => [
                'id' => null,
                'name' => 'Starter',
                'slug' => 'starter',
                'price' => 9900,
                'max_stores' => 3,
                'max_users' => 10,
                'max_products' => 1000,
                'features' => [
                    'Jusqu\'à 3 magasins',
                    'Jusqu\'à 10 utilisateurs',
                    'Jusqu\'à 1 000 produits',
                    'Rapports avancés',
                    'Support prioritaire',
                    'Exportation des données',
                ],
                'is_popular' => false,
                'color' => 'blue',
            ],
            'professional' => [
                'id' => null,
                'name' => 'Professionnel',
                'slug' => 'professional',
                'price' => 24900,
                'max_stores' => 10,
                'max_users' => 50,
                'max_products' => 10000,
                'features' => [
                    'Jusqu\'à 10 magasins',
                    'Jusqu\'à 50 utilisateurs',
                    'Jusqu\'à 10 000 produits',
                    'Rapports personnalisés',
                    'Support téléphonique',
                    'API access',
                    'Multi-devises',
                ],
                'is_popular' => true,
                'color' => 'purple',
            ],
            'enterprise' => [
                'id' => null,
                'name' => 'Entreprise',
                'slug' => 'enterprise',
                'price' => 49900,
                'max_stores' => 100,
                'max_users' => 500,
                'max_products' => 100000,
                'features' => [
                    'Jusqu\'à 100 magasins',
                    'Jusqu\'à 500 utilisateurs',
                    'Jusqu\'à 100 000 produits',
                    'Rapports sur mesure',
                    'Support dédié 24/7',
                    'API illimité',
                    'Multi-devises',
                    'Formation personnalisée',
                    'SLA garanti',
                ],
                'is_popular' => false,
                'color' => 'amber',
            ],
        ];
    }

    /**
     * Obtenir les détails d'un plan
     */
    public function getPlanDetails(string $plan): array
    {
        $limits = self::PLAN_LIMITS[$plan] ?? self::PLAN_LIMITS['free'];
        $price = SubscriptionPayment::PLAN_PRICES[$plan] ?? 0;

        return [
            'slug' => $plan,
            'name' => self::PLAN_LABELS[$plan] ?? $plan,
            'price' => $price,
            'limits' => $limits,
            'features' => $this->getPlanFeatures($plan),
        ];
    }

    /**
     * Obtenir les fonctionnalités d'un plan
     */
    public function getPlanFeatures(string $plan): array
    {
        $features = [
            'free' => [
                'Jusqu\'à 1 magasin',
                'Jusqu\'à 3 utilisateurs',
                'Jusqu\'à 100 produits',
                'Rapports de base',
                'Support par email',
            ],
            'starter' => [
                'Jusqu\'à 3 magasins',
                'Jusqu\'à 10 utilisateurs',
                'Jusqu\'à 1 000 produits',
                'Rapports avancés',
                'Support prioritaire',
                'Exportation des données',
            ],
            'professional' => [
                'Jusqu\'à 10 magasins',
                'Jusqu\'à 50 utilisateurs',
                'Jusqu\'à 10 000 produits',
                'Rapports personnalisés',
                'Support téléphonique',
                'API access',
                'Multi-devises',
            ],
            'enterprise' => [
                'Jusqu\'à 100 magasins',
                'Jusqu\'à 500 utilisateurs',
                'Jusqu\'à 100 000 produits',
                'Rapports sur mesure',
                'Support dédié 24/7',
                'API illimité',
                'Multi-devises',
                'Formation personnalisée',
                'SLA garanti',
            ],
        ];

        return $features[$plan] ?? $features['free'];
    }

    /**
     * Obtenir tous les plans disponibles
     */
    public function getAvailablePlans(): array
    {
        $plans = [];
        foreach (array_keys(self::PLAN_LIMITS) as $plan) {
            $plans[$plan] = $this->getPlanDetails($plan);
        }
        return $plans;
    }

    /**
     * Créer un abonnement initial (lors de la création d'organisation)
     */
    public function createInitialSubscription(
        Organization $organization,
        string $plan = 'free',
        bool $isTrial = true,
        int $trialDays = 14
    ): Organization {
        return DB::transaction(function () use ($organization, $plan, $isTrial, $trialDays) {
            $data = [
                'subscription_plan' => $plan,
                'is_trial' => $isTrial && $plan !== 'free',
                'subscription_starts_at' => now(),
            ];

            if ($plan !== 'free') {
                $data['subscription_ends_at'] = $isTrial
                    ? now()->addDays($trialDays)
                    : now()->addMonth();
            }

            $data = array_merge($data, self::PLAN_LIMITS[$plan] ?? self::PLAN_LIMITS['free']);

            $organization->update($data);

            // Enregistrer dans l'historique
            $action = $isTrial && $plan !== 'free'
                ? SubscriptionHistory::ACTION_TRIAL_STARTED
                : SubscriptionHistory::ACTION_CREATED;

            SubscriptionHistory::record($organization, $action);

            return $organization->fresh();
        });
    }

    /**
     * Mettre à niveau un abonnement (upgrade)
     */
    public function upgrade(
        Organization $organization,
        string $newPlan,
        int $durationMonths = 1,
        string $paymentMethod = SubscriptionPayment::METHOD_MOBILE_MONEY,
        ?string $transactionId = null
    ): Organization {
        $currentPlan = $organization->subscription_plan;

        // Vérifier que c'est bien un upgrade
        if (!$this->isUpgrade($currentPlan, $newPlan)) {
            throw new Exception("Le plan {$newPlan} n'est pas supérieur au plan actuel {$currentPlan}.");
        }

        return DB::transaction(function () use ($organization, $newPlan, $currentPlan, $durationMonths, $paymentMethod, $transactionId) {
            // Créer le paiement
            $payment = SubscriptionPayment::createForOrganization(
                $organization,
                $newPlan,
                $durationMonths,
                $paymentMethod
            );

            // Si transaction ID fourni, marquer comme complété
            if ($transactionId) {
                $payment->markAsCompleted($transactionId);
            }

            // Mettre à jour l'organisation
            $organization->update([
                'subscription_plan' => $newPlan,
                'subscription_starts_at' => now(),
                'subscription_ends_at' => now()->addMonths($durationMonths),
                'is_trial' => false,
                ...self::PLAN_LIMITS[$newPlan],
            ]);

            // Enregistrer dans l'historique
            SubscriptionHistory::record(
                $organization,
                SubscriptionHistory::ACTION_UPGRADED,
                $currentPlan,
                $payment,
                "Upgrade de {$currentPlan} vers {$newPlan}"
            );

            // Notifier le propriétaire
            $organization->owner->notify(new SubscriptionUpgradedNotification($organization, $currentPlan, $newPlan));

            Log::info("Subscription upgraded", [
                'organization_id' => $organization->id,
                'old_plan' => $currentPlan,
                'new_plan' => $newPlan,
                'payment_id' => $payment->id,
            ]);

            return $organization->fresh();
        });
    }

    /**
     * Rétrograder un abonnement (downgrade)
     */
    public function downgrade(
        Organization $organization,
        string $newPlan,
        bool $immediate = false
    ): Organization {
        $currentPlan = $organization->subscription_plan;

        // Vérifier que c'est bien un downgrade
        if (!$this->isDowngrade($currentPlan, $newPlan)) {
            throw new Exception("Le plan {$newPlan} n'est pas inférieur au plan actuel {$currentPlan}.");
        }

        // Vérifier les contraintes avant downgrade
        $this->validateDowngradeConstraints($organization, $newPlan);

        return DB::transaction(function () use ($organization, $newPlan, $currentPlan, $immediate) {
            if ($immediate) {
                // Application immédiate
                $organization->update([
                    'subscription_plan' => $newPlan,
                    'is_trial' => false,
                    ...self::PLAN_LIMITS[$newPlan],
                ]);

                if ($newPlan === 'free') {
                    $organization->update([
                        'subscription_starts_at' => null,
                        'subscription_ends_at' => null,
                    ]);
                }
            } else {
                // Planifié à la fin de l'abonnement actuel
                $organization->update([
                    'metadata' => array_merge($organization->metadata ?? [], [
                        'scheduled_downgrade' => [
                            'plan' => $newPlan,
                            'scheduled_at' => now()->toISOString(),
                            'effective_at' => $organization->subscription_ends_at?->toISOString(),
                        ],
                    ]),
                ]);
            }

            // Enregistrer dans l'historique
            SubscriptionHistory::record(
                $organization,
                SubscriptionHistory::ACTION_DOWNGRADED,
                $currentPlan,
                null,
                $immediate ? "Downgrade immédiat vers {$newPlan}" : "Downgrade planifié vers {$newPlan}"
            );

            Log::info("Subscription downgraded", [
                'organization_id' => $organization->id,
                'old_plan' => $currentPlan,
                'new_plan' => $newPlan,
                'immediate' => $immediate,
            ]);

            return $organization->fresh();
        });
    }

    /**
     * Renouveler un abonnement
     */
    public function renew(
        Organization $organization,
        int $durationMonths = 1,
        string $paymentMethod = SubscriptionPayment::METHOD_MOBILE_MONEY,
        ?string $transactionId = null
    ): Organization {
        $plan = $organization->subscription_plan;

        if ($plan === 'free') {
            throw new Exception("Le plan gratuit n'a pas besoin de renouvellement.");
        }

        return DB::transaction(function () use ($organization, $plan, $durationMonths, $paymentMethod, $transactionId) {
            // Créer le paiement
            $payment = SubscriptionPayment::createForOrganization(
                $organization,
                $plan,
                $durationMonths,
                $paymentMethod
            );

            if ($transactionId) {
                $payment->markAsCompleted($transactionId);
            }

            // Calculer la nouvelle date de fin
            $newEndDate = $organization->subscription_ends_at && $organization->subscription_ends_at->isFuture()
                ? $organization->subscription_ends_at->addMonths($durationMonths)
                : now()->addMonths($durationMonths);

            $organization->update([
                'subscription_ends_at' => $newEndDate,
                'is_trial' => false,
            ]);

            // Enregistrer dans l'historique
            SubscriptionHistory::record(
                $organization,
                SubscriptionHistory::ACTION_RENEWED,
                null,
                $payment,
                "Renouvellement pour {$durationMonths} mois"
            );

            // Notifier le propriétaire
            $organization->owner->notify(new SubscriptionRenewedNotification($organization, $durationMonths));

            Log::info("Subscription renewed", [
                'organization_id' => $organization->id,
                'plan' => $plan,
                'duration_months' => $durationMonths,
                'new_end_date' => $newEndDate->toISOString(),
            ]);

            return $organization->fresh();
        });
    }

    /**
     * Annuler un abonnement
     */
    public function cancel(
        Organization $organization,
        bool $immediate = false,
        ?string $reason = null
    ): Organization {
        if ($organization->subscription_plan === 'free') {
            throw new Exception("Le plan gratuit ne peut pas être annulé.");
        }

        return DB::transaction(function () use ($organization, $immediate, $reason) {
            $currentPlan = $organization->subscription_plan;

            if ($immediate) {
                // Passage immédiat au plan gratuit
                $organization->update([
                    'subscription_plan' => 'free',
                    'subscription_starts_at' => null,
                    'subscription_ends_at' => null,
                    'is_trial' => false,
                    ...self::PLAN_LIMITS['free'],
                ]);
            } else {
                // L'abonnement reste actif jusqu'à expiration
                $organization->update([
                    'metadata' => array_merge($organization->metadata ?? [], [
                        'cancellation' => [
                            'cancelled_at' => now()->toISOString(),
                            'reason' => $reason,
                            'effective_at' => $organization->subscription_ends_at?->toISOString(),
                        ],
                    ]),
                ]);
            }

            // Enregistrer dans l'historique
            SubscriptionHistory::record(
                $organization,
                SubscriptionHistory::ACTION_CANCELLED,
                $currentPlan,
                null,
                $reason ?? ($immediate ? 'Annulation immédiate' : 'Annulation à la fin de la période')
            );

            Log::info("Subscription cancelled", [
                'organization_id' => $organization->id,
                'plan' => $currentPlan,
                'immediate' => $immediate,
                'reason' => $reason,
            ]);

            return $organization->fresh();
        });
    }

    /**
     * Réactiver un abonnement expiré
     */
    public function reactivate(
        Organization $organization,
        string $plan,
        int $durationMonths = 1,
        string $paymentMethod = SubscriptionPayment::METHOD_MOBILE_MONEY,
        ?string $transactionId = null
    ): Organization {
        return DB::transaction(function () use ($organization, $plan, $durationMonths, $paymentMethod, $transactionId) {
            $oldPlan = $organization->subscription_plan;

            // Créer le paiement si plan payant
            $payment = null;
            if ($plan !== 'free') {
                $payment = SubscriptionPayment::createForOrganization(
                    $organization,
                    $plan,
                    $durationMonths,
                    $paymentMethod
                );

                if ($transactionId) {
                    $payment->markAsCompleted($transactionId);
                }
            }

            // Mettre à jour l'organisation
            $data = [
                'subscription_plan' => $plan,
                'is_trial' => false,
                ...self::PLAN_LIMITS[$plan],
            ];

            if ($plan !== 'free') {
                $data['subscription_starts_at'] = now();
                $data['subscription_ends_at'] = now()->addMonths($durationMonths);
            }

            // Nettoyer les métadonnées d'annulation
            $metadata = $organization->metadata ?? [];
            unset($metadata['cancellation'], $metadata['scheduled_downgrade']);
            $data['metadata'] = $metadata ?: null;

            $organization->update($data);

            // Enregistrer dans l'historique
            SubscriptionHistory::record(
                $organization,
                SubscriptionHistory::ACTION_REACTIVATED,
                $oldPlan,
                $payment,
                "Réactivation avec le plan {$plan}"
            );

            Log::info("Subscription reactivated", [
                'organization_id' => $organization->id,
                'old_plan' => $oldPlan,
                'new_plan' => $plan,
            ]);

            return $organization->fresh();
        });
    }

    /**
     * Marquer un abonnement comme expiré
     */
    public function markAsExpired(Organization $organization): Organization
    {
        return DB::transaction(function () use ($organization) {
            $currentPlan = $organization->subscription_plan;

            // Passer au plan gratuit
            $organization->update([
                'subscription_plan' => 'free',
                'subscription_starts_at' => null,
                'subscription_ends_at' => null,
                'is_trial' => false,
                ...self::PLAN_LIMITS['free'],
            ]);

            // Enregistrer dans l'historique
            SubscriptionHistory::record(
                $organization,
                SubscriptionHistory::ACTION_EXPIRED,
                $currentPlan,
                null,
                "Expiration automatique du plan {$currentPlan}"
            );

            // Notifier le propriétaire
            $organization->owner->notify(new SubscriptionExpiredNotification($organization, $currentPlan));

            Log::info("Subscription expired", [
                'organization_id' => $organization->id,
                'old_plan' => $currentPlan,
            ]);

            return $organization->fresh();
        });
    }

    /**
     * Terminer une période d'essai
     */
    public function endTrial(Organization $organization, bool $convertToPaid = false): Organization
    {
        if (!$organization->is_trial) {
            throw new Exception("L'organisation n'est pas en période d'essai.");
        }

        return DB::transaction(function () use ($organization, $convertToPaid) {
            $currentPlan = $organization->subscription_plan;

            if ($convertToPaid) {
                // Convertir en abonnement payé
                $organization->update([
                    'is_trial' => false,
                    'subscription_ends_at' => now()->addMonth(),
                ]);
            } else {
                // Passer au plan gratuit
                $organization->update([
                    'subscription_plan' => 'free',
                    'subscription_starts_at' => null,
                    'subscription_ends_at' => null,
                    'is_trial' => false,
                    ...self::PLAN_LIMITS['free'],
                ]);
            }

            // Enregistrer dans l'historique
            SubscriptionHistory::record(
                $organization,
                SubscriptionHistory::ACTION_TRIAL_ENDED,
                $currentPlan,
                null,
                $convertToPaid ? "Conversion en abonnement payé" : "Fin d'essai - passage au plan gratuit"
            );

            return $organization->fresh();
        });
    }

    /**
     * Obtenir les organisations avec abonnements expirants
     */
    public function getExpiringSubscriptions(int $days = 7): Collection
    {
        return $this->organizationRepository->getExpiringSubscriptions($days);
    }

    /**
     * Obtenir les organisations avec abonnements expirés
     */
    public function getExpiredSubscriptions(): Collection
    {
        return Organization::query()
            ->where('subscription_plan', '!=', 'free')
            ->where('subscription_ends_at', '<', now())
            ->get();
    }

    /**
     * Envoyer les notifications d'expiration imminente
     */
    public function sendExpiringNotifications(int $days = 7): int
    {
        $organizations = $this->getExpiringSubscriptions($days);
        $count = 0;

        foreach ($organizations as $organization) {
            $organization->owner->notify(new SubscriptionExpiringNotification($organization));
            $count++;
        }

        Log::info("Expiring subscription notifications sent", ['count' => $count]);

        return $count;
    }

    /**
     * Traiter les abonnements expirés
     */
    public function processExpiredSubscriptions(): int
    {
        $organizations = $this->getExpiredSubscriptions();
        $count = 0;

        foreach ($organizations as $organization) {
            $this->markAsExpired($organization);
            $count++;
        }

        Log::info("Expired subscriptions processed", ['count' => $count]);

        return $count;
    }

    /**
     * Obtenir l'historique des abonnements d'une organisation
     */
    public function getHistory(Organization $organization, int $limit = 20): Collection
    {
        return SubscriptionHistory::query()
            ->forOrganization($organization->id)
            ->with(['user', 'payment'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Obtenir les paiements d'une organisation
     */
    public function getPayments(Organization $organization, int $limit = 20): Collection
    {
        return SubscriptionPayment::query()
            ->forOrganization($organization->id)
            ->with('user')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Obtenir les statistiques d'abonnement d'une organisation
     */
    public function getSubscriptionStats(Organization $organization): array
    {
        $totalPaid = SubscriptionPayment::query()
            ->forOrganization($organization->id)
            ->completed()
            ->sum('total');

        $paymentsCount = SubscriptionPayment::query()
            ->forOrganization($organization->id)
            ->completed()
            ->count();

        $changesCount = SubscriptionHistory::query()
            ->forOrganization($organization->id)
            ->count();

        return [
            'current_plan' => $organization->subscription_plan,
            'plan_label' => self::PLAN_LABELS[$organization->subscription_plan] ?? $organization->subscription_plan,
            'is_trial' => $organization->is_trial,
            'is_active' => $organization->hasActiveSubscription(),
            'starts_at' => $organization->subscription_starts_at,
            'ends_at' => $organization->subscription_ends_at,
            'remaining_days' => $organization->remaining_days,
            'is_expiring_soon' => $organization->isSubscriptionExpiringSoon(),
            'total_paid' => $totalPaid,
            'payments_count' => $paymentsCount,
            'changes_count' => $changesCount,
            'limits' => [
                'max_stores' => $organization->max_stores,
                'max_users' => $organization->max_users,
                'max_products' => $organization->max_products,
            ],
            'usage' => [
                'stores' => $organization->getStoresUsage(),
                'users' => $organization->getUsersUsage(),
            ],
        ];
    }

    /**
     * Vérifier si c'est un upgrade
     */
    public function isUpgrade(string $currentPlan, string $newPlan): bool
    {
        $currentOrder = self::PLAN_ORDER[$currentPlan] ?? 0;
        $newOrder = self::PLAN_ORDER[$newPlan] ?? 0;
        return $newOrder > $currentOrder;
    }

    /**
     * Vérifier si c'est un downgrade
     */
    public function isDowngrade(string $currentPlan, string $newPlan): bool
    {
        $currentOrder = self::PLAN_ORDER[$currentPlan] ?? 0;
        $newOrder = self::PLAN_ORDER[$newPlan] ?? 0;
        return $newOrder < $currentOrder;
    }

    /**
     * Valider les contraintes de downgrade
     */
    public function validateDowngradeConstraints(Organization $organization, string $newPlan): void
    {
        $limits = self::PLAN_LIMITS[$newPlan] ?? self::PLAN_LIMITS['free'];

        // Vérifier le nombre de magasins
        $storesCount = $organization->stores()->count();
        if ($storesCount > $limits['max_stores']) {
            throw new Exception(
                "Impossible de passer au plan {$newPlan}. Vous avez {$storesCount} magasin(s), " .
                "mais ce plan n'autorise que {$limits['max_stores']} magasin(s)."
            );
        }

        // Vérifier le nombre d'utilisateurs
        $usersCount = $organization->members()->count();
        if ($usersCount > $limits['max_users']) {
            throw new Exception(
                "Impossible de passer au plan {$newPlan}. Vous avez {$usersCount} utilisateur(s), " .
                "mais ce plan n'autorise que {$limits['max_users']} utilisateur(s)."
            );
        }

        // Vérifier le nombre de produits
        $productsCount = $organization->stores()
            ->withCount('products')
            ->get()
            ->sum('products_count');

        if ($productsCount > $limits['max_products']) {
            throw new Exception(
                "Impossible de passer au plan {$newPlan}. Vous avez {$productsCount} produit(s), " .
                "mais ce plan n'autorise que {$limits['max_products']} produit(s)."
            );
        }
    }

    /**
     * Calculer le prorata pour un changement de plan
     */
    public function calculateProrata(Organization $organization, string $newPlan): array
    {
        $currentPlan = $organization->subscription_plan;
        $currentPrice = SubscriptionPayment::PLAN_PRICES[$currentPlan] ?? 0;
        $newPrice = SubscriptionPayment::PLAN_PRICES[$newPlan] ?? 0;

        // Si pas de date de fin, pas de prorata
        if (!$organization->subscription_ends_at) {
            return [
                'credit' => 0,
                'charge' => $newPrice,
                'total' => $newPrice,
                'remaining_days' => 0,
            ];
        }

        $remainingDays = max(0, now()->diffInDays($organization->subscription_ends_at, false));
        $daysInMonth = 30;

        // Crédit pour le temps restant sur l'ancien plan
        $credit = ($currentPrice / $daysInMonth) * $remainingDays;

        // Charge pour le nouveau plan (mois complet)
        $charge = $newPrice;

        // Total à payer
        $total = max(0, $charge - $credit);

        return [
            'credit' => round($credit, 2),
            'charge' => $charge,
            'total' => round($total, 2),
            'remaining_days' => $remainingDays,
        ];
    }

    /**
     * Récupérer un plan par son ID
     */
    public static function getPlanById(int $planId): ?SubscriptionPlan
    {
        return SubscriptionPlan::find($planId);
    }

    /**
     * Mettre à jour un plan
     */
    public static function updatePlan(int $planId, array $data): bool
    {
        $plan = SubscriptionPlan::findOrFail($planId);

        return $plan->update($data);
    }

    /**
     * Basculer le statut "populaire" d'un plan
     */
    public static function togglePlanPopularity(int $planId): void
    {
        // Désactiver "populaire" sur tous les plans
        SubscriptionPlan::query()->update(['is_popular' => false]);

        // Activer sur le plan sélectionné
        $plan = SubscriptionPlan::findOrFail($planId);
        $plan->update(['is_popular' => true]);
    }

    /**
     * Obtenir les statistiques des revenus d'abonnements
     */
    public static function getRevenueStats(): array
    {
        $totalRevenue = SubscriptionPayment::query()
            ->where('status', 'completed')
            ->sum('total');

        $monthlyRevenue = SubscriptionPayment::query()
            ->where('status', 'completed')
            ->whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year)
            ->sum('total');

        return [
            'total_revenue' => $totalRevenue,
            'monthly_revenue' => $monthlyRevenue,
        ];
    }
}
