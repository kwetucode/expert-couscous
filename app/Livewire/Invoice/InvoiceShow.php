<?php

namespace App\Livewire\Invoice;

use App\Actions\Invoice\MarkInvoiceAsPaidAction;
use App\Actions\Invoice\SendInvoiceAction;
use App\Actions\Invoice\CancelInvoiceAction;
use App\Mail\InvoiceMail;
use App\Models\Client;
use App\Repositories\InvoiceRepository;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Livewire\Component;

class InvoiceShow extends Component
{
    public $invoiceId;
    public $invoice;

    // Pour l'envoi (email ou WhatsApp) avec coordonnées optionnelles
    public $sendMode = ''; // 'email' ou 'whatsapp'
    public $contactName = '';
    public $contactPhone = '';
    public $contactEmail = '';

    public function mount($id, InvoiceRepository $repository)
    {
        $this->invoiceId = $id;
        $this->invoice = $repository->find($id);

        if (!$this->invoice) {
            session()->flash('error', 'Facture introuvable.');
            return redirect()->route('invoices.index');
        }

        $this->initContactInfo();
    }

    /**
     * Initialise les infos de contact depuis la facture/client
     */
    private function initContactInfo()
    {
        $client = $this->invoice->sale->client ?? null;
        $this->contactName = $client?->name ?? '';
        $this->contactPhone = $client?->phone ?? '';
        $this->contactEmail = $client?->email ?? '';
    }

    /**
     * Ouvrir la modal d'envoi (email ou WhatsApp)
     */
    public function openSendModal(string $mode)
    {
        $this->sendMode = $mode;
        $this->initContactInfo();

        // Toujours afficher la modal pour confirmer les coordonnées
        $this->dispatch('open-send-modal');
    }

    /**
     * Sauvegarder les coordonnées du contact
     * Met à jour le client existant ou crée un nouveau client
     */
    private function saveContactInfo()
    {
        $client = $this->invoice->sale->client;

        if ($client) {
            // Mettre à jour le client existant
            $updateData = [];

            if ($this->contactPhone && $this->contactPhone !== $client->phone) {
                $updateData['phone'] = $this->contactPhone;
            }
            if ($this->contactEmail && $this->contactEmail !== $client->email) {
                $updateData['email'] = $this->contactEmail;
            }
            if ($this->contactName && $this->contactName !== $client->name) {
                $updateData['name'] = $this->contactName;
            }

            if (!empty($updateData)) {
                $client->update($updateData);
            }
        } elseif ($this->contactName && ($this->contactEmail || $this->contactPhone)) {
            // Créer un nouveau client si on a le nom et au moins un moyen de contact
            $newClient = Client::create([
                'organization_id' => $this->invoice->organization_id,
                'name' => $this->contactName,
                'phone' => $this->contactPhone ?: null,
                'email' => $this->contactEmail ?: null,
            ]);

            // Associer le client à la vente
            $this->invoice->sale->update(['client_id' => $newClient->id]);
            $this->invoice->refresh();
        }
    }

    /**
     * Envoyer par le mode choisi (email ou WhatsApp)
     */
    public function sendInvoiceByMode(InvoiceRepository $repository)
    {
        if ($this->sendMode === 'email') {
            $this->sendByEmail($repository);
        } elseif ($this->sendMode === 'whatsapp') {
            $this->sendByWhatsApp($repository);
        }
    }

