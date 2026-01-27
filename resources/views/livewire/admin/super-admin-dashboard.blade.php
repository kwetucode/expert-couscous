<div>
    <x-slot name="header">
        <x-breadcrumb :items="[['label' => 'Administration'], ['label' => 'Tableau de bord Super Admin']]" />

    </x-slot>

    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Administration Système</h1>
                <p class="text-gray-500 mt-1">Vue d'ensemble de l'application</p>
            </div>
            <div class="flex items-center gap-3">
                <select wire:model.live="periodFilter"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                    <option value="7">7 derniers jours</option>
                    <option value="30">30 derniers jours</option>
                    <option value="90">90 derniers jours</option>
                    <option value="365">Cette année</option>
                </select>
            </div>
        </div>
        <!-- Navigation Tabs -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <nav class="flex space-x-1 p-1" aria-label="Tabs">
                <button wire:click="setTab('overview')"
                    class="flex-1 px-4 py-2.5 text-sm font-medium rounded-lg transition-all duration-200
                    {{ $activeTab === 'overview' ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100' }}">
                    <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                    </svg>
                    Vue d'ensemble
                </button>
                <button wire:click="setTab('users')"
                    class="flex-1 px-4 py-2.5 text-sm font-medium rounded-lg transition-all duration-200
                    {{ $activeTab === 'users' ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100' }}">
                    <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    Utilisateurs ({{ $stats['total_users'] }})
                </button>
                <button wire:click="setTab('organizations')"
                    class="flex-1 px-4 py-2.5 text-sm font-medium rounded-lg transition-all duration-200
                    {{ $activeTab === 'organizations' ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100' }}">
                    <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    Organisations ({{ $stats['total_organizations'] }})
                </button>
                <button wire:click="setTab('subscriptions')"
                    class="flex-1 px-4 py-2.5 text-sm font-medium rounded-lg transition-all duration-200
                    {{ $activeTab === 'subscriptions' ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100' }}">
                    <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                    </svg>
                    Abonnements
                </button>
            </nav>
        </div>

        <!-- Overview Tab -->
        @if ($activeTab === 'overview')
            <div class="space-y-6">
                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Total Users -->
                    <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-6 text-white shadow-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-blue-100 text-sm font-medium">Utilisateurs</p>
                                <p class="text-3xl font-bold mt-1">{{ number_format($stats['total_users']) }}</p>
                                <p class="text-blue-100 text-xs mt-2">
                                    <span class="text-white font-semibold">+{{ $stats['new_users_period'] }}</span>
                                    cette période
                                </p>
                            </div>
                            <div class="bg-white/20 rounded-full p-3">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                        </div>
                        <div class="mt-4 pt-4 border-t border-white/20">
                            <div class="flex justify-between text-sm">
                                <span class="text-blue-100">Actifs: {{ $stats['active_users'] }}</span>
                                <span class="text-blue-100">Vérifiés: {{ $stats['verified_users'] }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Total Organizations -->
                    <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-6 text-white shadow-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-purple-100 text-sm font-medium">Organisations</p>
                                <p class="text-3xl font-bold mt-1">{{ number_format($stats['total_organizations']) }}
                                </p>
                                <p class="text-purple-100 text-xs mt-2">
                                    <span
                                        class="text-white font-semibold">+{{ $stats['new_organizations_period'] }}</span>
                                    cette période
                                </p>
                            </div>
                            <div class="bg-white/20 rounded-full p-3">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </div>
                        </div>
                        <div class="mt-4 pt-4 border-t border-white/20">
                            <div class="flex justify-between text-sm">
                                <span class="text-purple-100">Payantes: {{ $stats['paid_organizations'] }}</span>
                                <span class="text-purple-100">Essai: {{ $stats['trial_organizations'] }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Revenue -->
                    <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl p-6 text-white shadow-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-green-100 text-sm font-medium">Revenus Abonnements</p>
                                <p class="text-3xl font-bold mt-1">
                                    {{ number_format($stats['subscription_revenue'], 0, ',', ' ') }}</p>
                                <p class="text-green-100 text-xs mt-2">{{ current_currency() }} total</p>
                            </div>
                            <div class="bg-white/20 rounded-full p-3">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                        <div class="mt-4 pt-4 border-t border-white/20">
                            <span class="text-green-100 text-sm">Cette période:
                                {{ number_format($stats['period_subscription_revenue'], 0, ',', ' ') }} {{ current_currency() }}</span>
                        </div>
                    </div>

                    <!-- Stores -->
                    <div class="bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl p-6 text-white shadow-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-amber-100 text-sm font-medium">Magasins</p>
                                <p class="text-3xl font-bold mt-1">{{ number_format($stats['total_stores']) }}</p>
                                <p class="text-amber-100 text-xs mt-2">Dans toutes les organisations</p>
                            </div>
                            <div class="bg-white/20 rounded-full p-3">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                        </div>
                        <div class="mt-4 pt-4 border-t border-white/20">
                            <span class="text-amber-100 text-sm">Actifs: {{ $stats['active_stores'] }}</span>
                        </div>
                    </div>
                </div>

                <!-- Charts & Activity -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- User Growth Chart -->
                    <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Croissance des utilisateurs</h3>
                        <div class="h-64" x-data="{
                            labels: @js($usersGrowth['labels']),
                            values: @js($usersGrowth['values'])
                        }" x-init="new Chart($refs.canvas, {
                            type: 'line',
                            data: {
                                labels: labels,
                                datasets: [{
                                    label: 'Nouveaux utilisateurs',
                                    data: values,
                                    borderColor: 'rgb(79, 70, 229)',
                                    backgroundColor: 'rgba(79, 70, 229, 0.1)',
                                    fill: true,
                                    tension: 0.4
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: { legend: { display: false } },
                                scales: {
                                    y: { beginAtZero: true }
                                }
                            }
                        })">
                            <canvas x-ref="canvas"></canvas>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Activité récente</h3>
                        <div class="space-y-4 max-h-64 overflow-y-auto">
                            @forelse($recentActivities as $activity)
                                <div class="flex items-start gap-3">
                                    <div
                                        class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center
                                @if ($activity['color'] === 'blue') bg-blue-100 text-blue-600
                                @elseif($activity['color'] === 'purple') bg-purple-100 text-purple-600
                                @elseif($activity['color'] === 'green') bg-green-100 text-green-600
                                @else bg-gray-100 text-gray-600 @endif">
                                        @if ($activity['icon'] === 'user-plus')
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                            </svg>
                                        @elseif($activity['icon'] === 'building')
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                            </svg>
                                        @elseif($activity['icon'] === 'credit-card')
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                            </svg>
                                        @endif
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 truncate">
                                            {{ $activity['message'] }}</p>
                                        <p class="text-xs text-gray-500">{{ $activity['detail'] }}</p>
                                        <p class="text-xs text-gray-400 mt-1">{{ $activity['date']->diffForHumans() }}
                                        </p>
                                    </div>
                                </div>
                            @empty
                                <p class="text-gray-500 text-sm text-center py-4">Aucune activité récente</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 text-center">
                        <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['total_products']) }}</p>
                        <p class="text-sm text-gray-500">Produits</p>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 text-center">
                        <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['total_sales_count']) }}
                        </p>
                        <p class="text-sm text-gray-500">Ventes totales</p>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 text-center">
                        <p class="text-2xl font-bold text-gray-900">
                            {{ number_format($stats['total_sales_amount'], 0, ',', ' ') }}</p>
                        <p class="text-sm text-gray-500">CA Total ({{ current_currency() }})</p>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 text-center">
                        <p class="text-2xl font-bold text-gray-900">{{ $stats['total_roles'] }}</p>
                        <p class="text-sm text-gray-500">Rôles</p>
                    </div>
                </div>
            </div>
        @endif

        <!-- Users Tab -->
        @if ($activeTab === 'users')
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex flex-col sm:flex-row gap-4">
                        <div class="flex-1">
                            <input type="text" wire:model.live.debounce.300ms="searchUsers"
                                placeholder="Rechercher un utilisateur..."
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        <select wire:model.live="userStatusFilter"
                            class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                            <option value="all">Tous les statuts</option>
                            <option value="active">Actifs</option>
                            <option value="inactive">Inactifs</option>
                        </select>
                        <a href="{{ route('users.index') }}"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition text-center">
                            Gérer les utilisateurs
                        </a>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Utilisateur</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Rôles</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Organisation</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Statut</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Inscrit le</th>
                                <th
                                    class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($users as $user)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div
                                                class="flex-shrink-0 h-10 w-10 bg-indigo-100 rounded-full flex items-center justify-center">
                                                <span
                                                    class="text-indigo-600 font-semibold">{{ $user->initials() }}</span>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">{{ $user->name }}
                                                </div>
                                                <div class="text-sm text-gray-500">{{ $user->email }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex flex-wrap gap-1">
                                            @foreach ($user->roles as $role)
                                                <span
                                                    class="px-2 py-1 text-xs font-medium rounded-full
                                        @if ($role->slug === 'super-admin') bg-red-100 text-red-800
                                        @elseif($role->slug === 'admin') bg-purple-100 text-purple-800
                                        @elseif($role->slug === 'manager') bg-blue-100 text-blue-800
                                        @else bg-gray-100 text-gray-800 @endif">
                                                    {{ $role->name }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $user->defaultOrganization?->name ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if ($user->is_active)
                                            <span
                                                class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">Actif</span>
                                        @else
                                            <span
                                                class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800">Inactif</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $user->created_at->format('d/m/Y') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        @if (!$user->hasRole('super-admin'))
                                            <button wire:click="toggleUserStatus({{ $user->id }})"
                                                class="text-indigo-600 hover:text-indigo-900 mr-3">
                                                {{ $user->is_active ? 'Désactiver' : 'Activer' }}
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                        Aucun utilisateur trouvé
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="p-4 border-t border-gray-200">
                    {{ $users->links() }}
                </div>
            </div>
        @endif

        <!-- Organizations Tab -->
        @if ($activeTab === 'organizations')
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex flex-col sm:flex-row gap-4">
                        <div class="flex-1">
                            <input type="text" wire:model.live.debounce.300ms="searchOrganizations"
                                placeholder="Rechercher une organisation..."
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        <select wire:model.live="orgStatusFilter"
                            class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                            <option value="all">Tous les statuts</option>
                            <option value="paid">Payantes</option>
                            <option value="trial">En essai</option>
                            <option value="expired">Expirées</option>
                        </select>
                        <a href="{{ route('organizations.index') }}"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition text-center">
                            Gérer les organisations
                        </a>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Organisation</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Propriétaire</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Plan</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Membres</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Magasins</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Statut</th>
                                <th
                                    class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($organizations as $org)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div
                                                class="flex-shrink-0 h-10 w-10 bg-purple-100 rounded-full flex items-center justify-center">
                                                <svg class="w-5 h-5 text-purple-600" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                                </svg>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">{{ $org->name }}
                                                </div>
                                                <div class="text-sm text-gray-500">{{ $org->slug }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $org->owner?->name ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            class="px-2 py-1 text-xs font-medium rounded-full
                                    @if ($org->subscription_plan?->value === 'premium') bg-amber-100 text-amber-800
                                    @elseif($org->subscription_plan?->value === 'professional') bg-indigo-100 text-indigo-800
                                    @else bg-gray-100 text-gray-800 @endif">
                                            {{ ucfirst($org->subscription_plan?->value ?? 'starter') }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $org->members_count }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $org->stores_count }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if ($org->payment_status?->value === 'completed')
                                            <span
                                                class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">Payée</span>
                                        @elseif($org->is_trial)
                                            <span
                                                class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">Essai</span>
                                        @elseif($org->subscription_ends_at && $org->subscription_ends_at < now())
                                            <span
                                                class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800">Expirée</span>
                                        @else
                                            <span
                                                class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <a href="{{ route('organizations.show', $org) }}"
                                            class="text-indigo-600 hover:text-indigo-900 mr-3">Voir</a>
                                        <button wire:click="toggleOrganizationStatus({{ $org->id }})"
                                            class="text-gray-600 hover:text-gray-900">
                                            {{ $org->is_active ? 'Désactiver' : 'Activer' }}
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                        Aucune organisation trouvée
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="p-4 border-t border-gray-200">
                    {{ $organizations->links() }}
                </div>
            </div>
        @endif

        <!-- Subscriptions Tab -->
        @if ($activeTab === 'subscriptions')
            <div class="space-y-6">
                <!-- Subscription Stats -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    @foreach ($subscriptionStats['by_plan'] as $plan => $count)
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Plan
                                        {{ ucfirst($plan ?? 'Non défini') }}</p>
                                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ $count }}</p>
                                </div>
                                <div
                                    class="w-12 h-12 rounded-full flex items-center justify-center
                            @if ($plan === 'premium') bg-amber-100 text-amber-600
                            @elseif($plan === 'professional') bg-indigo-100 text-indigo-600
                            @else bg-gray-100 text-gray-600 @endif">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Alerts -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-amber-50 border border-amber-200 rounded-xl p-6">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-amber-100 rounded-full flex items-center justify-center">
                                <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-lg font-semibold text-amber-800">
                                    {{ $subscriptionStats['expiring_soon'] }}</p>
                                <p class="text-sm text-amber-600">Abonnements expirant dans 7 jours</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-red-50 border border-red-200 rounded-xl p-6">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-lg font-semibold text-red-800">{{ $subscriptionStats['expired'] }}</p>
                                <p class="text-sm text-red-600">Abonnements expirés</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Actions rapides</h3>
                    <div class="flex flex-wrap gap-4">
                        <a href="{{ route('admin.subscription-settings') }}"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            Paramètres d'abonnement
                        </a>
                        <a href="{{ route('organizations.index') }}"
                            class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                            Gérer les organisations
                        </a>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
