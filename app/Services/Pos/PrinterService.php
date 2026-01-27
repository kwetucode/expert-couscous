<?php

declare(strict_types=1);

namespace App\Services\Pos;

use App\Models\Sale;
use App\Models\Invoice;
use App\Models\Organization;
use Illuminate\Support\Collection;

/**
 * Service de gestion de l'impression de reçus POS
 */
class PrinterService
{
    public function __construct(
        private readonly CalculationService $calculationService
    ) {}

    /**
     * Prépare les données pour l'impression thermique
     */
    public function prepareReceiptData(Sale $sale, Invoice $invoice, float $change): array
    {
        // Charger l'organisation de la facture pour avoir les infos de l'entreprise
        $organization = $this->getOrganizationFromInvoice($invoice);

        // Utiliser l'heure actuelle avec le timezone local - ajouter 1 heure pour WAT (UTC+1)
        $currentDate = \Carbon\Carbon::now('UTC')->addHour();

        return [
            'invoice_number' => $invoice->invoice_number,
            'date' => $currentDate->format('d/m/Y H:i:s'),
            'client' => $this->formatClient($sale),
            'items' => $this->formatItems($sale->items),
            'subtotal' => $sale->subtotal,
            'discount' => $sale->discount,
            'tax' => $sale->tax ?? 0,
            'total' => $sale->total,
            'paid' => $sale->paid_amount,
            'change' => $change,
            'payment_method' => $this->formatPaymentMethod($sale->payment_method),
            'cashier' => $sale->user->name ?? 'N/A',
            'notes' => $sale->notes,
            'company' => $this->getCompanyInfoFromOrganization($organization),
        ];
    }

    /**
     * Prépare les données pour impression A4
     */
    public function prepareInvoiceData(Sale $sale, Invoice $invoice): array
    {
        // Charger l'organisation de la facture pour avoir les infos de l'entreprise
        $organization = $this->getOrganizationFromInvoice($invoice);

        return [
            'invoice' => [
                'number' => $invoice->invoice_number,
                'date' => $invoice->invoice_date->format('d/m/Y'),
                'due_date' => $invoice->due_date?->format('d/m/Y'),
                'status' => $this->formatInvoiceStatus($invoice->status),
            ],
            'company' => $this->getCompanyInfoFromOrganization($organization),
            'client' => $this->getClientInfo($sale),
            'items' => $this->formatItemsDetailed($sale->items),
            'totals' => [
                'subtotal' => $sale->subtotal,
                'discount' => $sale->discount,
                'discount_label' => $this->getDiscountLabel($sale),
                'tax' => $sale->tax ?? 0,
                'tax_label' => $this->getTaxLabel($sale),
                'total' => $sale->total,
            ],
            'payment' => [
                'method' => $this->formatPaymentMethod($sale->payment_method),
                'amount_paid' => $sale->paid_amount,
                'status' => $sale->payment_status,
            ],
            'notes' => $sale->notes,
            'terms' => $this->getPaymentTerms($organization),
        ];
    }

