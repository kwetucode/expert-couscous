<div x-data="{ showReceiveModal: @entangle('showReceiveModal').live }">
    <x-slot name="header">
        <x-breadcrumb :items="[
            ['label' => 'Accueil', 'url' => route('dashboard')],
            ['label' => 'Transferts', 'url' => route('transfers.index')],
            ['label' => $transfer->reference],
        ]" />
    </x-slot>

    <!-- Toast Notifications -->
    <x-toast />

    <!-- Header -->
    <div class="flex items-center justify-between mt-4 mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Transfert {{ $transfer->reference }}</h1>
            <div class="flex items-center space-x-3 mt-2">
                <span
                    class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                    @if ($transfer->status === 'completed') bg-green-100 text-green-800
                    @elseif($transfer->status === 'in_transit') bg-blue-100 text-blue-800
                    @elseif($transfer->status === 'cancelled') bg-red-100 text-red-800
                    @else bg-yellow-100 text-yellow-800 @endif">
                    @if ($transfer->status === 'pending')
                        En attente d'approbation
                    @elseif($transfer->status === 'in_transit')
                        En transit
                    @elseif($transfer->status === 'completed')
                        Complété
                    @else
                        Annulé
                    @endif
                </span>
                <span class="text-sm text-gray-500">
                    Créé le {{ $transfer->created_at->format('d/m/Y à H:i') }}
                </span>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center space-x-3">
            @if ($canApprove)
                <button wire:click="approveTransfer" wire:loading.attr="disabled"
                    wire:loading.class="opacity-50 cursor-not-allowed" wire:target="approveTransfer"
                    class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md transition duration-150 disabled:opacity-50 disabled:cursor-not-allowed">
                    <span wire:loading.remove wire:target="approveTransfer">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Approuver
                    </span>
                    <span wire:loading wire:target="approveTransfer">
                        <svg class="w-5 h-5 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                        Approbation...
                    </span>
                </button>
            @endif

            @if ($canReceive)
                <button type="button" wire:click="openReceiveModal"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg shadow-md transition duration-150">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                    Réceptionner
                </button>
            @endif

            @if ($canCancel)
                <button wire:click="cancelTransfer"
                    onclick="return confirm('Êtes-vous sûr de vouloir annuler ce transfert ?')"
                    class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg shadow-md transition duration-150">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Annuler le transfert
                </button>
            @endif
        </div>
    </div>

    <!-- Transfer Information -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <!-- Source Store -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Magasin source</h3>
                <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
            </div>
            <div>
                <p class="text-xl font-bold text-gray-900">{{ $transfer->fromStore->name }}</p>
                @if ($transfer->fromStore->code)
                    <p class="text-sm text-gray-500 mt-1">Code: {{ $transfer->fromStore->code }}</p>
                @endif
                @if ($transfer->fromStore->address)
                    <p class="text-sm text-gray-500 mt-1">{{ $transfer->fromStore->address }}</p>
                @endif
            </div>
        </div>

        <!-- Arrow -->
        <div class="flex items-center justify-center">
            <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
            </svg>
        </div>

        <!-- Destination Store -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Magasin destination</h3>
                <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M7 16l-4-4m0 0l4-4m-4 4h18M13 8v1a3 3 0 003 3h4a3 3 0 003-3V7a3 3 0 00-3-3h-4a3 3 0 00-3 3v1z" />
                </svg>
            </div>
            <div>
                <p class="text-xl font-bold text-gray-900">{{ $transfer->toStore->name }}</p>
                @if ($transfer->toStore->code)
                    <p class="text-sm text-gray-500 mt-1">Code: {{ $transfer->toStore->code }}</p>
                @endif
                @if ($transfer->toStore->address)
                    <p class="text-sm text-gray-500 mt-1">{{ $transfer->toStore->address }}</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Transfer Details -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <!-- Timeline -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Historique</h3>

                <div class="space-y-4">
                    <!-- Created -->
                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900">Créé</p>
                            <p class="text-xs text-gray-500">{{ $transfer->created_at->format('d/m/Y à H:i') }}</p>
                            <p class="text-xs text-gray-500">Par {{ $transfer->requester?->name ?? 'N/A' }}</p>
                        </div>
                    </div>

                    <!-- Approved -->
                    @if ($transfer->approved_at)
                        <div class="flex items-start space-x-3">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">Approuvé</p>
                                <p class="text-xs text-gray-500">{{ $transfer->approved_at->format('d/m/Y à H:i') }}
                                </p>
                                <p class="text-xs text-gray-500">Par {{ $transfer->approver?->name ?? 'N/A' }}</p>
                            </div>
                        </div>
                    @endif

                    <!-- Received -->
                    @if ($transfer->received_at)
                        <div class="flex items-start space-x-3">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-indigo-100 rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" />
                                        <path fill-rule="evenodd"
                                            d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm9.707 5.707a1 1 0 00-1.414-1.414L9 12.586l-1.293-1.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">Réceptionné</p>
                                <p class="text-xs text-gray-500">{{ $transfer->received_at->format('d/m/Y à H:i') }}
                                </p>
                                <p class="text-xs text-gray-500">Par {{ $transfer->receiver?->name ?? 'N/A' }}</p>
                            </div>
                        </div>
                    @endif

                    <!-- Cancelled -->
                    @if ($transfer->cancelled_at)
                        <div class="flex items-start space-x-3">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">Annulé</p>
                                <p class="text-xs text-gray-500">{{ $transfer->cancelled_at->format('d/m/Y à H:i') }}
                                </p>
                                <p class="text-xs text-gray-500">Par {{ $transfer->canceller?->name ?? 'N/A' }}</p>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Notes -->
                @if ($transfer->notes)
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <h4 class="text-sm font-semibold text-gray-900 mb-2">Notes</h4>
                        <p class="text-sm text-gray-600">{{ $transfer->notes }}</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Items -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Produits transférés</h3>
                </div>

                <x-table.table>
                    <x-table.head>
                        <tr>
                            <x-table.header>Produit</x-table.header>
                            <x-table.header>SKU</x-table.header>
                            <x-table.header>Quantité demandée</x-table.header>
                            @if ($transfer->status === 'completed')
                                <x-table.header>Quantité reçue</x-table.header>
                            @endif
                        </tr>
                    </x-table.head>
                    <x-table.body>
                        @foreach ($transfer->items as $item)
                            <x-table.row>
                                <x-table.cell>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ $item->variant?->product?->name ?? 'Produit supprimé' }}
                                        </div>
                                        <div class="text-xs text-gray-500">{{ $item->variant?->name ?? '-' }}</div>
                                    </div>
                                </x-table.cell>
                                <x-table.cell>
                                    <span
                                        class="text-sm font-mono text-gray-600">{{ $item->variant?->sku ?? '-' }}</span>
                                </x-table.cell>
                                <x-table.cell>
                                    <span
                                        class="text-sm font-semibold text-gray-900">{{ $item->quantity_requested }}</span>
                                </x-table.cell>
                                @if ($transfer->status === 'completed')
                                    <x-table.cell>
                                        <span
                                            class="text-sm font-semibold {{ $item->quantity_received == $item->quantity_requested ? 'text-green-600' : 'text-yellow-600' }}">
                                            {{ $item->quantity_received }}
                                        </span>
                                        @if ($item->quantity_received != $item->quantity_requested)
                                            <span class="text-xs text-yellow-600">
                                                ({{ $item->quantity_requested - $item->quantity_received }} manquant)
                                            </span>
                                        @endif
                                    </x-table.cell>
                                @endif
                            </x-table.row>
                        @endforeach
                    </x-table.body>
                </x-table.table>

                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                    <div class="flex items-center justify-between text-sm">
                        <span class="font-medium text-gray-900">Total articles:</span>
                        <span class="font-semibold text-gray-900">{{ $transfer->items->count() }} produit(s)</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Receive Modal -->
    <x-ui.alpine-modal name="showReceiveModal" max-width="4xl" title="Réceptionner le transfert"
        icon-bg="from-indigo-500 to-purple-600">
        <x-slot name="icon">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
            </svg>
        </x-slot>

        <form wire:submit="receiveTransfer">
            <x-ui.alpine-modal-body>
                <div class="mb-4">
                    <div class="flex items-start space-x-3 p-4 bg-indigo-50 border border-indigo-100 rounded-xl">
                        <div class="flex-shrink-0">
                            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm text-indigo-900 font-medium">Vérification des quantités</p>
                            <p class="text-sm text-indigo-700 mt-1">
                                Vérifiez les quantités reçues pour chaque produit. Vous pouvez ajuster les quantités si
                                nécessaire.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-xl overflow-hidden shadow-sm">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                    <div class="flex items-center space-x-2">
                                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                        </svg>
                                        <span>Produit</span>
                                    </div>
                                </th>
                                <th
                                    class="px-6 py-3 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                    <div class="flex items-center justify-center space-x-2">
                                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                        </svg>
                                        <span>Demandé</span>
                                    </div>
                                </th>
                                <th
                                    class="px-6 py-3 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                    <div class="flex items-center justify-center space-x-2">
                                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span>Reçu</span>
                                    </div>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($transfer->items as $item)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center space-x-3">
                                            <div
                                                class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-indigo-100 to-purple-100 rounded-lg flex items-center justify-center">
                                                <svg class="w-5 h-5 text-indigo-600" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                                </svg>
                                            </div>
                                            <div>
                                                <div class="text-sm font-semibold text-gray-900">
                                                    {{ $item->variant?->product?->name ?? 'Produit supprimé' }}
                                                </div>
                                                <div class="flex items-center space-x-2 mt-1">
                                                    <span
                                                        class="text-xs text-gray-500">{{ $item->variant?->name ?? '-' }}</span>
                                                    @if ($item->variant?->sku)
                                                        <span
                                                            class="text-xs px-2 py-0.5 bg-gray-100 text-gray-600 rounded font-mono">
                                                            {{ $item->variant->sku }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span
                                            class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-blue-100 text-blue-700">
                                            {{ $item->quantity_sent ?? $item->quantity_requested }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center justify-center">
                                            <input type="number" wire:model="receivedQuantities.{{ $item->id }}"
                                                min="0"
                                                max="{{ $item->quantity_sent ?? $item->quantity_requested }}"
                                                class="w-28 px-4 py-2 text-center border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 font-semibold text-gray-900 shadow-sm transition-all">
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @error('receivedQuantities')
                    <div class="mt-4 flex items-start space-x-3 p-4 bg-red-50 border border-red-100 rounded-xl">
                        <svg class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="text-sm text-red-800 font-medium">{{ $message }}</p>
                    </div>
                @enderror
            </x-ui.alpine-modal-body>

            <!-- Actions -->
            <div
                class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex items-center justify-end space-x-3 rounded-b-2xl">
                <x-form.button type="button" @click="showReceiveModal = false" variant="secondary">
                    Annuler
                </x-form.button>
                <x-form.button type="submit" variant="primary-gradient" wire:loading.attr="disabled"
                    wire:target="receiveTransfer">
                    <span wire:loading.remove wire:target="receiveTransfer" class="flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Confirmer la réception
                    </span>
                    <span wire:loading wire:target="receiveTransfer" class="flex items-center">
                        <svg class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                        Traitement...
                    </span>
                </x-form.button>
            </div>
        </form>
    </x-ui.alpine-modal>
</div>
