<x-slot name="header">
    <x-breadcrumb :items="[
        ['label' => 'Accueil', 'url' => route('dashboard')],
        ['label' => 'Proformas', 'url' => route('proformas.index')],
        ['label' => 'Créer']
    ]" />

    <div class="flex items-center justify-between mt-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Nouvelle Proforma</h1>
            <p class="text-gray-500 mt-1">Créez une nouvelle facture proforma</p>
        </div>
        <x-form.button href="{{ route('proformas.index') }}" wire:navigate variant="secondary" icon="arrow-left">
            Retour
        </x-form.button>
    </div>
</x-slot>

<div class="max-w-7xl mx-auto">
    <!-- Toast -->
    <x-toast />

    <!-- Messages -->
    @if (session()->has('success'))
        <x-form.alert type="success" :message="session('success')" class="mb-6" />
    @endif

    @if (session()->has('error'))
        <x-form.alert type="error" :message="session('error')" class="mb-6" />
    @endif

    <form wire:submit="save">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6" wire:loading.class="opacity-50 pointer-events-none">
            <!-- Left Column - Items -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Add Items Card -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
                    {{-- Header --}}
                    <div class="px-6 py-4 border-b border-gray-100">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-gradient-to-br from-indigo-100 to-purple-100 rounded-xl flex items-center justify-center">
                                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-lg font-bold text-gray-900">Articles de la proforma</h2>
                                <p class="text-gray-500 text-sm">Recherchez et ajoutez des articles à la proforma</p>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Corps --}}
                    <div class="p-6">
                        {{-- Barre de recherche --}}
                        <div class="relative" x-data="{ open: false }">
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <svg wire:loading.remove wire:target="productSearch" class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                    </svg>
                                    <svg wire:loading wire:target="productSearch" class="h-5 w-5 text-indigo-500 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </div>
                                
                                <input
                                    type="text"
                                    wire:model.live.debounce.300ms="productSearch"
                                    @focus="open = true"
                                    @click="open = true"
                                    placeholder="Rechercher par nom, référence ou SKU..."
                                    autocomplete="off"
                                    class="block w-full pl-12 pr-12 py-4 text-base border-2 border-gray-200 rounded-xl shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all duration-200 hover:border-gray-300"
                                >
                                
                                @if(strlen($productSearch) > 0)
                                    <button
                                        wire:click="$set('productSearch', '')"
                                        @click="open = false"
                                        type="button"
                                        class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-gray-600 transition-colors"
                                    >
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                @endif
                            </div>

                            {{-- Dropdown résultats --}}
                            @if(strlen($productSearch) >= 2)
                                <div 
                                    x-show="open"
                                    x-cloak
                                    x-transition:enter="transition ease-out duration-200"
                                    x-transition:enter-start="opacity-0 -translate-y-2"
                                    x-transition:enter-end="opacity-100 translate-y-0"
                                    x-transition:leave="transition ease-in duration-150"
                                    x-transition:leave-start="opacity-100 translate-y-0"
                                    x-transition:leave-end="opacity-0 -translate-y-2"
                                    @click.away="open = false"
                                    class="absolute z-[100] w-full mt-2 bg-white rounded-xl shadow-2xl border border-gray-200 max-h-80 overflow-hidden"
                                >
                                    @if($this->hasResults)
                                        <div class="px-4 py-3 bg-gradient-to-r from-gray-50 to-gray-100 border-b border-gray-200">
                                            <span class="text-xs font-bold text-gray-600 uppercase tracking-wider flex items-center gap-2">
                                                <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                                                {{ count($this->searchResults) }} produit(s) trouvé(s)
                                            </span>
                                        </div>
                                        <div class="overflow-y-auto max-h-64">
                                            @foreach($this->searchResults as $result)
                                                <button type="button"
                                                        wire:click="selectProduct({{ $result['id'] }})"
                                                        @click="open = false"
                                                        class="w-full px-4 py-3.5 hover:bg-gradient-to-r hover:from-indigo-50 hover:to-purple-50 text-left border-b border-gray-100 last:border-0 transition-all duration-200 group">
                                                    <div class="flex justify-between items-center gap-4">
                                                        <div class="flex items-center gap-3 min-w-0 flex-1">
                                                            <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-100 to-purple-100 flex items-center justify-center group-hover:from-indigo-200 group-hover:to-purple-200 group-hover:scale-105 transition-all duration-200">
                                                                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                                                </svg>
                                                            </div>
                                                            <div class="min-w-0">
                                                                <div class="text-sm font-semibold text-gray-900 group-hover:text-indigo-700 truncate">{{ $result['name'] }}</div>
                                                                <div class="text-xs text-gray-500 mt-0.5">SKU: {{ $result['sku'] }}</div>
                                                            </div>
                                                        </div>
                                                        <div class="text-right flex-shrink-0">
                                                            <div class="text-sm font-bold text-indigo-600">
                                                                {{ format_currency($result['price']) }}
                                                            </div>
                                                            <div class="text-xs font-medium mt-0.5 {{ $result['stock'] > 10 ? 'text-green-600' : ($result['stock'] > 0 ? 'text-amber-600' : 'text-red-600') }}">
                                                                @if($result['stock'] > 0)
                                                                    <span class="inline-flex items-center gap-1">
                                                                        <span class="w-1.5 h-1.5 rounded-full {{ $result['stock'] > 10 ? 'bg-green-500' : 'bg-amber-500' }}"></span>
                                                                        {{ $result['stock'] }} en stock
                                                                    </span>
                                                                @else
                                                                    <span class="inline-flex items-center gap-1 text-red-600">
                                                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                                                        Rupture
                                                                    </span>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                </button>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="px-6 py-10 text-center">
                                            <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                            </div>
                                            <p class="text-sm font-medium text-gray-700">Aucun produit trouvé</p>
                                            <p class="text-xs text-gray-500 mt-1">pour "<span class="text-indigo-600 font-medium">{{ $productSearch }}</span>"</p>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>

                        {{-- Produit sélectionné - formulaire d'ajout --}}
                        @if($selectedVariant)
                            <div class="mt-6 p-5 bg-gradient-to-br from-indigo-50 to-purple-50 rounded-xl border-2 border-indigo-200 animate-fade-in">
                                <div class="flex items-center gap-2 mb-4">
                                    <div class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center">
                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                        </svg>
                                    </div>
                                    <span class="font-semibold text-gray-800">Configurer l'article</span>
                                </div>
                                
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Quantité</label>
                                        <input type="number" wire:model.live="selectedQuantity" min="1" 
                                               class="w-full px-3 py-2.5 border-2 border-gray-200 rounded-lg focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all text-center font-semibold">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Prix unitaire</label>
                                        <input type="number" wire:model.live="selectedPrice" step="0.01" min="0"
                                               class="w-full px-3 py-2.5 border-2 border-gray-200 rounded-lg focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all text-right font-semibold">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Remise</label>
                                        <input type="number" wire:model.live="selectedDiscount" step="0.01" min="0" value="0"
                                               class="w-full px-3 py-2.5 border-2 border-gray-200 rounded-lg focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all text-right">
                                    </div>
                                    <div class="flex items-end">
                                        <button type="button" wire:click="addItem" 
                                                class="w-full px-4 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-semibold rounded-lg shadow-md hover:shadow-lg transition-all duration-200 flex items-center justify-center gap-2">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                            </svg>
                                            Ajouter
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Carte: Liste des articles --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-gradient-to-br from-emerald-100 to-green-100 rounded-xl flex items-center justify-center">
                                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-900">Articles de la proforma</h3>
                                <p class="text-xs text-gray-500">{{ count($items) }} article(s)</p>
                            </div>
                        </div>
                        @if(count($items) > 0)
                            <span class="px-3 py-1 bg-emerald-100 text-emerald-700 text-sm font-bold rounded-full">
                                {{ count($items) }}
                            </span>
                        @endif
                    </div>
                    
                    <div class="p-4">
                        @if(count($items) > 0)
                            <div class="space-y-3">
                                @foreach($items as $index => $item)
                                    <div class="flex items-center gap-4 p-4 bg-gray-50 hover:bg-gray-100 rounded-xl transition-colors group">
                                        {{-- Icône produit --}}
                                        <div class="flex-shrink-0 w-12 h-12 bg-white rounded-xl shadow-sm flex items-center justify-center border border-gray-200">
                                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                            </svg>
                                        </div>
                                        
                                        {{-- Info produit --}}
                                        <div class="flex-1 min-w-0">
                                            <h4 class="font-semibold text-gray-900 truncate">{{ $item['name'] }}</h4>
                                            <div class="flex items-center gap-3 mt-1 text-sm text-gray-500">
                                                <span class="inline-flex items-center gap-1">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>
                                                    </svg>
                                                    {{ $item['quantity'] }}
                                                </span>
                                                <span>×</span>
                                                <span>{{ format_currency($item['unit_price']) }}</span>
                                                @if($item['discount'] > 0)
                                                    <span class="text-red-500">-{{ format_currency($item['discount']) }}</span>
                                                @endif
                                            </div>
                                        </div>
                                        
                                        {{-- Prix et action --}}
                                        <div class="flex items-center gap-4">
                                            <div class="text-right">
                                                <div class="font-bold text-gray-900">{{ format_currency($item['total']) }}</div>
                                            </div>
                                            <button type="button" wire:click="removeItem({{ $index }})"
                                                    class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all opacity-0 group-hover:opacity-100">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="py-4 text-center flex items-center justify-center gap-3">
                                <div class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                </div>
                                <div class="text-left">
                                    <p class="text-sm text-gray-500">Aucun article ajouté - Recherchez et ajoutez des produits</p>
                                </div>
                            </div>
                        @endif
                        
                        @error('items')
                            <div class="mt-3 p-3 bg-red-50 border border-red-200 rounded-lg">
                                <p class="text-sm text-red-600">{{ $message }}</p>
                            </div>
                        @enderror
                    </div>
                </div>

                <!-- Notes Card -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-gradient-to-br from-amber-100 to-orange-100 rounded-xl flex items-center justify-center">
                                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-900">Notes et conditions</h3>
                                <p class="text-xs text-gray-500">Informations complémentaires pour le client</p>
                            </div>
                        </div>
                    </div>

                    <div class="p-6 space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Notes</label>
                            <textarea wire:model="notes" rows="3" 
                                      placeholder="Notes internes ou pour le client..."
                                      class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all resize-none"></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Conditions générales</label>
                            <textarea wire:model="terms_conditions" rows="3" 
                                      placeholder="Conditions de paiement, livraison, etc."
                                      class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all resize-none"></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column - Client & Summary -->
            <div class="space-y-6">
                <!-- Client Info Card -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-gradient-to-br from-blue-100 to-cyan-100 rounded-xl flex items-center justify-center">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-900">Informations client</h3>
                                <p class="text-xs text-gray-500">Coordonnées du destinataire</p>
                            </div>
                        </div>
                    </div>

                    <div class="p-6 space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Nom du client <span class="text-red-500">*</span></label>
                            <input wire:model="client_name" type="text" placeholder="Nom complet ou entreprise"
                                   class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all">
                            @error('client_name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Téléphone</label>
                            <input wire:model="client_phone" type="tel" placeholder="+243 XXX XXX XXX"
                                   class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Email</label>
                            <input wire:model="client_email" type="email" placeholder="client@example.com"
                                   class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all">
                            @error('client_email')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Adresse</label>
                            <textarea wire:model="client_address" rows="2" placeholder="Adresse complète"
                                      class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all resize-none"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Dates Card -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-gradient-to-br from-purple-100 to-pink-100 rounded-xl flex items-center justify-center">
                                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-900">Dates</h3>
                                <p class="text-xs text-gray-500">Dates importantes</p>
                            </div>
                        </div>
                    </div>

                    <div class="p-6 space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Date de la proforma <span class="text-red-500">*</span></label>
                            <input wire:model="proforma_date" type="date"
                                   class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all">
                            @error('proforma_date')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Valide jusqu'au <span class="text-red-500">*</span></label>
                            <input wire:model="valid_until" type="date"
                                   class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all">
                            @error('valid_until')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Summary Card -->
                <div class="bg-gradient-to-br from-indigo-50 to-purple-50 rounded-2xl shadow-sm border-2 border-indigo-200 overflow-hidden">
                    <div class="px-6 py-4 bg-gradient-to-r from-indigo-600 to-purple-600">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-white/20 backdrop-blur rounded-xl flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-white">Récapitulatif</h3>
                                <p class="text-xs text-indigo-100">Montants de la proforma</p>
                            </div>
                        </div>
                    </div>

                    <div class="p-6 space-y-4">
                        <div class="flex justify-between items-center pb-3 border-b border-indigo-200">
                            <span class="text-sm font-medium text-gray-700">Sous-total</span>
                            <span class="text-base font-bold text-gray-900">{{ format_currency($subtotal) }}</span>
                        </div>
                        
                        <div class="flex justify-between items-center pb-3 border-b border-indigo-200">
                            <span class="text-sm font-medium text-gray-700">Remises</span>
                            <span class="text-base font-bold text-red-600">-{{ format_currency(collect($items)->sum('discount')) }}</span>
                        </div>
                        
                        <div class="flex justify-between items-center pt-2">
                            <span class="text-lg font-bold text-gray-900">Total</span>
                            <div class="text-right">
                                <div class="text-2xl font-black bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">
                                    {{ format_currency($total) }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="px-6 pb-6">
                        <button type="submit"
                                class="w-full px-6 py-4 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 flex items-center justify-center gap-2 group">
                            <svg class="w-5 h-5 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Créer la proforma
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
