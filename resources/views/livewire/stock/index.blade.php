<div>
    <x-slot name="header">
        <x-breadcrumb :items="[
            ['label' => 'Accueil', 'url' => route('dashboard')],
            ['label' => 'Stock']
        ]" />
    </x-slot>

    <!-- Page Header with Actions -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Gestion du Stock</h1>
            <p class="text-gray-500 mt-1">Gérez les entrées et sorties de stock</p>
        </div>
        <div class="flex space-x-3">
            <x-form.button wire:click="openAddModal" variant="success" icon="plus">
                Ajouter Stock
            </x-form.button>
            <x-form.button wire:click="openRemoveModal" variant="danger" icon="minus">
                Retirer Stock
            </x-form.button>
            <x-form.button wire:click="openAdjustModal" variant="warning" icon="adjustments">
                Ajuster Stock
            </x-form.button>
        </div>
    </div>

    <!-- Flash Messages -->
    <x-form.alert type="success" :message="session('message')" class="mb-6" />
    <x-form.alert type="error" :message="session('error')" class="mb-6" />

    <!-- Search and Filters -->
    <div class="mb-6 bg-white rounded-xl shadow-sm border border-gray-200 p-4">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            <!-- Search -->
            <x-form.search-input
                wire:model.live.debounce.300ms="search"
                wireModel="search"
                placeholder="Rechercher produit ou référence..."
            />

            <!-- Type Filter -->
            <x-form.select wire:model.live="type">
                <option value="">Tous les types</option>
                <option value="in">Entrées</option>
                <option value="out">Sorties</option>
            </x-form.select>

            <!-- Movement Type Filter -->
            <x-form.select wire:model.live="movementType">
                <option value="">Tous les mouvements</option>
                <option value="purchase">Achat</option>
                <option value="sale">Vente</option>
                <option value="adjustment">Ajustement</option>
                <option value="transfer">Transfert</option>
                <option value="return">Retour</option>
            </x-form.select>

            <!-- Per Page Selector -->
            <x-form.select wire:model.live="perPage">
                <option value="10">10 par page</option>
                <option value="25">25 par page</option>
                <option value="50">50 par page</option>
                <option value="100">100 par page</option>
            </x-form.select>

            <!-- View Mode Toggle -->
            <x-form.button wire:click="toggleViewMode" variant="secondary" class="w-full justify-center">
                @if($viewMode === 'grouped')
                    <svg class="w-5 h-5 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                    </svg>
                    Vue détaillée
                @else
                    <svg class="w-5 h-5 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    Vue groupée
                @endif
            </x-form.button>
        </div>

        <!-- Date Range -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
            <div>
                <x-form.label for="dateFrom">Date début</x-form.label>
                <x-form.input type="date" wire:model.live="dateFrom" id="dateFrom" />
            </div>
            <div>
                <x-form.label for="dateTo">Date fin</x-form.label>
                <x-form.input type="date" wire:model.live="dateTo" id="dateTo" />
            </div>
            <div>
                <x-form.label>Exports</x-form.label>
                <!-- Export PDF Dropdown -->
                <x-dropdown align="right" width="56">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center justify-center w-full px-4 py-2.5 border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 font-medium rounded-lg shadow-sm transition duration-150">
                            <svg class="w-5 h-5 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Exports PDF
                            <svg class="w-4 h-4 ml-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                    </x-slot>

                    <x-dropdown-item href="{{ route('reports.stock-movements', ['date_from' => $dateFrom, 'date_to' => $dateTo, 'type' => $type, 'movement_type' => $movementType]) }}" icon="clipboard-list" iconColor="text-blue-500">
                        Mouvements de stock
                    </x-dropdown-item>
                    <x-dropdown-item href="{{ route('reports.stock') }}" icon="cube" iconColor="text-green-500">
                        État du stock
                    </x-dropdown-item>
                    <x-dropdown-item href="{{ route('reports.inventory') }}" icon="calculator" iconColor="text-purple-500">
                        Inventaire valorisé
                    </x-dropdown-item>
                    <x-dropdown-item href="{{ route('reports.stock-alerts') }}" icon="exclamation-triangle" iconColor="text-red-500">
                        Alertes de stock
                    </x-dropdown-item>
                </x-dropdown>
            </div>
        </div>
    </div>

    <!-- Table - Grouped View -->
    @if($viewMode === 'grouped')
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <x-table.table>
            <x-table.head>
                <tr>
                    <x-table.header>Produit</x-table.header>
                    <x-table.header align="center">Nb Mouvements</x-table.header>
                    <x-table.header align="center">Total Entrées</x-table.header>
                    <x-table.header align="center">Total Sorties</x-table.header>
                    <x-table.header align="center">Variation Nette</x-table.header>
                    <x-table.header>Dernier Mouvement</x-table.header>
                    <x-table.header align="center">Actions</x-table.header>
                </tr>
            </x-table.head>

            <x-table.body>
                @forelse ($groupedMovements as $group)
                    <x-table.row wire:key="group-{{ $group->product_variant_id }}">
                        <x-table.cell>
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10 bg-indigo-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <div class="text-sm font-medium text-gray-900">{{ $group->productVariant->product->name }}</div>
                                    <div class="text-xs text-gray-500">
                                        SKU: {{ $group->productVariant->sku }}
                                        @if($group->productVariant->size || $group->productVariant->color)
                                            - {{ $group->productVariant->size }} {{ $group->productVariant->color }}
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </x-table.cell>
                        <x-table.cell align="center">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-800">
                                {{ $group->movement_count }}
                            </span>
                        </x-table.cell>
                        <x-table.cell align="center">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12" />
                                </svg>
                                +{{ $group->total_in }}
                            </span>
                        </x-table.cell>
                        <x-table.cell align="center">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6" />
                                </svg>
                                -{{ $group->total_out }}
                            </span>
                        </x-table.cell>
                        <x-table.cell align="center">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $group->net_change >= 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-orange-100 text-orange-800' }}">
                                {{ $group->net_change >= 0 ? '+' : '' }}{{ $group->net_change }}
                            </span>
                        </x-table.cell>
                        <x-table.cell>
                            <span class="text-sm text-gray-900">{{ \Carbon\Carbon::parse($group->last_date)->format('d/m/Y') }}</span>
                        </x-table.cell>
                        <x-table.cell align="center">
                            <x-actions-dropdown>
                                <x-dropdown-item wireClick="openDetailsModal({{ $group->product_variant_id }})" icon="eye">
                                    Voir les détails
                                </x-dropdown-item>
                                <x-dropdown-item href="{{ route('stock.history', $group->product_variant_id) }}" wireNavigate icon="clock">
                                    Historique complet
                                </x-dropdown-item>
                            </x-actions-dropdown>
                        </x-table.cell>
                    </x-table.row>
                @empty
                    <x-table.empty-state
                        colspan="7"
                        title="Aucun mouvement de stock trouvé"
                        description="Commencez par ajouter des mouvements de stock."
                    >
                        <x-slot name="action">
                            <x-form.button wire:click="openAddModal" size="sm">
                                <svg class="w-4 h-4 mr-2 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Ajouter Stock
                            </x-form.button>
                        </x-slot>
                    </x-table.empty-state>
                @endforelse
            </x-table.body>
        </x-table.table>

        <!-- Pagination -->
        @if($groupedMovements->hasPages())
            <div class="bg-gradient-to-r from-gray-50 to-white px-6 py-4 border-t border-gray-200">
                {{ $groupedMovements->links() }}
            </div>
        @endif
    </div>
    @else
    <!-- Table - Detailed View -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <x-table.table>
            <x-table.head>
                <tr>
                    <x-table.header>Date</x-table.header>
                    <x-table.header>Produit</x-table.header>
                    <x-table.header>Type</x-table.header>
                    <x-table.header>Mouvement</x-table.header>
                    <x-table.header>Quantité</x-table.header>
                    <x-table.header align="center">Actions</x-table.header>
                </tr>
            </x-table.head>

            <x-table.body>
                @forelse ($movements as $movement)
                    <x-table.row wire:key="movement-{{ $movement->id }}">
                        <x-table.cell>
                            <span class="text-sm text-gray-900 font-medium">{{ $movement->date->format('d/m/Y') }}</span>
                            <span class="text-xs text-gray-500 block">{{ $movement->created_at->format('H:i') }}</span>
                        </x-table.cell>
                        <x-table.cell>
                            <div class="flex items-center">
                                <div class="ml-0">
                                    <div class="text-sm font-medium text-gray-900">{{ $movement->productVariant->product->name }}</div>
                                    <div class="text-xs text-gray-500">
                                        SKU: {{ $movement->productVariant->sku }}
                                        @if($movement->productVariant->size || $movement->productVariant->color)
                                            - {{ $movement->productVariant->size }} {{ $movement->productVariant->color }}
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </x-table.cell>
                        <x-table.cell>
                            <div>
                                @if($movement->type === 'in')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12" />
                                        </svg>
                                        Entrée
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6" />
                                        </svg>
                                        Sortie
                                    </span>
                                @endif
                                <div class="text-xs text-gray-500 mt-1">{{ $movement->reference ?: '—' }}</div>
                            </div>
                        </x-table.cell>
                        <x-table.cell>
                            <div>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    {{ ucfirst(str_replace('_', ' ', $movement->movement_type)) }}
                                </span>
                                <div class="text-xs text-gray-500 mt-1">{{ $movement->user->name }}</div>
                            </div>
                        </x-table.cell>
                        <x-table.cell>
                            <span class="text-sm font-semibold {{ $movement->type === 'in' ? 'text-green-600' : 'text-red-600' }}">
                                {{ $movement->type === 'in' ? '+' : '-' }}{{ $movement->quantity }}
                            </span>
                        </x-table.cell>
                        <x-table.cell align="center">
                            <x-actions-dropdown>
                                @if($movement->movement_type !== 'sale')
                                    <x-dropdown-item wireClick="openEditModal({{ $movement->id }})" icon="edit">
                                        Modifier
                                    </x-dropdown-item>
                                @endif
                                <x-dropdown-item href="{{ route('stock.history', $movement->product_variant_id) }}" wireNavigate icon="clock">
                                    Historique complet
                                </x-dropdown-item>
                            </x-actions-dropdown>
                        </x-table.cell>
                    </x-table.row>
                @empty
                    <x-table.empty-state
                        colspan="8"
                        title="Aucun mouvement de stock trouvé"
                        description="Commencez par ajouter des mouvements de stock."
                    >
                        <x-slot name="action">
                            <x-form.button wire:click="openAddModal" size="sm">
                                <svg class="w-4 h-4 mr-2 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Ajouter Stock
                            </x-form.button>
                        </x-slot>
                    </x-table.empty-state>
                @endforelse
            </x-table.body>
        </x-table.table>

        <!-- Pagination -->
        @if($movements->hasPages())
            <div class="bg-gradient-to-r from-gray-50 to-white px-6 py-4 border-t border-gray-200">
                {{ $movements->links() }}
            </div>
        @endif
    </div>
    @endif

    <!-- Add Stock Modal -->
    @include('livewire.stock.modals.add-stock-new')

    <!-- Remove Stock Modal -->
    @include('livewire.stock.modals.remove-stock-new')

    <!-- Adjust Stock Modal -->
    @include('livewire.stock.modals.adjust-stock-new')

    <!-- Edit Movement Modal -->
    @include('livewire.stock.modals.edit-movement')

    <!-- Product Movements Details Modal -->
    @if($showDetailsModal)
    <div x-show="$wire.showDetailsModal"
         x-cloak
         x-on:keydown.escape.window="$wire.closeDetailsModal()"
         x-init="$watch('$wire.showDetailsModal', value => { document.body.style.overflow = value ? 'hidden' : '' })"
         class="fixed inset-0 z-50 overflow-hidden"
         aria-labelledby="modal-title"
         role="dialog"
         aria-modal="true">

        <!-- Backdrop -->
        <div x-show="$wire.showDetailsModal"
             x-on:click="$wire.closeDetailsModal()"
             x-transition.opacity.duration.150ms
             class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm">
        </div>

        <!-- Modal Container -->
        <div class="fixed inset-0 flex items-center justify-center p-4 pointer-events-none">
            <div x-show="$wire.showDetailsModal"
                 x-on:click.stop
                 x-transition:enter="ease-out duration-150"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="ease-in duration-100"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="relative bg-white rounded-2xl shadow-2xl w-full sm:max-w-4xl max-h-[90vh] flex flex-col pointer-events-auto overflow-hidden">

                <!-- Modal Header -->
                <div class="flex-shrink-0 bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4 flex items-center justify-between">
                    <div>
                        <h3 class="text-xl font-semibold text-white">
                            Détails des mouvements - {{ $selectedProductName }}
                        </h3>
                        @php
                            $currentStore = effective_store_id() ? \App\Models\Store::find(effective_store_id()) : null;
                        @endphp
                        @if($currentStore)
                            <p class="text-sm text-white/80 mt-1">
                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                                {{ $currentStore->name }}
                            </p>
                        @else
                            <p class="text-sm text-white/80 mt-1">
                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Tous les magasins
                            </p>
                        @endif
                    </div>
                    <button type="button" wire:click="closeDetailsModal"
                        class="text-white/80 hover:text-white transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Summary Stats -->
                @php
                    $totalIn = collect($selectedProductMovements)->where('type', 'in')->sum('quantity');
                    $totalOut = collect($selectedProductMovements)->where('type', 'out')->sum('quantity');
                    $netChange = $totalIn - $totalOut;
                @endphp
                <div class="flex-shrink-0 bg-gray-50 px-6 py-4 border-b border-gray-200">
                    <div class="grid grid-cols-4 gap-4">
                        <div class="text-center">
                            <p class="text-sm text-gray-500">Total Mouvements</p>
                            <p class="text-2xl font-bold text-gray-900">{{ count($selectedProductMovements) }}</p>
                        </div>
                        <div class="text-center">
                            <p class="text-sm text-gray-500">Total Entrées</p>
                            <p class="text-2xl font-bold text-green-600">+{{ $totalIn }}</p>
                        </div>
                        <div class="text-center">
                            <p class="text-sm text-gray-500">Total Sorties</p>
                            <p class="text-2xl font-bold text-red-600">-{{ $totalOut }}</p>
                        </div>
                        <div class="text-center">
                            <p class="text-sm text-gray-500">Variation Nette</p>
                            <p class="text-2xl font-bold {{ $netChange >= 0 ? 'text-emerald-600' : 'text-orange-600' }}">
                                {{ $netChange >= 0 ? '+' : '' }}{{ $netChange }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Movements List - Scrollable -->
                <div class="flex-1 overflow-y-auto min-h-0">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50 sticky top-0 z-10">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                @if(!$currentStore)
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Magasin</th>
                                @endif
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mouvement</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Quantité</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Référence</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Utilisateur</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($selectedProductMovements as $movement)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="text-sm text-gray-900">{{ \Carbon\Carbon::parse($movement['date'])->format('d/m/Y') }}</span>
                                        <span class="text-xs text-gray-500 block">{{ \Carbon\Carbon::parse($movement['created_at'])->format('H:i') }}</span>
                                    </td>
                                    @if(!$currentStore)
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span class="text-sm text-gray-900">{{ $movement['store']['name'] ?? '—' }}</span>
                                        </td>
                                    @endif
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        @if($movement['type'] === 'in')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12" />
                                                </svg>
                                                Entrée
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6" />
                                                </svg>
                                                Sortie
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            {{ ucfirst(str_replace('_', ' ', $movement['movement_type'])) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-center">
                                        <span class="text-sm font-semibold {{ $movement['type'] === 'in' ? 'text-green-600' : 'text-red-600' }}">
                                            {{ $movement['type'] === 'in' ? '+' : '-' }}{{ $movement['quantity'] }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="text-sm text-gray-500">{{ $movement['reference'] ?: '—' }}</span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="text-sm text-gray-900">{{ $movement['user']['name'] ?? '—' }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Footer -->
                <div class="flex-shrink-0 bg-gray-50 px-6 py-4 flex items-center justify-end space-x-3 border-t border-gray-200">
                    <button type="button" wire:click="closeDetailsModal"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                        Fermer
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
