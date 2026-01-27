<?php

namespace App\Livewire\Proforma;

use App\Mail\ProformaInvoiceMail;
use App\Models\ProformaInvoice;
use App\Services\ProformaService;
use Illuminate\Support\Facades\Mail;
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
