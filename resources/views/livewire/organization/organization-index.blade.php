<div x-data="{ showDeleteModal: false, orgToDelete: null, orgName: '', showSyncModal: false, orgToSync: null, syncOrgName: '' }"
     wire:poll.30s
     @organization-created.window="$wire.$refresh()"
     @organization-updated.window="$wire.$refresh()"
     @organization-deleted.window="$wire.$refresh()">
    <x-slot name="header">
        <x-breadcrumb :items="[['label' => 'Accueil', 'url' => route('dashboard')], ['label' => 'Organisations']]" />
    </x-slot>

    <div class="flex items-center justify-between mt-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Mes Organisations</h1>
            <p class="text-gray-500 mt-1">Gérez vos organisations et leurs magasins</p>
        </div>
    </div>

    <!-- Toast Notifications -->
    <x-toast />

    <!-- Search and Filters -->
    <div class="mb-6 bg-white rounded-xl shadow-sm border border-gray-200 p-4 mt-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Search -->
            <x-form.search-input
                wire:model.live.debounce.300ms="search"
                wireModel="search"
                placeholder="Rechercher une organisation..."
            />

            <!-- Type Filter -->
            <div class="flex items-center space-x-2">
                <label for="type" class="text-sm font-medium text-gray-700 whitespace-nowrap">Type :</label>
                <select id="type" wire:model.live="type"
                    class="block w-full px-3 py-2.5 border border-gray-300 rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                    <option value="">Tous les types</option>
                    @foreach($types as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <!-- Organizations List -->
    <div class="space-y-4">
        @forelse ($organizations as $organization)
            <div wire:key="org-{{ $organization->id }}"
                class="bg-white rounded-xl shadow-sm border {{ $organization->subscription_plan->value !== 'free' && !$organization->hasActiveSubscription() ? 'border-red-300' : 'border-gray-200' }} overflow-hidden hover:shadow-md transition duration-150 {{ $currentOrganization?->id === $organization->id ? 'ring-2 ring-indigo-500' : '' }}">

                {{-- Expiration Alert Banner --}}
                @if($organization->subscription_plan->value !== 'free' && !$organization->hasActiveSubscription())
                    <div class="bg-red-50 border-b border-red-200 px-4 py-2 flex items-center justify-between">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-red-500 mr-2 animate-pulse" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                            <span class="text-sm font-medium text-red-800">
                                ⚠️ Abonnement expiré depuis le {{ $organization->subscription_ends_at->format('d/m/Y') }}
                            </span>
                        </div>
                        <div class="flex items-center space-x-2">
                            {{-- Bouton paiement pour tous les utilisateurs --}}
                            <button
                                x-data
                                @click="$dispatch('open-renewal-modal', { organizationId: {{ $organization->id }} })"
                                class="inline-flex items-center px-3 py-1 text-xs font-medium rounded-md bg-red-600 text-white hover:bg-red-700 transition"
                            >
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                Renouveler
                            </button>
                            {{-- Bouton admin pour modifier les dates manuellement --}}
                            @if(auth()->user()->isSuperAdmin())
                                <button
                                    wire:click="openSubscriptionModal({{ $organization->id }})"
                                    class="inline-flex items-center px-3 py-1 text-xs font-medium rounded-md bg-red-100 text-red-700 hover:bg-red-200 transition"
                                    title="Modifier manuellement"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                            @endif
                        </div>
                    </div>
                @elseif($organization->subscription_plan->value !== 'free' && $organization->isSubscriptionExpiringToday())
                    <div class="bg-orange-50 border-b border-orange-200 px-4 py-2 flex items-center justify-between">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-orange-500 mr-2 animate-pulse" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                            </svg>
                            <span class="text-sm font-medium text-orange-800">
                                ⏰ L'abonnement expire AUJOURD'HUI à {{ $organization->subscription_ends_at->format('H:i') }}
                            </span>
                        </div>
                        <div class="flex items-center space-x-2">
                            {{-- Bouton paiement pour tous les utilisateurs --}}
                            <button
                                x-data
                                @click="$dispatch('open-renewal-modal', { organizationId: {{ $organization->id }} })"
                                class="inline-flex items-center px-3 py-1 text-xs font-medium rounded-md bg-orange-600 text-white hover:bg-orange-700 transition"
                            >
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                                Prolonger
                            </button>
                            {{-- Bouton admin pour modifier les dates manuellement --}}
                            @if(auth()->user()->isSuperAdmin())
                                <button
                                    wire:click="openSubscriptionModal({{ $organization->id }})"
                                    class="inline-flex items-center px-3 py-1 text-xs font-medium rounded-md bg-orange-100 text-orange-700 hover:bg-orange-200 transition"
                                    title="Modifier manuellement"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                            @endif
                        </div>
                    </div>
                @endif

                <div class="p-6">
                    <div class="flex items-start justify-between">
                        <!-- Organization Info -->
                        <div class="flex items-start space-x-4">
                            <!-- Logo/Icon -->
                            <div class="flex-shrink-0">
                                @if($organization->logo)
                                    <img src="{{ Storage::url($organization->logo) }}" alt="{{ $organization->name }}"
                                        class="w-16 h-16 rounded-lg object-cover">
                                @else
                                    <div class="w-16 h-16 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center">
                                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                        </svg>
                                    </div>
                                @endif
                            </div>

                            <div>
                                <div class="flex items-center space-x-2">
                                    <h3 class="text-lg font-bold text-gray-900">{{ $organization->name }}</h3>
                                    @if($organization->pivot)
                                        @if($organization->pivot->role === 'owner')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                                                Propriétaire
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                                {{ ucfirst($organization->pivot->role) }}
                                            </span>
                                        @endif
                                    @endif
                                    @if($currentOrganization?->id === $organization->id)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                            Active
                                        </span>
                                    @endif
                                </div>

                                <p class="text-sm text-gray-500 mt-1">{{ $organization->type_label }}</p>

                                @if($organization->legal_name)
                                    <p class="text-sm text-gray-600">{{ $organization->legal_name }}</p>
                                @endif

                                <!-- Stats -->
                                <div class="flex items-center flex-wrap gap-3 mt-3 text-sm text-gray-500">
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                        </svg>
                                        {{ $organization->stores->count() }} magasin(s)
                                    </div>
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                                        </svg>
                                        {{ $organization->members->count() }} membre(s)
                                    </div>
                                    <div class="flex items-center">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                            {{ $organization->subscription_plan->value === 'free' ? 'bg-gray-100 text-gray-800' : '' }}
                                            {{ $organization->subscription_plan->value === 'starter' ? 'bg-blue-100 text-blue-800' : '' }}
                                            {{ $organization->subscription_plan->value === 'professional' ? 'bg-purple-100 text-purple-800' : '' }}
                                            {{ $organization->subscription_plan->value === 'enterprise' ? 'bg-green-100 text-green-800' : '' }}">
                                            {{ $organization->plan_label }}
                                        </span>
                                    </div>

                                    {{-- Subscription Status --}}
                                    @if($organization->subscription_plan->value !== 'free')
                                        <div class="flex items-center">
                                            @if($organization->isPaymentPending())
                                                {{-- Paiement en attente --}}
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800">
                                                    <svg class="w-3 h-3 mr-1 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    Paiement en attente
                                                </span>
                                            @elseif($organization->hasActiveSubscription())
                                                @if($organization->isSubscriptionExpiringToday())
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-orange-100 text-orange-800 animate-pulse">
                                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                        Expire aujourd'hui
                                                    </span>
                                                @elseif($organization->isSubscriptionExpiringSoon())
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                        </svg>
                                                        Expire bientôt
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                        Actif
                                                    </span>
                                                @endif
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    Expiré
                                                </span>
                                            @endif
                                        </div>

                                        {{-- Expiration Date --}}
                                        @if($organization->isPaymentPending())
                                            {{-- Paiement en attente - pas de date d'expiration --}}
                                            <div class="flex items-center text-xs text-amber-600">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                Finalisez le paiement pour activer
                                            </div>
                                        @elseif($organization->subscription_ends_at)
                                            <div class="flex items-center text-xs {{ $organization->isSubscriptionExpiringToday() ? 'text-orange-600 font-semibold' : ($organization->isSubscriptionExpiringSoon() ? 'text-yellow-600' : ($organization->hasActiveSubscription() ? 'text-gray-500' : 'text-red-600')) }}">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                                @if($organization->hasActiveSubscription())
                                                    @if($organization->isSubscriptionExpiringToday())
                                                        ⚠️ Expire AUJOURD'HUI à {{ $organization->subscription_ends_at->format('H:i') }}
                                                    @else
                                                        Expire le {{ $organization->subscription_ends_at->format('d/m/Y') }}
                                                        ({{ $organization->remaining_days }} jour(s))
                                                    @endif
                                                @else
                                                    Expiré le {{ $organization->subscription_ends_at->format('d/m/Y') }}
                                                @endif
                                            </div>
                                        @endif
                                    @else
                                        <div class="flex items-center">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                                Plan gratuit
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center space-x-2">
                            @if($currentOrganization?->id !== $organization->id)
                                <x-form.button wire:click="switchTo({{ $organization->id }})" size="sm" icon="arrow-right">
                                    Basculer
                                </x-form.button>
                            @endif

                            <x-form.button href="{{ route('organizations.show', $organization) }}" variant="secondary" size="sm" icon="eye">
                                Voir
                            </x-form.button>

                            @can('update', $organization)
                                <x-form.button href="{{ route('organizations.edit', $organization) }}" variant="secondary" size="sm" icon="edit" />
                            @endcan

                            @can('manageMembers', $organization)
                                <x-form.button href="{{ route('organizations.members', $organization) }}" variant="secondary" size="sm" icon="users" />
                            @endcan

                            {{-- Subscription Management Button (Super Admin Only) --}}
                            @if(auth()->user()->isSuperAdmin())
                                <x-form.button
                                    wire:click="openSubscriptionModal({{ $organization->id }})"
                                    variant="secondary"
                                    size="sm"
                                    title="Gérer l'abonnement"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </x-form.button>

                                {{-- Subscription History Button --}}
                                <x-form.button
                                    wire:click="openHistoryModal({{ $organization->id }})"
                                    variant="secondary"
                                    size="sm"
                                    title="Historique des abonnements"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </x-form.button>

                            @endif

                            {{-- Sync Product Types & Categories Button (Admin or Super Admin) --}}
                            @if(auth()->user()->isSuperAdmin() || auth()->user()->isOrganizationAdmin($organization))
                                <x-form.button
                                    @click="showSyncModal = true; orgToSync = {{ $organization->id }}; syncOrgName = '{{ addslashes($organization->name) }}'"
                                    variant="secondary"
                                    size="sm"
                                    title="Synchroniser les types de produits et catégories"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                </x-form.button>
                            @endif
                        </div>
                    </div>

                    <!-- Stores Preview -->
                    @if($organization->stores->isNotEmpty())
                        <div class="mt-4 pt-4 border-t border-gray-100">
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">Magasins</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach($organization->stores->take(5) as $store)
                                    <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-700">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                        </svg>
                                        {{ $store->name }}
                                    </span>
                                @endforeach
                                @if($organization->stores->count() > 5)
                                    <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-indigo-100 text-indigo-700">
                                        +{{ $organization->stores->count() - 5 }} autre(s)
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
                <svg class="mx-auto h-16 w-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                <h3 class="mt-4 text-lg font-medium text-gray-900">Aucune organisation</h3>
                <p class="mt-2 text-sm text-gray-500">Vous n'êtes membre d'aucune organisation pour le moment.</p>
                @role('super-admin')
                <div class="mt-6">
                    <x-form.button href="{{ route('organizations.create') }}" icon="plus">
                        Créer votre première organisation
                    </x-form.button>
                </div>
                @else
                <p class="mt-4 text-sm text-gray-400">Contactez un administrateur pour être ajouté à une organisation.</p>
                @endrole
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($organizations->hasPages())
        <div class="mt-6">
            {{ $organizations->links() }}
        </div>
    @endif

    {{-- Subscription Management Modal (Super Admin Only) --}}
    @if(auth()->user()->isSuperAdmin())
        <div x-data="{ showModal: false, isEditing: true }"
             @open-subscription-modal.window="showModal = true"
             @close-subscription-modal.window="showModal = false; $wire.resetSubscriptionModal()">
            <x-ui.alpine-modal
                name="subscription"
                title="Gérer l'abonnement"
                editTitle="Gérer l'abonnement"
                maxWidth="md"
            >
                <x-slot name="icon">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </x-slot>

                <form wire:submit="updateSubscriptionDates">
                    <div class="p-6 space-y-4 overflow-y-auto" style="max-height: calc(90vh - 180px);">
                        {{-- Organization Name --}}
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-sm text-gray-500">Organisation</p>
                            <p class="text-lg font-semibold text-gray-900">{{ $subscriptionOrgName }}</p>
                        </div>

                        {{-- Subscription Start Date --}}
                        <div>
                            <label for="subscriptionStartsAt" class="block text-sm font-medium text-gray-700 mb-1">
                                Date de début d'abonnement
                            </label>
                            <input
                                type="date"
                                id="subscriptionStartsAt"
                                wire:model.live="subscriptionStartsAt"
                                value="{{ $subscriptionStartsAt }}"
                                class="block w-full px-3 py-2.5 border border-gray-300 rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                            >
                            @error('subscriptionStartsAt')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            @if($subscriptionStartsAt)
                                <p class="mt-1 text-xs text-gray-500">
                                    Actuellement : {{ \Carbon\Carbon::parse($subscriptionStartsAt)->format('d/m/Y') }}
                                </p>
                            @endif
                        </div>

                        {{-- Subscription End Date --}}
                        <div>
                            <label for="subscriptionEndsAt" class="block text-sm font-medium text-gray-700 mb-1">
                                Date de fin d'abonnement
                            </label>
                            <input
                                type="date"
                                id="subscriptionEndsAt"
                                wire:model.live="subscriptionEndsAt"
                                value="{{ $subscriptionEndsAt }}"
                                class="block w-full px-3 py-2.5 border border-gray-300 rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                            >
                            @error('subscriptionEndsAt')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            @if($subscriptionEndsAt)
                                <p class="mt-1 text-xs text-gray-500">
                                    Actuellement : {{ \Carbon\Carbon::parse($subscriptionEndsAt)->format('d/m/Y') }}
                                </p>
                            @else
                                <p class="mt-1 text-xs text-gray-500">
                                    Laissez vide pour un abonnement sans date d'expiration (plan gratuit).
                                </p>
                            @endif
                        </div>

                        {{-- Quick Actions --}}
                        <div class="border-t border-gray-200 pt-4">
                            <p class="text-sm font-medium text-gray-700 mb-2">Actions rapides</p>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    type="button"
                                    wire:click="$set('subscriptionEndsAt', '{{ now()->addMonth()->format('Y-m-d') }}')"
                                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md bg-blue-50 text-blue-700 hover:bg-blue-100 transition"
                                >
                                    +1 mois
                                </button>
                                <button
                                    type="button"
                                    wire:click="$set('subscriptionEndsAt', '{{ now()->addMonths(3)->format('Y-m-d') }}')"
                                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md bg-blue-50 text-blue-700 hover:bg-blue-100 transition"
                                >
                                    +3 mois
                                </button>
                                <button
                                    type="button"
                                    wire:click="$set('subscriptionEndsAt', '{{ now()->addMonths(6)->format('Y-m-d') }}')"
                                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md bg-blue-50 text-blue-700 hover:bg-blue-100 transition"
                                >
                                    +6 mois
                                </button>
                                <button
                                    type="button"
                                    wire:click="$set('subscriptionEndsAt', '{{ now()->addYear()->format('Y-m-d') }}')"
                                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md bg-green-50 text-green-700 hover:bg-green-100 transition"
                                >
                                    +1 an
                                </button>
                                <button
                                    type="button"
                                    wire:click="$set('subscriptionEndsAt', null)"
                                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md bg-gray-50 text-gray-700 hover:bg-gray-100 transition"
                                >
                                    Illimité
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Modal Footer --}}
                    <div class="flex-shrink-0 flex items-center justify-end space-x-3 p-6 border-t border-gray-200 bg-gray-50 rounded-b-2xl">
                        <button
                            type="button"
                            @click="showModal = false; $wire.resetSubscriptionModal()"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition"
                        >
                            Annuler
                        </button>
                        <button
                            type="submit"
                            class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition"
                        >
                            <span wire:loading.remove wire:target="updateSubscriptionDates">Enregistrer</span>
                            <span wire:loading wire:target="updateSubscriptionDates">
                                <svg class="animate-spin h-4 w-4 inline mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Enregistrement...
                            </span>
                        </button>
                    </div>
                </form>
            </x-ui.alpine-modal>
        </div>

        {{-- Subscription History Modal --}}
        <div x-data="{ showModal: false, isEditing: true }"
             @open-history-modal.window="showModal = true"
             @close-history-modal.window="showModal = false; $wire.resetHistoryModal()">
            <x-ui.alpine-modal
                name="subscription-history"
                title="Historique des abonnements"
                editTitle="Historique des abonnements"
                maxWidth="3xl"
            >
                <x-slot name="icon">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </x-slot>

                <div class="p-6 max-h-[70vh] overflow-y-auto">
                    {{-- Organization Name --}}
                    @if($historyOrgName)
                        <div class="bg-gray-50 rounded-lg p-4 mb-4">
                            <p class="text-sm text-gray-500">Organisation</p>
                            <p class="text-lg font-semibold text-gray-900">{{ $historyOrgName }}</p>
                        </div>
                    @endif

                    {{-- Filter --}}
                    <div class="mb-4">
                        <label for="historyActionFilter" class="block text-sm font-medium text-gray-700 mb-1">Filtrer par action</label>
                        <select
                            id="historyActionFilter"
                            wire:model.live="historyActionFilter"
                            class="block w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        >
                            <option value="">Toutes les actions</option>
                            @foreach($historyActions as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- History Timeline --}}
                    <div class="space-y-4">
                        @forelse($subscriptionHistory as $entry)
                            <div class="relative pl-8 pb-4 {{ !$loop->last ? 'border-l-2 border-gray-200' : '' }}" wire:key="history-{{ $entry->id }}">
                                {{-- Timeline Dot --}}
                                <div class="absolute left-0 top-0 -translate-x-1/2 w-4 h-4 rounded-full
                                    @switch($entry->action_color)
                                        @case('blue') bg-blue-500 @break
                                        @case('green') bg-green-500 @break
                                        @case('indigo') bg-indigo-500 @break
                                        @case('yellow') bg-yellow-500 @break
                                        @case('red') bg-red-500 @break
                                        @case('purple') bg-purple-500 @break
                                        @default bg-gray-400
                                    @endswitch
                                "></div>

                                {{-- Entry Card --}}
                                <div class="bg-white rounded-lg border border-gray-200 p-4 ml-4 shadow-sm">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            {{-- Action Badge --}}
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                @switch($entry->action_color)
                                                    @case('blue') bg-blue-100 text-blue-800 @break
                                                    @case('green') bg-green-100 text-green-800 @break
                                                    @case('indigo') bg-indigo-100 text-indigo-800 @break
                                                    @case('yellow') bg-yellow-100 text-yellow-800 @break
                                                    @case('red') bg-red-100 text-red-800 @break
                                                    @case('purple') bg-purple-100 text-purple-800 @break
                                                    @default bg-gray-100 text-gray-800
                                                @endswitch
                                            ">
                                                {{ $entry->action_label }}
                                            </span>

                                            {{-- Plan Info --}}
                                            @if($entry->old_plan && $entry->new_plan && $entry->old_plan !== $entry->new_plan)
                                                <span class="ml-2 text-sm text-gray-500">
                                                    {{ ucfirst($entry->old_plan) }} → {{ ucfirst($entry->new_plan) }}
                                                </span>
                                            @elseif($entry->new_plan)
                                                <span class="ml-2 text-sm text-gray-500">
                                                    Plan {{ ucfirst($entry->new_plan) }}
                                                </span>
                                            @endif

                                            {{-- Date Range --}}
                                            @if($entry->subscription_starts_at || $entry->subscription_ends_at)
                                                <div class="mt-2 text-sm text-gray-600">
                                                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                    </svg>
                                                    @if($entry->subscription_starts_at)
                                                        {{ $entry->subscription_starts_at->format('d/m/Y') }}
                                                    @endif
                                                    @if($entry->subscription_starts_at && $entry->subscription_ends_at)
                                                        →
                                                    @endif
                                                    @if($entry->subscription_ends_at)
                                                        {{ $entry->subscription_ends_at->format('d/m/Y') }}
                                                    @else
                                                        <span class="text-gray-400">(Illimité)</span>
                                                    @endif
                                                </div>
                                            @endif

                                            {{-- Notes --}}
                                            @if($entry->notes)
                                                <p class="mt-2 text-sm text-gray-500 italic">{{ $entry->notes }}</p>
                                            @endif

                                            {{-- Amount --}}
                                            @if($entry->amount && $entry->amount > 0)
                                                <div class="mt-2">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                                        {{ number_format($entry->amount, 0, ',', ' ') }} {{ $entry->currency }}
                                                    </span>
                                                </div>
                                            @endif
                                        </div>

                                        {{-- Timestamp --}}
                                        <div class="text-right text-xs text-gray-400 ml-4">
                                            <div>{{ $entry->created_at->format('d/m/Y') }}</div>
                                            <div>{{ $entry->created_at->format('H:i') }}</div>
                                            @if($entry->user)
                                                <div class="mt-1 text-gray-500">
                                                    par {{ $entry->user->name }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-8 text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p class="mt-2">Aucun historique trouvé</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- Modal Footer --}}
                <div class="flex-shrink-0 flex items-center justify-end p-6 border-t border-gray-200 bg-gray-50 rounded-b-2xl">
                    <button
                        type="button"
                        @click="showModal = false; $wire.resetHistoryModal()"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition"
                    >
                        Fermer
                    </button>
                </div>
            </x-ui.alpine-modal>
        </div>
    @endif

    {{-- Sync Confirmation Modal --}}
    <x-confirmation-modal
        show="showSyncModal"
        title="Synchroniser les types et catégories"
        icon-color="indigo"
        confirm-text="Synchroniser"
        on-confirm="$wire.syncProductTypesAndCategories(orgToSync); showSyncModal = false; orgToSync = null; syncOrgName = ''"
        on-cancel="showSyncModal = false; orgToSync = null; syncOrgName = ''"
    >
        <p>
            Voulez-vous synchroniser les types de produits et catégories pour 
            <span class="font-semibold text-gray-900" x-text="syncOrgName"></span> ?
        </p>
        <p class="mt-2 text-gray-500">
            Les types et catégories existants ne seront pas dupliqués.
        </p>
    </x-confirmation-modal>
</div>
