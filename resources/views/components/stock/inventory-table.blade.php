@props(['variants'])

@php
    $currency = auth()->user()->defaultOrganization->currency ?? 'CDF';
    $currentStoreId = current_store_id();
@endphp

<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Produit
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        SKU
                    </th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Stock
                    </th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Seuil
                    </th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Valeur Unitaire
                    </th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Valeur Totale
                    </th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Statut
                    </th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Actions
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($variants as $variant)
                    <tr class="hover:bg-gray-50 transition">
                        <!-- Product Name -->
                        <td class="px-6 py-4">
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $variant->product->name }}</p>
                                @if($variant->size || $variant->color)
                                    <p class="text-xs text-gray-500">
                                        {{ $variant->getVariantName() }}
                                    </p>
                                @endif
                                @if($variant->product->category)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 mt-1">
                                        {{ $variant->product->category->name }}
                                    </span>
                                @endif
                            </div>
                        </td>

                        <!-- SKU -->
                        <td class="px-6 py-4">
                            <p class="text-sm text-gray-900 font-mono">{{ $variant->sku }}</p>
                            @if($variant->barcode)
                                <p class="text-xs text-gray-500 font-mono">{{ $variant->barcode }}</p>
                            @endif
                        </td>

                        <!-- Stock Quantity -->
                        <td class="px-6 py-4 text-center">
                            @php
                                $storeQty = $currentStoreId !== null ? $variant->getStoreStock($currentStoreId) : $variant->stock_quantity;
                            @endphp
                            <span class="text-lg font-bold {{ $storeQty > $variant->low_stock_threshold ? 'text-green-600' : ($storeQty > 0 ? 'text-orange-600' : 'text-red-600') }}">
                                {{ $storeQty }}
                            </span>
                        </td>

                        <!-- Threshold -->
                        <td class="px-6 py-4 text-center">
                            <p class="text-sm text-gray-600">{{ $variant->low_stock_threshold }}</p>
                            @if($variant->min_stock_threshold > 0)
                                <p class="text-xs text-gray-400">(Min: {{ $variant->min_stock_threshold }})</p>
                            @endif
                        </td>

                        <!-- Unit Value -->
                        <td class="px-6 py-4 text-right">
                            <p class="text-sm font-medium text-gray-900">
                                {{ number_format($variant->product->cost_price ?? 0, 0, ',', ' ') }} {{ $currency }}
                            </p>
                            @if($variant->product->price)
                                <p class="text-xs text-gray-500">
                                    PV: {{ number_format($variant->product->price, 0, ',', ' ') }} {{ $currency }}
                                </p>
                            @endif
                        </td>

                        <!-- Total Value -->
                        <td class="px-6 py-4 text-right">
                            @php
                                $storeQty = $currentStoreId !== null ? $variant->getStoreStock($currentStoreId) : $variant->stock_quantity;
                            @endphp
                            <p class="text-sm font-bold text-gray-900">
                                {{ number_format($storeQty * ($variant->product->cost_price ?? 0), 0, ',', ' ') }} {{ $currency }}
                            </p>
                        </td>

                        <!-- Status Badge -->
                        <td class="px-6 py-4 text-center">
                            @php
                                $storeQty = $currentStoreId !== null ? $variant->getStoreStock($currentStoreId) : $variant->stock_quantity;
                            @endphp
                            @if($storeQty <= 0)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    Rupture
                                </span>
                            @elseif($storeQty <= $variant->low_stock_threshold)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                    Stock faible
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    En stock
                                </span>
                            @endif
                        </td>

                        <!-- Actions -->
                        <td class="px-6 py-4 text-right text-sm font-medium">
                            <x-actions-dropdown>
                                <x-dropdown-item wire:click="openAdjustModal({{ $variant->id }})" icon="pencil">
                                    Ajuster le stock
                                </x-dropdown-item>
                                <x-dropdown-item wire:click="viewHistory({{ $variant->id }})" icon="clock">
                                    Voir l'historique
                                </x-dropdown-item>
                            </x-actions-dropdown>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                            </svg>
                            <p class="mt-2 text-sm text-gray-500">Aucun produit trouvé</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
