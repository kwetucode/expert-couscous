<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PurchasesReportMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public string $recipientName,
        public string $periodLabel,
        public ?string $dateFrom = null,
        public ?string $dateTo = null,
        public array $totals = [],
        public ?string $pdfPath = null,
        public ?string $excelPath = null,
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $period = $this->periodLabel;
        if ($this->dateFrom && $this->dateTo) {
            $period .= ' (' . date('d/m/Y', strtotime($this->dateFrom)) . ' - ' . date('d/m/Y', strtotime($this->dateTo)) . ')';
        }

        return new Envelope(
            subject: 'Rapport des Achats - ' . $period,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.purchases-report',
            with: [
                'recipientName' => $this->recipientName,
                'periodLabel' => $this->periodLabel,
                'dateFrom' => $this->dateFrom,
                'dateTo' => $this->dateTo,
                'totals' => $this->totals,
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
        $attachments = [];

        if ($this->pdfPath && file_exists($this->pdfPath)) {
            $attachments[] = Attachment::fromPath($this->pdfPath)
                ->as('rapport_achats.pdf')
                ->withMime('application/pdf');
        }

        if ($this->excelPath && file_exists($this->excelPath)) {
            $attachments[] = Attachment::fromPath($this->excelPath)
                ->as('rapport_achats.xlsx')
                ->withMime('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        }

        return $attachments;
    }
}
