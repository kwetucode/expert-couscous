<div x-data="{ showDeleteModal: false, invoiceToDelete: null, invoiceNumber: '', showActionModal: false, invoiceToProcess: null, actionType: '', showCreateModal: false, showExportMenu: false }"
     @open-create-modal.window="showCreateModal = true"
     @close-create-modal.window="showCreateModal = false">
    <x-slot name="header">
        <x-breadcrumb :items="[['label' => 'Accueil', 'url' => route('dashboard')], ['label' => 'Factures']]" />


    </x-slot>

    <div class="space-y-6">
        <div class="flex items-center justify-between mt-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Gestion des Factures</h1>
                <p class="text-gray-500 mt-1">Gérez vos factures clients</p>
            </div>
            <div class="flex items-center space-x-3">
                <button type="button" @click="showCreateModal = true; $wire.openCreateModal()"
                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Nouvelle Facture
                </button>
            </div>
        </div>
        <!-- Statistics Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <x-kpi-card
                title="Total Factures"
                :value="$statistics['total_invoices']"
                color="indigo">
                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </x-kpi-card>

            <x-kpi-card
                title="Factures Payées"
                :value="$statistics['paid_invoices']"
                color="green">
                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </x-kpi-card>

            <x-kpi-card
                title="Factures Impayées"
                :value="$statistics['unpaid_invoices']"
                color="red">
                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </x-kpi-card>

            <x-kpi-card
                title="Montant Total"
                :value="format_currency($statistics['total_paid_amount'])"
                color="purple">
                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </x-kpi-card>
        </div>

        <!-- Filters Card -->
        <x-card>
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-900">Filtres</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Search -->
                <div>
                    <x-form.search-input wire:model.live.debounce.300ms="search" wireModel="search"
                        placeholder="Rechercher par numéro ou client..." />
                </div>

                <!-- Status Filter -->
                <div>
                    <x-form.select wire:model.live="statusFilter">
                        <option value="">Tous les statuts</option>
                        <option value="draft">Brouillon</option>
                        <option value="sent">Envoyée</option>
                        <option value="paid">Payée</option>
                        <option value="cancelled">Annulée</option>
                    </x-form.select>
                </div>

                <!-- Period Filter -->
                <div>
                    <x-form.select wire:model.live="periodFilter">
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
                        </div>
                    </div>
                </div>
            </div>

            <!-- Date Range (only in custom mode) -->
            @if($periodFilter === 'custom')
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-4">
                <div></div>
                <!-- Date From -->
                <x-form.form-group label="Date de début" for="dateFrom">
                    <x-form.input wire:model.live="dateFrom" type="date" id="dateFrom" />
                </x-form.form-group>

                <!-- Date To -->
                <x-form.form-group label="Date de fin" for="dateTo">
                    <x-form.input wire:model.live="dateTo" type="date" id="dateTo" />
                </x-form.form-group>
                <div></div>
            </div>
            @endif

            <!-- Period Info -->
            <div class="mt-4">
                <div class="px-3 py-2 bg-indigo-50 border border-indigo-200 rounded-lg text-sm text-indigo-700 inline-flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
        </x-card>

        <!-- Invoices List -->
        <x-card>
            <x-slot:header>
                <x-card-title title="Liste des Factures ({{ $invoices->total() }})">
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
                        <x-table.header sortable :sortKey="'invoice_number'">N° Facture</x-table.header>
                        <x-table.header>Client</x-table.header>
                        <x-table.header sortable :sortKey="'invoice_date'">Date</x-table.header>
                        <x-table.header sortable :sortKey="'due_date'">Échéance</x-table.header>
                        <x-table.header sortable :sortKey="'total'">Montant</x-table.header>
                        <x-table.header sortable :sortKey="'status'">Statut</x-table.header>
                        <x-table.header align="right">Actions</x-table.header>
                    </tr>
                </x-table.head>
                <x-table.body>
                    @forelse($invoices as $invoice)
                        <x-table.row wire:key="invoice-{{ $invoice->id }}">
                            <x-table.cell>
                                <a href="{{ route('invoices.show', $invoice->id) }}" wire:navigate
                                    class="text-sm font-semibold text-indigo-600 hover:text-indigo-900">
                                    {{ $invoice->invoice_number }}
                                </a>
                            </x-table.cell>
                            <x-table.cell>
                                <div class="text-sm font-medium text-gray-900">
                                    {{ $invoice->sale->client->name ?? 'Client Walk-in' }}
                                </div>
                                @if ($invoice->sale->client)
                                    <div class="text-xs text-gray-500">
                                        {{ $invoice->sale->client->phone }}
                                    </div>
                                @endif
                            </x-table.cell>
                            <x-table.cell>
                                <span class="text-sm text-gray-900">
                                    {{ $invoice->invoice_date->format('d/m/Y') }}
                                </span>
                            </x-table.cell>
                            <x-table.cell>
                                @if ($invoice->due_date)
                                    <span class="text-sm text-gray-900">
                                        {{ $invoice->due_date->format('d/m/Y') }}
                                    </span>
                                    @if ($invoice->isOverdue())
                                        <span
                                            class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                                            En retard
                                        </span>
                                    @endif
                                @else
                                    <span class="text-sm text-gray-500">-</span>
                                @endif
                            </x-table.cell>
                            <x-table.cell>
                                <div class="text-sm font-semibold text-gray-900">
                                    {{ format_currency($invoice->total) }}
                                </div>
                            </x-table.cell>
                            <x-table.cell>
                                @php
                                    $statusColors = [
                                        'draft' => 'gray',
                                        'sent' => 'blue',
                                        'paid' => 'green',
                                        'cancelled' => 'red',
                                    ];
                                    $statusLabels = [
                                        'draft' => 'Brouillon',
                                        'sent' => 'Envoyée',
                                        'paid' => 'Payée',
                                        'cancelled' => 'Annulée',
                                    ];
                                @endphp
                                <x-table.badge :color="$statusColors[$invoice->status] ?? 'gray'" dot>
                                    {{ $statusLabels[$invoice->status] ?? $invoice->status }}
                                </x-table.badge>
                            </x-table.cell>
                            <x-table.cell align="right">
                                <x-table.actions>
                                    <x-table.action-button href="{{ route('invoices.show', $invoice->id) }}"
                                        wire:navigate color="blue">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </x-table.action-button>

                                    @if ($invoice->status !== 'paid' && $invoice->status !== 'cancelled')
                                        <x-table.action-button href="{{ route('invoices.edit', $invoice->id) }}"
                                            wire:navigate color="indigo">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </x-table.action-button>
                                    @endif

                                    @if ($invoice->status === 'draft')
                                        <x-table.action-button type="button"
                                            @click="showActionModal = true; invoiceToProcess = {{ $invoice->id }}; actionType = 'send'"
                                            color="green">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                            </svg>
                                        </x-table.action-button>
                                    @endif

                                    @if ($invoice->status === 'sent')
                                        <x-table.action-button type="button"
                                            @click="showActionModal = true; invoiceToProcess = {{ $invoice->id }}; actionType = 'paid'"
                                            color="emerald">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </x-table.action-button>
                                    @endif

                                    @if ($invoice->status !== 'paid' && $invoice->status !== 'cancelled')
                                        <x-table.action-button type="button"
                                            @click="showActionModal = true; invoiceToProcess = {{ $invoice->id }}; actionType = 'cancel'"
                                            color="orange">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </x-table.action-button>
                                    @endif

                                    @if ($invoice->status === 'cancelled')
                                        <x-table.action-button type="button"
                                            @click="showDeleteModal = true; invoiceToDelete = {{ $invoice->id }}; invoiceNumber = '{{ addslashes($invoice->invoice_number) }}'"
                                            color="red">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </x-table.action-button>
                                    @endif
                                </x-table.actions>
                            </x-table.cell>
                        </x-table.row>
                    @empty
                        <x-table.empty-state colspan="7" title="Aucune facture"
                            description="Commencez par créer une nouvelle facture.">
                            <x-slot name="icon">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </x-slot>
                        </x-table.empty-state>
                    @endforelse
                </x-table.body>
            </x-table.table>

            <!-- Pagination -->
            @if ($invoices->hasPages())
                <div class="mt-4">
                    {{ $invoices->links() }}
                </div>
            @endif
        </x-card>

    </div>

    <!-- Delete Confirmation Modal -->
    <div x-show="showDeleteModal" x-cloak style="display: none;"
        class="fixed inset-0 overflow-y-auto px-4 py-6 sm:px-0 z-50" @keydown.escape.window="showDeleteModal = false">
        <!-- Backdrop -->
        <div @click="showDeleteModal = false" x-show="showDeleteModal" x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm"></div>

        <!-- Modal Container -->
        <div class="flex min-h-full items-center justify-center">
            <div x-show="showDeleteModal" @click.stop x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="relative bg-white rounded-2xl overflow-hidden shadow-2xl transform transition-all w-full sm:max-w-md">
                <div class="p-6">
                    <div class="flex items-center mb-4">
                        <div class="flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                            <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-medium text-gray-900">Supprimer la facture</h3>
                        </div>
                    </div>
                    <p class="text-sm text-gray-500">
                        Êtes-vous sûr de vouloir supprimer la facture <span class="font-semibold"
                            x-text="invoiceNumber"></span> ?
                        Cette action est irréversible.
                    </p>
                    <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                        <button type="button" @click="showDeleteModal = false"
                            class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:text-sm">
                            Annuler
                        </button>
                        <button type="button"
                            @click="$wire.set('invoiceToDelete', invoiceToDelete); $wire.delete(); showDeleteModal = false"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:text-sm">
                            Supprimer
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Confirmation Modal -->
    <div x-show="showActionModal" x-cloak style="display: none;"
        class="fixed inset-0 overflow-y-auto px-4 py-6 sm:px-0 z-50" @keydown.escape.window="showActionModal = false">
        <!-- Backdrop -->
        <div @click="showActionModal = false" x-show="showActionModal" x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm"></div>

        <!-- Modal Container -->
        <div class="flex min-h-full items-center justify-center">
            <div x-show="showActionModal" @click.stop x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="relative bg-white rounded-2xl overflow-hidden shadow-2xl transform transition-all w-full sm:max-w-md">
                <div class="p-6">
                    <div class="flex items-center mb-4">
                        <div class="flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full"
                            :class="{ 'bg-green-100': actionType === 'send' ||
                                actionType === 'paid', 'bg-orange-100': actionType === 'cancel' }">
                            <svg class="h-6 w-6"
                                :class="{ 'text-green-600': actionType === 'send' ||
                                    actionType === 'paid', 'text-orange-600': actionType === 'cancel' }"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-medium text-gray-900"
                                x-text="
                    actionType === 'send' ? 'Envoyer la facture' :
                    actionType === 'paid' ? 'Marquer comme payée' :
                    actionType === 'cancel' ? 'Annuler la facture' : ''
                ">
                            </h3>
                        </div>
                    </div>
                    <p class="text-sm text-gray-500"
                        x-text="
            actionType === 'send' ? 'Confirmer l\'envoi de cette facture au client ?' :
            actionType === 'paid' ? 'Marquer cette facture comme payée ?' :
            actionType === 'cancel' ? 'Annuler cette facture ? Cette action ne peut pas être annulée.' : ''
        ">
                    </p>
                    <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                        <button type="button" @click="showActionModal = false"
                            class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:text-sm">
                            Annuler
                        </button>
                        <button type="button"
                            @click="
                    $wire.set('invoiceToProcess', invoiceToProcess);
                    if (actionType === 'send') $wire.sendInvoice();
                    else if (actionType === 'paid') $wire.markAsPaid();
                    else if (actionType === 'cancel') $wire.cancelInvoice();
                    showActionModal = false;
                "
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 text-base font-medium text-white focus:outline-none focus:ring-2 focus:ring-offset-2 sm:text-sm"
                            :class="{ 'bg-green-600 hover:bg-green-700 focus:ring-green-500': actionType === 'send' ||
                                    actionType === 'paid', 'bg-orange-600 hover:bg-orange-700 focus:ring-orange-500': actionType === 'cancel' }">
                            Confirmer
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Invoice Modal -->
    <div x-show="showCreateModal"
         x-cloak
         style="display: none;"
         class="fixed inset-0 overflow-y-auto px-4 py-6 sm:px-0 z-50"
         @keydown.escape.window="showCreateModal = false; $wire.closeCreateModal()">
        <!-- Backdrop -->
        <div @click="showCreateModal = false; $wire.closeCreateModal()"
             x-show="showCreateModal"
             x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm"></div>

        <!-- Modal Container "
                 @click.stop

        <div class="flex min-h-full items-center justify-center">
            <div x-show="showCreateModal" @click.stop x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="relative bg-white rounded-2xl overflow-hidden shadow-2xl transform transition-all w-full max-w-3xl max-h-[90vh] overflow-y-auto">
                <!-- Modal Header -->
                <div
                    class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between z-10">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Créer une Facture</h3>
                        <p class="text-sm text-gray-500 mt-1">Créer une facture à partir d'une vente</p>
                    </div>
                    <button type="button" @click="showCreateModal = false; $wire.closeCreateModal()"
                        class="text-gray-400 hover:text-gray-500">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Modal Body -->
                <form wire:submit.prevent="createInvoice" class="p-6 space-y-6">
                    <!-- Sale Selection -->
                    <div>
                        <label for="modal-saleId" class="block text-sm font-medium text-gray-700 mb-1">
                            Vente <span class="text-red-500">*</span>
                        </label>
                        <select wire:model.live="saleId" id="modal-saleId"
                            class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 rounded-lg">
                            <option value="">Sélectionner une vente</option>
                            @foreach ($availableSales as $sale)
                                <option value="{{ $sale->id }}">
                                    {{ $sale->sale_number }} -
                                    {{ $sale->client ? $sale->client->name : 'Client Walk-in' }} -
                                    {{ format_currency($sale->total) }} -
                                    {{ $sale->sale_date->format('d/m/Y') }}
                                </option>
                            @endforeach
                        </select>
                        @error('saleId')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Selected Sale Details -->
                    @if ($selectedSale)
                        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                            <h4 class="text-sm font-semibold text-gray-900 mb-3">Détails de la vente</h4>
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="text-gray-600">Numéro:</span>
                                    <span class="ml-2 font-medium">{{ $selectedSale->sale_number }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-600">Date:</span>
                                    <span
                                        class="ml-2 font-medium">{{ $selectedSale->sale_date->format('d/m/Y') }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-600">Client:</span>
                                    <span
                                        class="ml-2 font-medium">{{ $selectedSale->client->name ?? 'Walk-in' }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-600">Total:</span>
                                    <span
                                        class="ml-2 font-medium">{{ format_currency($selectedSale->total) }}</span>
                                </div>
                            </div>

                            <!-- Sale Items -->
                            @if ($selectedSale->items->count() > 0)
                                <div class="mt-4">
                                    <h5 class="text-xs font-semibold text-gray-700 mb-2">Articles</h5>
                                    <div class="space-y-1 max-h-40 overflow-y-auto">
                                        @foreach ($selectedSale->items as $item)
                                            <div class="flex justify-between text-xs">
                                                <span class="text-gray-600">
                                                    {{ $item->productVariant->product->name }}
                                                    @if ($item->productVariant->size || $item->productVariant->color)
                                                        ({{ $item->productVariant->size }}
                                                        {{ $item->productVariant->color }})
                                                    @endif
                                                </span>
                                                <span class="text-gray-900">
                                                    {{ $item->quantity }} x
                                                    {{ format_currency($item->unit_price) }}
                                                </span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif

                    <!-- Invoice Date -->
                    <div>
                        <label for="modal-invoiceDate" class="block text-sm font-medium text-gray-700 mb-1">
                            Date de facturation <span class="text-red-500">*</span>
                        </label>
                        <input wire:model="invoiceDate" type="date" id="modal-invoiceDate"
                            class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" />
                        @error('invoiceDate')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Due Date -->
                    <div>
                        <label for="modal-dueDate" class="block text-sm font-medium text-gray-700 mb-1">
                            Date d'échéance
                        </label>
                        <input wire:model="dueDate" type="date" id="modal-dueDate"
                            class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" />
                        @error('dueDate')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-gray-500">Laisser vide si aucune date d'échéance n'est requise</p>
                    </div>

                    <!-- Status -->
                    <div>
                        <label for="modal-status" class="block text-sm font-medium text-gray-700 mb-1">
                            Statut <span class="text-red-500">*</span>
                        </label>
                        <select wire:model="invoiceStatus" id="modal-status"
                            class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 rounded-lg">
                            <option value="draft">Brouillon</option>
                            <option value="sent">Envoyée</option>
                        </select>
                        @error('invoiceStatus')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Modal Footer -->
                    <div class="flex items-center justify-end space-x-3 pt-4 border-t">
                        <button type="button" @click="showCreateModal = false; $wire.closeCreateModal()"
                            class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Annuler
                        </button>
                        <button type="submit"
                            class="px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Créer la facture
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
