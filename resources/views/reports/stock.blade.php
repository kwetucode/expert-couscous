@extends('reports.layouts.pdf')

@section('content')
    <!-- Filters -->
    @if(isset($filters))
    <div class="filters">
        <div class="filters-title">Filtres appliqués</div>
        <div class="filter-item">
            <span class="filter-label">Niveau de stock:</span> {{ $filters['stock_level'] }}
        </div>
        <div class="filter-item">
            <span class="filter-label">Catégorie:</span> {{ $filters['category'] }}
        </div>
    </div>
    @endif

    <!-- Summary -->
    <table style="width: 100%; margin-bottom: 20px;">
        <tr>
            <td style="width: 20%; text-align: center; background: #f9fafb; padding: 15px; border: 1px solid #e5e7eb;">
                <div class="summary-value">{{ $totals['variants'] }}</div>
                <div class="summary-label">Références</div>
            </td>
            <td style="width: 20%; text-align: center; background: #f9fafb; padding: 15px; border: 1px solid #e5e7eb;">
                <div class="summary-value">{{ number_format($totals['total_stock'], 0, ',', ' ') }}</div>
                <div class="summary-label">Quantité Totale</div>
            </td>
            <td style="width: 20%; text-align: center; background: #f9fafb; padding: 15px; border: 1px solid #e5e7eb;">
                <div class="summary-value money">{{ number_format($totals['total_value'], 0, ',', ' ') }} {{ current_currency() }}</div>
                <div class="summary-label">Valeur Stock</div>
            </td>
            <td style="width: 20%; text-align: center; background: #fee2e2; padding: 15px; border: 1px solid #fecaca;">
                <div class="summary-value" style="color: #dc2626;">{{ $totals['out_of_stock'] }}</div>
                <div class="summary-label">Ruptures</div>
            </td>
            <td style="width: 20%; text-align: center; background: #fef3c7; padding: 15px; border: 1px solid #fde68a;">
                <div class="summary-value" style="color: #f59e0b;">{{ $totals['low_stock'] }}</div>
                <div class="summary-label">Stock Faible</div>
            </td>
        </tr>
    </table>

    <!-- Stock Table -->
    <table>
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 25%;">Produit</th>
                <th style="width: 15%;">SKU</th>
                <th style="width: 12%;">Catégorie</th>
                <th style="width: 10%;" class="text-center">Stock</th>
                <th style="width: 10%;" class="text-center">Seuil Alerte</th>
                <th style="width: 10%;" class="text-right">Prix Unitaire</th>
                <th style="width: 13%;" class="text-right">Valeur Stock</th>
            </tr>
        </thead>
        <tbody>
            @foreach($variants as $index => $variant)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>
                    <strong>{{ $variant->product->name }}</strong>
                    @if($variant->size || $variant->color)
                    <br><small style="color: #666;">{{ $variant->size }} {{ $variant->color }}</small>
                    @endif
                </td>
                <td>{{ $variant->sku }}</td>
                <td>{{ $variant->product->category?->name ?? '—' }}</td>
                <td class="text-center">
                    @if($variant->stock_quantity <= 0)
                        <span class="badge badge-danger">{{ $variant->stock_quantity }}</span>
                    @elseif($variant->stock_quantity <= $variant->low_stock_threshold)
                        <span class="badge badge-warning">{{ $variant->stock_quantity }}</span>
                    @else
                        <span class="badge badge-success">{{ $variant->stock_quantity }}</span>
                    @endif
                </td>
                <td class="text-center">{{ $variant->low_stock_threshold }}</td>
                <td class="text-right money">{{ number_format($variant->product->cost_price ?? 0, 0, ',', ' ') }}</td>
                <td class="text-right money">{{ number_format($variant->stock_quantity * ($variant->product->cost_price ?? 0), 0, ',', ' ') }} {{ current_currency() }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background: #f3f4f6; font-weight: bold;">
                <td colspan="4" class="text-right">TOTAL</td>
                <td class="text-center">{{ number_format($totals['total_stock'], 0, ',', ' ') }}</td>
                <td></td>
                <td></td>
                <td class="text-right money">{{ number_format($totals['total_value'], 0, ',', ' ') }} {{ current_currency() }}</td>
            </tr>
        </tfoot>
    </table>
@endsection
