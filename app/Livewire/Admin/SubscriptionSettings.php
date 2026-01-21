<?php

namespace App\Livewire\Admin;

use App\Services\SubscriptionService;
use Illuminate\Support\Facades\Artisan;
use Livewire\Component;
use Illuminate\Support\Facades\Cache;

class SubscriptionSettings extends Component
{
    // Edit modal
    public ?int $editingPlanId = null;
    public array $editForm = [
        'name' => '',
        'price' => 0,
        'max_stores' => 1,
        'max_users' => 3,
        'max_products' => 100,
        'features' => [],
    ];

    public string $currency = 'CDF';

    public function mount(): void
    {
        $this->authorize('viewAny', \App\Models\Organization::class);
        $this->loadSettings();
    }

    public function loadSettings(): void
    {
        $this->currency = Cache::get('subscription_currency', 'CDF') ?: 'CDF';
    }

    public function openEditModal(int $planId): void
    {
        $plan = SubscriptionService::getPlanById($planId);

        if (!$plan) {
            $this->dispatch('show-toast', message: 'Plan introuvable', type: 'error');
            return;
        }

        $this->editingPlanId = $planId;
        $this->editForm = [
            'name' => $plan->name,
            'slug' => $plan->slug,
            'price' => $plan->price,
            'max_stores' => $plan->max_stores,
            'max_users' => $plan->max_users,
            'max_products' => $plan->max_products,
            'features_text' => implode("\n", $plan->features ?? []),
        ];

        $this->dispatch('open-plan-modal');
    }

    public function savePlan(): void
    {
        $this->validate([
            'editForm.name' => 'required|string|max:50',
            'editForm.price' => 'required|numeric|min:0',
            'editForm.max_stores' => 'required|integer|min:1',
            'editForm.max_users' => 'required|integer|min:1',
            'editForm.max_products' => 'required|integer|min:1',
            'editForm.features_text' => 'nullable|string',
        ]);

        // Convertir le texte des fonctionnalités en tableau
        $features = array_filter(
            array_map('trim', explode("\n", $this->editForm['features_text'] ?? '')),
            fn($f) => !empty($f)
        );

        SubscriptionService::updatePlan($this->editingPlanId, [
            'name' => $this->editForm['name'],
            'price' => (int) $this->editForm['price'],
            'max_stores' => (int) $this->editForm['max_stores'],
            'max_users' => (int) $this->editForm['max_users'],
            'max_products' => (int) $this->editForm['max_products'],
            'features' => array_values($features),
        ]);

        $this->dispatch('close-plan-modal');
        $this->dispatch('show-toast', message: 'Plan mis à jour avec succès !', type: 'success');
    }

    public function togglePopular(int $planId): void
    {
        SubscriptionService::togglePlanPopularity($planId);
        $this->dispatch('show-toast', message: 'Plan mis en avant !', type: 'success');
    }

    public function resetToDefaults(): void
    {
        // Réexécuter le seeder pour réinitialiser les plans
        Artisan::call('db:seed', ['--class' => 'SubscriptionPlanSeeder']);

        $this->currency = 'CDF';
        Cache::forget('subscription_currency');

        $this->dispatch('show-toast', message: 'Paramètres réinitialisés !', type: 'success');
    }

    /**
     * Obtenir les statistiques des abonnements
     */
    public function getStatsProperty(): array
    {
        $stats = \App\Models\Organization::query()
            ->selectRaw('subscription_plan, COUNT(*) as count')
            ->groupBy('subscription_plan')
            ->orderBy('subscription_plan')
            ->pluck('count', 'subscription_plan')
            ->toArray();

        $revenueStats = SubscriptionService::getRevenueStats();

        return [
            'by_plan' => $stats,
            'total_organizations' => array_sum($stats),
            'total_revenue' => $revenueStats['total_revenue'],
            'monthly_revenue' => $revenueStats['monthly_revenue'],
        ];
    }

    public function render()
    {
        $plans = SubscriptionService::getPlansFromDatabase();

        return view('livewire.admin.subscription-settings', [
            'plans' => $plans,
            'stats' => $this->stats,
        ]);
    }
}
