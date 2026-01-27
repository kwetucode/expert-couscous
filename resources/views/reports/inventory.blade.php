@extends('reports.layouts.pdf')

@section('content')
    <!-- Summary -->
    <table style="width: 100%; margin-bottom: 20px;">
        <tr>
            <td style="width: 25%; text-align: center; background: #f9fafb; padding: 15px; border: 1px solid #e5e7eb;">
                <div class="summary-value">{{ $totals['variants'] }}</div>
                <div class="summary-label">Références en stock</div>
            </td>
            <td style="width: 25%; text-align: center; background: #f9fafb; padding: 15px; border: 1px solid #e5e7eb;">
                <div class="summary-value">{{ number_format($totals['total_stock'], 0, ',', ' ') }}</div>
                <div class="summary-label">Quantité Totale</div>
            </td>
            <td style="width: 25%; text-align: center; background: #dbeafe; padding: 15px; border: 1px solid #bfdbfe;">
                <div class="summary-value money" style="color: #1e40af;">{{ number_format($totals['total_cost_value'], 0, ',', ' ') }} {{ current_currency() }}</div>
                <div class="summary-label">Valeur d'Achat</div>
            </td>
            <td style="width: 25%; text-align: center; background: #d1fae5; padding: 15px; border: 1px solid #a7f3d0;">
                <div class="summary-value money" style="color: #059669;">{{ number_format($totals['total_sale_value'], 0, ',', ' ') }} {{ current_currency() }}</div>
                <div class="summary-label">Valeur de Vente</div>
            </td>
        </tr>
    </table>

    <!-- By Category -->
    @foreach($byCategory as $categoryName => $variants)
    <div class="section-title">{{ $categoryName }} ({{ $variants->count() }} articles)</div>

    <table>
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 30%;">Produit</th>
                <th style="width: 15%;">SKU</th>
                <th style="width: 10%;" class="text-center">Stock</th>
                <th style="width: 13%;" class="text-right">Prix Achat</th>
                <th style="width: 13%;" class="text-right">Valeur Achat</th>
                <th style="width: 14%;" class="text-right">Valeur Vente</th>
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
                <td class="text-center">{{ $variant->stock_quantity }}</td>
                <td class="text-right money">{{ number_format($variant->product->cost_price ?? 0, 0, ',', ' ') }}</td>
                <td class="text-right money">{{ number_format($variant->stock_quantity * ($variant->product->cost_price ?? 0), 0, ',', ' ') }}</td>
                <td class="text-right money">{{ number_format($variant->stock_quantity * ($variant->product->price ?? 0), 0, ',', ' ') }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background: #f3f4f6; font-weight: bold;">
                <td colspan="3" class="text-right">Sous-total {{ $categoryName }}</td>
                <td class="text-center">{{ $variants->sum('stock_quantity') }}</td>
                <td></td>
                <td class="text-right money">{{ number_format($variants->sum(fn($v) => $v->stock_quantity * ($v->product->cost_price ?? 0)), 0, ',', ' ') }}</td>
                <td class="text-right money">{{ number_format($variants->sum(fn($v) => $v->stock_quantity * ($v->product->price ?? 0)), 0, ',', ' ') }}</td>
            </tr>
        </tfoot>
    </table>
    @endforeach

    <!-- Grand Total -->
    <table style="margin-top: 20px;">
        <tr style="background: #4f46e5; color: white; font-weight: bold;">
            <td style="width: 50%; padding: 12px;">TOTAL GÉNÉRAL</td>
            <td style="width: 12%; text-align: center; padding: 12px;">{{ number_format($totals['total_stock'], 0, ',', ' ') }} unités</td>
            <td style="width: 19%; text-align: right; padding: 12px;">{{ number_format($totals['total_cost_value'], 0, ',', ' ') }} {{ current_currency() }}</td>
            <td style="width: 19%; text-align: right; padding: 12px;">{{ number_format($totals['total_sale_value'], 0, ',', ' ') }} {{ current_currency() }}</td>
        </tr>
    </table>
@endsection
