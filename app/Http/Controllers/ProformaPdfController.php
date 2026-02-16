<?php

namespace App\Http\Controllers;

use App\Models\ProformaInvoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ProformaPdfController extends Controller
{
    /**
     * Exporter une proforma en PDF
     */
    public function export(ProformaInvoice $proforma)
    {
        $proforma->load(['items.productVariant.product', 'store', 'user']);

        $data = [
            'proforma' => $proforma,
            'title' => 'Facture Proforma ' . $proforma->proforma_number,
            'date' => now()->format('d/m/Y H:i'),
        ];

        $pdf = Pdf::loadView('pdf.proforma', $data);
        $pdf->setPaper('A4', 'portrait');

        return $pdf->download('proforma_' . $proforma->proforma_number . '.pdf');
    }

    /**
     * Afficher le PDF dans le navigateur
     */
    public function stream(ProformaInvoice $proforma)
    {
        $proforma->load(['items.productVariant.product', 'store', 'user']);

        $data = [
            'proforma' => $proforma,
            'title' => 'Facture Proforma ' . $proforma->proforma_number,
            'date' => now()->format('d/m/Y H:i'),
        ];

        $pdf = Pdf::loadView('pdf.proforma', $data);
        $pdf->setPaper('A4', 'portrait');

        return $pdf->stream('proforma_' . $proforma->proforma_number . '.pdf');
    }

    /**
     * Télécharger le PDF via un lien signé (public, sans authentification)
     */
    public function publicDownload(ProformaInvoice $proforma)
    {
        $proforma->load(['items.productVariant.product', 'store', 'user']);

        $data = [
            'proforma' => $proforma,
            'title' => 'Facture Proforma ' . $proforma->proforma_number,
            'date' => now()->format('d/m/Y H:i'),
        ];

        $pdf = Pdf::loadView('pdf.proforma', $data);
        $pdf->setPaper('A4', 'portrait');

        return $pdf->download('proforma_' . $proforma->proforma_number . '.pdf');
    }

    /**
     * Afficher le PDF dans le navigateur via un lien signé (public, sans authentification)
     */
    public function publicStream(ProformaInvoice $proforma)
    {
        $proforma->load(['items.productVariant.product', 'store', 'user']);

        $data = [
            'proforma' => $proforma,
            'title' => 'Facture Proforma ' . $proforma->proforma_number,
            'date' => now()->format('d/m/Y H:i'),
        ];

        $pdf = Pdf::loadView('pdf.proforma', $data);
        $pdf->setPaper('A4', 'portrait');

        return $pdf->stream('proforma_' . $proforma->proforma_number . '.pdf');
    }
}
