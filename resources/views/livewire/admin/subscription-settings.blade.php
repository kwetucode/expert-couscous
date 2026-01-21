<div>
    <x-slot name="header">
        <x-breadcrumb :items="[
            ['label' => 'Accueil', 'url' => route('dashboard')],
            ['label' => 'Administration'],
            ['label' => 'Paramètres Abonnements']
        ]" />
    </x-slot>

    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between mt-4">
            <div>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">Paramètres des Abonnements</h1>
                <p class="text-gray-600 mt-2 flex items-center gap-2">
                    <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Configurez les plans, prix et limites des abonnements
                </p>
            </div>
            <button wire:click="resetToDefaults" wire:confirm="Êtes-vous sûr de vouloir réinitialiser tous les paramètres ?"
                class="group inline-flex items-center px-4 py-2.5 text-sm font-semibold text-red-600 bg-white hover:bg-red-50 border-2 border-red-200 hover:border-red-300 rounded-xl transition-all shadow-sm hover:shadow-md">
                <svg class="w-4 h-4 mr-2 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Réinitialiser par défaut
            </button>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-gradient-to-br from-white to-gray-50 rounded-xl shadow-md border border-gray-100 p-5 hover:shadow-lg transition-shadow">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-gradient-to-br from-gray-500 to-gray-600 rounded-xl flex items-center justify-center shadow-md">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 font-medium">Total Organisations</p>
                        <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['total_organizations']) }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-gradient-to-br from-white to-green-50 rounded-xl shadow-md border border-green-100 p-5 hover:shadow-lg transition-shadow">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center shadow-md">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 font-medium">Revenus Total</p>
                        <p class="text-2xl font-bold text-green-600">{{ number_format($stats['total_revenue'], 0, ',', ' ') }} {{ $currency }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-gradient-to-br from-white to-blue-50 rounded-xl shadow-md border border-blue-100 p-5 hover:shadow-lg transition-shadow">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center shadow-md">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 font-medium">Revenus ce mois</p>
                        <p class="text-2xl font-bold text-blue-600">{{ number_format($stats['monthly_revenue'], 0, ',', ' ') }} {{ $currency }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-gradient-to-br from-white to-purple-50 rounded-xl shadow-md border border-purple-100 p-5 hover:shadow-lg transition-shadow">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-xl flex items-center justify-center shadow-md">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 font-medium">Plans payants</p>
                        <p class="text-2xl font-bold text-purple-600">
                            {{ ($stats['by_plan']['starter'] ?? 0) + ($stats['by_plan']['professional'] ?? 0) + ($stats['by_plan']['enterprise'] ?? 0) }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Distribution by Plan -->
        <div class="bg-gradient-to-br from-white to-indigo-50 rounded-xl shadow-md border border-indigo-100 p-6 hover:shadow-lg transition-shadow">
            <div class="flex items-center gap-2 mb-4">
                <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900">Distribution par plan</h3>
            </div>
            <div class="grid grid-cols-4 gap-4">
                @foreach(['free' => 'Gratuit', 'starter' => 'Starter', 'professional' => 'Pro', 'enterprise' => 'Enterprise'] as $key => $label)
                    <div class="text-center p-4 rounded-xl bg-white border border-gray-200 shadow-sm hover:shadow-md transition-shadow">
                        <p class="text-3xl font-bold
                            @if($key === 'free') text-gray-600
                            @elseif($key === 'starter') text-blue-600
                            @elseif($key === 'professional') text-purple-600
                            @else text-amber-600
                            @endif">
                            {{ $stats['by_plan'][$key] ?? 0 }}
                        </p>
                        <p class="text-sm text-gray-500 font-medium mt-1">{{ $label }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Plans Configuration -->
        <div class="bg-gradient-to-br from-white to-purple-50 rounded-xl shadow-md border border-purple-100 p-6 hover:shadow-lg transition-shadow">
            <div class="flex items-center gap-2 mb-4">
                <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900">Configuration des Plans</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach($plans as $plan)
                    <div class="relative bg-white border-2 rounded-xl p-5 transition-all hover:shadow-lg
                        @if($plan->is_popular) border-indigo-500 ring-2 ring-indigo-100
                        @else border-gray-200 hover:border-indigo-300
                        @endif">

                        @if($plan->is_popular)
                            <div class="absolute -top-3 left-1/2 transform -translate-x-1/2">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-md">
                                    ⭐ Populaire
                                </span>
                            </div>
                        @endif

                        <div class="text-center mb-4 pt-2">
                            <h4 class="text-lg font-bold text-gray-900">{{ $plan->name }}</h4>
                            <div class="mt-2">
                                <span class="text-3xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">
                                    {{ number_format($plan->price, 0, ',', ' ') }}
                                </span>
                                <span class="text-sm text-gray-500">{{ $currency }}/mois</span>
                            </div>
                        </div>

                        <div class="space-y-2 text-sm mb-4 bg-gray-50 rounded-lg p-3">
                            <div class="flex justify-between">
                                <span class="text-gray-500">Magasins</span>
                                <span class="font-semibold text-gray-900">{{ $plan->max_stores }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Utilisateurs</span>
                                <span class="font-semibold text-gray-900">{{ $plan->max_users }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Produits</span>
                                <span class="font-semibold text-gray-900">{{ number_format($plan->max_products, 0, ',', ' ') }}</span>
                            </div>
                        </div>

                        <div class="flex space-x-2">
                            <button wire:click="openEditModal({{ $plan->id }})"
                                class="flex-1 px-3 py-2.5 text-sm font-semibold bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white rounded-xl transition-all shadow-md hover:shadow-lg">
                                Modifier
                            </button>
                            <button wire:click="togglePopular({{ $plan->id }})"
                                class="px-3 py-2.5 text-sm border-2 border-gray-200 hover:border-amber-400 hover:bg-amber-50 rounded-xl transition-all"
                                title="Marquer comme populaire">
                                ⭐
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Preview Section -->
        <div class="bg-gradient-to-br from-white to-amber-50 rounded-xl shadow-md border border-amber-100 p-6 hover:shadow-lg transition-shadow">
            <div class="flex items-center gap-2 mb-2">
                <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-gray-900">Aperçu des tarifs (page publique)</h3>
                    <p class="text-sm text-gray-500">Voici comment les plans apparaîtront sur la page d'accueil</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mt-4">
                @foreach($plans as $slug => $plan)
                    <div class="relative bg-white rounded-2xl shadow-lg border border-gray-200 p-6 hover:shadow-xl transition-shadow
                        @if($plan['is_popular'] ?? false) ring-2 ring-indigo-500 @endif">

                        @if($plan['is_popular'] ?? false)
                            <div class="absolute -top-4 left-1/2 transform -translate-x-1/2">
                                <span class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white text-xs font-bold px-4 py-1 rounded-full shadow-md">
                                    POPULAIRE
                                </span>
                            </div>
                        @endif

                        <div class="text-center pt-2">
                            <h4 class="text-xl font-bold text-gray-900">{{ $plan['name'] }}</h4>
                            <div class="mt-4">
                                <span class="text-4xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">
                                    {{ number_format($plan['price'], 0, ',', ' ') }}
                                </span>
                                <span class="text-gray-500">{{ $currency }}/mois</span>
                            </div>
                        </div>

                        <ul class="mt-6 space-y-3">
                            @foreach($plan['features'] ?? [] as $feature)
                                <li class="flex items-start text-sm">
                                    <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                    <span class="text-gray-600">{{ $feature }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Edit Plan Modal -->
    <x-modal name="showEditModal" maxWidth="lg" :showHeader="false">
        <div class="bg-white rounded-xl shadow-xl">
            <!-- Modal Header -->
            <div class="flex items-center justify-between p-6 border-b border-gray-200">
                <div class="flex items-center space-x-3">
                    <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900">
                        Modifier le plan {{ $editForm['name'] ?? '' }}
                    </h3>
                </div>
                <button wire:click="closeEditModal" class="text-gray-400 hover:text-gray-500 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="p-6 space-y-5">
                <!-- Nom du plan -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nom du plan <span class="text-red-500">*</span></label>
                    <input type="text" wire:model="editForm.name"
                        class="w-full px-4 py-2.5 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white text-gray-900 transition-all"
                        placeholder="Ex: Starter">
                    @error('editForm.name') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                </div>

                <!-- Prix mensuel -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Prix mensuel ({{ $currency }}) @if(($editForm['slug'] ?? '') !== 'free')<span class="text-red-500">*</span>@endif</label>
                    <input type="number" wire:model="editForm.price" min="0" step="100"
                        class="w-full px-4 py-2.5 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white text-gray-900 transition-all disabled:bg-gray-100 disabled:cursor-not-allowed"
                        placeholder="Ex: 9900" @if(($editForm['slug'] ?? '') === 'free') disabled @endif>
                    @if(($editForm['slug'] ?? '') === 'free')
                        <p class="text-xs text-gray-500 mt-1">Le plan gratuit ne peut pas avoir de prix</p>
                    @endif
                    @error('editForm.price') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                </div>

                <!-- Limites -->
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Max Magasins <span class="text-red-500">*</span></label>
                        <input type="number" wire:model="editForm.max_stores" min="1"
                            class="w-full px-4 py-2.5 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white text-gray-900 transition-all"
                            placeholder="1">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Max Utilisateurs <span class="text-red-500">*</span></label>
                        <input type="number" wire:model="editForm.max_users" min="1"
                            class="w-full px-4 py-2.5 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white text-gray-900 transition-all"
                            placeholder="3">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Max Produits <span class="text-red-500">*</span></label>
                        <input type="number" wire:model="editForm.max_products" min="1"
                            class="w-full px-4 py-2.5 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white text-gray-900 transition-all"
                            placeholder="100">
                    </div>
                </div>

                <!-- Fonctionnalités -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fonctionnalités (une par ligne)</label>
                    <textarea wire:model="editForm.features_text" rows="6"
                        class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white text-gray-900 placeholder-gray-400 transition-all"
                        placeholder="Jusqu'à X magasins&#10;Support prioritaire&#10;Rapports avancés"></textarea>
                    <p class="text-xs text-gray-500 mt-1">Entrez une fonctionnalité par ligne</p>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="bg-gray-50 px-6 py-4 flex items-center justify-end space-x-3 border-t border-gray-200 rounded-b-xl">
                <button wire:click="closeEditModal"
                    class="px-5 py-2.5 text-gray-700 bg-white border-2 border-gray-300 hover:bg-gray-50 rounded-xl font-medium transition-all">
                    Annuler
                </button>
                <button wire:click="savePlan"
                    class="px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white rounded-xl font-medium transition-all shadow-md hover:shadow-lg">
                    <span class="flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Enregistrer
                    </span>
                </button>
            </div>
        </div>
    </x-modal>

    <!-- Toast -->
    <x-toast />
</div>
