<template>
    <div class="flex flex-col bg-white">
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
                        <p class="text-xs text-gray-500">{{ store.itemCount }} article(s)</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button @click="showClientModal = true" type="button"
                        class="px-2 py-1.5 bg-gradient-to-r from-indigo-50 to-purple-50 hover:from-indigo-100 hover:to-purple-100 border border-indigo-200 rounded-lg transition-all flex items-center gap-2">
                        <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        <span v-if="store.selectedClientId" class="text-xs font-bold text-indigo-900 max-w-[80px] truncate">
                            {{ selectedClientName }}
                        </span>
                        <span v-else class="text-xs font-medium text-gray-600">Client</span>
                    </button>

                    <!-- View Receipt Button -->
                    <button v-if="!store.isEmpty" @click="handleReceiptPreview" type="button"
                        class="px-2 py-1.5 bg-gradient-to-r from-green-50 to-emerald-50 hover:from-green-100 hover:to-emerald-100 border border-green-200 rounded-lg transition-all flex items-center gap-1.5"
                        title="Imprimer le reçu">
                        <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        <span class="text-xs font-medium text-green-700">Reçu</span>
                    </button>

                    <!-- Clear Button -->
                    <button v-if="!store.isEmpty" @click="handleClearCart"
                        class="px-2 py-1 text-xs text-red-600 hover:bg-red-50 font-semibold rounded transition-colors"
                        title="Vider le panier">
                        🗑️
                    </button>
                </div>
            </div>
        </div>

        <!-- Client Modal -->
        <Teleport to="body">
            <div v-if="showClientModal"
                class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4"
                @click="showClientModal = false">
                <div @click.stop
                    class="bg-white rounded-2xl shadow-2xl max-w-md w-full max-h-[90vh] flex flex-col overflow-hidden"
                    style="animation: modalEnter 0.2s ease-out;">

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
                        <div v-if="store.selectedClientId && selectedClientName"
                            class="bg-indigo-50 border-2 border-indigo-200 rounded-lg p-3 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <div>
                                    <p class="text-xs text-indigo-600 font-semibold">Client actuel</p>
                                    <p class="text-sm font-bold text-indigo-900">{{ selectedClientName }}</p>
                                </div>
                            </div>
                            <button @click="store.selectedClientId = null; store.saveToSession()"
                                class="text-red-500 hover:bg-red-50 p-2 rounded-lg transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        <!-- Aucun client sélectionné -->
                        <div v-else class="bg-gray-50 border-2 border-dashed border-gray-300 rounded-lg p-3 text-center">
                            <svg class="w-8 h-8 text-gray-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            <p class="text-sm font-semibold text-gray-600">Aucun client sélectionné</p>
                            <p class="text-xs text-gray-500">Vente comptant (Walk-in)</p>
                        </div>

                        <!-- Liste des clients -->
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Choisir un client</label>
                            <select v-model="store.selectedClientId" @change="store.saveToSession()"
                                class="w-full px-4 py-3 text-sm border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all bg-white">
                                <option :value="null">👤 Vente comptant (Walk-in)</option>
                                <option v-for="client in clients" :key="client.id" :value="client.id">
                                    {{ client.name }}
                                </option>
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
        </Teleport>

        <!-- Receipt Preview Modal -->
        <Teleport to="body">
            <div v-if="showReceiptModal"
                class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4"
                @click="showReceiptModal = false">
                <div @click.stop
                    class="bg-white rounded-2xl shadow-2xl max-w-sm w-full max-h-[90vh] flex flex-col overflow-hidden"
                    style="animation: modalEnter 0.2s ease-out;">

                    <!-- Header -->
                    <div class="bg-gradient-to-r from-green-600 to-emerald-600 px-4 py-3 flex items-center justify-between flex-shrink-0">
                        <div class="flex items-center gap-2">
                            <div class="p-1.5 bg-white/20 rounded-lg">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <h3 class="text-base font-black text-white">Aperçu du Reçu</h3>
                        </div>
                        <button @click="showReceiptModal = false" class="p-1.5 hover:bg-white/20 rounded-lg transition-colors">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Receipt Content (style ticket thermique) -->
                    <div class="flex-1 overflow-y-auto p-4">
                        <div class="bg-gray-50 border-2 border-dashed border-gray-300 rounded-lg p-4 font-mono text-xs">
                            <!-- En-tête du reçu -->
                            <div class="text-center border-b border-gray-300 pb-3 mb-3">
                                <p class="font-bold text-sm">*** APERÇU ***</p>
                                <p class="text-gray-600 mt-1">{{ new Date().toLocaleString('fr-FR') }}</p>
                            </div>

                            <!-- Client -->
                            <div v-if="selectedClientName" class="border-b border-gray-300 pb-2 mb-2">
                                <p><span class="text-gray-500">Client:</span> <span class="font-semibold">{{ selectedClientName }}</span></p>
                            </div>

                            <!-- Articles -->
                            <div class="border-b border-gray-300 pb-2 mb-2">
                                <div v-for="(item, key) in store.cart" :key="key" class="flex justify-between py-1">
                                    <div class="flex-1 min-w-0">
                                        <p class="font-medium truncate">{{ item.product_name || item.name || 'Article' }}</p>
                                        <p class="text-gray-500 text-[10px]">
                                            <span v-if="item.variant_size || item.variant_color">
                                                {{ [item.variant_size, item.variant_color].filter(Boolean).join(' / ') }} -
                                            </span>
                                            {{ item.quantity }} x {{ formatPrice(item.price) }}
                                        </p>
                                    </div>
                                    <p class="font-semibold text-right whitespace-nowrap ml-2">{{ formatPrice(item.price * item.quantity) }}</p>
                                </div>
                            </div>

                            <!-- Totaux -->
                            <div class="space-y-1">
                                <div class="flex justify-between">
                                    <span>Sous-total:</span>
                                    <span>{{ formatPrice(store.subtotal) }}</span>
                                </div>
                                <div v-if="store.discount > 0" class="flex justify-between text-orange-600">
                                    <span>Remise:</span>
                                    <span>-{{ formatPrice(store.discount) }}</span>
                                </div>
                                <div v-if="store.tax > 0" class="flex justify-between">
                                    <span>Taxe:</span>
                                    <span>{{ formatPrice(store.tax) }}</span>
                                </div>
                                <div class="flex justify-between font-bold text-base border-t border-gray-400 pt-2 mt-2">
                                    <span>TOTAL:</span>
                                    <span>{{ formatPrice(store.total) }}</span>
                                </div>
                            </div>

                            <!-- Footer -->
                            <div class="text-center mt-4 pt-3 border-t border-gray-300">
                                <p class="text-gray-500">{{ store.itemCount }} article(s)</p>
                                <p class="font-semibold mt-2">Merci de votre visite !</p>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="bg-gray-50 px-4 py-3 flex gap-2 flex-shrink-0 border-t">
                        <button @click="showReceiptModal = false"
                            class="flex-1 py-2.5 border-2 border-gray-300 text-gray-700 font-bold rounded-xl hover:bg-gray-100 transition-colors text-sm">
                            Fermer
                        </button>
                        <button @click="printReceiptFromPreview"
                            class="flex-1 py-2.5 bg-gradient-to-r from-green-600 to-emerald-600 text-white font-bold rounded-xl hover:from-green-700 hover:to-emerald-700 transition-colors shadow-lg text-sm flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                            </svg>
                            Imprimer
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Cart Items -->
        <div class="px-2 py-2 space-y-1.5 flex-1 overflow-y-auto">
            <!-- Empty Cart Message -->
            <div v-if="store.isEmpty" class="text-center py-8">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gradient-to-br from-gray-100 to-gray-200 mb-3">
                    <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg>
                </div>
                <p class="text-sm font-bold text-gray-400">Panier vide</p>
                <p class="text-xs text-gray-400">Ajoutez des produits</p>
            </div>

            <!-- Cart Items List - Ultra Compact -->
            <div v-for="item in store.cartItems" :key="`${item.key}-${item.price}-${item.quantity}`"
                class="bg-white rounded-lg px-2.5 py-2 shadow-sm hover:shadow-md transition-all duration-200 border border-gray-100">
                <div class="flex items-center gap-2">
                    <!-- Nom et variantes -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-baseline gap-1.5">
                            <h3 class="font-bold text-gray-900 text-xs truncate">{{ item.product_name }}</h3>
                            <span v-if="item.variant_size || item.variant_color" class="text-xs text-gray-400 flex-shrink-0">
                                <span v-if="item.variant_size">{{ item.variant_size }}</span>
                                <span v-if="item.variant_size && item.variant_color">/</span>
                                <span v-if="item.variant_color">{{ item.variant_color }}</span>
                            </span>
                        </div>
                        <!-- Prix -->
                        <div class="flex items-center gap-2 mt-0.5">
                            <button @click="togglePriceEdit(item.key)"
                                class="flex items-center gap-1.5 hover:opacity-80 transition-opacity"
                                title="Cliquez pour négocier le prix">
                                <!-- Prix barré si modifié -->
                                <span v-if="item.price < item.original_price" class="line-through text-gray-400 text-xs font-medium">
                                    {{ item.original_price.toFixed(0) }} {{ currency }}
                                </span>
                                <!-- Prix actuel -->
                                <span :class="item.price < item.original_price ? 'text-green-600 font-bold text-sm' : 'text-gray-600 font-medium text-xs'">
                                    {{ item.price.toFixed(0) }} {{ currency }}
                                </span>
                                <!-- Icône -->
                                <svg v-if="item.price < item.original_price" class="w-3.5 h-3.5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <svg v-else class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Quantité -->
                    <div class="flex items-center gap-1">
                        <button @click="store.decrementQuantity(item.key)"
                            class="w-6 h-6 flex items-center justify-center bg-gray-100 hover:bg-indigo-100 rounded transition-all group"
                            :disabled="item.quantity <= 1">
                            <svg class="w-3 h-3 text-gray-600 group-hover:text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M20 12H4" />
                            </svg>
                        </button>
                        <input type="number" :value="item.quantity"
                            @change="store.updateQuantity(item.key, parseInt($event.target.value))"
                            class="w-10 text-center text-sm font-bold border border-gray-200 rounded py-0.5 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                            min="1" :max="item.stock || 999">
                        <button @click="store.incrementQuantity(item.key)"
                            class="w-6 h-6 flex items-center justify-center bg-gray-100 hover:bg-indigo-100 rounded transition-all group"
                            :disabled="item.quantity >= (item.stock || 999)">
                            <svg class="w-3 h-3 text-gray-600 group-hover:text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 6v12m6-6H6" />
                            </svg>
                        </button>
                    </div>

                    <!-- Total -->
                    <div class="text-right w-20 flex-shrink-0">
                        <div class="text-sm font-black text-indigo-600">
                            {{ (item.price * item.quantity).toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, ' ') }}
                        </div>
                        <div class="text-xs text-gray-400">{{ currency }}</div>
                    </div>

                    <!-- Supprimer -->
                    <button @click="store.removeItem(item.key)"
                        class="p-1 text-red-500 hover:bg-red-50 rounded transition-colors flex-shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Zone de négociation du prix (collapsible) -->
                <div v-show="showPriceEdit[item.key]" class="mt-2 pt-2 border-t border-gray-100">
                    <div class="flex items-center gap-2">
                        <div class="flex-1">
                            <label class="text-xs text-gray-500 mb-1 block">Prix négocié</label>
                            <div class="flex items-center gap-1">
                                <input type="number"
                                    :ref="'priceInput' + item.key"
                                    v-model="priceInputs[item.key]"
                                    class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-green-500 focus:border-green-500"
                                    :min="0"
                                    :max="item.original_price"
                                    step="0.01"
                                    @keydown.enter="applyPriceChange(item.key)">
                                <span class="text-xs text-gray-400">{{ currency }}</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-1 pt-4">
                            <button @click="applyPriceChange(item.key)"
                                class="p-1.5 bg-green-500 hover:bg-green-600 text-white rounded transition-colors"
                                title="Appliquer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </button>
                            <button v-if="item.price < item.original_price"
                                @click="resetPriceChange(item.key)"
                                class="p-1.5 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded transition-colors"
                                title="Rétablir le prix original">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                            </button>
                            <button @click="showPriceEdit[item.key] = false"
                                class="p-1.5 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded transition-colors"
                                title="Annuler">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <p class="text-xs text-gray-400 mt-1">
                        Prix original: <span class="font-semibold">{{ formatPrice(item.original_price) }}</span>
                    </p>
                </div>
            </div>
        </div>

        <!-- Cart Summary - Ultra Compact -->
        <div class="border-t-2 border-gray-300 bg-gradient-to-b from-gray-50 to-white px-3 py-2.5">
            <!-- Discount & Tax en ligne compacte -->
            <div class="flex gap-2 mb-2">
                <div class="flex-1">
                    <div class="flex items-center gap-1">
                        <label class="text-xs font-semibold text-gray-600 whitespace-nowrap">Remise</label>
                        <input type="number"
                            v-model.number="store.globalDiscount"
                            @change="store.setGlobalDiscount($event.target.value)"
                            placeholder="0"
                            class="w-full px-2 py-1 text-xs border border-gray-200 rounded focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 font-semibold"
                            min="0" step="100">
                    </div>
                    <p class="text-[10px] text-gray-400 mt-0.5">Remise globale</p>
                </div>

                <div class="flex-1">
                    <div class="flex items-center gap-1">
                        <label class="text-xs font-semibold text-gray-600 whitespace-nowrap">Taxe</label>
                        <input type="number"
                            v-model.number="store.globalTax"
                            @change="store.setGlobalTax($event.target.value)"
                            placeholder="0"
                            class="w-full px-2 py-1 text-xs border border-gray-200 rounded focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 font-semibold"
                            min="0" step="100">
                    </div>
                    <p class="text-[10px] text-gray-400 mt-0.5">Taxe globale</p>
                </div>
            </div>

            <div class="space-y-1">
                <!-- Items Count -->
                <div class="flex justify-between text-xs">
                    <span class="text-gray-600">Articles</span>
                    <span class="font-bold text-gray-800">{{ store.itemCount }}</span>
                </div>
                <!-- Total Quantity -->
                <div class="flex justify-between text-xs">
                    <span class="text-gray-600">Quantité</span>
                    <span class="font-bold text-gray-800">{{ store.totalQuantity }}</span>
                </div>
                <!-- Subtotal -->
                <div class="flex justify-between text-xs">
                    <span class="text-gray-600">Sous-total</span>
                    <span class="font-semibold text-gray-700">{{ formatPrice(store.subtotal) }}</span>
                </div>
                <!-- Discount -->
                <div v-if="store.discount > 0" class="flex justify-between text-xs text-green-600">
                    <span>Remise</span>
                    <span class="font-semibold">-{{ formatPrice(store.discount) }}</span>
                </div>
                <!-- Tax -->
                <div v-if="store.tax > 0" class="flex justify-between text-xs">
                    <span class="text-gray-600">TVA</span>
                    <span class="font-semibold text-gray-700">{{ formatPrice(store.tax) }}</span>
                </div>
            </div>
            <!-- Total Line -->
            <div class="flex justify-between items-center pt-2 mt-2 border-t border-gray-300">
                <span class="text-sm font-black text-gray-800 uppercase">Total</span>
                <div class="text-right">
                    <div class="text-2xl font-black text-indigo-600 leading-tight">
                        {{ store.total.toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, ' ') }}
                    </div>
                    <div class="text-xs text-gray-500">{{ currency }}</div>
                </div>
            </div>
        </div>

        <!-- Payment Buttons - Ultra Compact (2 boutons) -->
        <div v-if="!store.isEmpty" class="px-3 py-3 bg-white border-t-2 border-gray-300 space-y-2">
            <!-- Boutons d'action -->
            <div class="flex gap-2">
                <!-- Bouton Valider seul (sans impression) -->
                <button @click="handleProcessSale(false)"
                    :disabled="store.isEmpty || store.isProcessing"
                    class="flex-1 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-bold rounded-lg hover:from-blue-700 hover:to-indigo-700 transition-all shadow-lg disabled:opacity-50 disabled:cursor-not-allowed border-2 border-blue-700">
                    <span class="flex items-center justify-center gap-2">
                        <svg v-if="!store.isProcessing" class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <svg v-else class="w-5 h-5 flex-shrink-0 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="text-sm uppercase tracking-wide font-black">
                            {{ store.isProcessing ? 'EN COURS...' : 'VALIDER' }}
                        </span>
                    </span>
                </button>

                <!-- Bouton Valider & Imprimer -->
                <button @click="handleProcessSale(true)"
                    :disabled="store.isEmpty || store.isProcessing"
                    class="flex-[2] py-3 bg-gradient-to-r from-green-600 to-emerald-600 text-white font-black rounded-lg hover:from-green-700 hover:to-emerald-700 transition-all shadow-xl disabled:opacity-50 disabled:cursor-not-allowed border-2 border-green-700">
                    <span class="flex items-center justify-center gap-2">
                        <svg v-if="!store.isProcessing" class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        <svg v-else class="w-5 h-5 flex-shrink-0 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="text-sm uppercase tracking-wide font-black">
                            {{ store.isProcessing ? 'EN COURS...' : 'IMPRIMER' }}
                        </span>
                    </span>
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, reactive } from 'vue'
import { usePosStore } from '../stores/posStore'

