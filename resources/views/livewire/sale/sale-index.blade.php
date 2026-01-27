<div x-data="{ showDeleteModal: false, saleToDelete: null, saleNumber: '', showCompleteModal: false, saleToComplete: null, completeNumber: '', showRestoreModal: false, saleToRestore: null, restoreNumber: '', showForceDeleteModal: false, saleToForceDelete: null, forceDeleteNumber: '', showExportMenu: false, showModal: false, isEditing: false }"
    @open-email-modal.window="showModal = true"
    @close-email-modal.window="showModal = false"
>
<x-slot name="header">
    <x-breadcrumb :items="[
        ['label' => 'Accueil', 'url' => route('dashboard')],
        ['label' => 'Ventes']
    ]" />
</x-slot>

<div class="space-y-6">
    <div class="flex items-center justify-between mt-2">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Gestion des Ventes</h1>
            <p class="text-gray-500 mt-1">Gérez vos ventes et transactions</p>
        </div>
        <div class="flex items-center space-x-3">
            <x-form.button href="{{ route('sales.create') }}" wire:navigate icon="plus">
                Nouvelle Vente
            </x-form.button>
        </div>
    </div>
    <!-- Statistics Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <x-kpi-card
            title="Ventes Complétées"
            :value="$stats['total_sales']"
            color="green">
            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </x-kpi-card>
        <x-kpi-card
            title="Montant Total"
            :value="format_currency($stats['total_amount'])"
            color="indigo">
            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </x-kpi-card>
        <x-kpi-card
            title="Ventes en Attente"
            :value="$stats['pending_sales']"
            color="orange">
            <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </x-kpi-card>
        <x-kpi-card
            title="Montant en Attente"
            :value="format_currency($stats['pending_amount'])"
            color="purple">
            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
        </x-kpi-card>
    </div>

    <!-- Filters Card -->
    <x-card>
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-900">Filtres</h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
            <!-- Search -->
            <div class="md:col-span-2">
                <x-form.search-input
                    wire:model.live.debounce.300ms="search"
                    wireModel="search"
                    placeholder="Rechercher par numéro ou client..."
                />
            </div>

            <!-- Client Filter -->
            <div>
                <x-form.select wire:model.live="clientFilter">
                    <option value="">Tous les clients</option>
                    @foreach ($clients as $client)
                        <option value="{{ $client->id }}">{{ $client->name }}</option>
                    @endforeach
                </x-form.select>
            </div>

            <!-- Status Filter -->
            <div>
                <x-form.select wire:model.live="statusFilter">
                    <option value="">Tous les statuts</option>
                    <option value="pending">En attente</option>
                    <option value="completed">Complétée</option>
                    <option value="cancelled">Annulée</option>
                </x-form.select>
            </div>

            <!-- Payment Status Filter -->
            <div>
                <x-form.select wire:model.live="paymentStatusFilter">
                    <option value="">Tous les paiements</option>
                    <option value="pending">En attente</option>
                    <option value="paid">Payé</option>
                    <option value="partial">Partiel</option>
                    <option value="refunded">Remboursé</option>
                </x-form.select>
            </div>

            <!-- Export Dropdown -->
            <div class="relative" x-data="{ open: false }">
                <button
                    @click="open = !open"
                    @click.outside="open = false"
                    type="button"
                    class="inline-flex items-center justify-center w-full px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                >
                    <svg class="w-5 h-5 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Exporter
                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <div
                    x-show="open"
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="transform opacity-0 scale-95"
                    x-transition:enter-end="transform opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="transform opacity-100 scale-100"
                    x-transition:leave-end="transform opacity-0 scale-95"
                    class="absolute right-0 z-10 mt-2 w-48 origin-top-right rounded-lg bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
                    style="display: none;"
                >
                    <div class="py-1">
                        <button
                            wire:click="exportExcel"
                            @click="open = false"
                            class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                        >
                            <svg class="w-5 h-5 mr-3 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Export Excel
                        </button>
                        <button
                            wire:click="exportPdf"
                            @click="open = false"
                            class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                        >
                            <svg class="w-5 h-5 mr-3 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                            Export PDF
                        </button>
                        <hr class="my-1">
                        <button
                            wire:click="openEmailModal"
                            @click="open = false"
                            class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                        >
                            <svg class="w-5 h-5 mr-3 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            Envoyer par email
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Date Range Filter -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-4">
            <!-- Period Filter -->
            <div>
                <x-form.form-group label="Période" for="periodFilter">
                    <x-form.select wire:model.live="periodFilter" id="periodFilter">
                        <option value="today">Aujourd'hui</option>
                        <option value="yesterday">Hier</option>
                        <option value="this_week">Cette semaine</option>
                        <option value="last_week">Semaine dernière</option>
                        <option value="this_month">Ce mois</option>
                        <option value="last_month">Mois dernier</option>
                        <option value="last_3_months">3 derniers mois</option>
                        <option value="last_6_months">6 derniers mois</option>
                        <option value="this_year">Cette année</option>
                        <option value="last_year">Année dernière</option>
                        <option value="all">Tout</option>
                        <option value="custom">Personnalisé</option>
                    </x-form.select>
                </x-form.form-group>
            </div>

            @if($periodFilter === 'custom')
            <!-- Date From (only in custom mode) -->
            <x-form.form-group label="Date de début" for="dateFrom">
                <x-form.input
                    wire:model.live="dateFrom"
                    type="date"
                    id="dateFrom"
                />
            </x-form.form-group>

            <!-- Date To (only in custom mode) -->
            <x-form.form-group label="Date de fin" for="dateTo">
                <x-form.input
                    wire:model.live="dateTo"
                    type="date"
                    id="dateTo"
                />
            </x-form.form-group>
            @endif

            <!-- Period Info -->
            <div class="flex items-end {{ $periodFilter === 'custom' ? '' : 'md:col-span-3' }}">
                <div class="w-full px-3 py-2 bg-indigo-50 border border-indigo-200 rounded-lg text-sm text-indigo-700">
                    <svg class="inline-block w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    @if($dateFrom && $dateTo)
                        {{ \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($dateTo)->format('d/m/Y') }}
                    @elseif($dateFrom)
                        À partir du {{ \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }}
                    @elseif($dateTo)
                        Jusqu'au {{ \Carbon\Carbon::parse($dateTo)->format('d/m/Y') }}
                    @else
                        Toutes les dates
                    @endif
                </div>
            </div>
        </div>
    </x-card>

    <!-- Sales List -->
    <x-card>
        <x-slot:header>
            <x-card-title title="Liste des Ventes ({{ $sales->total() }})">
                <x-slot:action>
                    <x-form.select wire:model.live="perPage" class="text-sm">
                        <option value="15">15 par page</option>
                        <option value="25">25 par page</option>
                        <option value="50">50 par page</option>
                    </x-form.select>
                </x-slot:action>
            </x-card-title>
        </x-slot:header>

        <!-- Success/Error Messages -->
        @if (session()->has('success'))
            <div class="mb-4" wire:key="success-{{ now() }}">
                <x-form.alert type="success" :message="session('success')" />
            </div>
        @endif

        @if (session()->has('error'))
            <div class="mb-4" wire:key="error-{{ now() }}">
                <x-form.alert type="error" :message="session('error')" />
            </div>
        @endif

        <x-table.table>
            <x-table.head>
                <tr>
                    <x-table.header sortable :sortKey="'sale_number'">Numéro</x-table.header>
                    <x-table.header sortable :sortKey="'sale_date'">Date</x-table.header>
                    <x-table.header>Client</x-table.header>
                    <x-table.header>Articles</x-table.header>
                    <x-table.header sortable :sortKey="'total'">Montant</x-table.header>
                    <x-table.header>Paiement</x-table.header>
                    <x-table.header sortable :sortKey="'status'">Statut</x-table.header>
                    <x-table.header align="right">Actions</x-table.header>
                </tr>
            </x-table.head>
            <x-table.body>
                @forelse($sales as $sale)
                    <x-table.row>
                        <x-table.cell>
                            <span class="text-sm font-medium text-gray-900">{{ $sale->sale_number }}</span>
                        </x-table.cell>
                        <x-table.cell>
                            <span class="text-sm text-gray-900">{{ $sale->sale_date->format('d/m/Y') }}</span>
                            <div class="text-xs text-gray-500">{{ $sale->sale_date->format('H:i') }}</div>
                        </x-table.cell>
                        <x-table.cell>
                            @if($sale->client)
                                <div class="text-sm font-medium text-gray-900">{{ $sale->client->name }}</div>
                                <div class="text-xs text-gray-500">{{ $sale->client->phone }}</div>
                            @else
                                <span class="text-sm text-gray-500">Client anonyme</span>
                            @endif
                        </x-table.cell>
                        <x-table.cell>
                            <span class="text-sm text-gray-900">{{ $sale->items->count() }} article(s)</span>
                        </x-table.cell>
                        <x-table.cell>
                            <div class="text-sm font-semibold text-gray-900">
                                {{ format_currency($sale->total) }}
                            </div>
                            @if($sale->discount > 0)
                                <div class="text-xs text-gray-500">
                                    Remise: {{ format_currency($sale->discount) }}
                                </div>
                            @endif
                            @if($sale->payment_status === 'partial')
                                <div class="text-xs text-blue-600 font-medium">
                                    Payé: {{ format_currency($sale->paid_amount) }}
                                </div>
                                <div class="text-xs text-red-600">
                                    Reste: {{ format_currency($sale->remaining_amount) }}
                                </div>
                            @endif
                        </x-table.cell>
                        <x-table.cell>
                            @php
                                $paymentColors = [
                                    'pending' => 'yellow',
                                    'paid' => 'green',
                                    'partial' => 'blue',
                                    'refunded' => 'red',
                                ];
                                $paymentLabels = [
                                    'pending' => 'En attente',
                                    'paid' => 'Payé',
                                    'partial' => 'Partiel',
                                    'refunded' => 'Remboursé',
                                ];
                            @endphp
                            <x-table.badge :color="$paymentColors[$sale->payment_status]" dot>
                                {{ $paymentLabels[$sale->payment_status] }}
                            </x-table.badge>
                            <div class="text-xs text-gray-500 mt-1">
                                {{ ucfirst($sale->payment_method) }}
                            </div>
                        </x-table.cell>
                        <x-table.cell>
                            @php
                                $statusColors = [
                                    'pending' => 'yellow',
                                    'completed' => 'green',
                                    'cancelled' => 'red',
                                ];
                                $statusLabels = [
                                    'pending' => 'En attente',
                                    'completed' => 'Complétée',
                                    'cancelled' => 'Annulée',
                                ];
                            @endphp
                            <x-table.badge :color="$statusColors[$sale->status]" dot>
                                {{ $statusLabels[$sale->status] }}
                            </x-table.badge>
                        </x-table.cell>
                        <x-table.cell align="right">
                            <x-table.actions>
                                @if($sale->status === 'pending')
                                    <x-table.action-button
                                        type="button"
                                        @click="showCompleteModal = true; saleToComplete = {{ $sale->id }}; completeNumber = '{{ $sale->sale_number }}'"
                                        color="green"
                                        title="Compléter la vente">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </x-table.action-button>
                                @endif
                                @if($sale->status === 'cancelled')
                                    <x-table.action-button
                                        type="button"
                                        @click="showRestoreModal = true; saleToRestore = {{ $sale->id }}; restoreNumber = '{{ $sale->sale_number }}'"
                                        color="blue"
                                        title="Réactiver la vente">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                    </x-table.action-button>
                                @endif
                                <x-table.action-button href="{{ route('sales.edit', $sale->id) }}" wire:navigate color="indigo" title="Modifier">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </x-table.action-button>
                                @if($sale->status !== 'cancelled')
                                    <x-table.action-button
                                        type="button"
                                        @click="showDeleteModal = true; saleToDelete = {{ $sale->id }}; saleNumber = '{{ $sale->sale_number }}'"
                                        color="red"
                                        title="Annuler">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </x-table.action-button>
                                @else
                                    <x-table.action-button
                                        type="button"
                                        @click="showForceDeleteModal = true; saleToForceDelete = {{ $sale->id }}; forceDeleteNumber = '{{ $sale->sale_number }}'"
                                        color="red"
                                        title="Supprimer définitivement">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </x-table.action-button>
                                @endif
                            </x-table.actions>
                        </x-table.cell>
                    </x-table.row>
                @empty
                    <x-table.empty-state
                        colspan="8"
                        title="Aucune vente"
                        description="Commencez par créer une nouvelle vente.">
                        <x-slot name="icon">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </x-slot>
                    </x-table.empty-state>
                @endforelse
            </x-table.body>
        </x-table.table>

        @if ($sales->hasPages())
            <div class="mt-4">
                {{ $sales->links() }}
            </div>
        @endif
    </x-card>

    <!-- Complete Confirmation Modal -->
    <div
        x-show="showCompleteModal"
        style="display: none;"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm"
        @click.self="showCompleteModal = false; saleToComplete = null; completeNumber = ''"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    >
        <div
            class="bg-white rounded-xl shadow-2xl max-w-md w-full p-6"
            @click.stop
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        >
            <!-- Icon -->
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100 mb-4">
                <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>

            <!-- Message -->
            <div class="text-center mb-6">
                <h3 class="text-lg font-medium text-gray-900 mb-2">
                    Confirmer la complétion
                </h3>
                <p class="text-sm text-gray-600 mb-3">
                    Voulez-vous vraiment compléter la vente
                </p>
                <p class="text-lg font-bold text-green-600" x-text="completeNumber"></p>
                <p class="text-xs text-gray-500 mt-2">
                    Cette action va déduire le stock et marquer la vente comme complétée.
                </p>
            </div>

            <!-- Boutons -->
            <div class="flex gap-3 justify-center">
                <button
                    type="button"
                    @click="showCompleteModal = false; saleToComplete = null; completeNumber = ''"
                    class="px-6 py-2.5 bg-gray-200 text-gray-800 font-medium rounded-lg hover:bg-gray-300 transition-colors focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2"
                >
                    Annuler
                </button>
                <button
                    type="button"
                    @click="$wire.set('saleToComplete', saleToComplete); $wire.call('completeSale'); showCompleteModal = false; saleToComplete = null; completeNumber = ''"
                    class="px-6 py-2.5 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 transition-colors focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                >
                    Compléter
                </button>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <x-delete-confirmation-modal
        show="showDeleteModal"
        itemName="saleNumber"
        itemType="la vente"
        onConfirm="$wire.set('saleToDelete', saleToDelete); $wire.call('delete'); showDeleteModal = false"
        onCancel="showDeleteModal = false; saleToDelete = null; saleNumber = ''"
    />

    <!-- Restore Confirmation Modal -->
    <div
        x-show="showRestoreModal"
        style="display: none;"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm"
        @click.self="showRestoreModal = false; saleToRestore = null; restoreNumber = ''"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    >
        <div
            class="bg-white rounded-xl shadow-2xl max-w-md w-full p-6"
            @click.stop
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        >
            <!-- Icon -->
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 mb-4">
                <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
            </div>

            <!-- Message -->
            <div class="text-center mb-6">
                <h3 class="text-lg font-medium text-gray-900 mb-2">
                    Réactiver la vente
                </h3>
                <p class="text-sm text-gray-600 mb-3">
                    Voulez-vous réactiver la vente annulée
                </p>
                <p class="text-lg font-bold text-blue-600" x-text="restoreNumber"></p>
                <p class="text-xs text-gray-500 mt-2">
                    La vente sera remise en statut "En attente".
                </p>
            </div>

            <!-- Boutons -->
            <div class="flex gap-3 justify-center">
                <button
                    type="button"
                    @click="showRestoreModal = false; saleToRestore = null; restoreNumber = ''"
                    class="px-6 py-2.5 bg-gray-200 text-gray-800 font-medium rounded-lg hover:bg-gray-300 transition-colors focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2"
                >
                    Annuler
                </button>
                <button
                    type="button"
                    @click="$wire.set('saleToRestore', saleToRestore); $wire.call('restoreSale'); showRestoreModal = false; saleToRestore = null; restoreNumber = ''"
                    class="px-6 py-2.5 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                >
                    Réactiver
                </button>
            </div>
        </div>
    </div>

    <!-- Force Delete Confirmation Modal -->
    <div
        x-show="showForceDeleteModal"
        style="display: none;"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm"
        @click.self="showForceDeleteModal = false; saleToForceDelete = null; forceDeleteNumber = ''"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    >
        <div
            class="bg-white rounded-xl shadow-2xl max-w-md w-full p-6"
            @click.stop
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        >
            <!-- Icon -->
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </div>

            <!-- Message -->
            <div class="text-center mb-6">
                <h3 class="text-lg font-medium text-gray-900 mb-2">
                    Supprimer définitivement
                </h3>
                <p class="text-sm text-gray-600 mb-3">
                    Êtes-vous sûr de vouloir supprimer définitivement la vente
                </p>
                <p class="text-lg font-bold text-red-600" x-text="forceDeleteNumber"></p>
                <p class="text-xs text-red-500 mt-2 font-medium">
                    ⚠️ Cette action est irréversible. La vente et tous ses éléments seront supprimés du système.
                </p>
            </div>

            <!-- Boutons -->
            <div class="flex gap-3 justify-center">
                <button
                    type="button"
                    @click="showForceDeleteModal = false; saleToForceDelete = null; forceDeleteNumber = ''"
                    class="px-6 py-2.5 bg-gray-200 text-gray-800 font-medium rounded-lg hover:bg-gray-300 transition-colors focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2"
                >
                    Annuler
                </button>
                <button
                    type="button"
                    @click="$wire.set('saleToForceDelete', saleToForceDelete); $wire.call('forceDelete'); showForceDeleteModal = false; saleToForceDelete = null; forceDeleteNumber = ''"
                    class="px-6 py-2.5 bg-red-600 text-white font-medium rounded-lg hover:bg-red-700 transition-colors focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
                >
                    Supprimer
                </button>
            </div>
        </div>
    </div>

    <!-- Email Modal -->
    <x-ui.alpine-modal name="email" max-width="lg" title="Envoyer le rapport par email" icon-bg="from-indigo-500 to-indigo-600">
        <x-slot name="icon">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
        </x-slot>

        <form wire:submit.prevent="sendReportEmail">
            <x-ui.alpine-modal-body>
                <div class="space-y-6">
                    <!-- Info sur la période -->
                    <div class="bg-indigo-50 border border-indigo-100 rounded-lg p-4">
                        <div class="flex items-center space-x-3">
                            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <div>
                                <p class="text-sm font-medium text-indigo-900">Période sélectionnée</p>
                                <p class="text-sm text-indigo-700">
                                    {{ $this->getPeriodLabel() }}
                                    @if($dateFrom && $dateTo)
                                        ({{ \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($dateTo)->format('d/m/Y') }})
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- User selection -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-form.form-group label="Destinataire 1" for="selectedUser" required>
                            <x-form.select wire:model="selectedUserId" id="selectedUser">
                                <option value="">-- Sélectionner un utilisateur --</option>
                                @foreach($this->users as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                                @endforeach
                            </x-form.select>
                            <x-form.input-error for="selectedUserId" />
                        </x-form.form-group>

                        <x-form.form-group label="Destinataire 2 (optionnel)" for="selectedUser2">
                            <x-form.select wire:model="selectedUserId2" id="selectedUser2">
                                <option value="">-- Aucun --</option>
                                @foreach($this->users as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                                @endforeach
                            </x-form.select>
                            <x-form.input-error for="selectedUserId2" />
                        </x-form.form-group>
                    </div>

                    <!-- Files info -->
                    <div class="bg-gray-50 rounded-lg p-4">
                        <p class="text-sm font-medium text-gray-700 mb-3">Pièces jointes incluses :</p>
                        <div class="space-y-2">
                            <div class="flex items-center space-x-3">
                                <div class="flex-shrink-0 w-8 h-8 bg-red-100 rounded flex items-center justify-center">
                                    <svg class="w-4 h-4 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <span class="text-sm text-gray-600">rapport_ventes.pdf</span>
                            </div>
                            <div class="flex items-center space-x-3">
                                <div class="flex-shrink-0 w-8 h-8 bg-green-100 rounded flex items-center justify-center">
                                    <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <span class="text-sm text-gray-600">rapport_ventes.xlsx</span>
                            </div>
                        </div>
                    </div>
                </div>
            </x-ui.alpine-modal-body>

            <div class="flex-shrink-0 flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 bg-gray-50 rounded-b-2xl">
                <button type="button" @click="showModal = false"
                    class="inline-flex items-center px-4 py-2.5 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition">
                    Annuler
                </button>
                <button type="button"
                    wire:click="sendReportEmail"
                    wire:loading.attr="disabled"
                    class="inline-flex items-center px-4 py-2.5 border border-transparent text-sm font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 transition">
                    <svg wire:loading.remove wire:target="sendReportEmail" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                    </svg>
                    <svg wire:loading wire:target="sendReportEmail" class="animate-spin w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span wire:loading.remove wire:target="sendReportEmail">Envoyer</span>
                    <span wire:loading wire:target="sendReportEmail">Envoi en cours...</span>
                </button>
            </div>
        </form>
    </x-ui.alpine-modal>
</div>
</div>
