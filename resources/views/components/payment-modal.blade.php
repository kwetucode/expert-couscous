@props([
    'show' => 'showPaymentModal',
    'remainingAmount' => 0,
    'onConfirm' => '',
    'onCancel' => ''
])

<div
    x-show="{{ $show }}"
    style="display: none;"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm"
    @click.self="{{ $onCancel }}"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
>
    <div
        class="bg-white rounded-xl shadow-2xl max-w-lg w-full p-6"
        @click.stop
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
    >
        <!-- Header with Icon -->
        <div class="flex items-start mb-4">
            <div class="flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-green-100">
                <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="ml-4 flex-1">
                <h3 class="text-lg font-medium text-gray-900">
                    Enregistrer un paiement
                </h3>
            </div>
        </div>

        <!-- Form Content -->
        <div class="space-y-4">
            <!-- Payment Amount -->
            <x-form.form-group label="Montant" for="paymentAmount" required>
                <x-form.input
                    wire:model="paymentAmount"
                    type="number"
                    step="0.01"
                    min="0.01"
                    placeholder="Montant du paiement"
                />
                <x-form.input-error for="paymentAmount" />
                <p class="text-xs text-gray-500 mt-1">
                    Reste à payer: {{ number_format($remainingAmount, 0, ',', ' ') }} {{ current_currency() }}
                </p>
            </x-form.form-group>

            <!-- Payment Method -->
            <x-form.form-group label="Méthode de paiement" for="paymentMethod" required>
                <x-form.select wire:model="paymentMethod">
                    <option value="cash">Espèces</option>
                    <option value="card">Carte bancaire</option>
                    <option value="transfer">Virement</option>
                    <option value="cheque">Chèque</option>
                </x-form.select>
                <x-form.input-error for="paymentMethod" />
            </x-form.form-group>

            <!-- Payment Date -->
            <x-form.form-group label="Date de paiement" for="paymentDate" required>
                <x-form.input
                    wire:model="paymentDate"
                    type="date"
                />
                <x-form.input-error for="paymentDate" />
            </x-form.form-group>

            <!-- Notes -->
            <x-form.form-group label="Notes" for="paymentNotes">
                <x-form.textarea
                    wire:model="paymentNotes"
                    rows="2"
                    placeholder="Notes additionnelles..."
                />
            </x-form.form-group>
        </div>

        <!-- Boutons -->
        <div class="flex gap-3 justify-end mt-6">
            <button
                type="button"
                @click="{{ $onCancel }}"
                class="px-6 py-2.5 bg-gray-200 text-gray-800 font-medium rounded-lg hover:bg-gray-300 transition-colors focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2"
            >
                Annuler
            </button>
            <button
                type="button"
                @click="{{ $onConfirm }}"
                class="px-6 py-2.5 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 transition-colors focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
            >
                Enregistrer
            </button>
        </div>
    </div>
</div>
