@props(['kpis'])

@php
    $currency = auth()->user()->defaultOrganization->currency ?? 'CDF';
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <x-stock.kpi-card
        title="Valeur du Stock"
        :value="number_format($kpis['total_stock_value'], 0, ',', ' ') . ' ' . $currency"
        :subtitle="number_format($kpis['total_units'], 0, ',', ' ') . ' unités'"
        color="blue">
        <x-slot:icon>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </x-slot:icon>
    </x-stock.kpi-card>

    <x-stock.kpi-card
        title="Produits en Stock"
        :value="$kpis['in_stock_count']"
        :subtitle="'Sur ' . $kpis['total_products'] . ' total'"
        color="green">
        <x-slot:icon>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </x-slot:icon>
    </x-stock.kpi-card>

    <x-stock.kpi-card
        title="Rupture de Stock"
        :value="$kpis['out_of_stock_count']"
        subtitle="Nécessitent réappro"
        color="red">
        <x-slot:icon>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </x-slot:icon>
    </x-stock.kpi-card>

    <x-stock.kpi-card
        title="Stock Faible"
        :value="$kpis['low_stock_count']"
        subtitle="Sous le seuil d'alerte"
        color="orange">
        <x-slot:icon>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
        </x-slot:icon>
    </x-stock.kpi-card>
</div>

<!-- Potential Profit Card (Optional - Full Width) -->
<x-card class="bg-gradient-to-r from-indigo-500 to-purple-600 mb-6">
    <div class="flex items-center justify-between text-white">
        <div>
            <p class="text-sm font-medium opacity-90">Valeur de Vente Potentielle</p>
            <p class="text-2xl font-bold mt-1">{{ number_format($kpis['total_retail_value'], 0, ',', ' ') }} {{ $currency }}</p>
            <p class="text-sm opacity-90 mt-1">
                Profit potentiel : {{ number_format($kpis['potential_profit'], 0, ',', ' ') }} {{ $currency }}
                ({{ $kpis['profit_margin_percentage'] }}%)
            </p>
        </div>
        <div class="p-3 bg-black/20 rounded-lg">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
            </svg>
        </div>
    </div>
</x-card>
