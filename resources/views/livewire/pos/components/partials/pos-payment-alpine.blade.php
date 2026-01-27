<!-- Section Paiement Alpine.js - DESIGN IDENTIQUE À PosPaymentPanel -->
<div class="border-t-2 border-gray-200 bg-white px-3 py-2 space-y-2" x-data="{ discount: 0 }">
    <!-- Discount & Tax en ligne compacte -->
    <div class="flex gap-2">
        <div class="flex-1">
            <div class="flex items-center gap-1">
                <label class="text-xs font-semibold text-gray-600 whitespace-nowrap">Remise</label>
                <input type="number" x-model.number="discount" placeholder="0"
                    class="w-full px-2 py-1 text-xs border border-gray-200 rounded focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 font-semibold"
                    min="0" step="100">
            </div>
            <p class="text-[10px] text-gray-400 mt-0.5">Remise globale</p>
        </div>

        <div class="flex-1 flex items-center gap-1" title="Taxe non configurée">
            <label class="text-xs font-semibold text-gray-400 whitespace-nowrap">Taxe</label>
            <div class="w-full px-2 py-1 text-xs border border-gray-100 rounded bg-gray-50 text-gray-400 cursor-not-allowed">
                Non configuré
            </div>
        </div>
    </div>

    <!-- Totals ultra-compact -->
    <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-lg p-2 space-y-1">
        <div class="flex justify-between text-xs">
            <span class="text-gray-600">Sous-total</span>
            <span class="font-bold text-gray-900" x-text="($store.posCart?.subtotal || 0).toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, ' ')"></span>
        </div>
        <div x-show="discount > 0" class="flex justify-between text-xs">
            <span class="text-green-600">Remise</span>
            <span class="font-bold text-green-600" x-text="'-' + discount.toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, ' ')"></span>
        </div>
        <div x-show="$store.posCart?.tax > 0" class="flex justify-between text-xs">
            <span class="text-gray-600">Taxe</span>
            <span class="font-bold text-gray-900" x-text="'+' + ($store.posCart?.tax || 0).toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, ' ')"></span>
        </div>
        <div class="flex justify-between text-lg font-black pt-1 border-t border-gray-300">
            <span class="text-gray-900">TOTAL</span>
            <span class="text-indigo-600" x-text="(($store.posCart?.subtotal || 0) - discount).toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, ' ')"></span>
        </div>
    </div>

    <!-- Boutons d'action - Style amélioré avec meilleure visibilité -->
    <div class="flex gap-2">
        <!-- Bouton Valider seul (sans impression) -->
        <button @click="console.log('Validate only'); window.Alpine.store('toast').show('Fonctionnalité en développement', 'info')"
            :disabled="($store.posCart?.isEmpty ?? true) || ($store.posCart?.isProcessing ?? false)"
            class="flex-1 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-bold rounded-lg hover:from-blue-700 hover:to-indigo-700 transition-all shadow-lg disabled:opacity-50 disabled:cursor-not-allowed border-2 border-blue-700">
            <span class="flex items-center justify-center gap-2">
                <svg x-show="!($store.posCart?.isProcessing ?? false)" class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M5 13l4 4L19 7" />
                </svg>
                <svg x-show="$store.posCart?.isProcessing ?? false" class="w-5 h-5 flex-shrink-0 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-sm uppercase tracking-wide font-black" x-text="($store.posCart?.isProcessing ?? false) ? 'EN COURS...' : 'VALIDER'"></span>
            </span>
        </button>

        <!-- Bouton Valider & Imprimer -->
        <button @click="console.log('Validate and print'); window.Alpine.store('toast').show('Fonctionnalité en développement', 'info')"
            :disabled="($store.posCart?.isEmpty ?? true) || ($store.posCart?.isProcessing ?? false)"
            class="flex-[2] py-3 bg-gradient-to-r from-green-600 to-emerald-600 text-white font-black rounded-lg hover:from-green-700 hover:to-emerald-700 transition-all shadow-xl disabled:opacity-50 disabled:cursor-not-allowed border-2 border-green-700">
            <span class="flex items-center justify-center gap-2">
                <svg x-show="!($store.posCart?.isProcessing ?? false)" class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                <svg x-show="$store.posCart?.isProcessing ?? false" class="w-5 h-5 flex-shrink-0 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-sm uppercase tracking-wide font-black" x-text="($store.posCart?.isProcessing ?? false) ? 'EN COURS...' : 'IMPRIMER'"></span>
            </span>
        </button>
    </div>
</div>