    /**
     * Envoyer par WhatsApp
     */
    public function sendByWhatsApp(InvoiceRepository $repository)
    {
        $client = $this->invoice->sale->client ?? null;

        // Validation: nom requis si pas de client existant
        $rules = [
            'contactPhone' => 'required|min:8',
        ];
        $messages = [
            'contactPhone.required' => 'Le numéro de téléphone est requis pour WhatsApp.',
            'contactPhone.min' => 'Le numéro de téléphone doit contenir au moins 8 chiffres.',
        ];

        if (!$client) {
            $rules['contactName'] = 'required|min:2';
            $messages['contactName.required'] = 'Le nom du client est requis.';
            $messages['contactName.min'] = 'Le nom doit contenir au moins 2 caractères.';
        }

        $this->validate($rules, $messages);

        // Sauvegarder les coordonnées
        $this->saveContactInfo();

        // Marquer comme envoyée si en brouillon
        if ($this->invoice->status === 'draft') {
            $this->invoice->update(['status' => 'sent']);
            $this->invoice = $repository->find($this->invoiceId);
        }

        // Générer le lien WhatsApp avec URL signée (valide 7 jours, sans authentification)
        $pdfUrl = URL::temporarySignedRoute(
            'public.invoices.pdf',
            now()->addDays(7),
            ['invoice' => $this->invoice->id]
        );

        $whatsappNumber = preg_replace('/[^0-9]/', '', $this->contactPhone);
        $message = "Bonjour" . ($this->contactName ? " {$this->contactName}" : "") . ",\n\n";
        $message .= "Veuillez trouver ci-joint votre facture {$this->invoice->invoice_number} d'un montant de " . format_currency($this->invoice->total) . ".\n\n";
        $message .= "Vous pouvez la télécharger ici : " . $pdfUrl . "\n\n";
        $message .= "Cordialement,\n" . ($this->invoice->organization?->name ?? config('app.name'));

        $whatsappUrl = "https://wa.me/{$whatsappNumber}?text=" . urlencode($message);

        $this->dispatch('close-send-modal');
        $this->dispatch('open-whatsapp', url: $whatsappUrl);

        session()->flash('success', 'Facture prête à être envoyée par WhatsApp.');
    }

    /**
     * Envoyer par email
     */
    public function sendByEmail(InvoiceRepository $repository)
    {
        $client = $this->invoice->sale->client ?? null;

        // Validation: nom requis si pas de client existant
        $rules = [
            'contactEmail' => 'required|email',
        ];
        $messages = [
            'contactEmail.required' => 'L\'adresse email est requise.',
            'contactEmail.email' => 'L\'adresse email n\'est pas valide.',
        ];

        if (!$client) {
            $rules['contactName'] = 'required|min:2';
            $messages['contactName.required'] = 'Le nom du client est requis.';
            $messages['contactName.min'] = 'Le nom doit contenir au moins 2 caractères.';
        }

        $this->validate($rules, $messages);

        // Sauvegarder les coordonnées (et créer le client si nécessaire)
        $this->saveContactInfo();

        try {
            // Charger les relations nécessaires pour le PDF
            $this->invoice->load(['sale.items.productVariant.product', 'sale.client', 'organization']);

            // Envoyer l'email
            Mail::to($this->contactEmail)->send(new InvoiceMail($this->invoice));

            // Marquer comme envoyée si en brouillon
            if ($this->invoice->status === 'draft') {
                $this->invoice->update(['status' => 'sent']);
                $this->invoice = $repository->find($this->invoiceId);
            }

            session()->flash('success', "Facture envoyée par email à {$this->contactEmail}");
        } catch (\Exception $e) {
            \Log::error('Erreur envoi email facture', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            session()->flash('error', 'Erreur lors de l\'envoi : ' . $e->getMessage());
        }

        $this->dispatch('close-send-modal');
    }

    public function markAsPaid(MarkInvoiceAsPaidAction $action, InvoiceRepository $repository)
    {
        try {
            $action->execute($this->invoiceId);
            $this->invoice = $repository->find($this->invoiceId);
            session()->flash('success', 'Facture marquée comme payée avec succès.');
        } catch (\Exception $e) {
            session()->flash('error', 'Erreur : ' . $e->getMessage());
        }
    }

    public function sendInvoice(SendInvoiceAction $action, InvoiceRepository $repository)
    {
        try {
            $action->execute($this->invoiceId);
            $this->invoice = $repository->find($this->invoiceId);
            session()->flash('success', 'Facture envoyée avec succès.');
        } catch (\Exception $e) {
            session()->flash('error', 'Erreur : ' . $e->getMessage());
        }
    }

    public function cancelInvoice(CancelInvoiceAction $action, InvoiceRepository $repository)
    {
        try {
            $action->execute($this->invoiceId);
            $this->invoice = $repository->find($this->invoiceId);
            session()->flash('success', 'Facture annulée avec succès.');
        } catch (\Exception $e) {
            session()->flash('error', 'Erreur : ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.invoice.invoice-show');
    }
}
