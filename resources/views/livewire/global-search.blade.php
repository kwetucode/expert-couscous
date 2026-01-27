<div
    x-data="{
        open: false,
        selectedIndex: 0,
        init() {
            // Écouter l'événement Livewire pour ouvrir la recherche
            Livewire.on('openSearch', () => {
                this.open = true;
                this.$nextTick(() => {
                    this.$refs.searchInput?.focus();
                });
            });

            // Raccourci clavier Ctrl+K ou Cmd+K
            document.addEventListener('keydown', (e) => {
                if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                    e.preventDefault();
                    this.open = true;
                    this.$nextTick(() => {
                        this.$refs.searchInput?.focus();
                    });
                }
                // Touche / pour ouvrir
                if (e.key === '/' && !this.open && document.activeElement.tagName !== 'INPUT') {
                    e.preventDefault();
                    this.open = true;
                    this.$nextTick(() => {
                        this.$refs.searchInput?.focus();
                    });
                }
                // ESC pour fermer
                if (e.key === 'Escape' && this.open) {
                    this.open = false;
                    $wire.clearSearch();
                }
            });
        }
    }"
    x-show="open"
    @keydown.escape.window="open = false; $wire.clearSearch()"
    class="fixed inset-0 z-50 overflow-y-auto"
    style="display: none;"
