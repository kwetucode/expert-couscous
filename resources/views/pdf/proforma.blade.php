<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $title ?? 'Facture Proforma' }}</title>
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

        .proforma-info {
            text-align: right;
            vertical-align: top;
        }

        .proforma-title {
            font-size: 28px;
            font-weight: bold;
            color: #4f46e5;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .proforma-number {
            font-size: 14px;
            color: #333;
            margin-bottom: 5px;
        }

        .proforma-date {
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
        .status-accepted { background-color: #d1fae5; color: #065f46; }
        .status-rejected { background-color: #fee2e2; color: #991b1b; }
        .status-converted { background-color: #e0e7ff; color: #3730a3; }
        .status-expired { background-color: #fef3c7; color: #92400e; }

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

        /* Validity Section */
        .validity-section {
            margin-top: 20px;
            padding: 10px 15px;
            background-color: #fef3c7;
            border-radius: 5px;
            border: 1px solid #f59e0b;
        }

        .validity-text {
            font-size: 10px;
            color: #92400e;
        }

        .validity-date {
            font-weight: bold;
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

        /* Watermark for draft */
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
    </style>
</head>
<body>
    <div class="container">
        @if($proforma->status === 'draft')
            <div class="watermark">BROUILLON</div>
        @endif

        <!-- Header -->
        <div class="header">
            <table class="header-table">
                <tr>
                    <td class="company-info" style="width: 50%;">
                        <div class="company-name">{{ $proforma->store?->name ?? config('app.name') }}</div>
                        <div class="company-details">
                            @if($proforma->store?->address)
                                {{ $proforma->store->address }}<br>
                            @endif
                            @if($proforma->store?->phone)
                                Tél: {{ $proforma->store->phone }}<br>
                            @endif
                            @if($proforma->store?->email)
                                Email: {{ $proforma->store->email }}
                            @endif
                        </div>
                    </td>
                    <td class="proforma-info">
                        <div class="proforma-title">Proforma</div>
                        <div class="proforma-number">N° {{ $proforma->proforma_number }}</div>
                        <div class="proforma-date">Date: {{ $proforma->proforma_date->format('d/m/Y') }}</div>
                        <div class="status-badge status-{{ $proforma->status }}">
                            {{ $proforma->status_label }}
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Client Information -->
        <div class="client-section">
            <div class="section-title">Informations Client</div>
            <div class="client-box">
                <div class="client-name">{{ $proforma->client_name }}</div>
                @if($proforma->client_phone)
                    <div class="client-detail">Tél: {{ $proforma->client_phone }}</div>
                @endif
                @if($proforma->client_email)
                    <div class="client-detail">Email: {{ $proforma->client_email }}</div>
                @endif
                @if($proforma->client_address)
                    <div class="client-detail">Adresse: {{ $proforma->client_address }}</div>
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
                        <th style="width: 35%;">Désignation</th>
                        <th style="width: 12%;" class="text-center">Quantité</th>
                        <th style="width: 16%;" class="text-right">Prix Unitaire</th>
                        <th style="width: 16%;" class="text-right">Remise</th>
                        <th style="width: 16%;" class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($proforma->items as $index => $item)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>
                                <div class="item-name">{{ $item->name }}</div>
                                @if($item->description && $item->description !== $item->name)
                                    <div class="item-description">{{ $item->description }}</div>
                                @endif
                            </td>
                            <td class="text-center">{{ $item->quantity }}</td>
                            <td class="text-right">{{ format_currency($item->unit_price) }}</td>
                            <td class="text-right">
                                @if($item->discount > 0)
                                    <span class="discount">-{{ format_currency($item->discount) }}</span>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="text-right">{{ format_currency($item->total) }}</td>
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
                    <td class="value">{{ format_currency($proforma->subtotal) }}</td>
                </tr>
                @if($proforma->discount > 0)
                    <tr>
                        <td class="label">Remise globale:</td>
                        <td class="value discount">-{{ format_currency($proforma->discount) }}</td>
                    </tr>
                @endif
                @if($proforma->tax_amount > 0)
                    <tr>
                        <td class="label">Taxes ({{ $proforma->tax_rate }}%):</td>
                        <td class="value">{{ format_currency($proforma->tax_amount) }}</td>
                    </tr>
                @endif
                <tr class="total-row">
                    <td class="label" style="color: white;">TOTAL:</td>
                    <td class="value">{{ format_currency($proforma->total) }}</td>
                </tr>
            </table>
        </div>

        <!-- Validity -->
        @if($proforma->valid_until)
            <div class="validity-section">
                <div class="validity-text">
                    Cette proforma est valable jusqu'au <span class="validity-date">{{ $proforma->valid_until->format('d/m/Y') }}</span>.
                    @if($proforma->isExpired())
                        <strong>(EXPIRÉE)</strong>
                    @endif
                </div>
            </div>
        @endif

        <!-- Notes -->
        @if($proforma->notes)
            <div class="notes-section">
                <div class="notes-title">Notes / Conditions</div>
                <div class="notes-content">{{ $proforma->notes }}</div>
            </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            <div>Document généré le {{ now()->format('d/m/Y à H:i') }} par {{ $proforma->user?->name ?? 'Système' }}</div>
            <div class="footer-note">Ce document est une proposition commerciale et ne constitue pas une facture définitive.</div>
        </div>
    </div>
</body>
</html>
