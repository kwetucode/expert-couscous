<div>
    @if($showModal && $organization)
    <!-- Payment Overlay Modal - Blocks entire page -->
    <div class="fixed inset-0 z-[9999] overflow-y-auto px-4 py-4 sm:px-0" aria-labelledby="modal-title" role="dialog" aria-modal="true"
        x-data="{
            checkingStatus: false,
            intervalId: null,
            startStatusCheck() {
                if (this.intervalId) return;
                this.checkingStatus = true;
                this.intervalId = setInterval(() => {
                    $wire.checkPaymentStatus();
                }, {{ $checkStatusInterval }});
            },
            stopStatusCheck() {
                if (this.intervalId) {
                    clearInterval(this.intervalId);
                    this.intervalId = null;
                }
                this.checkingStatus = false;
            }
        }"
        x-init="
            @if($pendingTransactionId)
                startStatusCheck();
            @endif
        "
        @payment-initiated.window="startStatusCheck()"
        @payment-completed.window="stopStatusCheck()">

        <!-- Background overlay with transparency -->
        <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity"></div>

        <!-- Modal content -->
        <div class="flex min-h-full items-center justify-center p-2">
            <div class="relative bg-white rounded-2xl overflow-hidden shadow-2xl transform transition-all w-full max-w-3xl">

                <!-- Header compact -->
                <div class="px-6 py-4 border-b border-gray-200 flex items-center">
                    <div class="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center mr-3">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-gray-900">Finalisez votre inscription</h2>
                        <p class="text-gray-500 text-xs">Procédez au paiement pour activer votre organisation</p>
                    </div>
                </div>

                <!-- Body - Two columns layout -->
                <div class="px-6 py-5">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Left Column: Plan Summary & Features -->
                        <div>
                            <!-- Plan Summary -->
                            <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
                                <div class="flex justify-between items-center mb-3">
                                    <span class="text-sm font-medium text-gray-500">Organisation</span>
                                    <span class="text-sm font-semibold text-gray-900">{{ $organization->name }}</span>
                                </div>
                                <div class="flex justify-between items-center mb-3">
                                    <span class="text-sm font-medium text-gray-500">Plan choisi</span>
                                    <span class="text-sm font-semibold text-indigo-600">
                                        {{ $planData['name'] ?? $organization->subscription_plan->label() }}
                                    </span>
                                </div>
                                <div class="flex justify-between items-center pt-3 border-t border-gray-200">
                                    <span class="text-sm font-medium text-gray-500">Total</span>
                                    <span class="text-lg font-bold text-emerald-600">
                                        {{ number_format($planData['price'] ?? 0, 0, ',', ' ') }} {{ $currency }}/mois
                                    </span>
                                </div>
                            </div>

                            <!-- Features -->
                            @if(!empty($planData['features']))
                            <div class="mt-4">
                                <h4 class="text-xs font-medium text-gray-500 mb-2 uppercase tracking-wide">Inclus dans votre plan</h4>
                                <div class="grid grid-cols-1 gap-1.5">
                                    @foreach(array_slice($planData['features'] ?? [], 0, 4) as $feature)
                                    <div class="flex items-center text-sm text-gray-600">
                                        <svg class="w-3.5 h-3.5 text-emerald-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                        <span>{{ $feature }}</span>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            @endif

                            <!-- Supported Operators -->
                            <div class="mt-4 p-3 bg-orange-50 rounded-lg border border-orange-100">
                                <h4 class="text-xs font-medium text-orange-700 mb-2 flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                    </svg>
                                    Opérateurs Mobile Money
                                </h4>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($operators ?? [] as $operator)
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-white text-orange-700 border border-orange-200">
                                        {{ $operator }}
                                    </span>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <!-- Right Column: Payment Methods & Actions -->
                        <div>
                            <!-- Payment Status Message -->
                            @if($paymentStatus)
                            <div class="mb-4 p-4 rounded-xl {{ $paymentStatus === 'success' ? 'bg-green-50 border border-green-200' : ($paymentStatus === 'pending' ? 'bg-amber-50 border border-amber-200' : ($paymentStatus === 'error' || $paymentStatus === 'failed' ? 'bg-red-50 border border-red-200' : 'bg-blue-50 border border-blue-200')) }}">
                                <div class="flex items-start">
                                    @if($paymentStatus === 'success')
                                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center mr-3 flex-shrink-0">
                                        <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                    @elseif($paymentStatus === 'pending')
                                    <div class="w-10 h-10 bg-amber-100 rounded-full flex items-center justify-center mr-3 flex-shrink-0">
                                        <svg class="w-6 h-6 text-amber-600 animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </div>
                                    @else
                                    <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center mr-3 flex-shrink-0">
                                        <svg class="w-6 h-6 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                    @endif
                                    <div class="flex-1">
                                        <h4 class="text-sm font-semibold {{ $paymentStatus === 'success' ? 'text-green-800' : ($paymentStatus === 'pending' ? 'text-amber-800' : 'text-red-800') }}">
                                            @if($paymentStatus === 'success')
                                                Paiement réussi !
                                            @elseif($paymentStatus === 'pending')
                                                En attente de confirmation
                                            @elseif($paymentStatus === 'failed')
                                                Paiement échoué
                                            @else
                                                Erreur de paiement
                                            @endif
                                        </h4>
                                        <p class="mt-1 text-sm {{ $paymentStatus === 'success' ? 'text-green-700' : ($paymentStatus === 'pending' ? 'text-amber-700' : 'text-red-700') }}">
                                            {{ $paymentMessage }}
                                        </p>

                                        {{-- Actions selon le statut --}}
                                        <div class="mt-3 flex flex-wrap gap-2">
                                            @if($paymentStatus === 'pending' && $pendingTransactionId)
                                                <button wire:click="confirmPaymentManually" 
                                                        wire:loading.attr="disabled"
                                                        wire:loading.class="opacity-50 cursor-wait"
                                                        class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-lg text-white bg-green-600 hover:bg-green-700 transition-colors">
                                                    <svg wire:loading.remove wire:target="confirmPaymentManually" class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                    </svg>
                                                    <svg wire:loading wire:target="confirmPaymentManually" class="animate-spin w-3.5 h-3.5 mr-1.5" fill="none" viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                    </svg>
                                                    <span wire:loading.remove wire:target="confirmPaymentManually">J'ai payé</span>
                                                    <span wire:loading wire:target="confirmPaymentManually">Confirmation...</span>
                                                </button>
                                                <button wire:click="cancelPendingPayment" class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-lg text-amber-700 bg-amber-100 hover:bg-amber-200 transition-colors">
                                                    <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                    </svg>
                                                    Annuler
                                                </button>
                                            @endif

                                            @if($paymentStatus === 'error' || $paymentStatus === 'failed')
                                                <button wire:click="cancelPendingPayment" class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-lg text-white bg-red-600 hover:bg-red-700 transition-colors">
                                                    <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                                    </svg>
                                                    Réessayer
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endif

                            <!-- Payment Methods -->
                            <h4 class="text-xs font-medium text-gray-500 mb-2 uppercase tracking-wide">Mode de paiement</h4>
                            <div class="flex gap-2 mb-4">
                                <!-- Credit Card Option -->
                                <button
                                    wire:click="$set('paymentMethod', 'card')"
                                    @if($pendingTransactionId) disabled @endif
                                    class="flex-1 relative flex items-center p-2.5 border-2 rounded-lg transition-all
                                        {{ $paymentMethod === 'card' ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50' }}
                                        {{ $pendingTransactionId ? 'opacity-50 cursor-not-allowed' : '' }}">
                                    <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-2">
                                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                        </svg>
                                    </div>
                                    <div class="text-left">
                                        <span class="font-medium text-gray-900 text-xs">Carte Bancaire</span>
                                        <span class="block text-xs text-gray-400">Visa, Mastercard</span>
                                    </div>
                                    @if($paymentMethod === 'card')
                                    <svg class="w-4 h-4 text-indigo-600 absolute top-1.5 right-1.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    @endif
                                </button>

                                <!-- Mobile Money Option -->
                                <button
                                    wire:click="$set('paymentMethod', 'mobile_money')"
                                    @if($pendingTransactionId) disabled @endif
                                    class="flex-1 relative flex items-center p-2.5 border-2 rounded-lg transition-all
                                        {{ $paymentMethod === 'mobile_money' ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50' }}
                                        {{ $pendingTransactionId ? 'opacity-50 cursor-not-allowed' : '' }}">
                                    <div class="w-8 h-8 bg-orange-100 rounded-full flex items-center justify-center mr-2">
                                        <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                    <div class="text-left">
                                        <span class="font-medium text-gray-900 text-xs">Mobile Money</span>
                                        <span class="block text-xs text-gray-400">{{ implode(', ', array_slice($operators ?? [], 0, 2)) }}</span>
                                    </div>
                                    @if($paymentMethod === 'mobile_money')
                                    <svg class="w-4 h-4 text-indigo-600 absolute top-1.5 right-1.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    @endif
                                </button>
                            </div>

                            <!-- Mobile Money Form -->
                            @if($paymentMethod === 'mobile_money' && !$pendingTransactionId)
                            <div class="mb-4 space-y-3">
                                <!-- Country Selection -->
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Pays</label>
                                    <select wire:model.live="selectedCountry" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                        @foreach($supportedCountries ?? [] as $code => $country)
                                        <option value="{{ $code }}">{{ $country['name'] }} ({{ $country['phone_prefix'] }})</option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Phone Number Input -->
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Numéro de téléphone Mobile Money</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <span class="text-gray-500 text-sm">{{ $phonePrefix }}</span>
                                        </div>
                                        <input
                                            type="tel"
                                            wire:model="phoneNumber"
                                            placeholder="812345678"
                                            class="w-full pl-14 pr-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('phoneNumber') border-red-500 @enderror"
                                        >
                                    </div>
                                    @error('phoneNumber')
                                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                    @enderror
                                    <p class="mt-1 text-xs text-gray-400">Entrez le numéro sans le préfixe pays</p>
                                </div>
                            </div>
                            @endif

                            <!-- Payment Button -->
                            @if(!$pendingTransactionId)
                            <button
                                wire:click="processPayment"
                                wire:loading.attr="disabled"
                                @if($isProcessing) disabled @endif
                                class="w-full px-4 py-3 bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700
                                    text-white font-bold rounded-lg transition-all transform hover:scale-[1.01] active:scale-[0.99]
                                    shadow-md shadow-emerald-500/25 disabled:opacity-50 disabled:cursor-not-allowed">
                                <span wire:loading.remove wire:target="processPayment" class="flex items-center justify-center text-sm">
                                    @if($paymentMethod === 'mobile_money')
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                    </svg>
                                    Payer via Mobile Money
                                    @else
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                    </svg>
                                    Payer {{ number_format($planData['price'] ?? 0, 0, ',', ' ') }} {{ $currency }}
                                    @endif
                                </span>
                                <span wire:loading wire:target="processPayment" class="flex items-center justify-center text-sm">
                                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Traitement en cours...
                                </span>
                            </button>
                            @else
                            <!-- Waiting for confirmation -->
                            <div class="w-full px-4 py-3 bg-yellow-100 text-yellow-800 font-medium rounded-lg text-center text-sm">
                                <div class="flex items-center justify-center">
                                    <svg class="animate-spin mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    En attente de votre confirmation...
                                </div>
                                <p class="text-xs mt-1">Veuillez valider le paiement sur votre téléphone</p>
                            </div>
                            @endif

                            <!-- Security Badge -->
                            <div class="flex items-center justify-center mt-2 text-xs text-gray-400">
                                <svg class="w-3.5 h-3.5 mr-1.5 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                Paiement 100% sécurisé via Shwary
                            </div>

                            <!-- Divider -->
                            <div class="relative my-4">
                                <div class="absolute inset-0 flex items-center">
                                    <div class="w-full border-t border-gray-200"></div>
                                </div>
                                <div class="relative flex justify-center text-xs">
                                    <span class="px-3 bg-white text-gray-400">ou</span>
                                </div>
                            </div>

                            <!-- Free Plan Option -->
                            <button
                                wire:click="useFreePlan"
                                wire:loading.attr="disabled"
                                @if($pendingTransactionId) disabled @endif
                                class="w-full px-4 py-2.5 bg-gray-50 hover:bg-gray-100
                                    text-gray-600 font-medium text-sm rounded-lg transition-all
                                    border border-gray-200 hover:border-gray-300
                                    {{ $pendingTransactionId ? 'opacity-50 cursor-not-allowed' : '' }}">
                                <span wire:loading.remove wire:target="useFreePlan">
                                    Continuer avec le plan gratuit
                                </span>
                                <span wire:loading wire:target="useFreePlan">
                                    Chargement...
                                </span>
                            </button>
                            <p class="text-center text-xs text-gray-400 mt-2">
                                1 magasin • 2 utilisateurs • 100 produits
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Shwary Logo/Badge -->
                <div class="px-6 py-3 bg-gray-50 border-t border-gray-200 flex items-center justify-center">
                    <span class="text-xs text-gray-400">Paiement sécurisé par</span>
                    <span class="ml-2 font-semibold text-orange-600">Shwary</span>
                    <svg class="w-4 h-4 ml-1 text-orange-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