    /**
     * Génère un reçu en texte brut pour imprimante thermique
     */
    public function generateThermalReceipt(array $data): string
    {
        $width = 32; // Largeur typique des imprimantes thermiques
        $receipt = [];

        // En-tête
        $receipt[] = $this->centerText('REÇU DE VENTE', $width);
        $receipt[] = str_repeat('=', $width);
        $receipt[] = '';

        // Informations de base
        $receipt[] = 'N° Facture: ' . $data['invoice_number'];
        $receipt[] = 'Date: ' . $data['date'];
        if (!empty($data['client'])) {
            $receipt[] = 'Client: ' . $data['client'];
        }
        $receipt[] = str_repeat('-', $width);
        $receipt[] = '';

        // Articles
        foreach ($data['items'] as $item) {
            $receipt[] = $this->wordWrap($item['name'], $width);
            $line = sprintf(
                '%d x %s = %s',
                $item['quantity'],
                number_format($item['unit_price'], 0),
                number_format($item['total'], 0)
            );
            $receipt[] = $this->rightAlign($line, $width);
        }

        $receipt[] = '';
        $receipt[] = str_repeat('-', $width);

        // Totaux - utiliser la devise de l'organisation
        $currency = current_currency();
        $receipt[] = $this->formatLine('Sous-total', number_format($data['subtotal'], 0) . ' ' . $currency, $width);

        if ($data['discount'] > 0) {
            $receipt[] = $this->formatLine('Remise', '-' . number_format($data['discount'], 0) . ' ' . $currency, $width);
        }

        if ($data['tax'] > 0) {
            $receipt[] = $this->formatLine('Taxe', number_format($data['tax'], 0) . ' ' . $currency, $width);
        }

        $receipt[] = str_repeat('=', $width);
        $receipt[] = $this->formatLine('TOTAL', number_format($data['total'], 0) . ' ' . $currency, $width, true);
        $receipt[] = str_repeat('=', $width);

        // Paiement
        $receipt[] = '';
        $receipt[] = $this->formatLine('Payé', number_format($data['paid'], 0) . ' ' . $currency, $width);
        $receipt[] = $this->formatLine('Rendu', number_format($data['change'], 0) . ' ' . $currency, $width);

        // Pied de page
        $receipt[] = '';
        $receipt[] = str_repeat('-', $width);
        $receipt[] = $this->centerText('Merci de votre visite!', $width);
        $receipt[] = $this->centerText('À bientôt', $width);
        $receipt[] = '';

        return implode("\n", $receipt);
    }

    /**
     * Formate le client
     */
    private function formatClient(Sale $sale): string
    {
        return $sale->client?->name ?? 'Client Comptant';
    }

    /**
     * Formate les articles pour l'impression
     */
    private function formatItems(Collection $items): array
    {
        return $items->map(function ($item) {
            return [
                'name' => $item->productVariant->product->name,
                'variant' => $this->formatVariant($item->productVariant),
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'total' => $item->subtotal,
            ];
        })->toArray();
    }

    /**
     * Formate les articles de manière détaillée
     */
    private function formatItemsDetailed(Collection $items): array
    {
        return $items->map(function ($item) {
            return [
                'name' => $item->productVariant->product->name,
                'reference' => $item->productVariant->product->reference,
                'variant' => $this->formatVariant($item->productVariant),
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'subtotal' => $item->quantity * $item->unit_price,
                'total' => $item->subtotal,
            ];
        })->toArray();
    }

    /**
     * Formate la variante
     */
    private function formatVariant($variant): string
    {
        $parts = [];
        if ($variant->size) $parts[] = $variant->size;
        if ($variant->color) $parts[] = $variant->color;
        return implode(' - ', $parts);
    }

    /**
     * Formate la méthode de paiement
     */
    private function formatPaymentMethod(string $method): string
    {
        return match($method) {
            'cash' => 'Espèces',
            'card' => 'Carte bancaire',
            'mobile' => 'Paiement mobile',
            'bank_transfer' => 'Virement bancaire',
            default => ucfirst($method),
        };
    }

    /**
     * Formate le statut de la facture
     */
    private function formatInvoiceStatus(string $status): string
    {
        return match($status) {
            'paid' => 'Payée',
            'pending' => 'En attente',
            'overdue' => 'En retard',
            'cancelled' => 'Annulée',
            default => ucfirst($status),
        };
    }

    /**
     * Récupère l'organisation associée à la facture
     */
    private function getOrganizationFromInvoice(Invoice $invoice): ?Organization
    {
        // Charger la relation si elle n'est pas déjà chargée
        if (!$invoice->relationLoaded('organization')) {
            $invoice->load('organization');
        }

        return $invoice->organization;
    }

