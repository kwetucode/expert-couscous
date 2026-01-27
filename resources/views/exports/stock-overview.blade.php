<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>État du Stock</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #4F46E5;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            color: #4F46E5;
            font-size: 20px;
        }
        .header p {
            margin: 5px 0 0 0;
            color: #666;
        }
        .kpis {
            display: table;
            width: 100%;
            margin-bottom: 20px;
            border-collapse: collapse;
        }
        .kpi-row {
            display: table-row;
        }
        .kpi-cell {
            display: table-cell;
            padding: 10px;
            background: #F8FAFC;
            border: 1px solid #E2E8F0;
            width: 25%;
        }
        .kpi-label {
            font-size: 9px;
            color: #64748B;
            margin-bottom: 5px;
        }
        .kpi-value {
            font-size: 14px;
            font-weight: bold;
            color: #1E293B;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th {
            background-color: #4F46E5;
            color: white;
            padding: 8px;
            text-align: left;
            font-size: 9px;
            font-weight: bold;
        }
        td {
            padding: 6px 8px;
            border-bottom: 1px solid #E2E8F0;
            font-size: 9px;
        }
        tr:nth-child(even) {
            background-color: #F8FAFC;
        }
        .status-badge {
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 8px;
            font-weight: bold;
        }
        .status-in-stock {
            background-color: #DEF7EC;
            color: #03543F;
        }
        .status-low {
            background-color: #FEF3C7;
            color: #92400E;
        }
        .status-out {
            background-color: #FDE8E8;
            color: #991B1B;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 8px;
            color: #64748B;
            border-top: 1px solid #E2E8F0;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>État du Stock</h1>
        <p>Généré le {{ $date }}</p>
    </div>

    <!-- KPIs Summary -->
    <div class="kpis">
        <div class="kpi-row">
            <div class="kpi-cell">
                <div class="kpi-label">Valeur Totale du Stock</div>
                <div class="kpi-value">{{ number_format($kpis['total_stock_value'], 0, ',', ' ') }} {{ current_currency() }}</div>
            </div>
            <div class="kpi-cell">
                <div class="kpi-label">Articles en Stock</div>
                <div class="kpi-value">{{ $kpis['in_stock_count'] }}</div>
            </div>
            <div class="kpi-cell">
                <div class="kpi-label">Valeur de Vente Potentielle</div>
                <div class="kpi-value">{{ number_format($kpis['total_retail_value'], 0, ',', ' ') }} {{ current_currency() }}</div>
            </div>
            <div class="kpi-cell">
                <div class="kpi-label">Profit Potentiel</div>
                <div class="kpi-value">{{ number_format($kpis['potential_profit'], 0, ',', ' ') }} {{ current_currency() }}</div>
            </div>
        </div>
    </div>

    <!-- Inventory Table -->
    <table>
        <thead>
            <tr>
                <th>Produit</th>
                <th>SKU</th>
                <th class="text-center">Stock</th>
                <th class="text-center">Seuil</th>
                <th class="text-right">Valeur Unit.</th>
                <th class="text-right">Valeur Tot.</th>
                <th class="text-center">Statut</th>
                <th>Catégorie</th>
            </tr>
        </thead>
        <tbody>
            @foreach($variants as $variant)
                <tr>
                    <td>
                        {{ $variant->product->name }}
                        @if($variant->size || $variant->color)
                            <br><span style="color: #64748B; font-size: 8px;">{{ $variant->getVariantName() }}</span>
                        @endif
                    </td>
                    <td>{{ $variant->sku ?? 'N/A' }}</td>
                    <td class="text-center"><strong>{{ $variant->stock_quantity }}</strong></td>
                    <td class="text-center">{{ $variant->low_stock_threshold }}</td>
                    <td class="text-right">{{ number_format($variant->product->cost_price, 0, ',', ' ') }} {{ current_currency() }}</td>
                    <td class="text-right"><strong>{{ number_format($variant->stock_quantity * $variant->product->cost_price, 0, ',', ' ') }} {{ current_currency() }}</strong></td>
                    <td class="text-center">
                        @if($variant->isOutOfStock())
                            <span class="status-badge status-out">Rupture</span>
                        @elseif($variant->isLowStock())
                            <span class="status-badge status-low">Stock faible</span>
                        @else
                            <span class="status-badge status-in-stock">En stock</span>
                        @endif
                    </td>
                    <td>{{ $variant->product->category->name ?? 'N/A' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>Document généré automatiquement - {{ now()->format('d/m/Y à H:i:s') }}</p>
    </div>
</body>
</html>
