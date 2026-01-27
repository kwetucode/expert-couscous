<x-slot name="header">
    <x-breadcrumb :items="[
        ['label' => 'Accueil', 'url' => route('dashboard')],
        ['label' => 'Achats', 'url' => route('purchases.index')],
        ['label' => 'Modifier']
    ]" />


</x-slot>

<div x-data="{ showPaymentModal: false }"
     @payment-recorded.window="showPaymentModal = false"
     class="max-w-7xl mx-auto">

     <div class="flex items-center justify-between mt-2 mb-2">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Modifier l'Achat</h1>
            <p class="text-gray-500 mt-1">Achat N° {{ $purchase->purchase_number }}</p>
        </div>
        <a href="{{ route('purchases.index') }}" wire:navigate class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white font-semibold rounded-lg shadow-md transition duration-150">
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

    <!-- Status Warning -->
    @if(!$canEditItems)
        <div class="mb-6">
            <x-form.alert type="warning" message="Cet achat ne peut plus être modifié car il est {{ $purchase->status === 'completed' ? 'complété' : 'annulé' }}. Seules les informations générales peuvent être mises à jour." />
        </div>
    @endif

    <form wire:submit="update">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6" wire:loading.class="opacity-50 pointer-events-none">
            <!-- Left Column - Purchase Details -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Add Items Card -->
                <x-card>
                    <x-slot:header>
                        <x-card-title title="Articles de l'achat" />
                    </x-slot:header>

                    <!-- Product Search (Only if can edit) -->
                    @if($canEditItems)
                        <div class="mb-6" x-data="{ showResults: @entangle('showSearchResults') }">
                            <x-form.form-group label="Rechercher un produit" for="productSearch">
                                <div class="relative">
                                    <x-form.input
                                        wire:model.live.debounce.300ms="productSearch"
                                        id="productSearch"
                                        type="text"
                                        placeholder="Rechercher par nom, référence ou SKU..."
                                        autocomplete="off"
                                    />

                                    <!-- Search Results Dropdown -->
                                    <div x-show="showResults"
                                         x-transition
                                         @click.away="showResults = false"
                                         class="absolute z-50 w-full mt-1 bg-white rounded-lg shadow-lg border border-gray-200 max-h-60 overflow-y-auto">
                                        @foreach($searchResults as $result)
                                            <button type="button"
                                                    wire:click="selectProduct({{ $result['id'] }})"
                                                    class="w-full px-4 py-3 hover:bg-gray-50 text-left border-b border-gray-100 last:border-0 transition">
                                                <div class="flex justify-between items-center">
                                                    <div>
                                                        <div class="text-sm font-medium text-gray-900">{{ $result['name'] }}</div>
                                                        <div class="text-xs text-gray-500">SKU: {{ $result['sku'] }}</div>
                                                    </div>
                                                    <div class="text-right">
                                                        <div class="text-sm font-semibold text-indigo-600">
                                                            {{ number_format($result['cost_price'], 0, ',', ' ') }} {{ current_currency() }}
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
                    @endif

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
                                        @if($canEditItems)
                                            <button type="button"
                                                    wire:click="removeItem({{ $index }})"
                                                    class="text-red-600 hover:text-red-800 transition">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        @endif
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
                            <p class="text-sm">Aucun article dans cet achat.</p>
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

                            <!-- Purchase Status -->
                            <x-form.form-group label="Statut de l'achat" for="form.status" required>
                                <x-form.select wire:model="form.status" id="form.status">
                                    <option value="pending">En attente</option>
                                    <option value="completed">Complété</option>
                                    <option value="cancelled">Annulé</option>
                                </x-form.select>
                                <x-form.input-error for="form.status" />
                            </x-form.form-group>
                        </div>

                        <!-- Paid Amount (if partial or paid, and not using payment management) -->
                        @if(($form->payment_status === 'partial' || $form->payment_status === 'paid') && $purchase->payments->count() === 0)
                        <x-form.form-group label="Montant payé" for="form.paid_amount" :required="$form->payment_status === 'partial'">
                            <x-form.input
                                wire:model.live="form.paid_amount"
                                id="form.paid_amount"
                                type="number"
                                step="0.01"
                                min="0"
                                :max="$total"
                                :placeholder="$form->payment_status === 'paid' ? 'Montant total payé' : 'Montant déjà payé'"
                            />
                            <x-form.input-error for="form.paid_amount" />
                            <p class="text-xs text-gray-500 mt-1">
                                @if($form->payment_status === 'paid')
                                    Montant total payé (Total: {{ number_format($total, 0, ',', ' ') }} {{ current_currency() }})
                                @else
                                    Montant partiel déjà versé (Total: {{ number_format($total, 0, ',', ' ') }} {{ current_currency() }})
                                @endif
                            </p>
                        </x-form.form-group>
                        @elseif($purchase->payments && $purchase->payments->count() > 0)
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                            <p class="text-sm text-blue-800">
                                💡 Utilisez la section "Gestion des paiements" ci-dessous pour gérer les paiements de cet achat.
                            </p>
                        </div>
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

                <!-- Payment Management Card -->
                @if($purchase->status !== 'cancelled' && $purchase->payment_status !== 'paid')
                <x-card>
                    <x-slot:header>
                        <div class="flex items-center justify-between">
                            <x-card-title title="Gestion des paiements" />
                            @if($purchaseRemainingAmount > 0)
                            <button type="button"
                                    @click="showPaymentModal = true"
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
                                    {{ number_format($purchase->total, 0, ',', ' ') }} {{ current_currency() }}
                                </span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-green-600">Payé:</span>
                                <span class="font-semibold text-green-600">
                                    {{ number_format($purchasePaidAmount, 0, ',', ' ') }} {{ current_currency() }}
                                </span>
                            </div>
                            <div class="flex justify-between text-sm pt-2 border-t border-gray-200">
                                <span class="text-red-600">Reste:</span>
                                <span class="font-bold text-red-600">
                                    {{ number_format($purchaseRemainingAmount, 0, ',', ' ') }} {{ current_currency() }}
                                </span>
                            </div>
                        </div>

                        <!-- Payment History -->
                        @if($purchase->payments && $purchase->payments->count() > 0)
                        <div>
                            <h4 class="text-sm font-semibold text-gray-700 mb-2">Historique des paiements</h4>
                            <div class="space-y-2">
                                @foreach($purchase->payments as $payment)
                                <div class="bg-white border border-gray-200 rounded-lg p-3">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ number_format($payment->amount, 0, ',', ' ') }} {{ current_currency() }}
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                {{ ucfirst($payment->payment_method) }} -
                                                {{ $payment->payment_date->format('d/m/Y H:i') }}
                                            </div>
                                            @if($payment->notes)
                                            <div class="text-xs text-gray-600 mt-1">{{ $payment->notes }}</div>
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

                            <!-- Purchase Info -->
                            <div class="pt-4 border-t border-gray-200 space-y-2">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Créé par:</span>
                                    <span class="font-medium text-gray-900">{{ $purchase->user?->name ?? 'N/A' }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Créé le:</span>
                                    <span class="font-medium text-gray-900">{{ $purchase->created_at->format('d/m/Y H:i') }}</span>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="pt-4">
                                <x-form.button type="submit" :fullWidth="true" size="lg" wire:loading.attr="disabled">
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
    <x-payment-modal
        show="showPaymentModal"
        :remainingAmount="$purchaseRemainingAmount"
        onConfirm="$wire.recordPayment()"
        onCancel="showPaymentModal = false"
    />
</div>
