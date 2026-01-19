<div x-data="{ showCancelModal: false, showDeleteModal: false, showApproveModal: false, transferToCancel: null, transferToDelete: null, transferToApprove: null, transferReference: '', showModal: false, isEditing: false }"
     @open-transfer-modal.window="showModal = true; isEditing = false"
     @close-transfer-modal.window="showModal = false">
    <x-slot name="header">
        <x-breadcrumb :items="[['label' => 'Accueil', 'url' => route('dashboard')], ['label' => 'Transferts']]" />
    </x-slot>

    <div class="flex items-center justify-between mt-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Transferts Inter-Magasins</h1>
            <p class="text-gray-500 mt-1">Gérez les mouvements de stock entre vos magasins</p>
        </div>
        <x-form.button @click="showModal = true" icon="switch-horizontal">
            Nouveau Transfert
        </x-form.button>
    </div>

    <!-- Toast Notifications -->
    <x-toast />

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6 mt-6">
        <!-- En Attente (Sortants) -->
        <x-kpi-card title="En Attente (Sortants)" :value="$statistics['pending_outgoing']" color="orange">
            <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </x-kpi-card>

        <!-- En Attente (Entrants) -->
        <x-kpi-card title="En Attente (Entrants)" :value="$statistics['pending_incoming']" color="indigo">
            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
            </svg>
        </x-kpi-card>

        <!-- En Transit -->
        <x-kpi-card title="En Transit" :value="$statistics['in_transit']" color="purple">
            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
            </svg>
        </x-kpi-card>

        <!-- Complétés (Mois) -->
        <x-kpi-card title="Complétés (Mois)" :value="$statistics['completed_this_month']" color="green">
            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </x-kpi-card>
    </div>

    <!-- Filters -->
    <div class="mb-6 bg-white rounded-xl shadow-sm border border-gray-200 p-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Search -->
            <x-form.search-input
                wire:model.live.debounce.300ms="search"
                wireModel="search"
                placeholder="Rechercher par référence..."
            />

            <!-- Direction Filter -->
            <div>
                <select wire:model.live="directionFilter"
                    class="block w-full px-3 py-2.5 border border-gray-300 rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                    <option value="all">Tous les transferts</option>
                    <option value="outgoing">Sortants</option>
                    <option value="incoming">Entrants</option>
                </select>
            </div>

            <!-- Status Filter -->
            <div>
                <select wire:model.live="statusFilter"
                    class="block w-full px-3 py-2.5 border border-gray-300 rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                    <option value="">Tous les statuts</option>
                    <option value="pending">En attente</option>
                    <option value="in_transit">En transit</option>
                    <option value="completed">Complété</option>
                    <option value="cancelled">Annulé</option>
                </select>
            </div>

            <!-- Per Page -->
            <div>
                <select wire:model.live="perPage"
                    class="block w-full px-3 py-2.5 border border-gray-300 rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                    <option value="10">10 transferts</option>
                    <option value="25">25 transferts</option>
                    <option value="50">50 transferts</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Transfers Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <x-table.table>
            <x-table.head>
                <tr>
                    <x-table.header>Référence</x-table.header>
                    <x-table.header>De → Vers</x-table.header>
                    <x-table.header>Articles</x-table.header>
                    <x-table.header>Statut</x-table.header>
                    <x-table.header>Date</x-table.header>
                    <x-table.header align="center">Actions</x-table.header>
                </tr>
            </x-table.head>

            <x-table.body>
                @forelse ($transfers as $transfer)
                    <x-table.row wire:key="transfer-{{ $transfer->id }}">
                        <x-table.cell>
                            <a href="{{ route('transfers.show', $transfer->id) }}" wire:navigate
                                class="text-indigo-600 hover:text-indigo-800 font-medium">
                                {{ $transfer->transfer_number }}
                            </a>
                        </x-table.cell>
                        <x-table.cell>
                            <div class="flex items-center space-x-2">
                                <span
                                    class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-800">
                                    {{ $transfer->fromStore->name }}
                                </span>
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                </svg>
                                <span
                                    class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-800">
                                    {{ $transfer->toStore->name }}
                                </span>
                            </div>
                        </x-table.cell>
                        <x-table.cell>
                            <span class="text-sm font-semibold">{{ $transfer->items->count() }}</span>
                            <span class="text-xs text-gray-500">articles</span>
                        </x-table.cell>
                        <x-table.cell>
                            @php
                                $statusConfig = [
                                    'pending' => ['label' => 'En attente', 'bg' => 'bg-amber-100', 'text' => 'text-amber-800', 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
                                    'in_transit' => ['label' => 'En transit', 'bg' => 'bg-blue-100', 'text' => 'text-blue-800', 'icon' => 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4'],
                                    'completed' => ['label' => 'Complété', 'bg' => 'bg-emerald-100', 'text' => 'text-emerald-800', 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
                                    'cancelled' => ['label' => 'Annulé', 'bg' => 'bg-red-100', 'text' => 'text-red-800', 'icon' => 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z'],
                                ];
                                $config = $statusConfig[$transfer->status] ?? $statusConfig['pending'];
                            @endphp
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold {{ $config['bg'] }} {{ $config['text'] }}">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $config['icon'] }}"/>
                                </svg>
                                {{ $config['label'] }}
                            </span>
                        </x-table.cell>
                        <x-table.cell>
                            <div>
                                <div class="text-sm text-gray-900">{{ $transfer->created_at->format('d/m/Y') }}</div>
                                <div class="text-xs text-gray-500">{{ $transfer->created_at->format('H:i') }}</div>
                            </div>
                        </x-table.cell>
                        <x-table.cell align="center">
                            <div class="flex items-center justify-center space-x-2">
                                <a href="{{ route('transfers.show', $transfer->id) }}" wire:navigate
                                    class="p-2 text-gray-600 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </a>

                                @if($transfer->status === 'pending')
                                    <button
                                        @click="showApproveModal = true; transferToApprove = {{ $transfer->id }}; transferReference = '{{ $transfer->reference }}'"
                                        class="p-2 text-gray-600 hover:text-green-600 hover:bg-green-50 rounded-lg transition"
                                        title="Approuver">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </button>

                                    <button
                                        @click="showDeleteModal = true; transferToDelete = {{ $transfer->id }}; transferReference = '{{ $transfer->reference }}'"
                                        class="p-2 text-gray-600 hover:text-red-600 hover:bg-red-50 rounded-lg transition"
                                        title="Supprimer">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                @endif

                                @if($transfer->status === 'in_transit')
                                    <button
                                        @click="showCancelModal = true; transferToCancel = {{ $transfer->id }}; transferReference = '{{ $transfer->reference }}'"
                                        class="p-2 text-gray-600 hover:text-red-600 hover:bg-red-50 rounded-lg transition"
                                        title="Annuler">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </button>
                                @endif
                            </div>
                        </x-table.cell>
                    </x-table.row>
                @empty
                    <x-table.row>
                        <x-table.cell colspan="6">
                            <div class="text-center py-12">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">Aucun transfert</h3>
                                <p class="mt-1 text-sm text-gray-500">Commencez par créer votre premier transfert.</p>
                                <div class="mt-6">
                                    <button @click="showModal = true"
                                        class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                        </svg>
                                        Nouveau Transfert
                                    </button>
                                </div>
                            </div>
                        </x-table.cell>
                    </x-table.row>
                @endforelse
            </x-table.body>
        </x-table.table>
    </div>

    <!-- Pagination -->
    @if($transfers->hasPages())
        <div class="mt-6">
            {{ $transfers->links() }}
        </div>
    @endif

    <!-- Delete Confirmation Modal (for pending transfers) -->
    <x-delete-confirmation-modal title="Supprimer le transfert" show="showDeleteModal"
        onConfirm="$wire.deleteTransfer(transferToDelete); showDeleteModal = false; transferToDelete = null; transferReference = ''"
        onCancel="showDeleteModal = false; transferToDelete = null; transferReference = ''">
        <p class="text-sm text-gray-500">
            Êtes-vous sûr de vouloir supprimer le transfert <strong x-text="transferReference"></strong> ?
            Cette action est irréversible.
        </p>
    </x-delete-confirmation-modal>

    <!-- Cancel Confirmation Modal (for in-transit transfers) -->
    <x-delete-confirmation-modal title="Annuler le transfert" show="showCancelModal"
        onConfirm="$wire.cancelTransfer(transferToCancel); showCancelModal = false; transferToCancel = null; transferReference = ''"
        onCancel="showCancelModal = false; transferToCancel = null; transferReference = ''">
        <p class="text-sm text-gray-500">
            Êtes-vous sûr de vouloir annuler le transfert <strong x-text="transferReference"></strong> ?
            Le stock sera restauré dans le magasin source.
        </p>
    </x-delete-confirmation-modal>

    <!-- Approve Confirmation Modal -->
    <x-confirmation-modal title="Approuver le transfert" show="showApproveModal" icon-color="green"
        onConfirm="$wire.approveTransfer(transferToApprove); showApproveModal = false; transferToApprove = null; transferReference = ''"
        onCancel="showApproveModal = false; transferToApprove = null; transferReference = ''">
        <p class="text-sm text-gray-500">
            Êtes-vous sûr de vouloir approuver le transfert <strong x-text="transferReference"></strong> ?
            Le transfert passera en statut "En transit" et le stock sera réservé.
        </p>
    </x-confirmation-modal>

    <!-- Create Transfer Modal -->
    @livewire('transfer.transfer-create')
</div>
