<?php

namespace App\Livewire\Proforma;

use App\Mail\ProformaInvoiceMail;
use App\Models\ProformaInvoice;
use App\Services\ProformaService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Livewire\Component;

class ProformaShow extends Component
{
    public ProformaInvoice $proforma;

    public $showDeleteModal = false;
    public $showActionModal = false;
    public $actionType = '';

    // Pour l'envoi par email
    public $showEmailModal = false;
    public $emailTo = '';

    // Pour l'envoi (email ou WhatsApp) avec coordonnées optionnelles
    public $sendMode = ''; // 'email' ou 'whatsapp'
    public $contactName = '';
    public $contactPhone = '';
    public $contactEmail = '';

    /**
     * Recharge les relations après chaque requête Livewire
     */
    public function hydrate()
    {
        $this->proforma->load(['items.productVariant.product', 'store', 'user', 'convertedInvoice']);
    }

    public function mount(ProformaInvoice $proforma)
    {
        $this->proforma = $proforma->load(['items.productVariant.product', 'store', 'user', 'convertedInvoice']);
        $this->initContactInfo();
    }

    /**
     * Initialise les infos de contact depuis la proforma
     */
    private function initContactInfo()
    {
        $this->contactName = $this->proforma->client_name ?? '';
        $this->contactPhone = $this->proforma->client_phone ?? '';
        $this->contactEmail = $this->proforma->client_email ?? '';
    }

    /**
     * Ouvrir la modal d'envoi (email ou WhatsApp)
     */
    public function openSendModal(string $mode)
    {
        $this->sendMode = $mode;
        $this->initContactInfo();
        $this->dispatch('open-send-modal');
    }

    /**
     * Sauvegarder les coordonnées du contact
     */
    public function saveContactInfo()
    {
        $updateData = [];

        if ($this->contactName && $this->contactName !== $this->proforma->client_name) {
            $updateData['client_name'] = $this->contactName;
        }
        if ($this->contactPhone && $this->contactPhone !== $this->proforma->client_phone) {
            $updateData['client_phone'] = $this->contactPhone;
        }
        if ($this->contactEmail && $this->contactEmail !== $this->proforma->client_email) {
            $updateData['client_email'] = $this->contactEmail;
        }

        if (!empty($updateData)) {
            $this->proforma->update($updateData);
            $this->proforma = $this->proforma->fresh(['items.productVariant.product', 'store', 'user', 'convertedInvoice']);
        }
    }

    /**
     * Envoyer par le mode choisi (email ou WhatsApp)
     */
    public function sendProforma(ProformaService $service)
    {
        if ($this->sendMode === 'email') {
            $this->sendByEmailFromModal($service);
        } elseif ($this->sendMode === 'whatsapp') {
            $this->sendByWhatsApp($service);
        }
    }

    /**
     * Envoyer par WhatsApp
     */
    public function sendByWhatsApp(ProformaService $service)
    {
        $this->validate([
            'contactPhone' => 'required|min:8',
        ], [
            'contactPhone.required' => 'Le numéro de téléphone est requis pour WhatsApp.',
            'contactPhone.min' => 'Le numéro de téléphone doit contenir au moins 8 chiffres.',
        ]);

        // Sauvegarder les coordonnées
        $this->saveContactInfo();

        // Marquer comme envoyée si en brouillon
        if ($this->proforma->status === ProformaInvoice::STATUS_DRAFT) {
            $service->markAsSent($this->proforma);
            $this->proforma = $this->proforma->fresh(['items.productVariant.product', 'store', 'user', 'convertedInvoice']);
        }

        // Générer le lien WhatsApp avec URL signée (valide 7 jours, sans authentification)
        $pdfUrl = URL::temporarySignedRoute(
            'public.proformas.pdf',
            now()->addDays(7),
            ['proforma' => $this->proforma->id]
        );

        $whatsappNumber = preg_replace('/[^0-9]/', '', $this->contactPhone);
        $message = "Bonjour" . ($this->contactName ? " {$this->contactName}" : "") . ",\n\n";
        $message .= "Veuillez trouver ci-joint votre facture proforma {$this->proforma->proforma_number} d'un montant de " . format_currency($this->proforma->total) . ".\n\n";
        $message .= "Vous pouvez la télécharger ici : " . $pdfUrl . "\n\n";
        $message .= "Cordialement,\n" . ($this->proforma->store->name ?? config('app.name'));

        $whatsappUrl = "https://wa.me/{$whatsappNumber}?text=" . urlencode($message);

        $this->dispatch('close-send-modal');
        $this->dispatch('open-whatsapp', url: $whatsappUrl);

        session()->flash('success', 'Proforma prête à être envoyée par WhatsApp.');
    }