const props = defineProps({
    clients: {
        type: Array,
        default: () => []
    },
    currency: {
        type: String,
        default: 'USD'
    }
})

const store = usePosStore()
const showClientModal = ref(false)
const showReceiptModal = ref(false)
const showPriceEdit = reactive({})
const priceInputs = reactive({})

// Load cart from session on mount
store.loadFromSession()

const selectedClientName = computed(() => {
    if (!store.selectedClientId) return ''
    const client = props.clients.find(c => c.id == store.selectedClientId)
    return client?.name || ''
})

const formatPrice = (value) => {
    return parseFloat(value).toFixed(2) + ' ' + props.currency
}

// Handle clear cart with confirmation
const handleClearCart = () => {
    if (confirm('Êtes-vous sûr de vouloir vider le panier ?')) {
        store.clear()
    }
}

// Handle receipt preview - Show modal with receipt preview
const handleReceiptPreview = () => {
    if (store.isEmpty) {
        if (window.Alpine?.store('toast')) {
            window.Alpine.store('toast').warning('Le panier est vide', 3000)
        }
        return
    }
    showReceiptModal.value = true
}

// Print receipt from preview modal
const printReceiptFromPreview = async () => {
    try {
        const printData = {
            invoice_number: 'APERÇU-' + Date.now(),
            date: new Date().toLocaleString('fr-FR'),
            items: Object.entries(store.cart).map(([key, item]) => ({
                name: item.name || 'Article',
                quantity: item.quantity || 1,
                unit_price: item.price || 0,
                total: (item.price || 0) * (item.quantity || 1)
            })),
            subtotal: store.subtotal,
            discount: store.discount || 0,
            tax: store.tax || 0,
            total: store.total,
            paid: store.total,
            change: 0,
            currency: props.currency || 'CDF',
            client: selectedClientName.value || null,
            cashier: 'Caissier',
            isPreview: true
        }

        console.log('[POS] Preview receipt data:', JSON.stringify(printData, null, 2))

        if (typeof window.printWithRetry === 'function') {
            await window.printWithRetry(printData)
            console.log('[POS] Preview receipt printed successfully')
            showReceiptModal.value = false
            if (window.Alpine?.store('toast')) {
                window.Alpine.store('toast').success('Reçu imprimé !', 2000)
            }
        } else if (typeof window.thermalPrinter !== 'undefined') {
            await window.thermalPrinter.printReceipt(printData)
            console.log('[POS] Preview receipt printed successfully')
            showReceiptModal.value = false
            if (window.Alpine?.store('toast')) {
                window.Alpine.store('toast').success('Reçu imprimé !', 2000)
            }
        } else {
            console.warn('[POS] QZ Tray thermal printer not available')
            if (window.Alpine?.store('toast')) {
                window.Alpine.store('toast').warning('Imprimante thermique non disponible', 3000)
            }
        }
    } catch (error) {
        console.error('[POS] Preview print error:', error)
        if (window.Alpine?.store('toast')) {
            window.Alpine.store('toast').error('Erreur d\'impression: ' + error.message, 5000)
        }
    }
}

