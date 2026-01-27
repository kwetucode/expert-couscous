<x-slot name="header">
    <x-breadcrumb :items="[
        ['label' => 'Accueil', 'url' => route('dashboard')],
        ['label' => 'Ventes', 'url' => route('sales.index')],
        ['label' => 'Nouvelle vente']
    ]" />
</x-slot>

<div class="max-w-7xl mx-auto" x-data="{ showNotes: false }">
    {{-- Header avec titre et actions --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">
                Nouvelle Vente
            </h1>
            <p class="text-gray-500 mt-1 text-sm">Créez une nouvelle transaction de vente</p>
        </div>
        <a href="{{ route('sales.index') }}" wire:navigate 
           class="inline-flex items-center justify-center px-4 py-2.5 bg-white hover:bg-gray-50 text-gray-700 font-medium rounded-xl border border-gray-300 shadow-sm transition-all hover:shadow-md group">
            <svg class="w-5 h-5 mr-2 text-gray-500 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Retour aux ventes
        </a>
    </div>

    {{-- Messages flash --}}
    @if (session()->has('success'))
        <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-xl flex items-center gap-3">
            <div class="flex-shrink-0 w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <p class="text-green-800 font-medium">{{ session('success') }}</p>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl flex items-center gap-3">
            <div class="flex-shrink-0 w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </div>
            <p class="text-red-800 font-medium">{{ session('error') }}</p>
        </div>
    @endif

    <form wire:submit="save">
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6" wire:loading.class="opacity-60 pointer-events-none">
            
            {{-- ===== COLONNE GAUCHE - Produits ===== --}}
            <div class="xl:col-span-2 space-y-6">
                
                {{-- Carte: Recherche & Ajout de produits --}}
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
                                <h2 class="text-lg font-bold text-gray-900">Ajouter des produits</h2>
                                <p class="text-gray-500 text-sm">Recherchez et ajoutez des articles à la vente</p>
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
                                                                {{ number_format($result['price'], 0, ',', ' ') }} <span class="text-xs">{{ current_currency() }}</span>
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
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-900">Panier</h3>
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
                                                <span>{{ number_format($item['unit_price'], 0, ',', ' ') }} {{ current_currency() }}</span>
                                                @if($item['discount'] > 0)
                                                    <span class="text-red-500">-{{ number_format($item['discount'], 0, ',', ' ') }}</span>
                                                @endif
                                            </div>
                                        </div>
                                        
                                        {{-- Prix et action --}}
                                        <div class="flex items-center gap-4">
                                            <div class="text-right">
                                                <div class="font-bold text-gray-900">{{ number_format($item['total'], 0, ',', ' ') }}</div>
                                                <div class="text-xs text-gray-500">{{ current_currency() }}</div>
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
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                </div>
                                <div class="text-left">
                                    <p class="text-sm text-gray-500">Panier vide - Recherchez et ajoutez des produits</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Carte: Informations de la vente --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-gradient-to-br from-blue-100 to-cyan-100 rounded-xl flex items-center justify-center">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-900">Détails de la vente</h3>
                                <p class="text-xs text-gray-500">Client, paiement et autres informations</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="p-6 space-y-5">
                        {{-- Ligne 1: Client et Date --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    <span class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                        Client
                                    </span>
                                </label>
                                <select wire:model="form.client_id" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all bg-white">
                                    <option value="">👤 Client anonyme</option>
                                    @foreach($clients as $client)
                                        <option value="{{ $client->id }}">{{ $client->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    <span class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                        Date de vente
                                    </span>
                                </label>
                                <input type="date" wire:model="form.sale_date" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all">
                            </div>
                        </div>

                        {{-- Ligne 2: Paiement --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    <span class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                        </svg>
                                        Mode de paiement
                                    </span>
                                </label>
                                <select wire:model="form.payment_method" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all bg-white">
                                    <option value="cash">💵 Espèces</option>
                                    <option value="card">💳 Carte bancaire</option>
                                    <option value="transfer">🏦 Virement</option>
                                    <option value="cheque">📝 Chèque</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    <span class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        Statut du paiement
                                    </span>
                                </label>
                                <select wire:model.live="form.payment_status" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all bg-white">
                                    <option value="pending">⏳ En attente</option>
                                    <option value="paid">✅ Payé</option>
                                    <option value="partial">🔄 Partiel</option>
                                </select>
                            </div>
                        </div>

                        {{-- Montant payé (conditionnel) --}}
                        @if($form->payment_status === 'partial' || $form->payment_status === 'paid')
                            <div class="p-4 bg-amber-50 rounded-xl border border-amber-200">
                                <label class="block text-sm font-semibold text-amber-800 mb-2">
                                    💰 Montant {{ $form->payment_status === 'paid' ? 'total payé' : 'déjà versé' }}
                                </label>
                                <input type="number" wire:model.live="form.paid_amount" step="0.01" min="0"
                                       placeholder="{{ $form->payment_status === 'paid' ? 'Montant total' : 'Montant partiel' }}"
                                       class="w-full px-4 py-3 border-2 border-amber-300 rounded-xl focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 transition-all bg-white">
                            </div>
                        @endif

                        {{-- Notes (toggle) --}}
                        <div>
                            <button type="button" @click="showNotes = !showNotes" 
                                    class="flex items-center gap-2 text-sm font-medium text-gray-600 hover:text-indigo-600 transition-colors">
                                <svg class="w-4 h-4 transition-transform" :class="showNotes && 'rotate-90'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                                Ajouter une note (optionnel)
                            </button>
                            <div x-show="showNotes" x-collapse class="mt-3">
                                <textarea wire:model="form.notes" rows="3" placeholder="Notes additionnelles..."
                                          class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all resize-none"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ===== COLONNE DROITE - Résumé sticky ===== --}}
            <div class="xl:col-span-1">
                <div class="sticky top-6 space-y-4">
                    {{-- Carte Résumé --}}
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100">
                            <h3 class="font-bold text-lg flex items-center gap-2 text-gray-900">
                                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                </svg>
                                Résumé
                            </h3>
                        </div>
                        
                        <div class="p-6 space-y-4">
                            {{-- Sous-total --}}
                            <div class="flex justify-between items-center text-gray-600">
                                <span>Sous-total</span>
                                <span class="font-semibold text-gray-900">{{ number_format($subtotal, 0, ',', ' ') }} {{ current_currency() }}</span>
                            </div>
                            
                            {{-- Remise --}}
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1.5">Remise globale</label>
                                <input type="number" wire:model.live="form.discount" step="0.01" min="0" value="0" placeholder="0"
                                       class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-gray-900 placeholder-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all text-right">
                            </div>
                            
                            {{-- Taxe --}}
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1.5">Taxe</label>
                                <input type="number" wire:model.live="form.tax" step="0.01" min="0" value="0" placeholder="0"
                                       class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-gray-900 placeholder-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all text-right">
                            </div>

                            {{-- Affichage remise/taxe --}}
                            @if(floatval($form->discount) > 0)
                                <div class="flex justify-between items-center text-red-600">
                                    <span>- Remise</span>
                                    <span>{{ number_format($form->discount, 0, ',', ' ') }} {{ current_currency() }}</span>
                                </div>
                            @endif
                            @if(floatval($form->tax) > 0)
                                <div class="flex justify-between items-center text-gray-600">
                                    <span>+ Taxe</span>
                                    <span>{{ number_format($form->tax, 0, ',', ' ') }} {{ current_currency() }}</span>
                                </div>
                            @endif
                            
                            {{-- Séparateur --}}
                            <div class="border-t border-gray-200 pt-4">
                                <div class="flex justify-between items-center">
                                    <span class="text-lg font-bold text-gray-900">Total</span>
                                    <span class="text-2xl font-black text-indigo-600">{{ number_format($total, 0, ',', ' ') }} <span class="text-base font-normal">{{ current_currency() }}</span></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Infos rapides --}}
                    <div class="bg-white rounded-xl border border-gray-200 p-4">
                        <div class="grid grid-cols-2 gap-4 text-center">
                            <div class="p-3 bg-gray-50 rounded-lg">
                                <div class="text-2xl font-bold text-gray-900">{{ count($items) }}</div>
                                <div class="text-xs text-gray-500">Articles</div>
                            </div>
                            <div class="p-3 bg-gray-50 rounded-lg">
                                <div class="text-2xl font-bold text-gray-900">{{ array_sum(array_column($items, 'quantity')) }}</div>
                                <div class="text-xs text-gray-500">Unités</div>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Bouton de validation --}}
                    <button type="submit" 
                            wire:loading.attr="disabled"
                            wire:loading.class="opacity-75 cursor-wait"
                            class="w-full px-6 py-4 bg-gradient-to-r from-indigo-500 to-indigo-600 hover:from-indigo-600 hover:to-indigo-700 disabled:from-gray-400 disabled:to-gray-500 text-white font-bold text-lg rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 flex items-center justify-center gap-3 group">
                        <span wire:loading.remove wire:target="save" class="flex items-center gap-3">
                            <svg class="w-6 h-6 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Valider la vente
                        </span>
                        <span wire:loading wire:target="save" class="flex items-center gap-3">
                            <svg class="w-6 h-6 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            Enregistrement...
                        </span>
                    </button>
                    
                    {{-- Info sécurité --}}
                    <p class="text-xs text-gray-400 text-center flex items-center justify-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        Transaction sécurisée
                    </p>
                </div>
            </div>
        </div>
    </form>
</div>
