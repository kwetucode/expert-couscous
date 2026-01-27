@extends('reports.layouts.pdf')

@section('content')
    <!-- Filters -->
    @if(isset($filters))
    <div class="filters">
        <div class="filters-title">Filtres appliqués</div>
        <div class="filter-item">
            <span class="filter-label">Période:</span> {{ $filters['period'] }}
        </div>
        @if($filters['client'] !== 'Tous')
        <div class="filter-item">
            <span class="filter-label">Client:</span> {{ $filters['client'] }}
        </div>
        @endif
        @if($filters['status'] !== 'Tous')
        <div class="filter-item">
            <span class="filter-label">Statut:</span> {{ $filters['status'] }}
        </div>
        @endif
        @if($filters['payment_status'] !== 'Tous')
        <div class="filter-item">
            <span class="filter-label">Paiement:</span> {{ $filters['payment_status'] }}
        </div>
        @endif
    </div>
    @endif

    <!-- Summary -->
    <table style="width: 100%; margin-bottom: 20px;">
        <tr>
            <td style="width: 25%; text-align: center; background: #d1fae5; padding: 15px; border: 1px solid #10b981;">
                <div class="summary-value" style="color: #065f46;">{{ $totals['completed_count'] }}</div>
                <div class="summary-label">Ventes Complétées</div>
            </td>
            <td style="width: 25%; text-align: center; background: #dbeafe; padding: 15px; border: 1px solid #3b82f6;">
                <div class="summary-value money" style="color: #1e40af;">{{ number_format($totals['completed_amount'], 0, ',', ' ') }} {{ current_currency() }}</div>
                <div class="summary-label">Montant Total</div>
            </td>
            <td style="width: 25%; text-align: center; background: #fef3c7; padding: 15px; border: 1px solid #f59e0b;">
                <div class="summary-value" style="color: #92400e;">{{ $totals['pending_count'] }}</div>
                <div class="summary-label">En Attente</div>
            </td>
            <td style="width: 25%; text-align: center; background: #fce7f3; padding: 15px; border: 1px solid #ec4899;">
                <div class="summary-value money" style="color: #9d174d;">{{ number_format($totals['paid_amount'], 0, ',', ' ') }} {{ current_currency() }}</div>
                <div class="summary-label">Montant Payé</div>
            </td>
        </tr>
    </table>

    <!-- Sales Table -->
    <table>
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 12%;">N° Vente</th>
                <th style="width: 10%;">Date</th>
                <th style="width: 18%;">Client</th>
                <th style="width: 8%;" class="text-center">Articles</th>
                <th style="width: 12%;" class="text-right">Total</th>
                <th style="width: 12%;" class="text-right">Payé</th>
                <th style="width: 10%;" class="text-center">Paiement</th>
                <th style="width: 10%;" class="text-center">Statut</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sales as $index => $sale)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td><strong>{{ $sale->sale_number }}</strong></td>
                <td>{{ $sale->sale_date->format('d/m/Y') }}</td>
                <td>{{ $sale->client?->name ?? 'Client anonyme' }}</td>
                <td class="text-center">{{ $sale->items->count() }}</td>
                <td class="text-right money">{{ number_format($sale->total, 0, ',', ' ') }}</td>
                <td class="text-right money">{{ number_format($sale->paid_amount, 0, ',', ' ') }}</td>
                <td class="text-center">
                    @switch($sale->payment_status)
                        @case('paid')
                            <span class="badge badge-success">Payé</span>
                            @break
                        @case('partial')
                            <span class="badge badge-warning">Partiel</span>
                            @break
                        @case('pending')
                            <span class="badge badge-danger">En attente</span>
                            @break
                        @case('refunded')
                            <span class="badge badge-info">Remboursé</span>
                            @break
                        @default
                            <span class="badge">{{ $sale->payment_status }}</span>
                    @endswitch
                </td>
                <td class="text-center">
                    @switch($sale->status)
                        @case('completed')
                            <span class="badge badge-success">Complétée</span>
                            @break
                        @case('pending')
                            <span class="badge badge-warning">En attente</span>
                            @break
                        @case('cancelled')
                            <span class="badge badge-danger">Annulée</span>
                            @break
                        @default
                            <span class="badge">{{ $sale->status }}</span>
                    @endswitch
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background: #f3f4f6; font-weight: bold;">
                <td colspan="5" class="text-right">TOTAUX</td>
                <td class="text-right money">{{ number_format($sales->sum('total'), 0, ',', ' ') }} {{ current_currency() }}</td>
                <td class="text-right money">{{ number_format($sales->sum('paid_amount'), 0, ',', ' ') }} {{ current_currency() }}</td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>

    <!-- Sales Details (Items) -->
    @if($showDetails ?? false)
    <div style="page-break-before: always;"></div>
    <h3 style="margin: 20px 0 10px; font-size: 14px; color: #4f46e5;">Détail des Ventes</h3>

    @foreach($sales as $sale)
    <div style="margin-bottom: 20px; border: 1px solid #e5e7eb; padding: 10px;">
        <div style="background: #f9fafb; padding: 8px; margin: -10px -10px 10px; border-bottom: 1px solid #e5e7eb;">
            <strong>{{ $sale->sale_number }}</strong> - {{ $sale->sale_date->format('d/m/Y H:i') }}
            | Client: {{ $sale->client?->name ?? 'Anonyme' }}
            | Vendeur: {{ $sale->user?->name ?? '—' }}
        </div>
        <table style="width: 100%; font-size: 9px;">
            <thead>
                <tr style="background: #f3f4f6;">
                    <th style="width: 40%;">Produit</th>
                    <th style="width: 15%;" class="text-center">Quantité</th>
                    <th style="width: 15%;" class="text-right">Prix Unit.</th>
                    <th style="width: 15%;" class="text-right">Remise</th>
                    <th style="width: 15%;" class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sale->items as $item)
                <tr>
                    <td>
                        {{ $item->product_name }}
                        @if($item->variant_info)
                            <br><small style="color: #666;">{{ $item->variant_info }}</small>
                        @endif
                    </td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-right">{{ number_format($item->unit_price, 0, ',', ' ') }}</td>
                    <td class="text-right">{{ number_format($item->discount_amount ?? 0, 0, ',', ' ') }}</td>
                    <td class="text-right">{{ number_format($item->total, 0, ',', ' ') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endforeach
    @endif
@endsection
