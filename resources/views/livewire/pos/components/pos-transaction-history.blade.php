<div x-data="{
        showListModal: @entangle('showModal').live,
        showDetailModal: @entangle('showDetailModal').live
    }"
    x-on:open-detail-modal.window="showDetailModal = true"
    x-on:close-detail-modal.window="showDetailModal = false">
    <!-- Bouton Trigger (pour la top bar) - Style plus visible -->
    <button @click="showListModal = true; $wire.loadTransactions()"
        class="flex items-center gap-2 px-4 py-2 bg-amber-400/90 hover:bg-amber-300 text-amber-900 rounded-xl transition-all duration-200 backdrop-blur-sm hover:scale-105 shadow-lg border border-amber-300/50">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
        </svg>
        <div class="text-left">
            <div class="text-xs font-semibold">📋 Factures</div>
            <div class="text-sm font-black">{{ $totalCount }} vente(s)</div>
        </div>
    </button>

    <!-- Modal Liste des Transactions -->
    <div x-show="showListModal"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4"
        @click="showListModal = false">

        <div @click.stop
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="bg-white rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] flex flex-col overflow-hidden">

            <!-- Header -->
            <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4 flex items-center justify-between flex-shrink-0">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-white/20 rounded-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-black text-white">Factures du jour</h3>
                        <p class="text-xs text-indigo-100">Cliquez pour voir ou réimprimer</p>
                    </div>
                </div>
                <button @click="showListModal = false" class="p-2 hover:bg-white/20 rounded-lg transition-colors flex-shrink-0">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Transactions List -->
            @if(count($transactions) > 0)
                <div class="max-h-[55vh] overflow-y-auto custom-scrollbar divide-y divide-gray-100/80 flex-1"
                    x-data="{
                        init() {
                            this.$el.addEventListener('scroll', () => {
                                if (this.$el.scrollTop + this.$el.clientHeight >= this.$el.scrollHeight - 50) {
                                    $wire.loadMore()
                                }
                            })
                        }
                    }">
                    @foreach($transactions as $transaction)
                        <div class="px-5 py-4 hover:bg-gradient-to-r hover:from-indigo-50/80 hover:to-purple-50/80 transition-all duration-300 group relative">
                            <div class="absolute left-0 top-0 bottom-0 w-1 bg-gradient-to-b from-indigo-500 to-purple-600 rounded-r opacity-0 group-hover:opacity-100 transition-opacity"></div>

                            <div class="flex items-center gap-4">
                                <!-- Icon badge -->
                                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center flex-shrink-0 shadow-lg group-hover:shadow-xl group-hover:scale-105 transition-all duration-300">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                </div>

                                <!-- Details -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex justify-between items-start gap-3">
                                        <div>
                                            <p class="font-bold text-gray-900 text-sm group-hover:text-indigo-600 transition-colors">
                                                {{ $transaction['invoice_number'] ?? 'N/A' }}
                                            </p>
                                            <div class="flex items-center gap-2 mt-1 flex-wrap">
                                                <span class="text-xs text-gray-500 flex items-center gap-1">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                    </svg>
                                                    {{ $transaction['time'] ?? '' }}
                                                </span>
                                                @if(isset($transaction['client']) && $transaction['client'] !== 'Comptant')
                                                    <span class="px-2 py-0.5 bg-indigo-100 text-indigo-700 rounded-full text-xs font-semibold">
                                                        {{ $transaction['client'] }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="text-right flex-shrink-0">
                                            <p class="font-black text-lg bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">
                                                {{ number_format($transaction['total'] ?? 0, 0, ',', ' ') }}
                                                <span class="text-xs">{{ current_currency() }}</span>
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Actions buttons -->
                                <div class="flex-shrink-0 flex items-center gap-2">
                                    <!-- Voir -->
                                    <button wire:click="viewTransaction({{ $transaction['id'] }})"
                                        class="p-2.5 bg-white border border-gray-200 hover:border-indigo-300 hover:bg-indigo-50 rounded-xl text-indigo-600 shadow-sm hover:shadow-md transition-all duration-200"
                                        title="Voir les détails">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </button>

                                    <!-- Imprimer -->
                                    <button wire:click="reprintTransaction({{ $transaction['id'] }})"
                                        class="p-2.5 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl text-white shadow-lg hover:shadow-xl hover:scale-105 transition-all duration-200"
                                        title="Réimprimer la facture">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach

                    <!-- Loading more indicator -->
                    @if($hasMore)
                        <div class="px-5 py-4 text-center" wire:loading.remove wire:target="loadMore">
                            <button wire:click="loadMore" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                                ↓ Charger plus ({{ count($transactions) }}/{{ $totalCount }})
                            </button>
                        </div>
                        <div class="px-5 py-4 text-center" wire:loading wire:target="loadMore">
                            <div class="flex items-center justify-center gap-2 text-indigo-600">
                                <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span class="text-sm font-medium">Chargement...</span>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Footer -->
                <div class="bg-gray-50 px-5 py-4 border-t border-gray-200 flex-shrink-0">
                    <div class="flex items-center justify-between w-full">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow">
                                <span class="text-white font-bold">{{ $totalCount }}</span>
                            </div>
                            <div>
                                <span class="text-sm text-gray-600 font-medium">facture(s) aujourd'hui</span>
                                @if(count($transactions) < $totalCount)
                                    <span class="text-xs text-gray-400 block">{{ count($transactions) }} affichée(s)</span>
                                @endif
                            </div>
                        </div>
                        <button wire:click="loadTransactions"
                            class="px-4 py-2.5 bg-white hover:bg-indigo-50 border border-gray-200 hover:border-indigo-300 text-indigo-700 rounded-xl font-medium text-sm flex items-center gap-2 transition-all duration-200 shadow-sm hover:shadow">
                            <svg class="w-4 h-4" wire:loading.class="animate-spin" wire:target="loadTransactions" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            <span wire:loading.remove wire:target="loadTransactions">Actualiser</span>
                            <span wire:loading wire:target="loadTransactions">Chargement...</span>
                        </button>
                    </div>
                </div>
            @else
                <!-- Empty State -->
                <div class="px-8 py-16 text-center flex-1">
                    <div class="relative w-24 h-24 mx-auto mb-6">
                        <div class="absolute inset-0 bg-gradient-to-br from-indigo-100 to-purple-100 rounded-full animate-pulse"></div>
                        <div class="relative w-full h-full bg-gradient-to-br from-gray-50 to-gray-100 rounded-full flex items-center justify-center shadow-inner">
                            <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                    </div>
                    <h4 class="text-gray-800 font-bold text-lg">Aucune facture aujourd'hui</h4>
                    <p class="text-gray-400 mt-2 text-sm">Les factures apparaîtront ici après chaque vente</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Modal Détails de la Transaction (Aperçu Reçu) - Z-index plus élevé -->
    <div x-show="showDetailModal && $wire.selectedTransaction"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[60] flex items-center justify-center p-4"
        @click="showDetailModal = false; $wire.backToList()">

        <div @click.stop
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="bg-white rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] flex flex-col overflow-hidden">

            @if($selectedTransaction)
            <!-- Header avec gradient -->
            <div class="bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 px-6 py-4 text-white flex-shrink-0">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <button wire:click="backToList" class="p-2 bg-white/20 hover:bg-white/30 rounded-xl transition-all">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                        </button>
                        <div>
                            <h3 class="text-lg font-bold">Détails de la facture</h3>
                            <p class="text-indigo-100 text-sm">{{ $selectedTransaction['invoice_number'] }}</p>
                        </div>
                    </div>
                    <button @click="showDetailModal = false; $wire.backToList()" class="p-2 bg-white/20 hover:bg-white/30 rounded-xl transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Scrollable Content -->
            <div class="flex-1 overflow-y-auto">
                <!-- Informations générales -->
                <div class="px-6 py-4 bg-gray-50 border-b">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <div>
                                <p class="text-xs text-gray-500">Date</p>
                                <p class="font-semibold text-gray-800 text-sm">{{ $selectedTransaction['date'] }} à {{ $selectedTransaction['time'] }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            <div>
                                <p class="text-xs text-gray-500">Caissier</p>
                                <p class="font-semibold text-gray-800 text-sm">{{ $selectedTransaction['cashier'] }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <div>
                                <p class="text-xs text-gray-500">Client</p>
                                <p class="font-semibold text-gray-800 text-sm">{{ $selectedTransaction['client'] }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            <div>
                                <p class="text-xs text-gray-500">Paiement</p>
                                <p class="font-semibold text-gray-800 text-sm">{{ $selectedTransaction['payment_method'] }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Liste des articles -->
                <div class="px-6 py-4">
                    <h4 class="font-bold text-gray-700 mb-3 flex items-center gap-2 text-sm">
                        <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                        Articles ({{ count($selectedTransaction['items']) }})
                    </h4>
                    <div class="space-y-2">
                        @foreach($selectedTransaction['items'] as $item)
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                            <div class="flex-1">
                                <p class="font-medium text-gray-800 text-sm">{{ $item['name'] }}</p>
                                @if($item['variant'])
                                    <p class="text-xs text-gray-500">{{ $item['variant'] }}</p>
                                @endif
                            </div>
                            <div class="text-right">
                                <p class="text-xs text-gray-600">{{ $item['quantity'] }} x {{ number_format($item['unit_price'], 0, ',', ' ') }}</p>
                                <p class="font-bold text-indigo-600 text-sm">{{ number_format($item['subtotal'], 0, ',', ' ') }} {{ current_currency() }}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                <!-- Totaux -->
                <div class="px-6 py-4 bg-gradient-to-br from-gray-50 to-gray-100 border-t">
                    <div class="space-y-2">
                        <div class="flex justify-between text-gray-600 text-sm">
                            <span>Sous-total</span>
                            <span>{{ number_format($selectedTransaction['subtotal'], 0, ',', ' ') }} {{ current_currency() }}</span>
                        </div>
                        @if($selectedTransaction['discount'] > 0)
                        <div class="flex justify-between text-orange-600 text-sm">
                            <span>Remise</span>
                            <span>-{{ number_format($selectedTransaction['discount'], 0, ',', ' ') }} {{ current_currency() }}</span>
                        </div>
                        @endif
                        @if($selectedTransaction['tax'] > 0)
                        <div class="flex justify-between text-gray-600 text-sm">
                            <span>Taxe</span>
                            <span>{{ number_format($selectedTransaction['tax'], 0, ',', ' ') }} {{ current_currency() }}</span>
                        </div>
                        @endif
                        <div class="border-t border-gray-200 pt-2 mt-2">
                            <div class="flex justify-between text-xl font-black">
                                <span class="text-gray-800">TOTAL</span>
                                <span class="bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">
                                    {{ number_format($selectedTransaction['total'], 0, ',', ' ') }} {{ current_currency() }}
                                </span>
                            </div>
                        </div>
                        <div class="flex justify-between text-gray-600 text-sm mt-2">
                            <span>Montant payé</span>
                            <span class="font-semibold">{{ number_format($selectedTransaction['paid_amount'], 0, ',', ' ') }} {{ current_currency() }}</span>
                        </div>
                        @if($selectedTransaction['change'] > 0)
                        <div class="flex justify-between text-green-600 font-medium text-sm">
                            <span>Monnaie rendue</span>
                            <span>{{ number_format($selectedTransaction['change'], 0, ',', ' ') }} {{ current_currency() }}</span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="px-6 py-4 border-t bg-white flex items-center justify-between gap-3 flex-shrink-0">
                <button wire:click="backToList"
                    class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl font-medium text-sm flex items-center gap-2 transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Retour
                </button>
                <button wire:click="printFromDetail"
                    class="px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white rounded-xl font-medium text-sm flex items-center gap-2 transition-all shadow-lg hover:shadow-xl">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    Imprimer
                </button>
            </div>
            @endif
        </div>
    </div>
</div>
