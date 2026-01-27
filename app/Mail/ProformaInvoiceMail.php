<?php

namespace App\Mail;

use App\Models\ProformaInvoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProformaInvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public ProformaInvoice $proforma
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Facture Proforma ' . $this->proforma->proforma_number,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.proforma',
            with: [
                'proforma' => $this->proforma,
                'storeName' => $this->proforma->store?->name ?? config('app.name'),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        $this->proforma->load(['items.productVariant.product', 'store', 'user']);

        $pdf = Pdf::loadView('pdf.proforma', [
            'proforma' => $this->proforma,
            'title' => 'Facture Proforma ' . $this->proforma->proforma_number,
            'date' => now()->format('d/m/Y H:i'),
        ]);

        return [
            Attachment::fromData(
                fn () => $pdf->output(),
                'proforma_' . $this->proforma->proforma_number . '.pdf'
            )->withMime('application/pdf'),
        ];
    }
}
