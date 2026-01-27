<div>
<x-slot name="header">
    <x-breadcrumb :items="[
        ['label' => 'Accueil', 'url' => route('dashboard')],
        ['label' => 'Factures', 'url' => route('invoices.index')],
        ['label' => 'Modifier', 'url' => route('invoices.show', $invoice->id)],
        ['label' => $invoice->invoice_number]
    ]" />

    <div class="flex items-center justify-between mt-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Modifier la Facture</h1>
            <p class="text-gray-500 mt-1">{{ $invoice->invoice_number }}</p>
        </div>
    </div>
</x-slot>

<div class="max-w-4xl mx-auto space-y-6">

    <x-card>
        <x-slot:header>
            <x-card-title title="Informations de la Facture" />
        </x-slot:header>

        @if (session()->has('error'))
            <div class="mb-4">
                <x-form.alert type="error" :message="session('error')" />
            </div>
        @endif

        <form wire:submit.prevent="save">
            <div class="space-y-6">
                <!-- Sale Info (Read Only) -->
                <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                    <h3 class="text-sm font-semibold text-gray-900 mb-3">Vente associée</h3>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-gray-600">Numéro:</span>
                            <span class="ml-2 font-medium">{{ $invoice->sale->sale_number }}</span>
                        </div>
                        <div>
                            <span class="text-gray-600">Client:</span>
                            <span class="ml-2 font-medium">{{ $invoice->sale->client->name ?? 'Walk-in' }}</span>
                        </div>
                        <div>
                            <span class="text-gray-600">Total:</span>
                            <span class="ml-2 font-medium">{{ format_currency($invoice->total) }}</span>
                        </div>
                        <div>
                            <span class="text-gray-600">Date vente:</span>
                            <span class="ml-2 font-medium">{{ $invoice->sale->sale_date->format('d/m/Y') }}</span>
                        </div>
                    </div>
                </div>

                <!-- Invoice Date -->
                <div>
                    <x-form.label for="invoiceDate" required>Date de facturation</x-form.label>
                    <x-form.input wire:model="invoiceDate" type="date" id="invoiceDate" />
                    @error('invoiceDate')
                        <x-form.error>{{ $message }}</x-form.error>
                    @enderror
                </div>

                <!-- Due Date -->
                <div>
                    <x-form.label for="dueDate">Date d'échéance</x-form.label>
                    <x-form.input wire:model="dueDate" type="date" id="dueDate" />
                    @error('dueDate')
                        <x-form.error>{{ $message }}</x-form.error>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">Laisser vide si aucune date d'échéance n'est requise</p>
                </div>

                <!-- Status -->
                <div>
                    <x-form.label for="status" required>Statut</x-form.label>
                    <x-form.select wire:model="status" id="status">
                        <option value="draft">Brouillon</option>
                        <option value="sent">Envoyée</option>
                        <option value="paid">Payée</option>
                        <option value="cancelled">Annulée</option>
                    </x-form.select>
                    @error('status')
                        <x-form.error>{{ $message }}</x-form.error>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">
                        Note: Il est recommandé d'utiliser les actions spécifiques plutôt que de changer le statut manuellement
                    </p>
                </div>

                <!-- Buttons -->
                <div class="flex items-center justify-end space-x-3 pt-4 border-t">
                    <a href="{{ route('invoices.show', $invoice->id) }}" wire:navigate
                        class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Annuler
                    </a>
                    <button type="submit"
                        class="px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Mettre à jour
                    </button>
                </div>
            </div>
        </form>
    </x-card>

</div>
</div>
