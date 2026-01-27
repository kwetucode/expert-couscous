@extends('reports.layouts.pdf')

@section('content')
    <!-- Filters -->
    @if(isset($filters))
    <div class="filters">
        <div class="filters-title">Filtres appliqués</div>
        <div class="filter-item">
            <span class="filter-label">Catégorie:</span> {{ $filters['category'] }}
        </div>
        <div class="filter-item">
            <span class="filter-label">Statut:</span> {{ $filters['status'] }}
        </div>
    </div>
    @endif

    <!-- Summary -->
    <table style="width: 100%; margin-bottom: 20px;">
        <tr>
            <td style="width: 25%; text-align: center; background: #f9fafb; padding: 15px; border: 1px solid #e5e7eb;">
                <div class="summary-value">{{ $totals['products'] }}</div>
                <div class="summary-label">Produits</div>
            </td>
            <td style="width: 25%; text-align: center; background: #f9fafb; padding: 15px; border: 1px solid #e5e7eb;">
                <div class="summary-value">{{ $totals['variants'] }}</div>
                <div class="summary-label">Variantes</div>
            </td>
            <td style="width: 25%; text-align: center; background: #f9fafb; padding: 15px; border: 1px solid #e5e7eb;">
                <div class="summary-value">{{ number_format($totals['total_stock'], 0, ',', ' ') }}</div>
                <div class="summary-label">Stock Total</div>
            </td>
            <td style="width: 25%; text-align: center; background: #f9fafb; padding: 15px; border: 1px solid #e5e7eb;">
                <div class="summary-value money">{{ number_format($totals['total_value'], 0, ',', ' ') }} {{ current_currency() }}</div>
                <div class="summary-label">Valeur Totale</div>
            </td>
        </tr>
    </table>

    <!-- Products Table -->
    <table>
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 25%;">Produit</th>
                <th style="width: 15%;">Catégorie</th>
                <th style="width: 10%;">SKU</th>
                <th style="width: 10%;" class="text-right">Prix Achat</th>
                <th style="width: 10%;" class="text-right">Prix Vente</th>
                <th style="width: 10%;" class="text-center">Stock</th>
                <th style="width: 10%;" class="text-center">Statut</th>
            </tr>
        </thead>
        <tbody>
            @foreach($products as $index => $product)
                @if($product->variants->isEmpty())
                {{-- Produit sans variantes --}}
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        <strong>{{ $product->name }}</strong>
                        @if($product->description)
                        <br><small style="color: #666;">{{ Str::limit($product->description, 50) }}</small>
                        @endif
                    </td>
                    <td>{{ $product->category?->name ?? '—' }}</td>
                    <td>{{ $product->reference ?? '—' }}</td>
                    <td class="text-right money">{{ number_format($product->cost_price ?? 0, 0, ',', ' ') }}</td>
                    <td class="text-right money">{{ number_format($product->price ?? 0, 0, ',', ' ') }}</td>
                    <td class="text-center"><span class="stock-out">0</span></td>
                    <td class="text-center">
                        @if($product->status === 'active')
                            <span class="badge badge-success">Actif</span>
                        @else
                            <span class="badge badge-danger">Inactif</span>
                        @endif
                    </td>
                </tr>
                @else
                @foreach($product->variants as $variantIndex => $variant)
                <tr>
                    @if($variantIndex === 0)
                    <td rowspan="{{ $product->variants->count() }}">{{ $index + 1 }}</td>
                    <td rowspan="{{ $product->variants->count() }}">
                        <strong>{{ $product->name }}</strong>
                        @if($product->description)
                        <br><small style="color: #666;">{{ Str::limit($product->description, 50) }}</small>
                        @endif
                    </td>
                    <td rowspan="{{ $product->variants->count() }}">{{ $product->category?->name ?? '—' }}</td>
                    @endif
                    <td>
                        {{ $variant->sku }}
                        @if($variant->size || $variant->color)
                        <br><small style="color: #666;">{{ $variant->size }} {{ $variant->color }}</small>
                        @endif
                    </td>
                    <td class="text-right money">{{ number_format($product->cost_price ?? 0, 0, ',', ' ') }}</td>
                    <td class="text-right money">{{ number_format($product->price ?? 0, 0, ',', ' ') }}</td>
                    <td class="text-center">
                        @if($variant->stock_quantity <= 0)
                            <span class="stock-out">{{ $variant->stock_quantity }}</span>
                        @elseif($variant->stock_quantity <= $variant->low_stock_threshold)
                            <span class="stock-low">{{ $variant->stock_quantity }}</span>
                        @else
                            <span class="stock-ok">{{ $variant->stock_quantity }}</span>
                        @endif
                    </td>
                    @if($variantIndex === 0)
                    <td rowspan="{{ $product->variants->count() }}" class="text-center">
                        @if($product->status === 'active')
                            <span class="badge badge-success">Actif</span>
                        @else
                            <span class="badge badge-danger">Inactif</span>
                        @endif
                    </td>
                    @endif
                </tr>
                @endforeach
                @endif
            @endforeach
        </tbody>
    </table>
@endsection
