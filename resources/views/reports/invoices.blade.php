@extends('reports.layouts.pdf')

@section('content')
    <!-- Filters -->
    @if(isset($filters))
    <div class="filters">
        <div class="filters-title">Filtres appliqués</div>
        <div class="filter-item">
            <span class="filter-label">Période:</span> {{ $filters['period'] }}
        </div>
        @if($filters['status'] !== 'Tous')
        <div class="filter-item">
            <span class="filter-label">Statut:</span> {{ $filters['status'] }}
        </div>
        @endif
    </div>
    @endif

    <!-- Summary -->
    <table style="width: 100%; margin-bottom: 20px;">
        <tr>
            <td style="width: 25%; text-align: center; background: #dbeafe; padding: 15px; border: 1px solid #3b82f6;">
                <div class="summary-value" style="color: #1e40af;">{{ $totals['total_invoices'] }}</div>
                <div class="summary-label">Total Factures</div>
            </td>
            <td style="width: 25%; text-align: center; background: #d1fae5; padding: 15px; border: 1px solid #10b981;">
                <div class="summary-value" style="color: #065f46;">{{ $totals['paid_invoices'] }}</div>
                <div class="summary-label">Factures Payées</div>
            </td>
            <td style="width: 25%; text-align: center; background: #fee2e2; padding: 15px; border: 1px solid #ef4444;">
                <div class="summary-value" style="color: #991b1b;">{{ $totals['unpaid_invoices'] }}</div>
                <div class="summary-label">Factures Impayées</div>
            </td>
            <td style="width: 25%; text-align: center; background: #fce7f3; padding: 15px; border: 1px solid #ec4899;">
                <div class="summary-value money" style="color: #9d174d;">{{ number_format($totals['total_amount'], 0, ',', ' ') }} {{ current_currency() }}</div>
                <div class="summary-label">Montant Total</div>
            </td>
        </tr>
    </table>

    <!-- Invoices Table -->
    <table>
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 15%;">N° Facture</th>
                <th style="width: 10%;">Date</th>
                <th style="width: 10%;">Échéance</th>
                <th style="width: 20%;">Client</th>
                <th style="width: 12%;">N° Vente</th>
                <th style="width: 13%;" class="text-right">Montant</th>
                <th style="width: 12%;" class="text-center">Statut</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoices as $index => $invoice)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td><strong>{{ $invoice->invoice_number }}</strong></td>
                <td>{{ $invoice->invoice_date ? $invoice->invoice_date->format('d/m/Y') : 'N/A' }}</td>
                <td>{{ $invoice->due_date ? $invoice->due_date->format('d/m/Y') : 'N/A' }}</td>
                <td>{{ $invoice->sale?->client?->name ?? 'Client anonyme' }}</td>
                <td>{{ $invoice->sale?->sale_number ?? 'N/A' }}</td>
                <td class="text-right money">{{ number_format($invoice->sale?->total ?? 0, 0, ',', ' ') }}</td>
                <td class="text-center">
                    @switch($invoice->status)
                        @case('paid')
                            <span class="badge badge-success">Payée</span>
                            @break
                        @case('sent')
                            <span class="badge badge-info">Envoyée</span>
                            @break
                        @case('draft')
                            <span class="badge badge-secondary">Brouillon</span>
                            @break
                        @case('cancelled')
                            <span class="badge badge-danger">Annulée</span>
                            @break
                        @case('overdue')
                            <span class="badge badge-warning">En retard</span>
                            @break
                        @default
                            <span class="badge">{{ $invoice->status }}</span>
                    @endswitch
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Totals Footer -->
    <table style="width: 100%; margin-top: 20px;">
        <tr>
            <td style="width: 60%;"></td>
            <td style="width: 40%;">
                <table style="width: 100%;">
                    <tr style="background: #f3f4f6;">
                        <td style="padding: 8px; font-weight: bold;">Total factures:</td>
                        <td style="padding: 8px; text-align: right; font-weight: bold;">{{ $invoices->count() }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px; font-weight: bold;">Montant total:</td>
                        <td style="padding: 8px; text-align: right; font-weight: bold;">{{ number_format($totals['total_amount'], 0, ',', ' ') }} {{ current_currency() }}</td>
                    </tr>
                    <tr style="background: #d1fae5;">
                        <td style="padding: 8px; font-weight: bold; color: #065f46;">Montant payé:</td>
                        <td style="padding: 8px; text-align: right; font-weight: bold; color: #065f46;">{{ number_format($totals['paid_amount'], 0, ',', ' ') }} {{ current_currency() }}</td>
                    </tr>
                    <tr style="background: #fee2e2;">
                        <td style="padding: 8px; font-weight: bold; color: #991b1b;">Montant impayé:</td>
                        <td style="padding: 8px; text-align: right; font-weight: bold; color: #991b1b;">{{ number_format($totals['unpaid_amount'], 0, ',', ' ') }} {{ current_currency() }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
@endsection
