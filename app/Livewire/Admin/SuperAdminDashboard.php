<?php

namespace App\Livewire\Admin;

use App\Models\Organization;
use App\Models\Role;
use App\Models\Store;
use App\Models\User;
use App\Models\Sale;
use App\Models\Product;
use App\Models\SubscriptionPayment;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class SuperAdminDashboard extends Component
{
    use WithPagination;

    public string $activeTab = 'overview';
    public string $searchUsers = '';
    public string $searchOrganizations = '';
    public string $userStatusFilter = 'all';
    public string $orgStatusFilter = 'all';
    public string $periodFilter = '30';

    protected $queryString = [
        'activeTab' => ['except' => 'overview'],
        'searchUsers' => ['except' => ''],
        'searchOrganizations' => ['except' => ''],
    ];

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function updatedSearchUsers(): void
    {
        $this->resetPage();
    }

    public function updatedSearchOrganizations(): void
    {
        $this->resetPage();
    }

    public function getOverviewStats(): array
    {
        $period = (int) $this->periodFilter;
        $startDate = now()->subDays($period);

        return [
            // Utilisateurs
            'total_users' => User::count(),
            'active_users' => User::where('is_active', true)->count(),
            'new_users_period' => User::where('created_at', '>=', $startDate)->count(),
            'verified_users' => User::whereNotNull('email_verified_at')->count(),

            // Organisations
            'total_organizations' => Organization::count(),
            'active_organizations' => Organization::where('is_active', true)->count(),
            'paid_organizations' => Organization::where('payment_status', 'completed')->count(),
            'trial_organizations' => Organization::where('is_trial', true)->count(),
            'new_organizations_period' => Organization::where('created_at', '>=', $startDate)->count(),

            // Magasins
            'total_stores' => Store::count(),
            'active_stores' => Store::where('is_active', true)->count(),

            // Ventes globales
            'total_sales_amount' => Sale::sum('total'),
            'period_sales_amount' => Sale::where('created_at', '>=', $startDate)->sum('total'),
            'total_sales_count' => Sale::count(),

            // Produits
            'total_products' => Product::count(),

            // Rôles
            'total_roles' => Role::count(),

            // Revenus d'abonnements
            'subscription_revenue' => SubscriptionPayment::where('status', 'completed')->sum('amount'),
            'period_subscription_revenue' => SubscriptionPayment::where('status', 'completed')
                ->where('created_at', '>=', $startDate)
                ->sum('amount'),
        ];
    }

    public function getSubscriptionStats(): array
    {
        return [
            'by_plan' => Organization::select('subscription_plan', DB::raw('count(*) as count'))
                ->groupBy('subscription_plan')
                ->pluck('count', 'subscription_plan')
                ->toArray(),

            'by_status' => Organization::select('payment_status', DB::raw('count(*) as count'))
                ->groupBy('payment_status')
                ->pluck('count', 'payment_status')
                ->toArray(),

            'expiring_soon' => Organization::where('subscription_ends_at', '<=', now()->addDays(7))
                ->where('subscription_ends_at', '>', now())
                ->count(),

            'expired' => Organization::where('subscription_ends_at', '<', now())->count(),
        ];
    }

    public function getUsersGrowthData(): array
    {
        $data = User::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as count')
        )
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'labels' => $data->pluck('date')->map(fn($d) => \Carbon\Carbon::parse($d)->format('d/m'))->toArray(),
            'values' => $data->pluck('count')->toArray(),
        ];
    }

    public function getRecentActivities(): \Illuminate\Support\Collection
    {
        $activities = collect();

        // Nouveaux utilisateurs
        User::latest()->take(5)->get()->each(function ($user) use ($activities) {
            $activities->push([
                'type' => 'user',
                'icon' => 'user-plus',
                'color' => 'blue',
                'message' => "Nouvel utilisateur: {$user->name}",
                'detail' => $user->email,
                'date' => $user->created_at,
            ]);
        });

        // Nouvelles organisations
        Organization::latest()->take(5)->get()->each(function ($org) use ($activities) {
            $activities->push([
                'type' => 'organization',
                'icon' => 'building',
                'color' => 'purple',
                'message' => "Nouvelle organisation: {$org->name}",
                'detail' => $org->subscription_plan ?? 'trial',
                'date' => $org->created_at,
            ]);
        });

        // Paiements récents
        SubscriptionPayment::with('organization')
            ->where('status', 'completed')
            ->latest()
            ->take(5)
            ->get()
            ->each(function ($payment) use ($activities) {
                $currency = $payment->organization?->currency ?? current_currency();
                $activities->push([
                    'type' => 'payment',
                    'icon' => 'credit-card',
                    'color' => 'green',
                    'message' => "Paiement reçu: " . number_format($payment->amount, 0, ',', ' ') . " " . $currency,
                    'detail' => $payment->organization?->name ?? 'N/A',
                    'date' => $payment->created_at,
                ]);
            });

        return $activities->sortByDesc('date')->take(10)->values();
    }

    public function toggleUserStatus(int $userId): void
    {
        $user = User::find($userId);
        if ($user && !$user->hasRole('super-admin')) {
            $user->update(['is_active' => !$user->is_active]);
            $this->dispatch('notify', type: 'success', message: 'Statut utilisateur mis à jour');
        }
    }

    public function toggleOrganizationStatus(int $orgId): void
    {
        $org = Organization::find($orgId);
        if ($org) {
            $org->update(['is_active' => !$org->is_active]);
            $this->dispatch('notify', type: 'success', message: 'Statut organisation mis à jour');
        }
    }

    public function render()
    {
        $users = User::with(['roles', 'defaultOrganization'])
            ->when($this->searchUsers, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', "%{$this->searchUsers}%")
                        ->orWhere('email', 'like', "%{$this->searchUsers}%");
                });
            })
            ->when($this->userStatusFilter !== 'all', function ($query) {
                $query->where('is_active', $this->userStatusFilter === 'active');
            })
            ->latest()
            ->paginate(10, ['*'], 'usersPage');

        $organizations = Organization::with(['owner', 'stores'])
            ->withCount(['members', 'stores'])
            ->when($this->searchOrganizations, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', "%{$this->searchOrganizations}%")
                        ->orWhere('slug', 'like', "%{$this->searchOrganizations}%");
                });
            })
            ->when($this->orgStatusFilter !== 'all', function ($query) {
                if ($this->orgStatusFilter === 'paid') {
                    $query->where('payment_status', 'completed');
                } elseif ($this->orgStatusFilter === 'trial') {
                    $query->where('is_trial', true);
                } elseif ($this->orgStatusFilter === 'expired') {
                    $query->where('subscription_ends_at', '<', now());
                }
            })
            ->latest()
            ->paginate(10, ['*'], 'orgsPage');

        return view('livewire.admin.super-admin-dashboard', [
            'stats' => $this->getOverviewStats(),
            'subscriptionStats' => $this->getSubscriptionStats(),
            'usersGrowth' => $this->getUsersGrowthData(),
            'recentActivities' => $this->getRecentActivities(),
            'users' => $users,
            'organizations' => $organizations,
        ]);
    }
}
