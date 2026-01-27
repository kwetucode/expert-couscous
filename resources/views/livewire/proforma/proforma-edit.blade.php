<x-slot name="header">
    <x-breadcrumb :items="[
        ['label' => 'Accueil', 'url' => route('dashboard')],
        ['label' => 'Proformas', 'url' => route('proformas.index')],
        ['label' => $proforma->proforma_number]
    ]" />

    <div class="flex items-center justify-between mt-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Modifier la Proforma</h1>
            <p class="text-gray-500 mt-1">{{ $proforma->proforma_number }}</p>
        </div>
        <x-form.button href="{{ route('proformas.show', $proforma) }}" wire:navigate variant="secondary" icon="arrow-left">
            Retour
        </x-form.button>
    </div>
</x-slot>

<div class="max-w-7xl mx-auto">
    <!-- Toast -->
    <x-toast />

    <!-- Messages -->
    @if (session()->has('success'))
        <x-form.alert type="success" :message="session('success')" class="mb-6" />
    @endif

    @if (session()->has('error'))
        <x-form.alert type="error" :message="session('error')" class="mb-6" />
    @endif

    <form wire:submit="save">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6" wire:loading.class="opacity-50 pointer-events-none">
            <!-- Left Column - Items -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Add Items Card -->
                <x-card>
                    <x-slot:header>
                        <x-card-title title="Articles de la proforma" />
                    </x-slot:header>

                    <!-- Product Search -->
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
                                                        {{ number_format($result['price'], 0, ',', ' ') }} {{ current_currency() }}
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

                        <!-- Add Item Form -->
                        <div class="mt-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
                            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                                <x-form.form-group label="Description" for="selectedDescription" class="md:col-span-2">
                                    <x-form.input
                                        wire:model="selectedDescription"
                                        type="text"
                                        placeholder="Description de l'article"
                                    />
                                </x-form.form-group>

                                <x-form.form-group label="Quantité" for="selectedQuantity">
                                    <x-form.input
                                        wire:model.live="selectedQuantity"
                                        type="number"
                                        min="1"
                                        placeholder="1"
                                    />
                                </x-form.form-group>

                                <x-form.form-group label="Prix unitaire ({{ current_currency() }})" for="selectedPrice">
                                    <x-form.input
                                        wire:model.live="selectedPrice"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                    />
                                </x-form.form-group>

                                <div class="flex items-end space-x-2">
                                    @if($selectedVariant)
                                        <x-form.button type="button" wire:click="addItem" :fullWidth="true" icon="plus">
                                            Ajouter
                                        </x-form.button>
                                    @else
                                        <x-form.button type="button" wire:click="addCustomItem" :fullWidth="true" variant="secondary" icon="plus">
                                            Ajouter
                                        </x-form.button>
                                    @endif
                                </div>
                            </div>
                        </div>
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
                                            @if($item['discount'] > 0)
                                                - Remise: {{ number_format($item['discount'], 0, ',', ' ') }} {{ current_currency() }}
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-4">
                                        <div class="text-sm font-semibold text-gray-900">
                                            {{ number_format($item['total'], 0, ',', ' ') }} {{ current_currency() }}
                                        </div>
                                        <button type="button"
                                                wire:click="removeItem({{ $index }})"
                                                class="text-red-600 hover:text-red-800 transition">
                                            <x-icons.trash class="w-5 h-5" />
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8 text-gray-500">
                            <svg class="mx-auto h-12 w-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <p class="text-sm">Aucun article. Ajoutez des articles à la proforma.</p>
                        </div>
                    @endif

                    @error('items')
                        <x-form.error class="mt-2">{{ $message }}</x-form.error>
                    @enderror
                </x-card>

                <!-- Notes Card -->
                <x-card>
                    <x-slot:header>
                        <x-card-title title="Notes et conditions" />
                    </x-slot:header>

                    <div class="space-y-4">
                        <x-form.form-group label="Notes" for="notes">
                            <x-form.textarea wire:model="notes" rows="3" placeholder="Notes internes ou pour le client..." />
                        </x-form.form-group>

                        <x-form.form-group label="Conditions générales" for="terms_conditions">
                            <x-form.textarea wire:model="terms_conditions" rows="3" placeholder="Conditions de paiement, livraison, etc." />
                        </x-form.form-group>
                    </div>
                </x-card>
            </div>

            <!-- Right Column - Client & Summary -->
            <div class="space-y-6">
                <!-- Client Info Card -->
                <x-card>
                    <x-slot:header>
                        <x-card-title title="Informations client" />
                    </x-slot:header>

                    <div class="space-y-4">
                        <x-form.form-group label="Nom du client" for="client_name" required>
                            <x-form.input wire:model="client_name" placeholder="Nom complet ou entreprise" />
                            @error('client_name')
                                <x-form.error>{{ $message }}</x-form.error>
                            @enderror
                        </x-form.form-group>

                        <x-form.form-group label="Téléphone" for="client_phone">
                            <x-form.input wire:model="client_phone" type="tel" placeholder="+243 XXX XXX XXX" />
                        </x-form.form-group>

                        <x-form.form-group label="Email" for="client_email">
                            <x-form.input wire:model="client_email" type="email" placeholder="client@example.com" />
                            @error('client_email')
                                <x-form.error>{{ $message }}</x-form.error>
                            @enderror
                        </x-form.form-group>

                        <x-form.form-group label="Adresse" for="client_address">
                            <x-form.textarea wire:model="client_address" rows="2" placeholder="Adresse complète" />
                        </x-form.form-group>
                    </div>
                </x-card>

                <!-- Dates Card -->
                <x-card>
                    <x-slot:header>
                        <x-card-title title="Dates" />
                    </x-slot:header>

                    <div class="space-y-4">
                        <x-form.form-group label="Date de la proforma" for="proforma_date" required>
                            <x-form.input wire:model="proforma_date" type="date" />
                            @error('proforma_date')
                                <x-form.error>{{ $message }}</x-form.error>
                            @enderror
                        </x-form.form-group>

                        <x-form.form-group label="Valide jusqu'au" for="valid_until" required>
                            <x-form.input wire:model="valid_until" type="date" />
                            @error('valid_until')
                                <x-form.error>{{ $message }}</x-form.error>
                            @enderror
                        </x-form.form-group>
                    </div>
                </x-card>

                <!-- Summary Card -->
                <x-card>
                    <x-slot:header>
                        <x-card-title title="Récapitulatif" />
                    </x-slot:header>

                    <div class="space-y-3">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Sous-total</span>
                            <span class="font-medium">{{ number_format($subtotal, 0, ',', ' ') }} {{ current_currency() }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Remises</span>
                            <span class="font-medium text-red-600">-{{ number_format(collect($items)->sum('discount'), 0, ',', ' ') }} {{ current_currency() }}</span>
                        </div>
                        <hr>
                        <div class="flex justify-between text-lg font-bold">
                            <span>Total</span>
                            <span class="text-indigo-600">{{ number_format($total, 0, ',', ' ') }} {{ current_currency() }}</span>
                        </div>
                    </div>

                    <div class="mt-6">
                        <x-form.button type="submit" :fullWidth="true" icon="check">
                            Enregistrer les modifications
                        </x-form.button>
                    </div>
                </x-card>
            </div>
        </div>
    </form>
</div>
