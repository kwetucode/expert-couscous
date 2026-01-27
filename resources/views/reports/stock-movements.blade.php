@extends('reports.layouts.pdf')

@section('content')
    <!-- Filters -->
    @if(isset($filters))
    <div class="filters">
        <div class="filters-title">Filtres appliqués</div>
        <div class="filter-item">
            <span class="filter-label">Période:</span> {{ $filters['date_from'] }} - {{ $filters['date_to'] }}
        </div>
        <div class="filter-item">
            <span class="filter-label">Type:</span> {{ $filters['type'] }}
        </div>
        <div class="filter-item">
            <span class="filter-label">Mouvement:</span> {{ $filters['movement_type'] }}
        </div>
    </div>
    @endif

    <!-- Summary -->
    <table style="width: 100%; margin-bottom: 20px;">
        <tr>
            <td style="width: 25%; text-align: center; background: #f9fafb; padding: 15px; border: 1px solid #e5e7eb;">
                <div class="summary-value">{{ $totals['movements'] }}</div>
                <div class="summary-label">Mouvements</div>
            </td>
            <td style="width: 25%; text-align: center; background: #d1fae5; padding: 15px; border: 1px solid #a7f3d0;">
                <div class="summary-value" style="color: #059669;">+{{ number_format($totals['entries'], 0, ',', ' ') }}</div>
                <div class="summary-label">Entrées</div>
            </td>
            <td style="width: 25%; text-align: center; background: #fee2e2; padding: 15px; border: 1px solid #fecaca;">
                <div class="summary-value" style="color: #dc2626;">-{{ number_format($totals['exits'], 0, ',', ' ') }}</div>
                <div class="summary-label">Sorties</div>
            </td>
            <td style="width: 25%; text-align: center; background: #f9fafb; padding: 15px; border: 1px solid #e5e7eb;">
                <div class="summary-value money">{{ number_format($totals['total_value'], 0, ',', ' ') }} {{ current_currency() }}</div>
                <div class="summary-label">Valeur Totale</div>
            </td>
        </tr>
    </table>

    <!-- Movements Table -->
    <table>
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 10%;">Date</th>
                <th style="width: 12%;">Référence</th>
                <th style="width: 20%;">Produit</th>
                <th style="width: 10%;" class="text-center">Type</th>
                <th style="width: 12%;">Mouvement</th>
                <th style="width: 8%;" class="text-center">Qté</th>
                <th style="width: 10%;" class="text-right">Prix Unit.</th>
                <th style="width: 13%;">Utilisateur</th>
            </tr>
        </thead>
        <tbody>
            @foreach($movements as $index => $movement)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $movement->date->format('d/m/Y') }}</td>
                <td>{{ $movement->reference ?: '—' }}</td>
                <td>
                    {{ $movement->productVariant->product->name }}
                    <br><small style="color: #666;">{{ $movement->productVariant->sku }}</small>
                </td>
                <td class="text-center">
                    @if($movement->type === 'in')
                        <span class="badge badge-success">Entrée</span>
                    @else
                        <span class="badge badge-danger">Sortie</span>
                    @endif
                </td>
                <td>
                    <span class="badge badge-info">{{ ucfirst(str_replace('_', ' ', $movement->movement_type)) }}</span>
                </td>
                <td class="text-center" style="font-weight: bold; color: {{ $movement->type === 'in' ? '#059669' : '#dc2626' }};">
                    {{ $movement->type === 'in' ? '+' : '-' }}{{ $movement->quantity }}
                </td>
                <td class="text-right money">{{ number_format($movement->unit_price ?? 0, 0, ',', ' ') }}</td>
                <td>{{ $movement->user?->name ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
@endsection
