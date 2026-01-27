<div>
    <x-slot name="header">
        <x-breadcrumb :items="[
            ['label' => 'Accueil', 'url' => route('dashboard')],
            ['label' => 'Organisations', 'url' => route('organizations.index')],
            ['label' => $organization->name, 'url' => route('organizations.show', $organization)],
            ['label' => 'Gestion de l\'abonnement']
        ]" />
    </x-slot>

    <!-- Header -->
    <div class="mt-6">
        <h1 class="text-3xl font-bold text-gray-900">Gestion de l'abonnement</h1>
        <p class="mt-2 text-gray-600">{{ $organization->name }}</p>
    </div>

    <!-- Toast -->
    <x-toast />

    <!-- Current Subscription Status -->
    <div class="mt-8 bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Abonnement actuel</h2>

        <div class="flex items-start justify-between">
            <div class="flex-1">
                <div class="flex items-center space-x-3">
                    <span class="inline-flex items-center px-4 py-2 rounded-full text-base font-medium
                        {{ $organization->subscription_plan === 'free' ? 'bg-gray-100 text-gray-800' : '' }}
                        {{ $organization->subscription_plan === 'starter' ? 'bg-blue-100 text-blue-800' : '' }}
                        {{ $organization->subscription_plan === 'professional' ? 'bg-purple-100 text-purple-800' : '' }}
                        {{ $organization->subscription_plan === 'enterprise' ? 'bg-green-100 text-green-800' : '' }}">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                        </svg>
                        Plan {{ $organization->plan_label }}
                    </span>

                    @if($subscriptionStatus === 'active')
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            Actif
                        </span>
                    @elseif($subscriptionStatus === 'expired')
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                            Expiré
                        </span>
                    @elseif($subscriptionStatus === 'expiring_soon')
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                            Expire bientôt
                        </span>
                    @endif
                </div>

                <div class="mt-4 space-y-2">
                    @if($organization->subscription_end_date)
                        <div class="flex items-center text-sm text-gray-600">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span>Date d'expiration : <strong>{{ $organization->subscription_end_date->format('d/m/Y') }}</strong></span>
                            @if($daysUntilExpiration > 0)
                                <span class="ml-2 text-gray-500">({{ $daysUntilExpiration }} jours restants)</span>
                            @endif
                        </div>
                    @endif

                    <div class="flex items-center text-sm text-gray-600">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                        <span>Magasins : <strong>{{ $organization->stores()->count() }} / {{ $organization->max_stores ?? '∞' }}</strong></span>
                    </div>

                    <div class="flex items-center text-sm text-gray-600">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <span>Utilisateurs : <strong>{{ $organization->members()->count() }} / {{ $organization->max_users ?? '∞' }}</strong></span>
                    </div>
                </div>
            </div>

            @if($subscriptionStatus === 'expired')
                <div class="ml-6">
                    <button wire:click="$set('showReactivateModal', true)"
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Réactiver l'abonnement
                    </button>
                </div>
            @elseif($subscriptionStatus === 'expiring_soon' || $subscriptionStatus === 'active')
                <div class="ml-6">
                    <button wire:click="$set('showRenewModal', true)"
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Renouveler
                    </button>
                </div>
            @endif
        </div>

        @if($subscriptionStatus === 'expired')
            <div class="mt-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="w-5 h-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">Abonnement expiré</h3>
                        <p class="mt-1 text-sm text-red-700">Votre abonnement a expiré le {{ $organization->subscription_end_date->format('d/m/Y') }}. Réactivez-le pour continuer à bénéficier de toutes les fonctionnalités.</p>
                    </div>
                </div>
            </div>
        @elseif($subscriptionStatus === 'expiring_soon')
            <div class="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800">Abonnement expire bientôt</h3>
                        <p class="mt-1 text-sm text-yellow-700">Votre abonnement expire dans {{ $daysUntilExpiration }} jours. Renouvelez-le maintenant pour éviter toute interruption de service.</p>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Available Plans -->
    @if($organization->subscription_plan === 'free' || $subscriptionStatus === 'expired')
        <div class="mt-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Plans disponibles</h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @foreach($availablePlans as $planKey => $planDetails)
                    <div class="bg-white rounded-xl shadow-sm border-2
                        {{ $planKey === 'professional' ? 'border-indigo-500 relative' : 'border-gray-200' }}
                        overflow-hidden transition-all duration-200 hover:shadow-lg">

                        @if($planKey === 'professional')
                            <div class="bg-indigo-500 text-white text-center py-2 text-sm font-semibold">
                                ⭐ Le plus populaire
                            </div>
                        @endif

                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-2xl font-bold text-gray-900">{{ $planDetails['name'] }}</h3>
                                @if($planKey === 'starter')
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">Starter</span>
                                @elseif($planKey === 'professional')
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">Pro</span>
                                @elseif($planKey === 'enterprise')
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">Enterprise</span>
                                @endif
                            </div>

                            <div class="mb-6">
                                <p class="text-sm text-gray-600 mb-4">{{ $planDetails['description'] }}</p>
                                <div class="flex items-baseline">
                                    <span class="text-4xl font-bold text-gray-900">{{ number_format($planDetails['monthly_price'], 0, ',', ' ') }}</span>
                                    <span class="ml-2 text-gray-600">{{ current_currency() }} / mois</span>
                                </div>
                                <div class="mt-2">
                                    <span class="text-sm text-gray-600">ou </span>
                                    <span class="text-2xl font-bold text-gray-900">{{ number_format($planDetails['yearly_price'], 0, ',', ' ') }}</span>
                                    <span class="text-gray-600"> {{ current_currency() }} / an</span>
                                    <span class="ml-2 inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Économisez {{ number_format(($planDetails['monthly_price'] * 12) - $planDetails['yearly_price'], 0, ',', ' ') }} {{ current_currency() }}
                                    </span>
                                </div>
                            </div>

                            <ul class="space-y-3 mb-6">
                                @foreach($planDetails['features'] as $feature)
                                    <li class="flex items-start">
                                        <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                        </svg>
                                        <span class="ml-3 text-sm text-gray-700">{{ $feature }}</span>
                                    </li>
                                @endforeach
                            </ul>

                            <button wire:click="selectPlan('{{ $planKey }}')"
                                    class="w-full inline-flex justify-center items-center px-4 py-3 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white
                                    {{ $planKey === 'professional' ? 'bg-indigo-600 hover:bg-indigo-700' : 'bg-gray-800 hover:bg-gray-900' }}
                                    focus:outline-none focus:ring-2 focus:ring-offset-2
                                    {{ $planKey === 'professional' ? 'focus:ring-indigo-500' : 'focus:ring-gray-900' }}
                                    transition-colors duration-200">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                Choisir ce plan
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Subscription History -->
    @if($subscriptionHistory->isNotEmpty())
        <div class="mt-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Historique des abonnements</h2>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plan</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Période</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Montant</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($subscriptionHistory as $payment)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                                            {{ $payment->plan === 'starter' ? 'bg-blue-100 text-blue-800' : '' }}
                                            {{ $payment->plan === 'professional' ? 'bg-purple-100 text-purple-800' : '' }}
                                            {{ $payment->plan === 'enterprise' ? 'bg-green-100 text-green-800' : '' }}">
                                            {{ ucfirst($payment->plan) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            {{ $payment->duration_months === 1 ? 'bg-gray-100 text-gray-800' : 'bg-blue-100 text-blue-800' }}">
                                            {{ $payment->duration_months === 1 ? 'Mensuel' : ($payment->duration_months === 12 ? 'Annuel' : $payment->duration_months . ' mois') }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ number_format($payment->amount, 0, ',', ' ') }} {{ current_currency() }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            {{ $payment->status === 'completed' ? 'bg-green-100 text-green-800' : '' }}
                                            {{ $payment->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                            {{ $payment->status === 'failed' ? 'bg-red-100 text-red-800' : '' }}
                                            {{ $payment->status === 'cancelled' ? 'bg-gray-100 text-gray-800' : '' }}">
                                            @if($payment->status === 'completed')
                                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                                </svg>
                                            @endif
                                            {{ $payment->status_label }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $payment->created_at->format('d/m/Y H:i') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <!-- Subscribe Modal -->
    <x-modal name="showSubscribeModal" max-width="2xl">
        <div class="p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Souscrire au plan {{ $selectedPlan ? ucfirst($selectedPlan) : '' }}</h3>

            @if($selectedPlan && isset($availablePlans[$selectedPlan]))
                <div class="mb-6">
                    <p class="text-sm text-gray-600 mb-4">{{ $availablePlans[$selectedPlan]['description'] }}</p>

                    <!-- Billing Period Selection -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Période de facturation</label>
                        <div class="grid grid-cols-2 gap-4">
                            <button wire:click="$set('billingPeriod', 'monthly')"
                                    class="flex items-center justify-between p-4 border-2 rounded-lg transition-all
                                    {{ $billingPeriod === 'monthly' ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:border-gray-300' }}">
                                <div class="text-left">
                                    <div class="font-semibold text-gray-900">Mensuel</div>
                                    <div class="text-sm text-gray-600">{{ number_format($availablePlans[$selectedPlan]['monthly_price'], 0, ',', ' ') }} {{ current_currency() }}/mois</div>
                                </div>
                                @if($billingPeriod === 'monthly')
                                    <svg class="w-6 h-6 text-indigo-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                @endif
                            </button>

                            <button wire:click="$set('billingPeriod', 'yearly')"
                                    class="flex items-center justify-between p-4 border-2 rounded-lg transition-all
                                    {{ $billingPeriod === 'yearly' ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:border-gray-300' }}">
                                <div class="text-left">
                                    <div class="flex items-center">
                                        <span class="font-semibold text-gray-900">Annuel</span>
                                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            -{{ round((1 - ($availablePlans[$selectedPlan]['yearly_price'] / ($availablePlans[$selectedPlan]['monthly_price'] * 12))) * 100) }}%
                                        </span>
                                    </div>
                                    <div class="text-sm text-gray-600">{{ number_format($availablePlans[$selectedPlan]['yearly_price'], 0, ',', ' ') }} {{ current_currency() }}/an</div>
                                </div>
                                @if($billingPeriod === 'yearly')
                                    <svg class="w-6 h-6 text-indigo-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                @endif
                            </button>
                        </div>
                    </div>

                    <!-- Features -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Fonctionnalités incluses</label>
                        <ul class="space-y-2">
                            @foreach($availablePlans[$selectedPlan]['features'] as $feature)
                                <li class="flex items-start text-sm text-gray-700">
                                    <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                    <span class="ml-2">{{ $feature }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <!-- Total -->
                    <div class="bg-gray-50 rounded-lg p-4 mb-4">
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium text-gray-700">Total à payer</span>
                            <span class="text-2xl font-bold text-gray-900">
                                {{ number_format($billingPeriod === 'monthly' ? $availablePlans[$selectedPlan]['monthly_price'] : $availablePlans[$selectedPlan]['yearly_price'], 0, ',', ' ') }} {{ current_currency() }}
                            </span>
                        </div>
                        @if($billingPeriod === 'yearly')
                            <p class="text-xs text-gray-500 mt-2">
                                Vous économisez {{ number_format(($availablePlans[$selectedPlan]['monthly_price'] * 12) - $availablePlans[$selectedPlan]['yearly_price'], 0, ',', ' ') }} {{ current_currency() }} avec la facturation annuelle
                            </p>
                        @endif
                    </div>
                </div>

                <div class="flex justify-end space-x-3">
                    <button wire:click="$set('showSubscribeModal', false)"
                            class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Annuler
                    </button>
                    <button wire:click="confirmSubscription"
                            class="px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Confirmer l'abonnement
                    </button>
                </div>
            @endif
        </div>
    </x-modal>

    <!-- Reactivate Modal -->
    <x-modal name="showReactivateModal" max-width="lg">
        <div class="p-6">
            <div class="flex items-center mb-4">
                <div class="flex-shrink-0 w-12 h-12 rounded-full bg-indigo-100 flex items-center justify-center">
                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                </div>
                <h3 class="ml-3 text-lg font-semibold text-gray-900">Réactiver l'abonnement</h3>
            </div>

            <p class="text-sm text-gray-600 mb-4">
                Vous êtes sur le point de réactiver votre abonnement <strong>{{ $organization->plan_label }}</strong>.
            </p>

            <!-- Billing Period Selection -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Période de facturation</label>
                <div class="grid grid-cols-2 gap-4">
                    <button wire:click="$set('billingPeriod', 'monthly')"
                            class="flex items-center justify-between p-4 border-2 rounded-lg transition-all
                            {{ $billingPeriod === 'monthly' ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:border-gray-300' }}">
                        <div class="text-left">
                            <div class="font-semibold text-gray-900">Mensuel</div>
                            <div class="text-sm text-gray-600">30 jours</div>
                        </div>
                        @if($billingPeriod === 'monthly')
                            <svg class="w-6 h-6 text-indigo-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        @endif
                    </button>

                    <button wire:click="$set('billingPeriod', 'yearly')"
                            class="flex items-center justify-between p-4 border-2 rounded-lg transition-all
                            {{ $billingPeriod === 'yearly' ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:border-gray-300' }}">
                        <div class="text-left">
                            <div class="flex items-center">
                                <span class="font-semibold text-gray-900">Annuel</span>
                                <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    Recommandé
                                </span>
                            </div>
                            <div class="text-sm text-gray-600">365 jours</div>
                        </div>
                        @if($billingPeriod === 'yearly')
                            <svg class="w-6 h-6 text-indigo-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        @endif
                    </button>
                </div>
            </div>

            <div class="flex justify-end space-x-3 mt-6">
                <button wire:click="$set('showReactivateModal', false)"
                        class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Annuler
                </button>
                <button wire:click="confirmReactivation"
                        class="px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Confirmer la réactivation
                </button>
            </div>
        </div>
    </x-modal>

    <!-- Renew Modal -->
    <x-modal name="showRenewModal" max-width="lg">
        <div class="p-6">
            <div class="flex items-center mb-4">
                <div class="flex-shrink-0 w-12 h-12 rounded-full bg-green-100 flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                </div>
                <h3 class="ml-3 text-lg font-semibold text-gray-900">Renouveler l'abonnement</h3>
            </div>

            <p class="text-sm text-gray-600 mb-4">
                Vous êtes sur le point de renouveler votre abonnement <strong>{{ $organization->plan_label }}</strong>.
                @if($organization->subscription_end_date)
                    <br>Votre abonnement actuel expire le <strong>{{ $organization->subscription_end_date->format('d/m/Y') }}</strong>.
                @endif
            </p>

            <!-- Billing Period Selection -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Période de facturation</label>
                <div class="grid grid-cols-2 gap-4">
                    <button wire:click="$set('billingPeriod', 'monthly')"
                            class="flex items-center justify-between p-4 border-2 rounded-lg transition-all
                            {{ $billingPeriod === 'monthly' ? 'border-green-500 bg-green-50' : 'border-gray-200 hover:border-gray-300' }}">
                        <div class="text-left">
                            <div class="font-semibold text-gray-900">Mensuel</div>
                            <div class="text-sm text-gray-600">30 jours supplémentaires</div>
                        </div>
                        @if($billingPeriod === 'monthly')
                            <svg class="w-6 h-6 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        @endif
                    </button>

                    <button wire:click="$set('billingPeriod', 'yearly')"
                            class="flex items-center justify-between p-4 border-2 rounded-lg transition-all
                            {{ $billingPeriod === 'yearly' ? 'border-green-500 bg-green-50' : 'border-gray-200 hover:border-gray-300' }}">
                        <div class="text-left">
                            <div class="flex items-center">
                                <span class="font-semibold text-gray-900">Annuel</span>
                                <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    Recommandé
                                </span>
                            </div>
                            <div class="text-sm text-gray-600">365 jours supplémentaires</div>
                        </div>
                        @if($billingPeriod === 'yearly')
                            <svg class="w-6 h-6 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        @endif
                    </button>
                </div>
            </div>

            <div class="flex justify-end space-x-3 mt-6">
                <button wire:click="$set('showRenewModal', false)"
                        class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    Annuler
                </button>
                <button wire:click="confirmRenewal"
                        class="px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    Confirmer le renouvellement
                </button>
            </div>
        </div>
    </x-modal>

    <!-- Cancel Subscription Modal -->
    <x-modal name="showCancelModal" max-width="lg">
        <div class="p-6">
            <div class="flex items-center mb-4">
                <div class="flex-shrink-0 w-12 h-12 rounded-full bg-red-100 flex items-center justify-center">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <h3 class="ml-3 text-lg font-semibold text-gray-900">Annuler l'abonnement</h3>
            </div>

            <p class="text-sm text-gray-600 mb-4">
                Êtes-vous sûr de vouloir annuler votre abonnement ? Vous perdrez l'accès aux fonctionnalités premium à la fin de la période en cours.
            </p>

            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
                <div class="flex">
                    <svg class="w-5 h-5 text-yellow-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                    <div class="ml-3">
                        <p class="text-sm text-yellow-700">
                            Cette action ne peut pas être annulée. Votre abonnement restera actif jusqu'à sa date d'expiration.
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex justify-end space-x-3">
                <button wire:click="$set('showCancelModal', false)"
                        class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                    Garder l'abonnement
                </button>
                <button wire:click="confirmCancellation"
                        class="px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                    Confirmer l'annulation
                </button>
            </div>
        </div>
    </x-modal>
</div>
