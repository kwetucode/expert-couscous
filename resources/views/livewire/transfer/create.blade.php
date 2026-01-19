<div>
    <!-- Modal Création Transfert (Alpine) -->
    <x-ui.alpine-modal name="transfer" max-width="4xl" title="Nouveau Transfert" icon-bg="from-indigo-500 to-purple-600">
        <x-slot name="icon">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
            </svg>
        </x-slot>

        <form wire:submit="save">
            <x-ui.alpine-modal-body>
                    <!-- Store Selection -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-form.form-group label="Magasin source" name="from_store_id" :required="true"
                            :error="$errors->first('from_store_id')">
                            <x-form.select id="from_store" name="from_store_id" wire:model="from_store_id" required>
                                <option value="">Sélectionner un magasin</option>
                                @foreach ($stores as $store)
                                    <option value="{{ $store->id }}">{{ $store->name }}</option>
                                @endforeach
                            </x-form.select>
                        </x-form.form-group>

                        <x-form.form-group label="Magasin destination" name="to_store_id" :required="true"
                            :error="$errors->first('to_store_id')">
                            <x-form.select id="to_store" name="to_store_id" wire:model="to_store_id" required>
                                <option value="">Sélectionner un magasin</option>
                                @foreach ($stores as $store)
                                    @if ($store->id != $from_store_id)
                                        <option value="{{ $store->id }}">{{ $store->name }}</option>
                                    @endif
                                @endforeach
                            </x-form.select>
                        </x-form.form-group>
                    </div>

                    <!-- Add Products -->
                    <div class="border-t border-gray-200 pt-4">
                        <h4 class="text-sm font-semibold text-gray-900 mb-3 flex items-center">
                            <svg class="w-4 h-4 mr-2 text-indigo-500" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            Ajouter des produits
                        </h4>

                        <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
                            <!-- Product Search -->
                            <div class="md:col-span-7">
                                <x-form.label for="searchProduct">Rechercher un produit</x-form.label>

                                @if ($selectedVariant)
                                    <!-- Selected Product Display -->
                                    <div
                                        class="flex items-center justify-between p-3 bg-indigo-50 border border-indigo-200 rounded-xl">
                                        <div class="flex items-center space-x-3">
                                            <div
                                                class="flex-shrink-0 w-8 h-8 bg-indigo-100 rounded-lg flex items-center justify-center">
                                                <svg class="w-4 h-4 text-indigo-600" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </div>
                                            <div>
                                                <div class="text-sm font-medium text-gray-900">
                                                    {{ $selectedVariantName }}</div>
                                                <div class="text-xs text-gray-500">{{ $selectedVariantSku }}</div>
                                            </div>
                                        </div>
                                        <button type="button" wire:click="clearSelection"
                                            class="p-1 text-gray-400 hover:text-gray-600 transition-colors">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                @else
                                    <!-- Search Input -->
                                    <div class="relative">
                                        <x-form.search-input wire:model.live.debounce.300ms="searchProduct"
                                            wireModel="searchProduct" placeholder="Rechercher un produit ou SKU..." />

                                        @if (strlen($searchProduct) >= 2 && count($variants) > 0)
                                            <div
                                                class="absolute z-10 w-full mt-1 bg-white border border-gray-200 rounded-xl shadow-lg max-h-48 overflow-y-auto">
                                                @foreach ($variants as $variant)
                                                    <button type="button"
                                                        wire:click="selectVariant({{ $variant->id }})"
                                                        class="w-full text-left px-4 py-3 hover:bg-indigo-50 border-b border-gray-100 last:border-0 transition-colors">
                                                        <div class="flex items-center justify-between">
                                                            <div>
                                                                <div class="text-sm font-medium text-gray-900">
                                                                    {{ $variant->product->name }}
                                                                </div>
                                                                <div class="text-xs text-gray-500">
                                                                    {{ $variant->name }} • {{ $variant->sku }}
                                                                </div>
                                                            </div>
                                                            <span
                                                                class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">
                                                                Stock: {{ $variant->available_stock }}
                                                            </span>
                                                        </div>
                                                    </button>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>

                            <!-- Quantity -->
                            <div class="md:col-span-3">
                                <x-form.label for="quantity">Quantité</x-form.label>
                                <x-form.input type="number" id="quantity" name="quantity" wire:model="quantity"
                                    placeholder="Qté" class="text-center" />
                            </div>

                            <!-- Add Button -->
                            <div class="md:col-span-2">
                                <x-form.button type="button" wire:click="addItem" variant="primary" fullWidth
                                    :disabled="!$selectedVariant">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 4v16m8-8H4" />
                                    </svg>
                                    Ajouter
                                </x-form.button>
                            </div>
                        </div>
                    </div>

                    <!-- Items List -->
                    @if (count($items) > 0)
                        <div class="border-t border-gray-200 pt-4">
                            <h4 class="text-sm font-semibold text-gray-900 mb-3">Produits à transférer</h4>

                            <div class="space-y-2">
                                @foreach ($items as $index => $item)
                                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
                                        wire:key="item-{{ $index }}">
                                        <div class="flex-1">
                                            <div class="text-sm font-medium text-gray-900">{{ $item['product_name'] }}
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                {{ $item['variant_name'] }} • {{ $item['sku'] }}
                                            </div>
                                        </div>

                                        <div class="flex items-center space-x-3">
                                            <div class="flex items-center space-x-2">
                                                <button type="button"
                                                    wire:click="updateQuantity({{ $index }}, {{ $item['quantity'] - 1 }})"
                                                    class="p-1 text-gray-600 hover:text-gray-800 rounded">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M20 12H4" />
                                                    </svg>
                                                </button>

                                                <span
                                                    class="text-sm font-semibold text-gray-900 min-w-[2rem] text-center">
                                                    {{ $item['quantity'] }}
                                                </span>

                                                <button type="button"
                                                    wire:click="updateQuantity({{ $index }}, {{ $item['quantity'] + 1 }})"
                                                    class="p-1 text-gray-600 hover:text-gray-800 rounded">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M12 4v16m8-8H4" />
                                                    </svg>
                                                </button>
                                            </div>

                                            <button type="button" wire:click="removeItem({{ $index }})"
                                                class="p-2 text-red-600 hover:text-red-800 hover:bg-red-50 rounded-lg transition">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="mt-3 flex items-center justify-between text-sm">
                                <span class="text-gray-600">Total articles:</span>
                                <span class="font-semibold text-gray-900">{{ count($items) }} produit(s)</span>
                            </div>
                        </div>
                    @else
                        <div class="border-t border-gray-200 pt-4">
                            <p class="text-sm text-gray-500 text-center py-4">
                                Aucun produit ajouté. Recherchez et ajoutez des produits ci-dessus.
                            </p>
                        </div>
                    @endif

                    @error('items')
                        <x-form.error name="items" />
                    @enderror

                    <!-- Notes -->
                    <x-form.form-group label="Notes" name="notes"
                        hint="Optionnel - Ajoutez des informations sur ce transfert">
                        <x-form.textarea id="notes" name="notes" wire:model="notes" rows="2"
                            placeholder="Notes sur ce transfert..." />
                    </x-form.form-group>
                </div>

                <!-- Actions -->
                <div
                    class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex items-center justify-end space-x-3 rounded-b-xl">
                    <x-form.button type="button" @click="showModal = false" variant="secondary">
                        Annuler
                    </x-form.button>
                    <x-form.button type="submit" variant="primary-gradient" :disabled="count($items) === 0">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                        </svg>
                        Créer le transfert
                    </x-form.button>
                </div>
            </x-ui.alpine-modal-body>
        </form>
    </x-ui.alpine-modal>
</div>
