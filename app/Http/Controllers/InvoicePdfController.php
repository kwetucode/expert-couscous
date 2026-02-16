<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class InvoicePdfController extends Controller
{
    /**
     * Exporter une facture en PDF
     */
    public function export(Invoice $invoice)
    {
        $invoice->load(['sale.items.productVariant.product', 'sale.client', 'organization']);

        $data = [
            'invoice' => $invoice,
            'title' => 'Facture ' . $invoice->invoice_number,
            'date' => now()->format('d/m/Y H:i'),
        ];

        $pdf = Pdf::loadView('pdf.invoice', $data);
        $pdf->setPaper('A4', 'portrait');

        return $pdf->download('facture_' . $invoice->invoice_number . '.pdf');
    }

    /**
     * Afficher le PDF dans le navigateur
     */
    public function stream(Invoice $invoice)
    {
        $invoice->load(['sale.items.productVariant.product', 'sale.client', 'organization']);

        $data = [
            'invoice' => $invoice,
            'title' => 'Facture ' . $invoice->invoice_number,
            'date' => now()->format('d/m/Y H:i'),
        ];

        $pdf = Pdf::loadView('pdf.invoice', $data);
        $pdf->setPaper('A4', 'portrait');

        return $pdf->stream('facture_' . $invoice->invoice_number . '.pdf');
    }

    /**
     * Télécharger le PDF via un lien signé (public, sans authentification)
     */
    public function publicDownload(Invoice $invoice)
    {
        $invoice->load(['sale.items.productVariant.product', 'sale.client', 'organization']);

        $data = [
            'invoice' => $invoice,
            'title' => 'Facture ' . $invoice->invoice_number,
            'date' => now()->format('d/m/Y H:i'),
        ];

        $pdf = Pdf::loadView('pdf.invoice', $data);
        $pdf->setPaper('A4', 'portrait');

        return $pdf->download('facture_' . $invoice->invoice_number . '.pdf');
    }

    /**
     * Afficher le PDF dans le navigateur via un lien signé (public, sans authentification)
     */
    public function publicStream(Invoice $invoice)
    {
        $invoice->load(['sale.items.productVariant.product', 'sale.client', 'organization']);

        $data = [
            'invoice' => $invoice,
            'title' => 'Facture ' . $invoice->invoice_number,
            'date' => now()->format('d/m/Y H:i'),
        ];

        $pdf = Pdf::loadView('pdf.invoice', $data);
        $pdf->setPaper('A4', 'portrait');

        return $pdf->stream('facture_' . $invoice->invoice_number . '.pdf');
    }
}
