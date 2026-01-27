<div class="min-h-screen flex flex-col bg-gradient-to-br from-gray-50 to-gray-100"
    x-data="cashRegisterModular()"
    wire:ignore.self>

    <!-- Toast Notifications -->
    <x-toast />

    <!-- Top Bar -->
    <div class="bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 text-white px-6 py-3 shadow-xl relative overflow-hidden flex-shrink-0">
        <div class="absolute inset-0 bg-black/10"></div>
        <div class="relative flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="p-2 bg-white/20 rounded-xl backdrop-blur-sm">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl font-bold tracking-tight">Point de Vente <span class="text-xs bg-white/30 px-2 py-0.5 rounded-full ml-2">Modulaire</span></h1>
                    <p class="text-xs text-indigo-100 flex items-center gap-2">
                        <span class="px-2 py-0.5 bg-white/20 rounded-full text-xs font-semibold">Caisse #{{ auth()->id() }}</span>
                        <span>{{ auth()->user()->name }}</span>
                    </p>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <!-- Statistiques du jour -->
                <button wire:click="toggleStats" @click="showStatsPanel = !showStatsPanel"
                    class="flex items-center gap-2 px-3 py-1.5 bg-white/20 hover:bg-white/30 rounded-xl transition-all duration-200 backdrop-blur-sm hover:scale-105">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    <div class="text-left">
                        <div class="text-xs opacity-80">CA Aujourd'hui</div>
                        <div class="text-sm font-bold">@currency($todayStats['revenue'])</div>
                    </div>
                </button>

                <!-- Bouton Factures du jour -->
                <livewire:pos.components.pos-transaction-history />

                <div class="text-right">
                    <div class="text-xs text-indigo-100 font-medium">{{ now()->format('d/m/Y') }}</div>
                    <div class="text-lg font-bold tabular-nums" x-ref="clock">{{ now()->format('H:i:s') }}</div>
                </div>

                <!-- Lien vers version Alpine optimisée -->
                <a href="{{ route('pos.cash-register.alpine') }}" wire:navigate
                    class="px-3 py-1.5 bg-green-400/30 hover:bg-green-400/40 rounded-xl transition-all duration-200 text-xs font-semibold flex items-center gap-1">
                    ⚡ Version Rapide
                </a>

                <a href="{{ route('dashboard') }}" wire:navigate
                    class="p-2 bg-white/20 hover:bg-white/30 rounded-xl transition-all duration-200 backdrop-blur-sm hover:scale-105">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </a>
            </div>
        </div>
    </div>

    <!-- Stats Panel (Collapsible) -->
    <div x-show="showStatsPanel" x-collapse class="bg-white border-b shadow-sm">
        <div class="max-w-7xl mx-auto px-6 py-4">
            <div class="grid grid-cols-4 gap-4">
                <div class="bg-gradient-to-br from-green-50 to-emerald-100 rounded-xl p-4 border border-green-200">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-green-500 rounded-lg">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-green-600 font-medium">Chiffre d'affaires</p>
                            <p class="text-xl font-black text-green-700">{{ number_format($todayStats['revenue'], 0, ',', ' ') }} <span class="text-sm">{{ current_currency() }}</span></p>
                        </div>
                    </div>
                </div>
                <div class="bg-gradient-to-br from-blue-50 to-indigo-100 rounded-xl p-4 border border-blue-200">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-blue-500 rounded-lg">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-blue-600 font-medium">Ventes</p>
                            <p class="text-xl font-black text-blue-700">{{ $todayStats['sales_count'] }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-gradient-to-br from-purple-50 to-pink-100 rounded-xl p-4 border border-purple-200">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-purple-500 rounded-lg">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-purple-600 font-medium">Transactions</p>
                            <p class="text-xl font-black text-purple-700">{{ $todayStats['transactions'] }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-gradient-to-br from-orange-50 to-amber-100 rounded-xl p-4 border border-orange-200">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-orange-500 rounded-lg">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-orange-600 font-medium">Panier moyen</p>
                            <p class="text-xl font-black text-orange-700">
                                @if($todayStats['sales_count'] > 0)
                                    {{ number_format($todayStats['revenue'] / $todayStats['sales_count'], 0, ',', ' ') }} <span class="text-sm">{{ current_currency() }}</span>
                                @else
                                    0 <span class="text-sm">{{ current_currency() }}</span>
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content - Modular Components -->
    <div class="flex-1 flex overflow-hidden" style="height: calc(100vh - 64px);">
        <!-- Left: Products Grid -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <livewire:pos.components.pos-product-grid />
        </div>

        <!-- Right: Cart + Payment -->
        <div class="w-[520px] bg-gradient-to-b from-white to-gray-50 border-l-2 border-gray-200 shadow-2xl overflow-y-auto custom-scrollbar" style="height: calc(100vh - 64px);">
            <!-- Cart Component -->
            <livewire:pos.components.pos-cart />

            <!-- Payment Component -->
            <livewire:pos.components.pos-payment-panel />
        </div>
    </div>

    <!-- Keyboard Shortcuts Handler -->
    <div x-data @keydown.window="handleKeyboard($event)"></div>

    <!-- Alpine.js Component -->
    <script>
        function cashRegisterModular() {
            return {
                showStatsPanel: false,

                init() {
                    // Horloge en temps réel
                    this.updateClock();
                    setInterval(() => this.updateClock(), 1000);
                },

                updateClock() {
                    const clock = this.$refs.clock;
                    if (clock) {
                        const now = new Date();
                        clock.textContent = now.toLocaleTimeString('fr-FR');
                    }
                },

                handleKeyboard(event) {
                    // F2 - Focus recherche
                    if (event.key === 'F2') {
                        event.preventDefault();
                        Livewire.dispatch('focus-search');
                    }
                    // F4 - Vider panier
                    if (event.key === 'F4') {
                        event.preventDefault();
                        Livewire.dispatch('trigger-clear-cart');
                    }
                    // F9 - Valider paiement
                    if (event.key === 'F9') {
                        event.preventDefault();
                        Livewire.dispatch('trigger-payment');
                    }
                }
            }
        }
    </script>

    <!-- Printer Scripts -->
    @include('livewire.pos.partials.printer-scripts')

    <style>
        [x-cloak] { display: none !important; }

        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #c7c7c7;
            border-radius: 3px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
    </style>
</div>
