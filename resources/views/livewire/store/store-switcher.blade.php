<div x-data="{ open: false }" @click.away="open = false" class="relative">
    @php
        $currentOrganization = app()->bound('current_organization') ? app('current_organization') : null;
    @endphp

    {{-- Ne pas afficher le switcher pour les super-admins --}}
    @if(auth()->user()->hasRole('super-admin'))
        <div class="flex items-center space-x-2 px-3 py-2 text-sm font-medium text-gray-500">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
            <span class="hidden md:inline">Super Admin</span>
        </div>
    @else
    <!-- Current Store Button -->
    <button @click="open = !open" type="button"
        class="flex items-center space-x-2 px-3 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
        </svg>
        <span class="hidden md:inline">
            @if($currentStoreId)
                {{ collect($availableStores)->firstWhere('id', $currentStoreId)['name'] ?? 'Sélectionner un magasin' }}
            @else
                Tous les magasins
            @endif
        </span>
        <svg class="w-4 h-4" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
    </button>

    <!-- Dropdown Menu -->
    <div x-show="open" x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="absolute right-0 mt-2 w-72 bg-white rounded-lg shadow-lg border border-gray-200 z-50" x-cloak>

        <!-- Header with Organization Name -->
        <div class="px-4 py-3 border-b border-gray-200">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Sélectionner un magasin</p>
            @if($currentOrganization)
                <p class="text-xs text-indigo-600 mt-1">{{ $currentOrganization->name }}</p>
            @endif
        </div>

        <!-- Store List -->
        <div class="py-2 max-h-64 overflow-y-auto">
            @if(auth()->user()->isAdmin())
                {{-- Option "Tous les magasins" pour les admins/managers --}}
                <button wire:click="switchStore(null)" type="button"
                    class="w-full px-4 py-3 flex items-center justify-between hover:bg-gray-50 transition {{ !$currentStoreId ? 'bg-indigo-50' : '' }}">
                    <div class="flex items-center space-x-3">
                        <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                            </svg>
                        </div>
                        <div class="text-left">
                            <p class="text-sm font-medium text-gray-900">Tous les magasins</p>
                            <span class="text-xs text-gray-500">Vue globale</span>
                        </div>
                    </div>

                    @if(!$currentStoreId)
                        <svg class="w-5 h-5 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    @endif
                </button>
            @endif

            @forelse($availableStores as $store)
                <button wire:click="switchStore({{ $store['id'] }})" type="button"
                    class="w-full px-4 py-3 flex items-center justify-between hover:bg-gray-50 transition {{ $store['id'] == $currentStoreId ? 'bg-indigo-50' : '' }}">
                    <div class="flex items-center space-x-3">
                        <div
                            class="flex-shrink-0 w-10 h-10 bg-gradient-to-br {{ $store['is_main'] ? 'from-indigo-500 to-purple-600' : 'from-gray-500 to-gray-600' }} rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        </div>
                        <div class="text-left">
                            <p class="text-sm font-medium text-gray-900">{{ $store['name'] }}</p>
                            <div class="flex items-center space-x-2 mt-0.5">
                                @if($store['code'])
                                    <span class="text-xs text-gray-500">{{ $store['code'] }}</span>
                                @endif
                                @if($store['is_main'])
                                    <span
                                        class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-700">
                                        Principal
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Current indicator -->
                    @if($store['id'] == $currentStoreId)
                        <svg class="w-5 h-5 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                clip-rule="evenodd" />
                        </svg>
                    @endif
                </button>
            @empty
                <div class="px-4 py-3 text-sm text-gray-500 text-center">
                    Aucun magasin disponible
                </div>
            @endforelse
        </div>

        <!-- Footer -->
        @if(auth()->user()->isAdmin())
            <div class="px-4 py-3 border-t border-gray-200">
                <a href="{{ route('stores.index') }}" wire:navigate
                    class="flex items-center justify-center space-x-2 text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span>Gérer les magasins</span>
                </a>
            </div>
        @endif
    </div>
    @endif
</div>
