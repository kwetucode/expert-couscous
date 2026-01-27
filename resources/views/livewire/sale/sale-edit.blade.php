<x-slot name="header">
    <x-breadcrumb :items="[
        ['label' => 'Accueil', 'url' => route('dashboard')],
        ['label' => 'Ventes', 'url' => route('sales.index')],
        ['label' => 'Modifier'],
    ]" />


</x-slot>
<div class="max-w-7xl mx-auto">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Modifier la Vente</h1>
            <p class="text-gray-500 mt-1">Vente N° {{ $sale->sale_number }}</p>
        </div>
        <a href="{{ route('sales.index') }}" wire:navigate
            class="inline-flex items-center justify-center px-4 py-2.5 bg-white hover:bg-gray-50 text-gray-700 font-medium rounded-xl border border-gray-300 shadow-sm transition-all hover:shadow-md group">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Retour
        </a>
    </div>

    <div x-data="{ showPaymentModal: false }" @payment-recorded.window="showPaymentModal = false" class="max-w-7xl mx-auto">
        <!-- Messages de succès/erreur -->
        @if (session()->has('success'))
            <x-form.alert type="success" :message="session('success')" class="mb-6" />
        @endif

        @if (session()->has('error'))
            <x-form.alert type="error" :message="session('error')" class="mb-6" />
        @endif

        <!-- Status Warning -->
        @if (!$canEditItems)
            <div class="mb-6">
                <x-form.alert type="warning"
                    message="Cette vente ne peut plus être modifiée car elle est {{ $sale->status === 'completed' ? 'complétée' : 'annulée' }}. Seules les informations générales peuvent être mises à jour." />
            </div>
        @endif

        <form wire:submit="save">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6" wire:loading.class="opacity-50 pointer-events-none">
                <!-- Left Column - Sale Details -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Add Items Card -->
                    <x-card>
                        <x-slot:header>
                            <x-card-title title="Articles de la vente" />
                        </x-slot:header>

                        <!-- Product Search (Only if can edit) -->
                        @if ($canEditItems)
                            <div class="mb-6" x-data="{ showResults: @entangle('showSearchResults') }">
                                <x-form.form-group label="Rechercher un produit" for="productSearch">
                                    <div class="relative">
                                        <x-form.input wire:model.live.debounce.300ms="productSearch" id="productSearch"
                                            type="text" placeholder="Rechercher par nom, référence ou SKU..."
                                            autocomplete="off" />

                                        <!-- Search Results Dropdown -->
                                        <div x-show="showResults" x-transition @click.away="showResults = false"
                                            class="absolute z-50 w-full mt-1 bg-white rounded-lg shadow-lg border border-gray-200 max-h-60 overflow-y-auto">
                                            @foreach ($searchResults as $result)
                                                <button type="button" wire:click="selectProduct({{ $result['id'] }})"
                                                    class="w-full px-4 py-3 hover:bg-gray-50 text-left border-b border-gray-100 last:border-0 transition">
                                                    <div class="flex justify-between items-center">
                                                        <div>
                                                            <div class="text-sm font-medium text-gray-900">
                                                                {{ $result['name'] }}</div>
                                                            <div class="text-xs text-gray-500">SKU: {{ $result['sku'] }}
                                                            </div>
                                                        </div>
                                                        <div class="text-right">
                                                            <div class="text-sm font-semibold text-indigo-600">
                                                                {{ format_currency($result['price']) }}
                                                            </div>
                                                            <div class="text-xs text-gray-500">
                                                                Stock: {{ $result['stock'] }}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </button>
                                            @endforeach
                                        </div>
                                    </div>
                                </x-form.form-group>

                                <!-- Selected Product Details -->
                                @if ($selectedVariant)
                                    <div class="mt-4 p-4 bg-indigo-50 rounded-lg border border-indigo-200">
                                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                            <x-form.form-group label="Quantité" for="selectedQuantity">
                                                <x-form.input wire:model.live="selectedQuantity" type="number"
                                                    min="1" placeholder="1" />
                                            </x-form.form-group>

                                            <x-form.form-group label="Prix unitaire ({{ current_currency() }})" for="selectedPrice">
                                                <x-form.input wire:model.live="selectedPrice" type="number"
                                                    step="0.01" min="0" />
                                            </x-form.form-group>

                                            <x-form.form-group label="Remise ({{ current_currency() }})" for="selectedDiscount">>
                                                <x-form.input wire:model.live="selectedDiscount" type="number"
                                                    step="0.01" min="0" value="0" />
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
                        @endif

                        <!-- Items List -->
                        @if (count($items) > 0)
                            <div class="space-y-2">
                                <h3 class="text-sm font-semibold text-gray-700 mb-3">Articles ajoutés</h3>
                                @foreach ($items as $index => $item)
                                    <div
                                        class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-200">
                                        <div class="flex-1">
                                            <div class="text-sm font-medium text-gray-900">{{ $item['name'] }}</div>
                                            <div class="text-xs text-gray-500 mt-1">
                                                {{ $item['quantity'] }} x
                                                {{ format_currency($item['unit_price']) }}
                                                @if ($item['discount'] > 0)
                                                    - Remise: {{ format_currency($item['discount']) }}
                                                @endif
                                            </div>
                                        </div>
                                        <div class="flex items-center space-x-4">
                                            <div class="text-sm font-semibold text-gray-900">
                                                {{ format_currency($item['total']) }}
                                            </div>
                                            @if ($canEditItems)
                                                <button type="button" wire:click="removeItem({{ $index }})"
                                                    class="text-red-600 hover:text-red-800 transition">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-8 text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-400 mb-3" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                <p class="text-sm">Aucun article dans cette vente.</p>
                            </div>
                        @endif
                    </x-card>

                    <!-- Sale Information Card -->
                    <x-card>
                        <x-slot:header>
                            <x-card-title title="Informations de la vente" />
                        </x-slot:header>

                        <div class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Client -->
                                <x-form.form-group label="Client" for="form.client_id">
                                    <x-form.select wire:model="form.client_id" id="form.client_id">
                                        <option value="">Client anonyme</option>
                                        @foreach ($clients as $client)
                                            <option value="{{ $client->id }}">{{ $client->name }}</option>
                                        @endforeach
                                    </x-form.select>
                                    <x-form.input-error for="form.client_id" />
                                </x-form.form-group>

                                <!-- Sale Date -->
                                <x-form.form-group label="Date de vente" for="form.sale_date" required>
                                    <x-form.input wire:model="form.sale_date" id="form.sale_date" type="date" />
                                    <x-form.input-error for="form.sale_date" />
                                </x-form.form-group>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
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

                                <!-- Sale Status -->
                                <x-form.form-group label="Statut de la vente" for="form.status" required>
                                    <x-form.select wire:model="form.status" id="form.status">
                                        <option value="pending">En attente</option>
                                        <option value="completed">Complétée</option>
                                        <option value="cancelled">Annulée</option>
                                    </x-form.select>
                                    <x-form.input-error for="form.status" />
                                </x-form.form-group>
                            </div>

                            <!-- Paid Amount (if partial or paid, and not using payment management) -->
                            @if (($form->payment_status === 'partial' || $form->payment_status === 'paid') && $sale->payments->count() === 0)
                                <x-form.form-group label="Montant payé" for="form.paid_amount" :required="$form->payment_status === 'partial'">
                                    <x-form.input wire:model.live="form.paid_amount" id="form.paid_amount"
                                        type="number" step="0.01" min="0" :max="$total"
                                        :placeholder="$form->payment_status === 'paid' ? 'Montant total payé' : 'Montant déjà payé'" />
                                    <x-form.input-error for="form.paid_amount" />
                                    <p class="text-xs text-gray-500 mt-1">
                                        @if ($form->payment_status === 'paid')
                                            Montant total payé (Total: {{ format_currency($total) }})
                                        @else
                                            Montant partiel déjà versé (Total: {{ format_currency($total) }})
                                        @endif
                                    </p>
                                </x-form.form-group>
                            @elseif($sale->payments->count() > 0)
                                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                                    <p class="text-sm text-blue-800">
                                        💡 Utilisez la section "Gestion des paiements" ci-dessous pour gérer les
                                        paiements
                                        de cette vente.
                                    </p>
                                </div>
                            @endif

                            <!-- Notes -->
                            <x-form.form-group label="Notes" for="form.notes">
                                <x-form.textarea wire:model="form.notes" id="form.notes" rows="3"
                                    placeholder="Notes additionnelles..." />
                                <x-form.input-error for="form.notes" />
                            </x-form.form-group>
                        </div>
                    </x-card>

                    <!-- Payment Management Card -->
                    @if ($sale->status !== 'cancelled' && $sale->payment_status !== 'paid')
                        <x-card>
                            <x-slot:header>
                                <div class="flex items-center justify-between">
                                    <x-card-title title="Gestion des paiements" />
                                    @if ($saleRemainingAmount > 0)
                                        <button type="button" @click="showPaymentModal = true"
                                            class="text-sm px-3 py-1 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition">
                                            + Nouveau paiement
                                        </button>
                                    @endif
                                </div>
                            </x-slot:header>

                            <div class="space-y-4">
                                <!-- Payment Summary -->
                                <div class="bg-gray-50 rounded-lg p-4 space-y-2">
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600">Total:</span>
                                        <span class="font-semibold text-gray-900">
                                            {{ format_currency($sale->total) }}
                                        </span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-green-600">Payé:</span>
                                        <span class="font-semibold text-green-600">
                                            {{ format_currency($salePaidAmount) }}
                                        </span>
                                    </div>
                                    <div class="flex justify-between text-sm pt-2 border-t border-gray-200">
                                        <span class="text-red-600">Reste:</span>
                                        <span class="font-bold text-red-600">
                                            {{ format_currency($saleRemainingAmount) }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Payment History -->
                                @if ($sale->payments->count() > 0)
                                    <div>
                                        <h4 class="text-sm font-semibold text-gray-700 mb-2">Historique des paiements
                                        </h4>
                                        <div class="space-y-2">
                                            @foreach ($sale->payments as $payment)
                                                <div class="bg-white border border-gray-200 rounded-lg p-3">
                                                    <div class="flex justify-between items-start">
                                                        <div>
                                                            <div class="text-sm font-medium text-gray-900">
                                                                {{ format_currency($payment->amount) }}
                                                            </div>
                                                            <div class="text-xs text-gray-500">
                                                                {{ ucfirst($payment->payment_method) }} -
                                                                {{ $payment->payment_date->format('d/m/Y H:i') }}
                                                            </div>
                                                            @if ($payment->notes)
                                                                <div class="text-xs text-gray-600 mt-1">
                                                                    {{ $payment->notes }}</div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </x-card>
                    @endif
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
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600">Sous-total:</span>
                                        <span class="font-medium text-gray-900">
                                            {{ format_currency($subtotal) }}
                                        </span>
                                    </div>

                                    <!-- Discount Input -->
                                    <div>
                                        <x-form.form-group label="Remise globale ({{ current_currency() }})" for="form.discount">>
                                            <x-form.input wire:model.live="form.discount" type="number"
                                                step="0.01" min="0" value="0" />
                                            <x-form.input-error for="form.discount" />
                                        </x-form.form-group>
                                    </div>

                                    <!-- Tax Input -->
                                    <div>
                                        <x-form.form-group label="Taxe ({{ current_currency() }})" for="form.tax">
                                            <x-form.input wire:model.live="form.tax" type="number" step="0.01"
                                                min="0" value="0" />
                                            <x-form.input-error for="form.tax" />
                                        </x-form.form-group>
                                    </div>

                                    <!-- Discount Display -->
                                    @if (floatval($form->discount) > 0)
                                        <div class="flex justify-between text-sm">
                                            <span class="text-red-600">- Remise:</span>
                                            <span class="font-medium text-red-600">
                                                {{ format_currency($form->discount) }}
                                            </span>
                                        </div>
                                    @endif

                                    <!-- Tax Display -->
                                    @if (floatval($form->tax) > 0)
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-600">+ Taxe:</span>
                                            <span class="font-medium text-gray-900">
                                                {{ format_currency($form->tax) }}
                                            </span>
                                        </div>
                                    @endif

                                    <div class="pt-3 border-t border-gray-200">
                                        <div class="flex justify-between text-lg font-bold">
                                            <span class="text-gray-900">Total:</span>
                                            <span class="text-indigo-600">
                                                {{ format_currency($total) }}
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

                                <!-- Sale Info -->
                                <div class="pt-4 border-t border-gray-200 space-y-2">
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600">Créé par:</span>
                                        <span class="font-medium text-gray-900">{{ $sale->user->name }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600">Créé le:</span>
                                        <span
                                            class="font-medium text-gray-900">{{ $sale->created_at->format('d/m/Y H:i') }}</span>
                                    </div>
                                </div>

                                <!-- Submit Button -->
                                <div class="pt-4">
                                    <x-form.button type="submit" :fullWidth="true" size="lg"
                                        wire:loading.attr="disabled">
                                        <span wire:loading.remove>Mettre à Jour</span>
                                        <span wire:loading>Mise à jour...</span>
                                    </x-form.button>
                                </div>
                            </div>
                        </x-card>
                    </div>
                </div>
            </div>
        </form>

        <!-- Payment Modal -->
        <x-payment-modal show="showPaymentModal" :remainingAmount="$saleRemainingAmount" onConfirm="$wire.recordPayment()"
            onCancel="showPaymentModal = false" />
    </div>

</div>