    /**
     * Obtient les informations de l'entreprise depuis l'organisation
     */
    private function getCompanyInfoFromOrganization(?Organization $organization): array
    {
        if (!$organization) {
            return $this->getDefaultCompanyInfo();
        }

        return [
            'name' => $organization->name ?? $organization->legal_name ?? config('app.company_name', 'Mon Entreprise'),
            'legal_name' => $organization->legal_name ?? $organization->name ?? '',
            'address' => $organization->address ?? '',
            'city' => $organization->city ?? '',
            'country' => $organization->country ?? '',
            'phone' => $organization->phone ?? '',
            'email' => $organization->email ?? '',
            'website' => $organization->website ?? '',
            'tax_id' => $organization->tax_id ?? '',
            'registration_number' => $organization->registration_number ?? '',
            'logo' => $organization->logo ?? null,
            'currency' => $organization->currency ?? 'CDF',
        ];
    }

    /**
     * Obtient les informations par défaut de l'entreprise (fallback)
     */
    private function getDefaultCompanyInfo(): array
    {
        return [
            'name' => config('app.company_name', 'Mon Entreprise'),
            'legal_name' => config('app.company_name', 'Mon Entreprise'),
            'address' => config('app.company_address', ''),
            'city' => '',
            'country' => '',
            'phone' => config('app.company_phone', ''),
            'email' => config('app.company_email', ''),
            'website' => '',
            'tax_id' => config('app.company_tax_id', ''),
            'registration_number' => '',
            'logo' => null,
            'currency' => 'CDF',
        ];
    }

    /**
     * Obtient les informations de l'entreprise (deprecated - use getCompanyInfoFromOrganization)
     */
    private function getCompanyInfo(): array
    {
        return $this->getDefaultCompanyInfo();
    }

    /**
     * Obtient les informations détaillées du client
     */
    private function getClientInfo(Sale $sale): array
    {
        if (!$sale->client) {
            return [
                'name' => 'Client Comptant',
                'address' => '',
                'phone' => '',
                'email' => '',
            ];
        }

        return [
            'name' => $sale->client->name,
            'address' => $sale->client->address ?? '',
            'phone' => $sale->client->phone ?? '',
            'email' => $sale->client->email ?? '',
        ];
    }

    /**
     * Obtient le libellé de la remise
     */
    private function getDiscountLabel(Sale $sale): string
    {
        if ($sale->discount <= 0) return '';

        $percentage = ($sale->discount / $sale->subtotal) * 100;
        return sprintf('Remise (%.1f%%)', $percentage);
    }

    /**
     * Obtient le libellé de la taxe
     */
    private function getTaxLabel(Sale $sale): string
    {
        if (!$sale->tax || $sale->tax <= 0) return '';

        return 'TVA';
    }

    /**
     * Obtient les conditions de paiement
     */
    private function getPaymentTerms(?Organization $organization = null): string
    {
        // On peut stocker les conditions de paiement dans les settings de l'organisation
        if ($organization && isset($organization->settings['payment_terms'])) {
            return $organization->settings['payment_terms'];
        }

        return config('app.payment_terms', 'Paiement à la livraison');
    }

    /**
     * Centre un texte
     */
    private function centerText(string $text, int $width): string
    {
        $padding = max(0, floor(($width - strlen($text)) / 2));
        return str_repeat(' ', (int) $padding) . $text;
    }

    /**
     * Aligne un texte à droite
     */
    private function rightAlign(string $text, int $width): string
    {
        $padding = max(0, $width - strlen($text));
        return str_repeat(' ', $padding) . $text;
    }

    /**
     * Formate une ligne label: valeur
     */
    private function formatLine(string $label, string $value, int $width, bool $bold = false): string
    {
        $available = $width - strlen($label) - strlen($value);
        $dots = str_repeat($bold ? '=' : '.', max(1, $available));
        return $label . $dots . $value;
    }

    /**
     * Word wrap pour texte long
     */
    private function wordWrap(string $text, int $width): string
    {
        return wordwrap($text, $width, "\n");
    }
}