>
    <!-- Backdrop -->
    <div
        class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity"
        @click="open = false; $wire.clearSearch()"
        x-show="open"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    ></div>

    <!-- Modal -->
    <div class="flex min-h-screen items-start justify-center p-4 pt-[10vh]">
        <div
            class="relative w-full max-w-2xl bg-white rounded-xl shadow-2xl ring-1 ring-black/5"
            x-show="open"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            @click.away="open = false; $wire.clearSearch()"
        >
            <!-- Search Input -->
            <div class="flex items-center gap-3 px-4 py-3 border-b border-gray-200">
                <!-- Search Icon / Loading Spinner -->
                <div class="w-5 h-5">
                    <svg wire:loading.remove wire:target="query,setCategory" class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <svg wire:loading wire:target="query,setCategory" class="w-5 h-5 text-indigo-500 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
                <input
                    x-ref="searchInput"
                    wire:model.live.debounce.300ms="query"
                    type="text"
                    placeholder="Rechercher produits, clients, ventes..."
                    class="flex-1 outline-none border-0 focus:ring-0 text-gray-900 placeholder-gray-400"
                    autocomplete="off"
                >
                <!-- Clear button -->
                @if(strlen($query) > 0)
                    <button
                        wire:click="clearSearch"
                        type="button"
                        class="p-1 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-full transition-colors"
                        title="Effacer la recherche"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                @endif
                <!-- Loading text indicator -->
                <span wire:loading wire:target="query,setCategory" class="text-xs text-indigo-500 font-medium">
                    Recherche...
                </span>
                <kbd wire:loading.remove wire:target="query,setCategory" class="hidden sm:inline-flex items-center gap-1 px-2 py-1 text-xs font-medium text-gray-500 bg-gray-100 border border-gray-200 rounded">
                    ESC
                </kbd>
            </div>

            <!-- Categories -->
            @if(strlen($query) >= 2)
                <div class="flex gap-2 px-4 py-3 border-b border-gray-100 overflow-x-auto">
                    <button
                        wire:click="setCategory('all')"
                        class="px-3 py-1.5 text-sm font-medium rounded-lg transition-colors whitespace-nowrap
                            {{ $selectedCategory === 'all' ? 'bg-indigo-100 text-indigo-700' : 'text-gray-600 hover:bg-gray-100' }}">
                        Tout
                    </button>
                    <button
                        wire:click="setCategory('products')"
                        class="px-3 py-1.5 text-sm font-medium rounded-lg transition-colors whitespace-nowrap
                            {{ $selectedCategory === 'products' ? 'bg-indigo-100 text-indigo-700' : 'text-gray-600 hover:bg-gray-100' }}">
                        📦 Produits
                    </button>
                    <button
                        wire:click="setCategory('clients')"
                        class="px-3 py-1.5 text-sm font-medium rounded-lg transition-colors whitespace-nowrap
                            {{ $selectedCategory === 'clients' ? 'bg-indigo-100 text-indigo-700' : 'text-gray-600 hover:bg-gray-100' }}">
                        👤 Clients
                    </button>
                    <button
                        wire:click="setCategory('suppliers')"
                        class="px-3 py-1.5 text-sm font-medium rounded-lg transition-colors whitespace-nowrap
                            {{ $selectedCategory === 'suppliers' ? 'bg-indigo-100 text-indigo-700' : 'text-gray-600 hover:bg-gray-100' }}">
                        🏢 Fournisseurs
                    </button>
                    <button
                        wire:click="setCategory('sales')"
                        class="px-3 py-1.5 text-sm font-medium rounded-lg transition-colors whitespace-nowrap
                            {{ $selectedCategory === 'sales' ? 'bg-indigo-100 text-indigo-700' : 'text-gray-600 hover:bg-gray-100' }}">
                        💰 Ventes
                    </button>
                </div>
            @endif

            <!-- Results -->
            <div class="max-h-[60vh] overflow-y-auto">
                @if(strlen($query) >= 2)
                    @if($this->hasResults)
                        <!-- Products Results -->
                        @if(isset($this->results['products']) && $this->results['products']->isNotEmpty())
                            <div class="px-4 py-3">
                                <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
                                    📦 Produits ({{ $this->results['products']->count() }})
                                </h3>
                                <div class="space-y-1">
                                    @foreach($this->results['products'] as $product)
                                        <a
                                            href="{{ route('products.index', ['search' => $product->name]) }}"
                                            wire:navigate
                                            class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 transition-colors group"
                                            @click="open = false"
                                        >
                                            <div class="flex-shrink-0 w-10 h-10 rounded-lg overflow-hidden {{ $product->image ? '' : 'bg-indigo-100 flex items-center justify-center' }}">
                                                @if($product->image)
                                                    <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
                                                @else
                                                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                                    </svg>
                                                @endif
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-medium text-gray-900 group-hover:text-indigo-600">
                                                    {{ $product->name }}
                                                </p>
                                                <div class="flex items-center gap-2 text-xs text-gray-500">
                                                    @if($product->reference)
                                                        <span>Réf: {{ $product->reference }}</span>
                                                        <span>•</span>
                                                    @endif
                                                    <span>Stock: {{ $product->variants->sum('stock_quantity') }}</span>
                                                </div>
                                            </div>
                                            <svg class="w-4 h-4 text-gray-400 group-hover:text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                            </svg>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <!-- Clients Results -->
                        @if(isset($this->results['clients']) && $this->results['clients']->isNotEmpty())
                            <div class="px-4 py-3 border-t border-gray-100">
                                <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
                                    👤 Clients ({{ $this->results['clients']->count() }})
                                </h3>
                                <div class="space-y-1">
                                    @foreach($this->results['clients'] as $client)
                                        <a
                                            href="{{ route('clients.index', ['search' => $client->name]) }}"
                                            wire:navigate
                                            class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 transition-colors group"
                                            @click="open = false"
                                        >
                                            <div class="flex-shrink-0 w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                                </svg>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-medium text-gray-900 group-hover:text-green-600">
                                                    {{ $client->name }}
                                                </p>
                                                <div class="flex items-center gap-2 text-xs text-gray-500">
                                                    @if($client->phone)
                                                        <span>{{ $client->phone }}</span>
                                                    @endif
                                                    @if($client->email)
                                                        <span>•</span>
                                                        <span>{{ $client->email }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                            <svg class="w-4 h-4 text-gray-400 group-hover:text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                            </svg>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <!-- Suppliers Results -->
                        @if(isset($this->results['suppliers']) && $this->results['suppliers']->isNotEmpty())
                            <div class="px-4 py-3 border-t border-gray-100">
                                <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
                                    🏢 Fournisseurs ({{ $this->results['suppliers']->count() }})
                                </h3>
                                <div class="space-y-1">
                                    @foreach($this->results['suppliers'] as $supplier)
                                        <a
                                            href="{{ route('suppliers.index', ['search' => $supplier->name]) }}"
                                            wire:navigate
                                            class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 transition-colors group"
                                            @click="open = false"
                                        >
                                            <div class="flex-shrink-0 w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                                                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                                </svg>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-medium text-gray-900 group-hover:text-purple-600">
                                                    {{ $supplier->name }}
                                                </p>
                                                <div class="flex items-center gap-2 text-xs text-gray-500">
                                                    @if($supplier->phone)
                                                        <span>{{ $supplier->phone }}</span>
                                                    @endif
                                                    @if($supplier->email)
                                                        <span>•</span>
                                                        <span>{{ $supplier->email }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                            <svg class="w-4 h-4 text-gray-400 group-hover:text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                            </svg>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <!-- Sales Results -->
                        @if(isset($this->results['sales']) && $this->results['sales']->isNotEmpty())
                            <div class="px-4 py-3 border-t border-gray-100">
                                <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
                                    💰 Ventes ({{ $this->results['sales']->count() }})
                                </h3>
                                <div class="space-y-1">
                                    @foreach($this->results['sales'] as $sale)
                                        <a
                                            href="{{ route('sales.edit', $sale->id) }}"
                                            wire:navigate
                                            class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 transition-colors group"
                                            @click="open = false"
                                        >
                                            <div class="flex-shrink-0 w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center">
                                                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z"/>
                                                </svg>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-medium text-gray-900 group-hover:text-amber-600">
                                                    {{ $sale->sale_number }}
                                                </p>
                                                <div class="flex items-center gap-2 text-xs text-gray-500">
                                                    <span>{{ $sale->client?->name ?? 'Client inconnu' }}</span>
                                                    <span>•</span>
                                                    <span>{{ number_format($sale->total, 0, ',', ' ') }} {{ current_currency() }}</span>
                                                </div>
                                            </div>
                                            <svg class="w-4 h-4 text-gray-400 group-hover:text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                            </svg>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @else
                        <!-- No Results -->
                        <div class="px-4 py-12 text-center">
                            <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900 mb-1">Aucun résultat</h3>
                            <p class="text-sm text-gray-500">Essayez avec d'autres mots-clés</p>
                        </div>
                    @endif
                @else
                    <!-- Empty State / Recent Searches -->
                    <div class="px-4 py-8">
                        @if(count($recentSearches) > 0)
                            <div class="flex items-center justify-between mb-3">
                                <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    ⏱️ Recherches récentes
                                </h3>
                                <button
                                    wire:click="clearRecent"
                                    class="text-xs text-gray-400 hover:text-gray-600 transition-colors"
                                >
                                    Effacer
                                </button>
                            </div>
                            <div class="space-y-1">
                                @foreach($recentSearches as $recent)
                                    <button
                                        wire:click="$set('query', '{{ $recent }}')"
                                        class="w-full flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 transition-colors text-left"
                                    >
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <span class="text-sm text-gray-700">{{ $recent }}</span>
                                    </button>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-4">
                                <div class="w-12 h-12 mx-auto mb-3 bg-gray-100 rounded-full flex items-center justify-center">
                                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                    </svg>
                                </div>
                                <p class="text-sm text-gray-500">Commencez à taper pour rechercher...</p>
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            <!-- Footer with shortcuts -->
            <div class="px-4 py-3 border-t border-gray-100 bg-gray-50">
                <div class="flex items-center justify-between text-xs text-gray-500">
                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-1">
                            <kbd class="px-1.5 py-0.5 bg-white border border-gray-200 rounded text-xs">Ctrl</kbd>
                            <span>+</span>
                            <kbd class="px-1.5 py-0.5 bg-white border border-gray-200 rounded text-xs">K</kbd>
                            <span class="ml-1">pour ouvrir</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <kbd class="px-1.5 py-0.5 bg-white border border-gray-200 rounded text-xs">ESC</kbd>
                            <span class="ml-1">pour fermer</span>
                        </div>
                    </div>
                    <span>{{ $this->totalResults }} résultat(s)</span>
                </div>
            </div>
        </div>
    </div>
</div>