// Price edit methods
const togglePriceEdit = (key) => {
    showPriceEdit[key] = !showPriceEdit[key]
    if (showPriceEdit[key]) {
        // Initialize price input with current price
        priceInputs[key] = store.cart[key].price
        console.log('[PosCart] Toggle price edit for', key, 'current price:', store.cart[key].price)
    }
}

const applyPriceChange = (key) => {
    const inputValue = priceInputs[key]
    const newPrice = parseFloat(inputValue)

    if (isNaN(newPrice) || newPrice <= 0) {
        // Afficher un toast d'erreur
        if (window.Alpine?.store('toast')) {
            window.Alpine.store('toast').error('Prix invalide')
        }
        return
    }

    // Vérifier le prix minimum = prix original - remise max autorisée
    const maxDiscountAmount = store.cart[key]?.max_discount_amount || 0
    const originalPrice = store.cart[key]?.original_price || 0
    const minPrice = maxDiscountAmount
    if (newPrice < minPrice) {
        console.warn('[PosCart] Prix trop bas. Minimum:', minPrice)
        if (window.Alpine?.store('toast')) {
            window.Alpine.store('toast').error(`Réduction max autorisée : ${maxDiscountAmount.toFixed(2)} ${props.currency}`)
        }
        return
    }

    if (newPrice > originalPrice) {
        if (window.Alpine?.store('toast')) {
            window.Alpine.store('toast').error(`Prix maximum : ${originalPrice.toFixed(2)} ${props.currency}`)
        }
        return
    }

    const success = store.updatePrice(key, newPrice)

    if (success) {
        console.log('[PosCart] Price updated successfully. New price:', store.cart[key]?.price)
        showPriceEdit[key] = false

        if (window.Alpine?.store('toast')) {
            window.Alpine.store('toast').success('Prix mis à jour')
        }
    } else {
        console.error('[PosCart] Failed to update price')
        if (window.Alpine?.store('toast')) {
            window.Alpine.store('toast').error('Échec de la mise à jour du prix')
        }
    }
}

