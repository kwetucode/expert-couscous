@extends('reports.layouts.pdf')

@section('content')
    <!-- Summary -->
    <table style="width: 100%; margin-bottom: 20px;">
        <tr>
            <td style="width: 25%; text-align: center; background: #d1fae5; padding: 15px; border: 1px solid #10b981;">
                <div class="summary-value" style="color: #065f46;">{{ $totals['received_count'] }}</div>
                <div class="summary-label">Achats Réceptionnés</div>
            </td>
            <td style="width: 25%; text-align: center; background: #dbeafe; padding: 15px; border: 1px solid #3b82f6;">
                <div class="summary-value money" style="color: #1e40af;">{{ number_format($totals['received_amount'], 0, ',', ' ') }} {{ current_currency() }}</div>
                <div class="summary-label">Montant Total</div>
            </td>
            <td style="width: 25%; text-align: center; background: #fef3c7; padding: 15px; border: 1px solid #f59e0b;">
                <div class="summary-value" style="color: #92400e;">{{ $totals['pending_count'] }}</div>
                <div class="summary-label">En Attente</div>
            </td>
            <td style="width: 25%; text-align: center; background: #fce7f3; padding: 15px; border: 1px solid #ec4899;">
                <div class="summary-value money" style="color: #9d174d;">{{ number_format($totals['pending_amount'], 0, ',', ' ') }} {{ current_currency() }}</div>
                <div class="summary-label">Montant en Attente</div>
            </td>
        </tr>
    </table>

    <!-- Purchases Table -->
    <table>
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 15%;">N° Achat</th>
                <th style="width: 12%;">Date</th>
                <th style="width: 25%;">Fournisseur</th>
                <th style="width: 10%;" class="text-center">Articles</th>
                <th style="width: 18%;" class="text-right">Total</th>
                <th style="width: 15%;" class="text-center">Statut</th>
            </tr>
        </thead>
        <tbody>
            @foreach($purchases as $index => $purchase)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td><strong>{{ $purchase->purchase_number }}</strong></td>
                <td>{{ $purchase->purchase_date->format('d/m/Y') }}</td>
                <td>{{ $purchase->supplier?->name ?? 'Fournisseur inconnu' }}</td>
                <td class="text-center">{{ $purchase->items->count() }}</td>
                <td class="text-right money">{{ number_format($purchase->total, 0, ',', ' ') }}</td>
                <td class="text-center">
                    @switch($purchase->status)
                        @case('received')
                            <span class="badge badge-success">Réceptionné</span>
                            @break
                        @case('pending')
                            <span class="badge badge-warning">En attente</span>
                            @break
                        @case('cancelled')
                            <span class="badge badge-danger">Annulé</span>
                            @break
                        @default
                            <span class="badge">{{ $purchase->status }}</span>
                    @endswitch
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background: #f3f4f6; font-weight: bold;">
                <td colspan="5" class="text-right">TOTAUX</td>
                <td class="text-right money">{{ number_format($purchases->sum('total'), 0, ',', ' ') }} {{ current_currency() }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <!-- Purchases Details (Items) -->
    @if($showDetails ?? false)
    <div style="page-break-before: always;"></div>
    <h3 style="margin: 20px 0 10px; font-size: 14px; color: #4f46e5;">Détail des Achats</h3>

    @foreach($purchases as $purchase)
    <div style="margin-bottom: 20px; border: 1px solid #e5e7eb; padding: 10px;">
        <div style="background: #f9fafb; padding: 8px; margin: -10px -10px 10px; border-bottom: 1px solid #e5e7eb;">
            <strong>{{ $purchase->purchase_number }}</strong> - {{ $purchase->purchase_date->format('d/m/Y') }}
            | Fournisseur: {{ $purchase->supplier?->name ?? 'Inconnu' }}
        </div>
        <table style="width: 100%; font-size: 9px;">
            <thead>
                <tr style="background: #f3f4f6;">
                    <th style="width: 50%;">Produit</th>
                    <th style="width: 15%;" class="text-center">Quantité</th>
                    <th style="width: 15%;" class="text-right">Prix Unit.</th>
                    <th style="width: 20%;" class="text-right">Sous-Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($purchase->items as $item)
                <tr>
                    <td>
                        {{ $item->productVariant?->full_name ?? 'Produit' }}
                        @if($item->productVariant?->sku)
                            <br><small style="color: #666;">SKU: {{ $item->productVariant->sku }}</small>
                        @endif
                    </td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-right">{{ number_format($item->unit_price, 0, ',', ' ') }}</td>
                    <td class="text-right">{{ number_format($item->subtotal, 0, ',', ' ') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endforeach
    @endif
@endsection
