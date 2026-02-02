<?php

namespace App\Livewire\Organization;

use App\Enums\PaymentStatus;
use App\Enums\SubscriptionPlan;
use App\Models\Organization;
use App\Models\ShwaryTransaction;
use App\Services\ShwaryPaymentService;
use App\Services\SubscriptionService;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class PaymentModal extends Component
{
    public ?Organization $organization = null;
    public bool $showModal = false;
    public string $paymentMethod = 'mobile_money';
    public array $planData = [];
    public string $currency = '€';

    // Shwary Mobile Money
    public string $phoneNumber = '';
    public string $selectedCountry = 'CD';
    public bool $isProcessing = false;
    public ?string $paymentStatus = null;
    public ?string $paymentMessage = null;
    public ?int $pendingTransactionId = null;
    public int $checkStatusInterval = 5000; // 5 secondes

    protected $rules = [
        'phoneNumber' => 'required|string|min:9|max:15',
    ];

    protected $messages = [
        'phoneNumber.required' => 'Le numéro de téléphone est requis',
        'phoneNumber.min' => 'Le numéro de téléphone doit avoir au moins 9 caractères',
    ];

    public function mount()
    {
        $user = Auth::user();

        if (!$user) {
            return;
        }

        $this->organization = $user->defaultOrganization;

        if (!$this->organization) {
            return;
        }

        // Check if payment is needed
        if (!$this->organization->isAccessible()) {
            $this->showModal = true;

            // Load plan data from cache
            $allPlans = SubscriptionService::getPlansFromCache();
            $planSlug = $this->organization->subscription_plan->value;
            $this->planData = $allPlans[$planSlug] ?? [];
            $this->currency = SubscriptionService::getCurrencyFromCache();
        }

        // Vérifier si une transaction est en attente
        $this->checkPendingTransaction();
    }

    /**
     * Vérifier si une transaction Shwary est en attente
     */
    public function checkPendingTransaction(): void
    {
        if (!$this->organization) {
            return;
        }

        $pendingTransaction = ShwaryTransaction::pending()
            ->forOrganization($this->organization->id)
            ->latest()
            ->first();

        if ($pendingTransaction) {
            $this->pendingTransactionId = $pendingTransaction->id;
            $this->paymentStatus = 'pending';
            $this->paymentMessage = 'Un paiement est en attente de confirmation. Veuillez valider sur votre téléphone.';
        }
    }

    /**
     * Initier un paiement Mobile Money via Shwary
     */
    public function processShwaryPayment(): void
    {
        $this->validate();

        if (!$this->organization) {
            $this->paymentStatus = 'error';
            $this->paymentMessage = 'Organisation non trouvée';
            return;
        }

        $this->isProcessing = true;
        $this->paymentStatus = null;
        $this->paymentMessage = null;

        try {
            $shwaryService = app(ShwaryPaymentService::class);

            // Vérifier que le service est configuré
            if (!$shwaryService->isConfigured()) {
                $this->paymentStatus = 'error';
                $this->paymentMessage = 'Le service de paiement Mobile Money n\'est pas configuré. Veuillez contacter l\'administrateur.';
                $this->isProcessing = false;
                return;
            }

            $amount = $this->planData['price'] ?? 0;

            if ($amount <= 0) {
                $this->paymentStatus = 'error';
                $this->paymentMessage = 'Montant invalide';
                $this->isProcessing = false;
                return;
            }

            // Initier le paiement
            $result = $shwaryService->initiatePayment(
                amount: (float) $amount,
                phoneNumber: $this->phoneNumber,
                metadata: [
                    'organization_id' => $this->organization->id,
                    'plan' => $this->organization->subscription_plan->value,
                    'user_id' => Auth::id(),
                ]
            );

            if ($result['success']) {
                $this->paymentStatus = 'pending';
                $this->paymentMessage = $result['message'];
                $this->pendingTransactionId = $result['transaction_id'];

                $this->dispatch('payment-initiated', [
                    'transactionId' => $result['transaction_id'],
                ]);
            } else {
                $this->paymentStatus = 'error';
                $this->paymentMessage = $result['message'];
            }
        } catch (\Exception $e) {
            $this->paymentStatus = 'error';
            $this->paymentMessage = 'Erreur: ' . $e->getMessage();
        } finally {
            $this->isProcessing = false;
        }
    }

    /**
     * Vérifier le statut du paiement en attente
     */
    public function checkPaymentStatus()
    {
        if (!$this->pendingTransactionId) {
            return;
        }

        $transaction = ShwaryTransaction::find($this->pendingTransactionId);

        if (!$transaction) {
            $this->paymentStatus = 'error';
            $this->paymentMessage = 'Transaction non trouvée';
            return;
        }

        $shwaryService = app(ShwaryPaymentService::class);

        // Récupérer le statut actuel depuis l'API Shwary (polling)
        // Ceci met à jour automatiquement la transaction locale
        if ($transaction->transaction_id && $transaction->isPending()) {
            $result = $shwaryService->getTransaction($transaction->transaction_id);

            // Recharger la transaction après la mise à jour
            $transaction->refresh();
        }

        if ($transaction->isCompleted()) {
            $this->paymentStatus = 'success';
            $this->paymentMessage = $shwaryService->getStatusMessage($transaction);

            // Marquer l'organisation comme payée avec toutes les informations
            if ($this->organization) {
                $this->organization->markPaymentCompleted(
                    paymentReference: $transaction->reference ?? $transaction->transaction_id,
                    paymentMethod: 'mobile_money',
                    amount: (float) $transaction->amount,
                    metadata: [
                        'shwary_transaction_id' => $transaction->transaction_id,
                        'shwary_local_id' => $transaction->id,
                        'phone_number' => $transaction->phone_number,
                        'country_code' => $transaction->country_code,
                    ]
                );
            }

            // Arrêter la vérification et rediriger
            $this->dispatch('payment-completed');

            session()->flash('success', 'Paiement Mobile Money effectué avec succès ! Bienvenue à bord 🎉');

            $this->showModal = false;
            return $this->redirect(route('dashboard'), navigate: true);
        }

        if ($transaction->isFailed()) {
            $this->paymentStatus = 'failed';
            $this->paymentMessage = $shwaryService->getStatusMessage($transaction);
            $this->pendingTransactionId = null;

            // Dispatch un événement pour arrêter la vérification
            $this->dispatch('payment-completed');
        }

        // Si toujours en attente, on continue de vérifier
        if ($transaction->isPending()) {
            $this->paymentStatus = 'pending';
            $this->paymentMessage = $shwaryService->getStatusMessage($transaction);
        }
    }

    /**
     * Confirmation manuelle du paiement (pour les cas où le webhook ne fonctionne pas)
     * Note: Cette méthode est utile en développement local ou quand l'API Shwary
     * ne peut pas renvoyer le statut (authentification différente pour GET)
     */
    public function confirmPaymentManually(): void
    {
        if (!$this->pendingTransactionId) {
            return;
        }

        $transaction = ShwaryTransaction::find($this->pendingTransactionId);

        if (!$transaction) {
            $this->paymentStatus = 'error';
            $this->paymentMessage = 'Transaction non trouvée';
            return;
        }

        // Marquer la transaction comme complétée
        $transaction->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $this->paymentStatus = 'success';
        $this->paymentMessage = 'Paiement confirmé avec succès !';

        // Marquer l'organisation comme payée avec toutes les informations
        if ($this->organization) {
            $this->organization->markPaymentCompleted(
                paymentReference: $transaction->reference ?? $transaction->transaction_id,
                paymentMethod: 'mobile_money',
                amount: (float) $transaction->amount,
                metadata: [
                    'shwary_transaction_id' => $transaction->transaction_id,
                    'shwary_local_id' => $transaction->id,
                    'phone_number' => $transaction->phone_number,
                    'country_code' => $transaction->country_code,
                    'confirmed_manually' => true,
                ]
            );
        }

        // Arrêter la vérification et rediriger
        $this->dispatch('payment-completed');

        session()->flash('success', 'Paiement Mobile Money confirmé avec succès ! Bienvenue à bord 🎉');

        $this->showModal = false;
        
        // Forcer une redirection complète (pas navigate)
        $this->redirectRoute('dashboard');
    }

    /**
     * Annuler la transaction en attente
     */
    public function cancelPendingPayment(): void
    {
        $this->pendingTransactionId = null;
        $this->paymentStatus = null;
        $this->paymentMessage = null;
    }

    /**
     * Process payment (pour carte bancaire - simulation)
     */
    public function processPayment()
    {
        if (!$this->organization) {
            return;
        }

        if ($this->paymentMethod === 'mobile_money') {
            $this->processShwaryPayment();
            return;
        }

        // Paiement par carte (simulation)
        $paymentReference = 'PAY_' . strtoupper(uniqid());
        $amount = $this->planData['price'] ?? 0;

        $this->organization->markPaymentCompleted(
            paymentReference: $paymentReference,
            paymentMethod: $this->paymentMethod,
            amount: (float) $amount,
            metadata: ['source' => 'payment_modal_card']
        );

        $this->showModal = false;

        session()->flash('success', 'Paiement effectué avec succès ! Bienvenue à bord 🎉');

        return redirect()->route('dashboard');
    }

    /**
     * Cancel and use free plan instead
     */
    public function useFreePlan()
    {
        if (!$this->organization) {
            return;
        }

        // Récupérer les limites du plan gratuit depuis le cache/DB
        $plans = SubscriptionService::getPlansFromCache();
        $freePlan = $plans['free'] ?? [];

        $this->organization->update([
            'subscription_plan' => SubscriptionPlan::FREE,
            'payment_status' => PaymentStatus::COMPLETED,
            'max_stores' => $freePlan['max_stores'] ?? 1,
            'max_users' => $freePlan['max_users'] ?? 3,
            'max_products' => $freePlan['max_products'] ?? 100,
        ]);

        // Assigner les rôles admin et manager au propriétaire
        $this->organization->assignOwnerRolesAndMenus();

        $this->showModal = false;

        session()->flash('success', 'Vous utilisez maintenant le plan gratuit. Vous pouvez passer à un plan supérieur à tout moment.');

        return redirect()->route('dashboard');
    }

    /**
     * Obtenir les pays supportés pour Mobile Money
     */
    public function getSupportedCountries(): array
    {
        return config('shwary.countries', []);
    }

    /**
     * Obtenir les opérateurs pour le pays sélectionné
     */
    public function getOperatorsProperty(): array
    {
        return config("shwary.countries.{$this->selectedCountry}.operators", []);
    }

    /**
     * Obtenir le préfixe téléphonique pour le pays sélectionné
     */
    public function getPhonePrefixProperty(): string
    {
        return config("shwary.countries.{$this->selectedCountry}.phone_prefix", '+243');
    }

    public function render()
    {
        return view('livewire.organization.payment-modal', [
            'supportedCountries' => $this->getSupportedCountries(),
            'operators' => $this->operators,
            'phonePrefix' => $this->phonePrefix,
        ]);
    }
}
