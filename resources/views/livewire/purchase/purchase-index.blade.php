<div x-data="{ showDeleteModal: false, purchaseToDelete: null, purchaseNumber: '', showReceiveModal: false, purchaseToReceive: null, receiveNumber: '', showCancelModal: false, purchaseToCancel: null, cancelNumber: '', showRestoreModal: false, purchaseToRestore: null, restoreNumber: '', showDetailsModal: false, purchaseDetails: null, showModal: false }"
    @open-email-modal.window="showModal = true"
    @close-email-modal.window="showModal = false"
>
<x-slot name="header">
    <x-breadcrumb :items="[
        ['label' => 'Accueil', 'url' => route('dashboard')],
        ['label' => 'Achats']
    ]" />


</x-slot>

<div class="space-y-4">
    <div class="flex items-center justify-between mt-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Gestion des Achats</h1>
            <p class="text-gray-500 mt-1">Gérez vos achats et approvisionnements</p>
        </div>
        <div class="flex items-center space-x-3">
            <x-form.button href="{{ route('purchases.create') }}" wire:navigate icon="plus">
                Nouvel Achat
            </x-form.button>
        </div>
    </div>
    <!-- Statistics Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <x-kpi-card
            title="Achats Réceptionnés"
            :value="$stats['total_purchases']"
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
            title="Achats en Attente"
            :value="$stats['pending_purchases']"
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
            <div class="flex items-center space-x-2">
                <!-- Period Filter Dropdown -->
                <x-form.select wire:model.live="periodFilter" class="w-48">
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
                    <option value="all">Toutes les dates</option>
                    <option value="custom">Personnalisé</option>
                </x-form.select>

                <!-- Export Buttons -->
                <x-form.button wire:click="exportPdf" variant="secondary" size="sm" icon="document">
                    PDF
                </x-form.button>
                <x-form.button wire:click="exportExcel" variant="secondary" size="sm" icon="download">
                    Excel
                </x-form.button>
                <x-form.button wire:click="openEmailModal" variant="secondary" size="sm" icon="mail">
                    Email
                </x-form.button>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
            <!-- Search -->
            <div class="md:col-span-2">
                <x-form.search-input
                    wire:model.live.debounce.300ms="search"
                    wireModel="search"
                    placeholder="Rechercher par numéro ou fournisseur..."
                />
            </div>

            <!-- Supplier Filter -->
            <div>
                <x-form.select wire:model.live="supplierFilter">
                    <option value="">Tous les fournisseurs</option>
                    @foreach ($suppliers as $supplier)
                        <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                    @endforeach
                </x-form.select>
            </div>

            <!-- Status Filter -->
            <div>
                <x-form.select wire:model.live="statusFilter">
                    <option value="">Tous les statuts</option>
                    <option value="pending">En attente</option>
                    <option value="received">Réceptionné</option>
                    <option value="cancelled">Annulé</option>
                </x-form.select>
            </div>

            <!-- Date From -->
            <div>
                <x-form.input wire:model.live="dateFrom" type="date" placeholder="Date début" />
            </div>

            <!-- Date To -->
            <div>
                <x-form.input wire:model.live="dateTo" type="date" placeholder="Date fin" />
            </div>
        </div>

        <!-- Period indicator -->
        @if($periodFilter && $periodFilter !== 'custom')
        <div class="mt-4 flex items-center">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-indigo-100 text-indigo-800">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                {{ $this->getPeriodLabel() }}
                @if($dateFrom && $dateTo)
                    : {{ \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($dateTo)->format('d/m/Y') }}
                @endif
            </span>
        </div>
        @endif
    </x-card>

    <!-- Purchases Table -->
    <x-card>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th wire:click="sortBy('purchase_number')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100">
                            <div class="flex items-center space-x-1">
                                <span>Numéro</span>
                                @if ($sortField === 'purchase_number')
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M{{ $sortDirection === 'asc' ? '5 15l7-7 7 7' : '19 9l-7 7-7-7' }}" />
                                    </svg>
                                @endif
                            </div>
                        </th>
                        <th wire:click="sortBy('purchase_date')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100">
                            <div class="flex items-center space-x-1">
                                <span>Date</span>
                                @if ($sortField === 'purchase_date')
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M{{ $sortDirection === 'asc' ? '5 15l7-7 7 7' : '19 9l-7 7-7-7' }}" />
                                    </svg>
                                @endif
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Fournisseur
                        </th>
                        <th wire:click="sortBy('total')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100">
                            <div class="flex items-center space-x-1">
                                <span>Total</span>
                                @if ($sortField === 'total')
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M{{ $sortDirection === 'asc' ? '5 15l7-7 7 7' : '19 9l-7 7-7-7' }}" />
                                    </svg>
                                @endif
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Statut
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($purchases as $purchase)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $purchase->purchase_number }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $purchase->purchase_date->format('d/m/Y') }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $purchase->supplier->name }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-semibold text-gray-900">{{ number_format($purchase->total, 0, ',', ' ') }} {{ current_currency() }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if ($purchase->status === 'received')
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        Réceptionné
                                    </span>
                                @elseif ($purchase->status === 'pending')
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                        En attente
                                    </span>
                                @else
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                        Annulé
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end space-x-2">
                                    @if ($purchase->status === 'pending')
                                        <button @click="purchaseToReceive = {{ $purchase->id }}; receiveNumber = '{{ $purchase->purchase_number }}'; showReceiveModal = true"
                                            class="text-green-600 hover:text-green-900" title="Réceptionner">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </button>
                                        <a href="{{ route('purchases.edit', $purchase->id) }}" wire:navigate
                                            class="text-indigo-600 hover:text-indigo-900" title="Modifier">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </a>
                                        <button @click="purchaseToCancel = {{ $purchase->id }}; cancelNumber = '{{ $purchase->purchase_number }}'; showCancelModal = true"
                                            class="text-red-600 hover:text-red-900" title="Annuler">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    @elseif ($purchase->status === 'cancelled')
                                        <button @click="purchaseToRestore = {{ $purchase->id }}; restoreNumber = '{{ $purchase->purchase_number }}'; showRestoreModal = true"
                                            class="text-blue-600 hover:text-blue-900" title="Réactiver">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                        </button>
                                        <button @click="purchaseToDelete = {{ $purchase->id }}; purchaseNumber = '{{ $purchase->purchase_number }}'; showDeleteModal = true"
                                            class="text-red-600 hover:text-red-900" title="Supprimer définitivement">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    @elseif ($purchase->status === 'received')
                                        <button wire:click="showDetails({{ $purchase->id }})" @click="showDetailsModal = true"
                                            class="text-blue-600 hover:text-blue-900" title="Voir les détails">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">Aucun achat trouvé</h3>
                                <p class="mt-1 text-sm text-gray-500">Commencez par créer un nouvel achat.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-4">
            {{ $purchases->links() }}
        </div>
    </x-card>

    <!-- Receive Confirmation Modal -->
    <x-receive-confirmation-modal
        show="showReceiveModal"
        itemName="receiveNumber"
        itemType="l'achat"
        onConfirm="$wire.set('purchaseToReceive', purchaseToReceive); $wire.call('receivePurchase'); showReceiveModal = false"
        onCancel="showReceiveModal = false; purchaseToReceive = null; receiveNumber = ''"
    />

    <!-- Delete Confirmation Modal -->
    <x-delete-confirmation-modal
        show="showDeleteModal"
        itemName="purchaseNumber"
        itemType="l'achat"
        onConfirm="$wire.set('purchaseToDelete', purchaseToDelete); $wire.call('delete'); showDeleteModal = false"
        onCancel="showDeleteModal = false; purchaseToDelete = null; purchaseNumber = ''"
    />

    <!-- Cancel Confirmation Modal -->
    <div
        x-show="showCancelModal"
        style="display: none;"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm"
        @click.self="showCancelModal = false; purchaseToCancel = null; cancelNumber = ''"
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
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </div>

            <!-- Message -->
            <div class="text-center mb-6">
                <h3 class="text-lg font-medium text-gray-900 mb-2">
                    Confirmer l'annulation
                </h3>
                <p class="text-sm text-gray-600 mb-3">
                    Êtes-vous sûr de vouloir annuler l'achat <strong x-text="cancelNumber"></strong> ?
                </p>
                <p class="text-xs text-gray-500">
                    L'achat sera marqué comme annulé. Vous pourrez le réactiver plus tard si nécessaire.
                </p>
            </div>

            <!-- Actions -->
            <div class="flex space-x-3">
                <button
                    @click="showCancelModal = false; purchaseToCancel = null; cancelNumber = ''"
                    type="button"
                    class="flex-1 px-4 py-2.5 bg-gray-100 text-gray-700 font-medium rounded-lg hover:bg-gray-200 transition-colors"
                >
                    Annuler
                </button>
                <button
                    @click="$wire.set('purchaseToCancel', purchaseToCancel); $wire.call('cancelPurchase'); showCancelModal = false"
                    type="button"
                    class="flex-1 px-4 py-2.5 bg-red-600 text-white font-medium rounded-lg hover:bg-red-700 transition-colors"
                >
                    Confirmer
                </button>
            </div>
        </div>
    </div>

    <!-- Restore Confirmation Modal -->
    <div
        x-show="showRestoreModal"
        style="display: none;"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm"
        @click.self="showRestoreModal = false; purchaseToRestore = null; restoreNumber = ''"
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
                    Confirmer la réactivation
                </h3>
                <p class="text-sm text-gray-600 mb-3">
                    Êtes-vous sûr de vouloir réactiver l'achat <strong x-text="restoreNumber"></strong> ?
                </p>
                <p class="text-xs text-gray-500">
                    L'achat sera restauré et marqué comme "En attente".
                </p>
            </div>

            <!-- Actions -->
            <div class="flex space-x-3">
                <button
                    @click="showRestoreModal = false; purchaseToRestore = null; restoreNumber = ''"
                    type="button"
                    class="flex-1 px-4 py-2.5 bg-gray-100 text-gray-700 font-medium rounded-lg hover:bg-gray-200 transition-colors"
                >
                    Annuler
                </button>
                <button
                    @click="$wire.set('purchaseToRestore', purchaseToRestore); $wire.call('restorePurchase'); showRestoreModal = false"
                    type="button"
                    class="flex-1 px-4 py-2.5 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors"
                >
                    Réactiver
                </button>
            </div>
        </div>
    </div>

    <!-- Purchase Details Modal -->
    <div x-show="showDetailsModal"
         @click.self="showDetailsModal = false"
         style="display: none;"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div @click.stop
             class="bg-white rounded-xl shadow-2xl max-w-3xl w-full max-h-[90vh] overflow-hidden"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">

            @if($selectedPurchase)
            <!-- Modal Header -->
            <div class="flex items-center justify-between p-6 border-b border-gray-200">
                <div class="flex items-center space-x-3">
                    <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-900">Détails de l'achat</h3>
                        <p class="text-sm text-gray-500">{{ $selectedPurchase->purchase_number }}</p>
                    </div>
                </div>
                <button @click="showDetailsModal = false" class="text-gray-400 hover:text-gray-500 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="overflow-y-auto max-h-[calc(90vh-140px)]">
                <div class="p-6 space-y-6">
                    <!-- Purchase Info -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-xs font-medium text-gray-500 mb-1">Fournisseur</p>
                            <p class="text-sm font-semibold text-gray-900">{{ $selectedPurchase->supplier->name }}</p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-xs font-medium text-gray-500 mb-1">Date d'achat</p>
                            <p class="text-sm font-semibold text-gray-900">{{ $selectedPurchase->purchase_date->format('d/m/Y') }}</p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-xs font-medium text-gray-500 mb-1">Statut</p>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                                Réceptionné
                            </span>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-xs font-medium text-gray-500 mb-1">Montant total</p>
                            <p class="text-sm font-bold text-indigo-600">{{ number_format($selectedPurchase->total, 0, ',', ' ') }} {{ current_currency() }}</p>
                        </div>
                    </div>

                    <!-- Items -->
                    <div>
                        <h4 class="text-sm font-semibold text-gray-900 mb-3">Articles ({{ $selectedPurchase->items->count() }})</h4>
                        <div class="border border-gray-200 rounded-lg overflow-hidden">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Produit</th>
                                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Qté</th>
                                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Prix unit.</th>
                                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Sous-total</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($selectedPurchase->items as $item)
                                    <tr>
                                        <td class="px-4 py-3">
                                            <div class="text-sm font-medium text-gray-900">{{ $item->productVariant->full_name }}</div>
                                            <div class="text-xs text-gray-500">SKU: {{ $item->productVariant->sku }}</div>
                                        </td>
                                        <td class="px-4 py-3 text-right text-sm text-gray-900">{{ $item->quantity }}</td>
                                        <td class="px-4 py-3 text-right text-sm text-gray-900">{{ number_format($item->unit_price, 0, ',', ' ') }} {{ current_currency() }}</td>
                                        <td class="px-4 py-3 text-right text-sm font-semibold text-gray-900">{{ number_format($item->subtotal, 0, ',', ' ') }} {{ current_currency() }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="bg-gray-50">
                                    <tr>
                                        <td colspan="3" class="px-4 py-3 text-right text-sm font-semibold text-gray-900">Total:</td>
                                        <td class="px-4 py-3 text-right text-base font-bold text-indigo-600">{{ number_format($selectedPurchase->total, 0, ',', ' ') }} {{ current_currency() }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- Notes -->
                    @if($selectedPurchase->notes)
                    <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
                        <p class="text-xs font-medium text-amber-800 mb-1">Notes</p>
                        <p class="text-sm text-amber-900">{{ $selectedPurchase->notes }}</p>
                    </div>
                    @endif
                </div>

                <!-- Modal Footer -->
                <div class="bg-gray-50 px-6 py-4 flex justify-end">
                    <button @click="showDetailsModal = false" type="button"
                        class="px-6 py-2.5 bg-gray-200 text-gray-800 font-medium rounded-lg hover:bg-gray-300 transition-colors">
                        Fermer
                    </button>
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- Email Modal -->
    <x-ui.alpine-modal name="email-modal" title="Envoyer le rapport par email" icon="envelope" maxWidth="lg" x-model="showModal">
        <x-ui.alpine-modal-body>
            <div class="space-y-4">
                <!-- Period info -->
                <div class="bg-indigo-50 rounded-lg p-4">
                    <div class="flex items-center space-x-3">
                        <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                            <span class="text-sm text-gray-600">rapport_achats.pdf</span>
                        </div>
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0 w-8 h-8 bg-green-100 rounded flex items-center justify-center">
                                <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <span class="text-sm text-gray-600">rapport_achats.xlsx</span>
                        </div>
                    </div>
                </div>
            </div>
        </x-ui.alpine-modal-body>

        <div class="flex-shrink-0 flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 bg-gray-50 rounded-b-2xl">
            <x-form.button type="button" variant="secondary" @click="showModal = false">
                Annuler
            </x-form.button>
            <x-form.button type="button" variant="primary" wire:click="sendReportEmail" wire:loading.attr="disabled">
                <span wire:loading wire:target="sendReportEmail" class="mr-2">
                    <svg class="animate-spin h-4 w-4 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </span>
                Envoyer
            </x-form.button>
        </div>
    </x-ui.alpine-modal>
</div>
</div>
