<!-- Composant Panier Alpine.js - DESIGN IDENTIQUE À PosCart -->
<div class="flex flex-col bg-white" x-data="{
    showClientModal: false,
    cartReady: false,
    checkCartReady() {
        if ($store.posCart && typeof $store.posCart.cart !== 'undefined') {
            this.cartReady = true;
        } else {
            setTimeout(() => this.checkCartReady(), 50);
        }
    }
}" x-init="checkCartReady()">
    <!-- Cart Header Compact -->
    <div class="px-3 py-2 border-b border-gray-200 bg-white sticky top-0 z-10 flex-shrink-0">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="p-1 bg-indigo-100 rounded">
                    <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-sm font-bold text-gray-900">Panier</h2>
                    <p class="text-xs text-gray-500" x-text="($store.posCart?.itemCount || 0) + ' article(s)'"></p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <!-- Client Button Compact -->
                <button @click="showClientModal = true" type="button"
                    class="px-2 py-1.5 bg-gradient-to-r from-indigo-50 to-purple-50 hover:from-indigo-100 hover:to-purple-100 border border-indigo-200 rounded-lg transition-all flex items-center gap-2 group">
                    <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <template x-if="$store.posCart?.selectedClientId">
                        <span class="text-xs font-bold text-indigo-900 max-w-[80px] truncate"
                            x-text="@json($clients).find(c => c.id == $store.posCart?.selectedClientId)?.name || 'Client'"></span>
                    </template>
                    <template x-if="!$store.posCart?.selectedClientId">
                        <span class="text-xs font-medium text-gray-600">Client</span>
                    </template>
                </button>
                <!-- View Receipt Button -->
                <button @click="window.location.href = '#'" type="button"
                    x-show="false"
                    class="px-2 py-1.5 bg-gradient-to-r from-purple-50 to-pink-50 hover:from-purple-100 hover:to-pink-100 border border-purple-200 rounded-lg transition-all flex items-center gap-1.5 group"
                    title="Voir le reçu">
                    <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <span class="text-xs font-medium text-purple-600">Reçu</span>
                </button>
                <button @click="$store.posCart?.clear()"
                    x-show="$store.posCart && !$store.posCart.isEmpty"
                    class="px-2 py-1 text-xs text-red-600 hover:bg-red-50 font-semibold rounded transition-colors"
                    title="Vider le panier">
                    🗑️
                </button>
            </div>
        </div>
    </div>

    <!-- Client Selection Modal -->
    <div x-show="showClientModal"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4"
        @click="showClientModal = false">

        <div @click.stop
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="bg-white rounded-2xl shadow-2xl max-w-md w-full max-h-[90vh] flex flex-col overflow-hidden">

            <!-- Header -->
            <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4 flex items-center justify-between flex-shrink-0">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-white/20 rounded-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-black text-white">Sélectionner un client</h3>
                        <p class="text-xs text-indigo-100">Optionnel - Laissez vide pour vente comptant</p>
                    </div>
                </div>
                <button @click="showClientModal = false" class="p-2 hover:bg-white/20 rounded-lg transition-colors flex-shrink-0">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Body -->
            <div class="p-6 space-y-4 overflow-y-auto flex-1">
                <!-- Client sélectionné actuel -->
                <template x-if="$store.posCart?.selectedClientId">
                    <div class="bg-indigo-50 border-2 border-indigo-200 rounded-lg p-3 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div>
                                <p class="text-xs text-indigo-600 font-semibold">Client actuel</p>
                                <p class="text-sm font-bold text-indigo-900" x-text="@json($clients).find(c => c.id == $store.posCart?.selectedClientId)?.name || ''"></p>
                            </div>
                        </div>
                        <button @click="if ($store.posCart) { $store.posCart.selectedClientId = null; $store.posCart.saveToSession(); }" class="text-red-500 hover:bg-red-50 p-2 rounded-lg transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </template>
                <template x-if="!$store.posCart?.selectedClientId">
                    <div class="bg-gray-50 border-2 border-dashed border-gray-300 rounded-lg p-3 text-center">
                        <svg class="w-8 h-8 text-gray-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        <p class="text-sm font-semibold text-gray-600">Aucun client sélectionné</p>
                        <p class="text-xs text-gray-500">Vente comptant (Walk-in)</p>
                    </div>
                </template>

                <!-- Liste des clients -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Choisir un client</label>
                    <select :value="$store.posCart?.selectedClientId || ''"
                        @change="if ($store.posCart) { $store.posCart.selectedClientId = $event.target.value; $store.posCart.saveToSession(); }"
                        class="w-full px-4 py-3 text-sm border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all bg-white">
                        <option value="">👤 Vente comptant (Walk-in)</option>
                        @foreach ($clients as $client)
                            <option value="{{ $client['id'] }}">{{ $client['name'] }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Footer -->
            <div class="bg-gray-50 px-6 py-4 flex gap-3 flex-shrink-0">
                <button @click="showClientModal = false"
                    class="flex-1 py-2.5 border-2 border-gray-300 text-gray-700 font-bold rounded-xl hover:bg-gray-100 transition-colors">
                    Annuler
                </button>
                <button @click="showClientModal = false"
                    class="flex-1 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-bold rounded-xl hover:from-indigo-700 hover:to-purple-700 transition-colors shadow-lg">
                    Confirmer
                </button>
            </div>
        </div>
    </div>

    <!-- Liste des articles - avec scroll -->
    <div class="px-3 py-2 overflow-y-auto flex-1 space-y-2">
        <!-- Message panier vide -->
        <div x-show="$store.posCart && $store.posCart.isEmpty"
            class="text-center py-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gradient-to-br from-gray-100 to-gray-200 mb-3">
                <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                </svg>
            </div>
            <p class="text-sm font-bold text-gray-400">Panier vide</p>
            <p class="text-xs text-gray-400">Ajoutez des produits</p>
        </div>

        <!-- Articles du panier - STRUCTURE IDENTIQUE À PosCart -->
        <div x-show="cartReady">
            <template x-for="[key, item] in Object.entries($store.posCart?.cart || {})" :key="key">
                <div class="bg-gray-50 rounded-lg p-3 border border-gray-200 hover:border-blue-300 transition">
                <div class="flex items-start gap-3">
                    <!-- Info produit -->
                    <div class="flex-1 min-w-0">
                        <h3 class="font-semibold text-gray-800 text-sm truncate"
                            x-text="item?.product_name || ''"></h3>
                        <p class="text-xs text-gray-500"
                            x-show="item && (item.variant_size || item.variant_color)">
                            <span x-show="item?.variant_size" x-text="item?.variant_size || ''"></span>
                            <span x-show="item?.variant_size && item?.variant_color"> • </span>
                            <span x-show="item?.variant_color" x-text="item?.variant_color || ''"></span>
                        </p>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="text-sm font-bold text-blue-600"
                                x-text="item ? ((item.price * item.quantity).toFixed(2) + ' {{ current_currency() }}') : ''">
                            </span>
                            <span x-show="item && item.price < item.original_price"
                                class="text-xs text-gray-400 line-through"
                                x-text="item ? ((item.original_price * item.quantity).toFixed(2) + ' {{ current_currency() }}') : ''">
                            </span>
                        </div>
                    </div>

                    <!-- Contrôles quantité -->
                    <div class="flex flex-col items-center gap-1">
                        <button @click="$store.posCart?.decrementQuantity(key)"
                            class="w-7 h-7 bg-red-500 hover:bg-red-600 text-white rounded-full flex items-center justify-center transition">
                            <span class="text-lg font-bold leading-none">−</span>
                        </button>
                        <input type="number"
                            :value="item?.quantity || 1"
                            @change="$store.posCart?.updateQuantity(key, parseInt($event.target.value) || 1)"
                            min="1"
                            :max="item?.stock || 999"
                            class="w-14 text-center border border-gray-300 rounded py-1 text-sm font-semibold">
                        <button @click="$store.posCart?.incrementQuantity(key)"
                            class="w-7 h-7 bg-green-500 hover:bg-green-600 text-white rounded-full flex items-center justify-center transition">
                            <span class="text-lg font-bold leading-none">+</span>
                        </button>
                    </div>

                    <!-- Bouton supprimer -->
                    <button @click="$store.posCart?.removeItem(key)"
                        class="text-red-500 hover:text-red-700 p-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Prix unitaire éditable -->
                <div class="mt-2 pt-2 border-t border-gray-200" x-data="{ editing: false }">
                    <div class="flex items-center justify-between">
                        <label class="text-xs text-gray-600">Prix unitaire :</label>
                        <button @click="editing = !editing"
                            class="text-xs text-blue-600 hover:text-blue-700">
                            <span x-show="!editing">✏️ Modifier</span>
                            <span x-show="editing">✓ OK</span>
                        </button>
                    </div>
                    <div x-show="editing" x-collapse>
                        <input type="number"
                            :value="item?.price || 0"
                            @change="$store.posCart?.updatePrice(key, parseFloat($event.target.value)); editing = false"
                            step="0.01"
                            :min="item ? (item.original_price - item.max_discount_amount) : 0"
                            :max="item?.original_price || 0"
                            class="w-full text-sm border border-gray-300 rounded px-2 py-1 mt-1">
                        <p class="text-xs text-gray-400 mt-1">
                            Min: <span x-text="item ? (item.original_price - item.max_discount_amount).toFixed(2) : '0.00'"></span>
                            {{ current_currency() }} |
                            Max: <span x-text="item ? item.original_price.toFixed(2) : '0.00'"></span>
                            {{ current_currency() }}
                        </p>
                    </div>
                </div>
            </div>
            </template>
        </div>
    </div>

    <!-- Résumé -->
    <div x-show="$store.posCart && !$store.posCart.isEmpty"
        class="mt-4 pt-4 border-t-2 border-gray-300 space-y-2 sticky bottom-0 bg-white">
        <div class="flex justify-between text-sm">
            <span class="text-gray-600">Sous-total</span>
            <span class="font-semibold"
                x-text="($store.posCart?.subtotal || 0).toFixed(2) + ' {{ current_currency() }}'">
            </span>
        </div>
        <div x-show="($store.posCart?.discount || 0) > 0"
            class="flex justify-between text-sm text-green-600">
            <span>Remise</span>
            <span x-text="'- ' + ($store.posCart?.discount || 0).toFixed(2) + ' {{ current_currency() }}'">
            </span>
        </div>
        <div class="flex justify-between text-lg font-bold text-gray-900 pt-2 border-t">
            <span>TOTAL</span>
            <span class="text-blue-600"
                x-text="($store.posCart?.total || 0).toFixed(2) + ' {{ current_currency() }}'">
            </span>
        </div>
    </div>
</div>
