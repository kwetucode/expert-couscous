@if($showAddModal)
<div x-data="{ show: true }"
     x-show="show"
     x-cloak
     x-init="document.body.style.overflow = 'hidden'"
     x-on:keydown.escape.window="$wire.closeAddModal()"
     class="fixed inset-0 z-50 overflow-hidden"
     aria-labelledby="modal-title"
     role="dialog"
     aria-modal="true">

    <!-- Backdrop -->
    <div
        x-on:click="$wire.closeAddModal()"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm">
    </div>

    <!-- Modal Container -->
    <div class="fixed inset-0 flex items-center justify-center p-4 pointer-events-none">
        <div
            x-on:click.stop
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="relative bg-white rounded-2xl shadow-2xl transform transition-all w-full sm:max-w-2xl pointer-events-auto">

    <div class="bg-white rounded-xl shadow-xl">
        <!-- Modal Header -->
        <div class="flex items-center justify-between p-6 border-b border-gray-200">
            <div class="flex items-center space-x-3">
                <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-green-500 to-green-600 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900">Ajouter du Stock</h3>
            </div>
            <button
                wire:click="closeAddModal"
                class="text-gray-400 hover:text-gray-500 transition-colors"
            >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Modal Body -->
        <form wire:submit.prevent="addStock">
            <div class="p-6 space-y-4" x-data="{
                selectedVariantId: @entangle('form.product_variant_id'),
                variants: {{ Js::from($variants->mapWithKeys(fn($v) => [$v->id => ['stock' => $v->stock_quantity, 'cost' => $v->product->cost_price ?? 0]])) }},
                get currentStock() {
                    return this.selectedVariantId ? (this.variants[this.selectedVariantId]?.stock ?? 0) : 0;
                },
                get productCost() {
                    return this.selectedVariantId ? (this.variants[this.selectedVariantId]?.cost ?? 0) : 0;
                }
            }">
                <!-- Product Variant Selection - Full Width -->
                <div>
                    <x-form.label for="add-product-variant">Produit *</x-form.label>
                    <select
                        id="add-product-variant"
                        x-model="selectedVariantId"
                        wire:model.live="form.product_variant_id"
                        class="mt-1 block w-full px-3 py-2.5 border border-gray-300 rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 transition"
                    >
                        <option value="">Sélectionnez un produit</option>
                        @foreach($variants as $variant)
                            <option value="{{ $variant->id }}">
                                {{ $variant->product->name }} - {{ $variant->sku }}
                                @if($variant->size || $variant->color)
                                    ({{ $variant->size }} {{ $variant->color }})
                                @endif
                                - Stock actuel: {{ $variant->stock_quantity }}
                            </option>
                        @endforeach
                    </select>
                    <x-form.error name="form.product_variant_id" />

                    <template x-if="selectedVariantId">
                        <p class="mt-2 text-sm text-gray-600">
                            Stock actuel:
                            <span class="font-semibold text-indigo-600" x-text="currentStock + ' unités'"></span>
                        </p>
                    </template>
                </div>

                <!-- Row: Quantity & Movement Type -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <x-form.label for="add-quantity">Quantité *</x-form.label>
                        <input
                            type="number"
                            id="add-quantity"
                            wire:model="form.quantity"
                            min="1"
                            class="mt-1 block w-full px-3 py-2.5 border border-gray-300 rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 transition"
                            placeholder="Ex: 50"
                        >
                        <x-form.error name="form.quantity" />
                    </div>

                    <div>
                        <x-form.label for="add-movement-type">Type de mouvement</x-form.label>
                        <select
                            id="add-movement-type"
                            wire:model.live="form.movement_type"
                            class="mt-1 block w-full px-3 py-2.5 border border-gray-300 rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 transition"
                        >
                            <option value="purchase">Achat</option>
                            <option value="return">Retour client</option>
                            <option value="adjustment">Ajustement</option>
                            <option value="transfer">Transfert</option>
                        </select>
                        <x-form.error name="form.movement_type" />
                    </div>
                </div>

                <!-- Row: Reference & Date -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <x-form.label for="add-reference">Référence (Bon de commande...)</x-form.label>
                        <input
                            type="text"
                            id="add-reference"
                            wire:model="form.reference"
                            class="mt-1 block w-full px-3 py-2.5 border border-gray-300 rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 transition"
                            placeholder="Ex: BC-2024-001"
                        >
                        <x-form.error name="form.reference" />
                    </div>

                    <div>
                        <x-form.label for="add-date">Date</x-form.label>
                        <input
                            type="date"
                            id="add-date"
                            wire:model="form.date"
                            class="mt-1 block w-full px-3 py-2.5 border border-gray-300 rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 transition"
                        >
                        <x-form.error name="form.date" />
                    </div>
                </div>

                <!-- Toggle: Unit Price -->
                <div x-data="{ showUnitPrice: @entangle('form.unit_price').live ? true : false }" x-init="$watch('showUnitPrice', value => { if(!value) $wire.set('form.unit_price', null); $wire.set('form.update_product_cost', false); })">
                    <div class="flex items-center justify-between">
                        <label for="toggle-unit-price" class="text-sm font-medium text-gray-700">
                            Ajouter le prix d'achat
                        </label>
                        <button
                            type="button"
                            @click="showUnitPrice = !showUnitPrice"
                            :class="showUnitPrice ? 'bg-green-500' : 'bg-gray-200'"
                            class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                            role="switch"
                            :aria-checked="showUnitPrice"
                        >
                            <span
                                :class="showUnitPrice ? 'translate-x-5' : 'translate-x-0'"
                                class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                            ></span>
                        </button>
                    </div>

                    <!-- Unit Price Field (conditionally shown) -->
                    <div x-show="showUnitPrice" x-collapse class="mt-3 space-y-3">
                        <div>
                            <x-form.label for="add-unit-price">Prix unitaire d'achat</x-form.label>
                            <div class="relative mt-1">
                                <input
                                    type="number"
                                    id="add-unit-price"
                                    wire:model="form.unit_price"
                                    step="0.01"
                                    min="0"
                                    class="block w-full px-3 py-2.5 border border-gray-300 rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 transition"
                                    placeholder="Ex: 25000"
                                >
                            </div>
                            <x-form.error name="form.unit_price" />
                            <template x-if="selectedVariantId && productCost > 0">
                                <p class="mt-1 text-xs text-gray-500">
                                    Prix d'achat actuel du produit : <span class="font-semibold text-indigo-600" x-text="new Intl.NumberFormat('fr-FR').format(productCost) + ' {{ current_currency() }}'"></span>
                                </p>
                            </template>
                        </div>

                        <!-- Option to update product cost_price -->
                        <div class="flex items-center space-x-2 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                            <input
                                type="checkbox"
                                id="update-product-cost"
                                wire:model="form.update_product_cost"
                                class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded"
                            >
                            <label for="update-product-cost" class="text-sm text-blue-800">
                                Mettre à jour le prix d'achat du produit avec ce nouveau prix
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Reason - Full Width -->
                <div>
                    <x-form.label for="add-reason">Raison / Notes</x-form.label>
                    <textarea
                        id="add-reason"
                        wire:model="form.reason"
                        rows="2"
                        class="mt-1 block w-full px-3 py-2.5 border border-gray-300 rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 transition resize-none"
                        placeholder="Notes optionnelles..."
                    ></textarea>
                    <x-form.error name="form.reason" />
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="flex items-center justify-end space-x-3 px-6 py-4 bg-gray-50 border-t border-gray-200 rounded-b-xl">
                <x-form.button
                    type="button"
                    wire:click="closeAddModal"
                    variant="secondary"
                >
                    Annuler
                </x-form.button>
                <x-form.button
                    type="submit"
                    variant="success"
                >
                    <svg class="w-5 h-5 mr-2 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Ajouter le Stock
                </x-form.button>
            </div>
        </form>
    </div>
        </div>
    </div>
</div>
@endif