    /**
     * Envoyer par email depuis la modal unifiée
     */
    public function sendByEmailFromModal(ProformaService $service)
    {
        $this->validate([
            'contactEmail' => 'required|email',
        ], [
            'contactEmail.required' => 'L\'adresse email est requise.',
            'contactEmail.email' => 'L\'adresse email n\'est pas valide.',
        ]);

        // Sauvegarder les coordonnées
        $this->saveContactInfo();

        try {
            // Charger les relations nécessaires pour le PDF
            $this->proforma->load(['items.productVariant.product', 'store', 'user']);

            // Envoyer l'email
            Mail::to($this->contactEmail)->send(new ProformaInvoiceMail($this->proforma));

            // Marquer comme envoyée si en brouillon
            if ($this->proforma->status === ProformaInvoice::STATUS_DRAFT) {
                $service->markAsSent($this->proforma);
                $this->proforma = $this->proforma->fresh(['items.productVariant.product', 'store', 'user', 'convertedInvoice']);
            }

            session()->flash('success', "Proforma envoyée par email à {$this->contactEmail}");
        } catch (\Exception $e) {
            \Log::error('Erreur envoi email proforma', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            session()->flash('error', 'Erreur lors de l\'envoi : ' . $e->getMessage());
        }

        $this->dispatch('close-send-modal');
    }

    /**
     * Préparer l'envoi par email - ouvre la modal
     */
    public function prepareEmailSend()
    {
        $this->emailTo = $this->proforma->client_email ?? '';
        $this->dispatch('open-email-modal');
    }

    /**
     * Envoyer la proforma par email
     */
    public function sendByEmail(ProformaService $service)
    {
        $this->validate([
            'emailTo' => 'required|email',
        ], [
            'emailTo.required' => 'L\'adresse email est requise.',
            'emailTo.email' => 'L\'adresse email n\'est pas valide.',
        ]);

        try {
            \Log::info('Tentative d\'envoi email proforma', [
                'proforma_id' => $this->proforma->id,
                'email' => $this->emailTo
            ]);

            // Mettre à jour l'email du client si différent
            if ($this->proforma->client_email !== $this->emailTo) {
                $this->proforma->update(['client_email' => $this->emailTo]);
                $this->proforma = $this->proforma->fresh(['items.productVariant.product', 'store', 'user', 'convertedInvoice']);
            }

            // Charger les relations nécessaires pour le PDF
            $this->proforma->load(['items.productVariant.product', 'store', 'user']);

            // Envoyer l'email
            Mail::to($this->emailTo)->send(new ProformaInvoiceMail($this->proforma));

            \Log::info('Email proforma envoyé avec succès', ['email' => $this->emailTo]);

            // Marquer comme envoyée si en brouillon
            if ($this->proforma->status === ProformaInvoice::STATUS_DRAFT) {
                $service->markAsSent($this->proforma);
                $this->proforma = $this->proforma->fresh(['items.productVariant.product', 'store', 'user', 'convertedInvoice']);
            }

            session()->flash('success', "Proforma envoyée par email à {$this->emailTo}");
        } catch (\Exception $e) {
            \Log::error('Erreur envoi email proforma', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            session()->flash('error', 'Erreur lors de l\'envoi : ' . $e->getMessage());
        }

        $this->closeEmailModal();
    }

    /**
     * Fermer la modal d'email
     */
    public function closeEmailModal()
    {
        $this->dispatch('close-email-modal');
        $this->emailTo = '';
    }

    public function markAsSent(ProformaService $service)
    {
        try {
            $service->markAsSent($this->proforma);
            $this->proforma = $this->proforma->fresh(['items.productVariant.product', 'store', 'user', 'convertedInvoice']);
            session()->flash('success', 'Proforma marquée comme envoyée.');
        } catch (\Exception $e) {
            session()->flash('error', 'Erreur : ' . $e->getMessage());
        }
    }

    public function accept(ProformaService $service)
    {
        try {
            $service->accept($this->proforma);
            $this->proforma = $this->proforma->fresh(['items.productVariant.product', 'store', 'user', 'convertedInvoice']);
            session()->flash('success', 'Proforma acceptée.');
        } catch (\Exception $e) {
            session()->flash('error', 'Erreur : ' . $e->getMessage());
        }
    }

    public function reject(ProformaService $service)
    {
        try {
            $service->reject($this->proforma);
            $this->proforma = $this->proforma->fresh(['items.productVariant.product', 'store', 'user', 'convertedInvoice']);
            session()->flash('success', 'Proforma refusée.');
        } catch (\Exception $e) {
            session()->flash('error', 'Erreur : ' . $e->getMessage());
        }
    }

    public function convert(ProformaService $service)
    {
        try {
            $this->proforma->load('items.productVariant.product');
            $invoice = $service->convertToInvoice($this->proforma);
            session()->flash('success', "Proforma convertie en facture {$invoice->invoice_number}.");
            return redirect()->route('invoices.show', ['id' => $invoice->id]);
        } catch (\Exception $e) {
            session()->flash('error', 'Erreur : ' . $e->getMessage());
        }
    }

    public function duplicate(ProformaService $service)
    {
        try {
            $newProforma = $service->duplicate($this->proforma);
            session()->flash('success', "Proforma dupliquée : {$newProforma->proforma_number}");
            return redirect()->route('proformas.edit', $newProforma);
        } catch (\Exception $e) {
            session()->flash('error', 'Erreur : ' . $e->getMessage());
        }
    }

    public function delete()
    {
        try {
            if ($this->proforma->status !== ProformaInvoice::STATUS_DRAFT) {
                session()->flash('error', 'Seules les proformas en brouillon peuvent être supprimées.');
                return;
            }

            $this->proforma->items()->delete();
            $this->proforma->delete();

            session()->flash('success', 'Proforma supprimée avec succès.');
            return redirect()->route('proformas.index');
        } catch (\Exception $e) {
            session()->flash('error', 'Erreur : ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.proforma.proforma-show');
    }
}