const resetPriceChange = (key) => {
    console.log('[PosCart] Reset price for', key)
    store.resetPrice(key)
    showPriceEdit[key] = false
}

// Handle process sale
const handleProcessSale = async (shouldPrint = false) => {
    if (store.isEmpty || store.isProcessing) return

    const result = await store.processSale('cash') // Toujours en espèces par défaut

    if (result.success) {
        console.log('[POS] Sale completed:', result.sale)

        // Show success notification
        if (window.Alpine?.store('toast')) {
            const message = shouldPrint
                ? 'Vente enregistrée ! Impression en cours...'
                : 'Vente enregistrée avec succès !'
            window.Alpine.store('toast').success(message, 4000)
        }

        // Si on doit imprimer, déclencher l'impression QZ Tray
        if (shouldPrint && result.sale) {
            console.log('[POS] Printing receipt... Full result:', JSON.stringify(result.sale, null, 2))
            try {
                // Transformer les données au format QZ Tray
                const printData = {
                    invoice_number: result.sale.sale?.reference || result.sale.invoice?.invoice_number || 'N/A',
                    date: new Date().toLocaleString('fr-FR'),
                    items: (result.sale.sale?.items || []).map(item => {
                        const quantity = parseInt(item.quantity) || 1
                        const subtotal = parseFloat(item.subtotal) || 0
                        // Calculer le prix unitaire depuis le subtotal
                        const unitPrice = quantity > 0 ? (subtotal / quantity) : 0

                        console.log('[POS] Item:', item.product_name, 'calculated unit_price:', unitPrice, 'quantity:', quantity, 'subtotal:', subtotal)
                        return {
                            name: item.product_name || 'Article',
                            quantity: quantity,
                            unit_price: unitPrice,
                            total: subtotal
                        }
                    }),
                    subtotal: parseFloat(result.sale.subtotal) || 0,
                    discount: parseFloat(result.sale.discount) || 0,
                    tax: parseFloat(result.sale.tax) || 0,
                    total: parseFloat(result.sale.total) || 0,
                    paid: parseFloat(result.sale.paid_amount) || parseFloat(result.sale.total) || 0,
                    change: parseFloat(result.sale.change) || 0,
                    currency: result.sale.currency || 'CDF',
                    client: result.sale.sale?.client?.name || null,
                    cashier: result.sale.sale?.cashier?.name || 'N/A'
                }

                console.log('[POS] Print data formatted:', JSON.stringify(printData, null, 2))

                // Vérifier si l'imprimante thermique est disponible
                if (typeof window.printWithRetry === 'function') {
                    await window.printWithRetry(printData)
                    console.log('[POS] Receipt printed successfully')
                } else if (typeof window.thermalPrinter !== 'undefined') {
                    await window.thermalPrinter.printReceipt(printData)
                    console.log('[POS] Receipt printed successfully')
                } else {
                    console.warn('[POS] QZ Tray thermal printer not available')
                    if (window.Alpine?.store('toast')) {
                        window.Alpine.store('toast').warning('Imprimante thermique non disponible', 3000)
                    }
                }
            } catch (error) {
                console.error('[POS] Print error:', error)
                if (window.Alpine?.store('toast')) {
                    window.Alpine.store('toast').error('Erreur d\'impression: ' + error.message, 5000)
                }
            }
        }
    } else {
        // Show error notification
        console.error('[POS] Sale failed:', result.error)
        if (window.Alpine?.store('toast')) {
            window.Alpine.store('toast').error(result.error || 'Erreur lors de l\'enregistrement de la vente', 5000)
        }
    }
}
</script>
