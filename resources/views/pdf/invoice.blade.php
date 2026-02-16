<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $title ?? 'Facture' }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            color: #333;
            line-height: 1.5;
        }

        .container {
            padding: 30px;
        }

        /* Header */
        .header {
            border-bottom: 3px solid #4f46e5;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .header-table {
            width: 100%;
        }

        .company-info {
            vertical-align: top;
        }

        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #4f46e5;
            margin-bottom: 5px;
        }

        .company-details {
            font-size: 10px;
            color: #666;
            line-height: 1.6;
        }

        .invoice-info {
            text-align: right;
            vertical-align: top;
        }

        .invoice-title {
            font-size: 28px;
            font-weight: bold;
            color: #4f46e5;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .invoice-number {
            font-size: 14px;
            color: #333;
            margin-bottom: 5px;
        }

        .invoice-date {
            font-size: 11px;
            color: #666;
        }

        /* Status Badge */
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 10px;
        }

        .status-draft { background-color: #f3f4f6; color: #374151; }
        .status-sent { background-color: #dbeafe; color: #1e40af; }
        .status-paid { background-color: #d1fae5; color: #065f46; }
        .status-cancelled { background-color: #fee2e2; color: #991b1b; }

        /* Client Section */
        .client-section {
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 12px;
            font-weight: bold;
            color: #4f46e5;
            text-transform: uppercase;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #e5e7eb;
        }

        .client-box {
            background-color: #f9fafb;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #e5e7eb;
        }

        .client-name {
            font-size: 14px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .client-detail {
            font-size: 10px;
            color: #666;
            margin-bottom: 3px;
        }

        /* Items Table */
        .items-section {
            margin-bottom: 30px;
        }

        table.items-table {
            width: 100%;
            border-collapse: collapse;
        }

        table.items-table th {
            background-color: #4f46e5;
            color: white;
            padding: 12px 10px;
            text-align: left;
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
        }

        table.items-table th.text-right {
            text-align: right;
        }

        table.items-table th.text-center {
            text-align: center;
        }

        table.items-table td {
            padding: 12px 10px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
        }

        table.items-table tr:nth-child(even) {
            background-color: #f9fafb;
        }

        .item-name {
            font-weight: bold;
            color: #333;
        }

        .item-description {
            font-size: 9px;
            color: #666;
            margin-top: 3px;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        /* Totals Section */
        .totals-section {
            margin-top: 20px;
        }

        .totals-table {
            width: 300px;
            margin-left: auto;
            border-collapse: collapse;
        }

        .totals-table td {
            padding: 8px 15px;
        }

        .totals-table .label {
            text-align: right;
            color: #666;
            font-size: 11px;
        }

        .totals-table .value {
            text-align: right;
            font-weight: bold;
            font-size: 11px;
        }

        .totals-table .total-row {
            background-color: #4f46e5;
            color: white;
        }

        .totals-table .total-row td {
            padding: 12px 15px;
            font-size: 14px;
        }

        .totals-table .discount {
            color: #dc2626;
        }

        /* Due Date Section */
        .due-date-section {
            margin-top: 20px;
            padding: 10px 15px;
            background-color: #fef3c7;
            border-radius: 5px;
            border: 1px solid #f59e0b;
        }

        .due-date-section.overdue {
            background-color: #fee2e2;
            border-color: #dc2626;
        }

        .due-date-text {
            font-size: 10px;
            color: #92400e;
        }

        .due-date-section.overdue .due-date-text {
            color: #991b1b;
        }

        .due-date {
            font-weight: bold;
        }

        /* Notes Section */
        .notes-section {
            margin-top: 30px;
            padding: 15px;
            background-color: #f9fafb;
            border-radius: 5px;
            border: 1px solid #e5e7eb;
        }

        .notes-title {
            font-weight: bold;
            color: #4f46e5;
            margin-bottom: 10px;
            font-size: 11px;
        }

        .notes-content {
            font-size: 10px;
            color: #666;
            white-space: pre-line;
        }

        /* Footer */
        .footer {
            position: fixed;
            bottom: 30px;
            left: 30px;
            right: 30px;
            text-align: center;
            font-size: 9px;
            color: #999;
            border-top: 1px solid #e5e7eb;
            padding-top: 15px;
        }

        .footer-note {
            margin-top: 5px;
            font-style: italic;
        }

        /* Watermark for cancelled */
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 100px;
            color: rgba(0, 0, 0, 0.05);
            font-weight: bold;
            text-transform: uppercase;
            z-index: -1;
        }

        /* Payment Info */
        .payment-section {
            margin-top: 20px;
            padding: 15px;
            background-color: #d1fae5;
            border-radius: 5px;
            border: 1px solid #10b981;
        }

        .payment-section.pending {
            background-color: #fef3c7;
            border-color: #f59e0b;
        }

        .payment-text {
            font-size: 11px;
            font-weight: bold;
        }

        .payment-section .payment-text {
            color: #065f46;
        }

        .payment-section.pending .payment-text {
            color: #92400e;
        }
    </style>
</head>
<body>
    <div class="container">
        @if($invoice->status === 'cancelled')
            <div class="watermark">ANNULÉE</div>
        @endif

        <!-- Header -->
        <div class="header">
            <table class="header-table">
                <tr>
                    <td class="company-info" style="width: 50%;">
                        @if($invoice->organization)
                            <div class="company-name">{{ $invoice->organization->legal_name ?? $invoice->organization->name }}</div>
                            <div class="company-details">
                                @if($invoice->organization->address)
                                    {{ $invoice->organization->address }}<br>
                                @endif
                                @if($invoice->organization->city)
                                    {{ $invoice->organization->city }}, {{ $invoice->organization->country ?? '' }}<br>
                                @endif
                                @if($invoice->organization->phone)
                                    Tél: {{ $invoice->organization->phone }}<br>
                                @endif
                                @if($invoice->organization->email)
                                    Email: {{ $invoice->organization->email }}<br>
                                @endif
                                @if($invoice->organization->tax_id)
                                    NIF: {{ $invoice->organization->tax_id }}<br>
                                @endif
                                @if($invoice->organization->registration_number)
                                    RCCM: {{ $invoice->organization->registration_number }}
                                @endif
                            </div>
                        @else
                            <div class="company-name">{{ config('app.name') }}</div>
                        @endif
                    </td>
                    <td class="invoice-info">
                        <div class="invoice-title">Facture</div>
                        <div class="invoice-number">N° {{ $invoice->invoice_number }}</div>
                        <div class="invoice-date">Date: {{ $invoice->invoice_date->format('d/m/Y') }}</div>
                        @php
                            $statusLabels = [
                                'draft' => 'Brouillon',
                                'sent' => 'Envoyée',
                                'paid' => 'Payée',
                                'cancelled' => 'Annulée'
                            ];
                        @endphp
                        <div class="status-badge status-{{ $invoice->status }}">
                            {{ $statusLabels[$invoice->status] ?? $invoice->status }}
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Client Information -->
        <div class="client-section">
            <div class="section-title">Informations Client</div>
            <div class="client-box">
                <div class="client-name">{{ $invoice->sale->client->name ?? 'Client Walk-in' }}</div>
                @if($invoice->sale->client)
                    @if($invoice->sale->client->phone)
                        <div class="client-detail">Tél: {{ $invoice->sale->client->phone }}</div>
                    @endif
                    @if($invoice->sale->client->email)
                        <div class="client-detail">Email: {{ $invoice->sale->client->email }}</div>
                    @endif
                    @if($invoice->sale->client->address)
                        <div class="client-detail">Adresse: {{ $invoice->sale->client->address }}</div>
                    @endif
                @endif
            </div>
        </div>

        <!-- Items -->
        <div class="items-section">
            <div class="section-title">Détail des Articles</div>
            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 5%;">#</th>
                        <th style="width: 45%;">Désignation</th>
                        <th style="width: 15%;" class="text-center">Quantité</th>
                        <th style="width: 17%;" class="text-right">Prix Unitaire</th>
                        <th style="width: 18%;" class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice->sale->items as $index => $item)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>
                                <div class="item-name">{{ $item->productVariant->product->name }}</div>
                                @if($item->productVariant->size || $item->productVariant->color)
                                    <div class="item-description">
                                        {{ $item->productVariant->size }} {{ $item->productVariant->color }}
                                    </div>
                                @endif
                            </td>
                            <td class="text-center">{{ $item->quantity }}</td>
                            <td class="text-right">{{ format_currency($item->unit_price) }}</td>
                            <td class="text-right">{{ format_currency($item->subtotal ?? ($item->unit_price * $item->quantity)) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Totals -->
        <div class="totals-section">
            <table class="totals-table">
                <tr>
                    <td class="label">Sous-total:</td>
                    <td class="value">{{ format_currency($invoice->subtotal) }}</td>
                </tr>
                @if($invoice->tax > 0)
                    <tr>
                        <td class="label">Taxes:</td>
                        <td class="value">{{ format_currency($invoice->tax) }}</td>
                    </tr>
                @endif
                <tr class="total-row">
                    <td class="label" style="color: white;">TOTAL:</td>
                    <td class="value">{{ format_currency($invoice->total) }}</td>
                </tr>
            </table>
        </div>

        <!-- Due Date -->
        @if($invoice->due_date)
            <div class="due-date-section {{ $invoice->isOverdue() ? 'overdue' : '' }}">
                <div class="due-date-text">
                    Date d'échéance: <span class="due-date">{{ $invoice->due_date->format('d/m/Y') }}</span>
                    @if($invoice->isOverdue())
                        <strong>(EN RETARD)</strong>
                    @endif
                </div>
            </div>
        @endif

        <!-- Payment Status -->
        @if($invoice->status === 'paid')
            <div class="payment-section">
                <div class="payment-text">✓ Cette facture a été payée</div>
            </div>
        @elseif($invoice->status !== 'cancelled')
            <div class="payment-section pending">
                <div class="payment-text">⏳ Paiement en attente</div>
            </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            <div>Document généré le {{ now()->format('d/m/Y à H:i') }}</div>
            <div class="footer-note">Merci pour votre confiance.</div>
        </div>
    </div>
</body>
</html>
