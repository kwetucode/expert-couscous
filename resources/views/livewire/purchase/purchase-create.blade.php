<x-slot name="header">
    <x-breadcrumb :items="[
        ['label' => 'Accueil', 'url' => route('dashboard')],
        ['label' => 'Achats', 'url' => route('purchases.index')],
        ['label' => 'Créer']
    ]" />
</x-slot>

<div class="max-w-7xl mx-auto">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Nouvel Achat</h1>
            <p class="text-gray-500 mt-1">Créez une nouvelle transaction d'achat</p>
        </div>
        <a href="{{ route('purchases.index') }}" wire:navigate class="inline-flex items-center justify-center px-4 py-2.5 bg-white hover:bg-gray-50 text-gray-700 font-medium rounded-xl border border-gray-300 shadow-sm transition-all hover:shadow-md group">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Retour
        </a>
    </div>
    <!-- Messages de succès/erreur -->
    @if (session()->has('success'))
        <x-form.alert type="success" :message="session('success')" class="mb-6" />
    @endif

    @if (session()->has('error'))
        <x-form.alert type="error" :message="session('error')" class="mb-6" />
    @endif

    <form wire:submit="save">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6" wire:loading.class="opacity-50 pointer-events-none">
            <!-- Left Column - Purchase Details -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Add Items Card -->
                <x-card class="overflow-visible">
                    <x-slot:header>
                        <x-card-title title="Articles de l'achat" />
                    </x-slot:header>

                    <!-- Product Search -->
                    <div class="mb-6 relative" x-data="{ open: false }">
                        <x-form.form-group label="Rechercher un produit" for="productSearch">
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <svg wire:loading.remove wire:target="productSearch" class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                    </svg>
                                    <svg wire:loading wire:target="productSearch" class="h-5 w-5 text-indigo-500 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </div>

                                <input
                                    type="text"
                                    wire:model.live.debounce.300ms="productSearch"
                                    @focus="open = true"
                                    @click="open = true"
                                    id="productSearch"
                                    placeholder="Rechercher par nom, référence ou SKU..."
                                    autocomplete="off"
                                    class="block w-full pl-12 pr-12 py-4 text-base border-2 border-gray-200 rounded-xl shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all duration-200 hover:border-gray-300"
                                >

                                @if(strlen($productSearch ?? '') > 0)
                                    <button
                                        wire:click="$set('productSearch', '')"
                                        @click="open = false"
                                        type="button"
                                        class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-gray-600 transition-colors"
                                    >
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                @endif
                            </div>

                            {{-- Dropdown résultats --}}
                            @if(strlen($productSearch ?? '') >= 2)
                                <div 
                                    x-show="open"
                                    x-cloak
                                    x-transition:enter="transition ease-out duration-200"
                                    x-transition:enter-start="opacity-0 -translate-y-2"
                                    x-transition:enter-end="opacity-100 translate-y-0"
                                    x-transition:leave="transition ease-in duration-150"
                                    x-transition:leave-start="opacity-100 translate-y-0"
                                    x-transition:leave-end="opacity-0 -translate-y-2"
                                    @click.away="open = false"
                                    class="absolute z-[100] w-full mt-2 bg-white rounded-xl shadow-2xl border border-gray-200 max-h-80 overflow-hidden"
                                >
                                    @if(count($searchResults) > 0)
                                        <div class="px-4 py-3 bg-gradient-to-r from-gray-50 to-gray-100 border-b border-gray-200">
                                            <span class="text-xs font-bold text-gray-600 uppercase tracking-wider flex items-center gap-2">
                                                <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                                                {{ count($searchResults) }} produit(s) trouvé(s)
                                            </span>
                                        </div>
                                        <div class="overflow-y-auto max-h-64">
                                            @foreach($searchResults as $result)
                                                <button type="button"
                                                        wire:click="selectProduct({{ $result['id'] }})"
                                                        @click="open = false"
                                                        class="w-full px-4 py-3.5 hover:bg-gradient-to-r hover:from-indigo-50 hover:to-purple-50 text-left border-b border-gray-100 last:border-0 transition-all duration-200 group">
                                                    <div class="flex justify-between items-center gap-4">
                                                        <div class="flex items-center gap-3 min-w-0 flex-1">
                                                            <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-100 to-purple-100 flex items-center justify-center group-hover:from-indigo-200 group-hover:to-purple-200 group-hover:scale-105 transition-all duration-200">
                                                                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                                                </svg>
                                                            </div>
                                                            <div class="min-w-0">
                                                                <div class="text-sm font-semibold text-gray-900 group-hover:text-indigo-700 truncate">{{ $result['name'] }}</div>
                                                                <div class="text-xs text-gray-500 mt-0.5">SKU: {{ $result['sku'] }}</div>
                                                            </div>
                                                        </div>
                                                        <div class="text-right flex-shrink-0">
                                                            <div class="text-sm font-bold text-indigo-600">
                                                                {{ number_format($result['cost_price'], 0, ',', ' ') }} <span class="text-xs">{{ current_currency() }}</span>
                                                            </div>
                                                            <div class="text-xs font-medium mt-0.5 {{ $result['stock'] > 10 ? 'text-green-600' : ($result['stock'] > 0 ? 'text-amber-600' : 'text-red-600') }}">
                                                                @if($result['stock'] > 0)
                                                                    <span class="inline-flex items-center gap-1">
                                                                        <span class="w-1.5 h-1.5 rounded-full {{ $result['stock'] > 10 ? 'bg-green-500' : 'bg-amber-500' }}"></span>
                                                                        {{ $result['stock'] }} en stock
                                                                    </span>
                                                                @else
                                                                    <span class="inline-flex items-center gap-1 text-red-600">
                                                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                                                        Rupture
                                                                    </span>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                </button>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="px-6 py-10 text-center">
                                            <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                            </div>
                                            <p class="text-sm font-medium text-gray-700">Aucun produit trouvé</p>
                                            <p class="text-xs text-gray-500 mt-1">Essayez avec d'autres termes de recherche</p>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </x-form.form-group>

                        <!-- Selected Product Details -->
                        @if($selectedVariant)
                            <div class="mt-4 p-4 bg-indigo-50 rounded-lg border border-indigo-200">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <x-form.form-group label="Quantité" for="selectedQuantity">
                                        <x-form.input
                                            wire:model.live="selectedQuantity"
                                            type="number"
                                            min="1"
                                            placeholder="1"
                                        />
                                    </x-form.form-group>

                                    <x-form.form-group label="Prix d'achat ({{ current_currency() }})" for="selectedPrice">
                                        <x-form.input
                                            wire:model.live="selectedPrice"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                        />
                                    </x-form.form-group>

                                    <div class="flex items-end">
                                        <x-form.button wire:click="addItem" :fullWidth="true">
                                            Ajouter
                                        </x-form.button>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Items List -->
                    @if(count($items) > 0)
                        <div class="space-y-2">
                            <h3 class="text-sm font-semibold text-gray-700 mb-3">Articles ajoutés</h3>
                            @foreach($items as $index => $item)
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-200">
                                    <div class="flex-1">
                                        <div class="text-sm font-medium text-gray-900">{{ $item['name'] }}</div>
                                        <div class="text-xs text-gray-500 mt-1">
                                            {{ $item['quantity'] }} x {{ number_format($item['unit_price'], 0, ',', ' ') }} {{ current_currency() }}
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-4">
                                        <div class="text-sm font-semibold text-gray-900">
                                            {{ number_format($item['total'], 0, ',', ' ') }} {{ current_currency() }}
                                        </div>
                                        <button type="button"
                                                wire:click="removeItem({{ $index }})"
                                                class="text-red-600 hover:text-red-800 transition">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8 text-gray-500">
                            <svg class="mx-auto h-12 w-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <p class="text-sm">Aucun article ajouté. Recherchez et ajoutez des produits.</p>
                        </div>
                    @endif
                </x-card>

                <!-- Purchase Information Card -->
                <x-card>
                    <x-slot:header>
                        <x-card-title title="Informations de l'achat" />
                    </x-slot:header>

                    <div class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Supplier -->
                            <x-form.form-group label="Fournisseur" for="form.supplier_id" required>
                                <x-form.select wire:model="form.supplier_id" id="form.supplier_id">
                                    <option value="">Sélectionner un fournisseur</option>
                                    @foreach($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                    @endforeach
                                </x-form.select>
                                <x-form.input-error for="form.supplier_id" />
                            </x-form.form-group>

                            <!-- Purchase Date -->
                            <x-form.form-group label="Date d'achat" for="form.purchase_date" required>
                                <x-form.input
                                    wire:model="form.purchase_date"
                                    id="form.purchase_date"
                                    type="date"
                                />
                                <x-form.input-error for="form.purchase_date" />
                            </x-form.form-group>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Payment Method -->
                            <x-form.form-group label="Méthode de paiement" for="form.payment_method" required>
                                <x-form.select wire:model="form.payment_method" id="form.payment_method">
                                    <option value="cash">Espèces</option>
                                    <option value="card">Carte bancaire</option>
                                    <option value="transfer">Virement</option>
                                    <option value="cheque">Chèque</option>
                                </x-form.select>
                                <x-form.input-error for="form.payment_method" />
                            </x-form.form-group>

                            <!-- Payment Status -->
                            <x-form.form-group label="Statut de paiement" for="form.payment_status" required>
                                <x-form.select wire:model.live="form.payment_status" id="form.payment_status">
                                    <option value="pending">En attente</option>
                                    <option value="paid">Payé</option>
                                    <option value="partial">Partiel</option>
                                </x-form.select>
                                <x-form.input-error for="form.payment_status" />
                            </x-form.form-group>
                        </div>

                        <!-- Paid Amount (if partial or paid) -->
                        @if($form->payment_status === 'partial' || $form->payment_status === 'paid')
                        <x-form.form-group label="Montant payé" for="form.paid_amount" :required="$form->payment_status === 'partial'">
                            <x-form.input
                                wire:model.live="form.paid_amount"
                                id="form.paid_amount"
                                type="number"
                                step="0.01"
                                min="0"
                                :placeholder="$form->payment_status === 'paid' ? 'Montant total payé' : 'Montant déjà payé'"
                            />
                            <x-form.input-error for="form.paid_amount" />
                            <p class="text-xs text-gray-500 mt-1">
                                @if($form->payment_status === 'paid')
                                    Montant total payé au fournisseur
                                @else
                                    Montant partiel déjà versé au fournisseur
                                @endif
                            </p>
                        </x-form.form-group>
                        @endif

                        <!-- Notes -->
                        <x-form.form-group label="Notes" for="form.notes">
                            <x-form.textarea
                                wire:model="form.notes"
                                id="form.notes"
                                rows="3"
                                placeholder="Notes additionnelles..."
                            />
                            <x-form.input-error for="form.notes" />
                        </x-form.form-group>
                    </div>
                </x-card>
            </div>

            <!-- Right Column - Summary -->
            <div class="lg:col-span-1">
                <div class="sticky top-6">
                    <x-card>
                        <x-slot:header>
                            <x-card-title title="Résumé" />
                        </x-slot:header>

                        <div class="space-y-4">
                            <!-- Totals -->
                            <div class="space-y-3">
                                <div class="pt-3 border-t border-gray-200">
                                    <div class="flex justify-between text-lg font-bold">
                                        <span class="text-gray-900">Total:</span>
                                        <span class="text-indigo-600">
                                            {{ number_format($total, 0, ',', ' ') }} {{ current_currency() }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Items Count -->
                            <div class="pt-4 border-t border-gray-200">
                                <div class="flex justify-between text-sm text-gray-600">
                                    <span>Nombre d'articles:</span>
                                    <span class="font-medium">{{ count($items) }}</span>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="pt-4">
                                <x-form.button type="submit" :fullWidth="true" size="lg" wire:loading.attr="disabled">
                                    <span wire:loading.remove>Créer l'Achat</span>
                                    <span wire:loading>Enregistrement...</span>
                                </x-form.button>
                            </div>
                        </div>
                    </x-card>
                </div>
            </div>
        </div>
    </form>
</div>
